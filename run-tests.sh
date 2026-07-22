#!/bin/bash

# Run the O3-Shop unit test suite (with timing).
#
# Default: PARALLEL (ParaTest WrapperRunner) with 4 workers — the fast gate
# (~1 min for the full suite). Coverage always runs SEQUENTIALLY (one process):
# parallel coverage only contends on the single DB / FUSE mount and is slower.
#
# Usage:
#   run-tests.sh [options] [test targets...]
#
# Options:
#   -p N            Parallel worker count (default 4). Ignored with --sequential.
#   --sequential    Force the single-process runner (default is parallel).
#   --coverage      Generate coverage reports (clover, html, junit); implies
#                   --sequential. Uses the pcov driver.
#   --fast          Skip the runtests wrapper's shop-install / UNC regeneration
#                   and call phpunit directly (single process). For quick
#                   iteration on a subset; requires a prior full run.
#   --all-failures  Don't stop at the first failure — run everything and report
#                   all failures (flips stopOnError/stopOnFailure off via a temp
#                   config).
#   --quarantine    Run ONLY @group quarantine tests (single process).
#
# Examples:
#   run-tests.sh                                        # full suite, parallel -p4
#   run-tests.sh -p 6                                   # full suite, parallel -p6
#   run-tests.sh --sequential                           # full suite, one process
#   run-tests.sh --coverage                             # full suite + coverage (sequential)
#   run-tests.sh --fast tests/Unit/Core/ConfigTest.php  # quick single-file run
#   run-tests.sh --all-failures                         # full suite, every failure reported

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

FAST_MODE=false
COVERAGE_MODE=false
QUARANTINE_MODE=false
ALL_FAILURES_MODE=false
PARALLEL_MODE=true      # parallel is the default for the normal test run
PROCESSES=4             # default worker count
PASSTHROUGH_ARGS=()

# Parse arguments
CAPTURE_PROCESSES=false
for arg in "$@"; do
    if [ "$CAPTURE_PROCESSES" = true ]; then
        PROCESSES="$arg"
        CAPTURE_PROCESSES=false
        continue
    fi
    case "$arg" in
        --fast)        FAST_MODE=true; PARALLEL_MODE=false ;;
        --coverage)    COVERAGE_MODE=true; PARALLEL_MODE=false ;;   # coverage is sequential
        --quarantine)  QUARANTINE_MODE=true; FAST_MODE=true; PARALLEL_MODE=false ;;
        --all-failures) ALL_FAILURES_MODE=true ;;
        --parallel)    PARALLEL_MODE=true ;;   # accepted for backwards-compat (already default)
        --sequential)  PARALLEL_MODE=false ;;
        -p|--processes) CAPTURE_PROCESSES=true ;;
        -p*)           PROCESSES="${arg#-p}" ;;
        --processes=*) PROCESSES="${arg#--processes=}" ;;
        *)             PASSTHROUGH_ARGS+=("$arg") ;;
    esac
done

# Self-heal the vendored testing-library entry files. base.php does chdir() into
# the testing-library directory, so a test that resets the file cache against the
# current working directory could delete every *.php there (base.php, bootstrap.php,
# AllTests*.php), leaving the NEXT run unable to bootstrap. Keep a fresh cache of
# the good files and restore them if they went missing.
ensure_testlib_files() {
    local tl="/var/www/html/vendor/o3-shop/testing-library"
    local cache="/var/www/html/tests/.testlib-cache"
    local files="base.php bootstrap.php additional.inc.php AllTestsUnit.php AllTestsIntegration.php AllTestsRunner.php AllTestsSelenium.php"

    mkdir -p "$cache"
    if [ -f "$tl/base.php" ]; then
        for f in $files; do
            [ -f "$tl/$f" ] && cp -f "$tl/$f" "$cache/$f"
        done
    elif [ -f "$cache/base.php" ]; then
        echo -e "${YELLOW}testing-library entry files missing — restoring from cache.${NC}"
        for f in $files; do
            [ -f "$cache/$f" ] && cp -f "$cache/$f" "$tl/$f"
        done
    else
        echo -e "${YELLOW}testing-library entry files missing and no cache — reinstalling.${NC}"
        COMPOSER_ROOT_VERSION=dev-b-1.6 composer reinstall o3-shop/testing-library --no-progress
        for f in $files; do
            [ -f "$tl/$f" ] && cp -f "$tl/$f" "$cache/$f"
        done
    fi
}
ensure_testlib_files

# runtests is only needed for the sequential, non-coverage, non-fast full run.
if [ "$PARALLEL_MODE" = false ] && [ "$FAST_MODE" = false ] && [ "$COVERAGE_MODE" = false ] \
   && ! command -v runtests &> /dev/null; then
    echo -e "${RED}Error: 'runtests' command not found${NC}"
    exit 1
fi

echo -e "${YELLOW}Changing to testing config${NC}"
sed -i 's/^O3SHOP_CONF_DBNAME="o3shop"$/O3SHOP_CONF_DBNAME="o3shop-test"/' .env
echo "----------------------------------------"

echo -e "${YELLOW}Starting tests...${NC}"
if [ "$QUARANTINE_MODE" = true ]; then
    echo "Mode: quarantine (single process)"
elif [ "$COVERAGE_MODE" = true ]; then
    echo "Mode: coverage (single process, pcov)"
elif [ "$PARALLEL_MODE" = true ]; then
    echo "Mode: parallel (ParaTest -p ${PROCESSES})"
elif [ "$FAST_MODE" = true ]; then
    echo "Mode: fast (phpunit direct)"
else
    echo "Mode: sequential (runtests)"
fi
echo "$(date)"
echo "----------------------------------------"

START_TIME=$(date +%s)

# Test targets: explicit args, else the whole unit suite.
if [ ${#PASSTHROUGH_ARGS[@]} -eq 0 ]; then
    TEST_TARGETS="/var/www/html/tests/Unit"
else
    TEST_TARGETS=""
    for arg in "${PASSTHROUGH_ARGS[@]}"; do
        if [[ "$arg" == /* ]] || [[ "$arg" == -* ]]; then
            TEST_TARGETS="$TEST_TARGETS $arg"
        else
            TEST_TARGETS="$TEST_TARGETS /var/www/html/$arg"
        fi
    done
fi

# Group filter.
if [ "$QUARANTINE_MODE" = true ]; then
    GROUP_FLAGS="--group quarantine"
else
    GROUP_FLAGS="--exclude-group quarantine"
fi

# xdebug is turned OFF for every run: it was loaded in coverage mode by the image
# and is both a source of PHP segfaults under the parallel workers (exit 139) and
# a big slowdown. Coverage uses the faster pcov driver instead. XDEBUG_MODE is
# xdebug's env override and is inherited by the ParaTest worker subprocesses.
export XDEBUG_MODE=off

# pcov coverage driver: enabled only for --coverage, scoped to source/. pcov is
# off by default in php.ini, so non-coverage runs are unaffected.
PCOV_PHP_FLAGS=""
if [ "$COVERAGE_MODE" = true ]; then
    PCOV_PHP_FLAGS="-d pcov.enabled=1 -d pcov.directory=/var/www/html/source"
fi

# The repo is a virtiofs (FUSE) mount under colima, so every PHP include is a slow
# round-trip to the host. CLI opcache is OFF by default, so each worker would
# re-read AND recompile every file for every test — the workers then spend most of
# their time blocked in the FUSE wait channel (more workers don't help; CPU/DB
# sit idle). Enabling CLI opcache (and skipping timestamp checks — code cannot
# change mid-run) keeps compiled files in memory and removes the stalls. For
# coverage, opcache optimizations are disabled so bytecode optimization can't drop
# lines pcov must see as covered.
PHP_FLAGS="-d opcache.enable_cli=1 -d opcache.validate_timestamps=0 -d opcache.memory_consumption=256 -d opcache.max_accelerated_files=30000 -d opcache.interned_strings_buffer=32"
if [ "$COVERAGE_MODE" = true ]; then
    PHP_FLAGS="$PHP_FLAGS -d opcache.optimization_level=0 $PCOV_PHP_FLAGS"
fi

# --all-failures: run to completion instead of stopping at the first failure.
# The default tests/phpunit.xml has stopOnError/stopOnFailure="true"; generate a
# sibling config with those off (kept in tests/ so the relative bootstrap and
# ../source coverage paths still resolve). Removed on exit.
CONFIG_FLAG=""
TMP_CONFIG=""
if [ "$ALL_FAILURES_MODE" = true ]; then
    TMP_CONFIG=$(mktemp /var/www/html/tests/phpunit-all-failures-XXXXXX.xml)
    sed -e 's/stopOnError="true"/stopOnError="false"/g' \
        -e 's/stopOnFailure="true"/stopOnFailure="false"/g' \
        /var/www/html/tests/phpunit.xml > "$TMP_CONFIG"
    CONFIG_FLAG="-c $TMP_CONFIG"
    trap '[ -n "$TMP_CONFIG" ] && rm -f "$TMP_CONFIG"' EXIT
    echo -e "${YELLOW}Mode: --all-failures (continue past first failure)${NC}"
fi
# The config used when none is otherwise supplied.
CONFIG_ARG="$CONFIG_FLAG"
[ -z "$CONFIG_ARG" ] && CONFIG_ARG="-c /var/www/html/tests/phpunit.xml"

# Provision N isolated worker databases + project-config dirs and run ParaTest.
# Each worker gets its own "<base>_<token>" database (installed fresh by the
# per-worker bootstrap), compile dir and var/configuration copy, so the workers
# never corrupt each other's shared state. See tests/paratest_bootstrap.php.
#
# A handful of tests are inherently order-coupled (tagged @group parallel-unsafe):
# they only pass in a stable serial order and pollute shared state for the other
# workers in parallel. So everything else runs in parallel, then that group runs
# once, serially.
provision_and_run_parallel() {
    local base_db dbroot dbhost dbuser t

    base_db=$(grep '^O3SHOP_CONF_DBNAME=' /var/www/html/.env | head -1 | cut -d'"' -f2)
    dbroot=$(grep '^O3SHOP_CONF_DBROOT=' /var/www/html/.env | head -1 | cut -d'"' -f2)
    dbhost=$(grep '^O3SHOP_CONF_DBHOST=' /var/www/html/.env | head -1 | cut -d'"' -f2)
    dbuser=$(grep '^O3SHOP_CONF_DBUSER=' /var/www/html/.env | head -1 | cut -d'"' -f2)

    # ParaTest takes a single --path.
    local paratest_path="/var/www/html/tests/Unit"
    if [ ${#PASSTHROUGH_ARGS[@]} -gt 0 ]; then
        local first="${PASSTHROUGH_ARGS[0]}"
        [[ "$first" == /* ]] && paratest_path="$first" || paratest_path="/var/www/html/$first"
    fi

    echo -e "${YELLOW}ParaTest -p ${PROCESSES} (base DB '${base_db}')${NC}"

    # 1. Patch the testing-library for per-worker config isolation (idempotent).
    php /var/www/html/tests/bin/apply-parallel-patches.php || return 1

    # 2. Regenerate the shared unified-namespace classes ONCE, up front (the
    #    per-worker bootstrap skips this — its delete-then-regenerate races).
    echo -e "${YELLOW}Regenerating unified-namespace classes...${NC}"
    php -r 'require "/var/www/html/vendor/autoload.php"; \OxidEsales\TestingLibrary\TestConfig::prepareUnifiedNamespaceClasses();' || return 1

    # 3. Provision per-worker databases, compile dirs and project-config dirs.
    echo -e "${YELLOW}Provisioning ${PROCESSES} worker databases + config dirs...${NC}"
    for t in $(seq 1 "$PROCESSES"); do
        mysql -uroot -p"$dbroot" -h "$dbhost" -e \
            "DROP DATABASE IF EXISTS \`${base_db}_${t}\`;
             CREATE DATABASE \`${base_db}_${t}\`;
             GRANT ALL ON \`${base_db}_${t}\`.* TO '${dbuser}'@'%';
             FLUSH PRIVILEGES;" 2>/dev/null || return 1

        rm -rf "/var/www/html/var/configuration_${t}" "/var/www/html/var/configuration_${t}-backup"
        cp -r /var/www/html/var/configuration "/var/www/html/var/configuration_${t}"

        rm -rf "/var/www/html/source/tmp/paratest_${t}"
        mkdir -p "/var/www/html/source/tmp/paratest_${t}/smarty"
    done

    export O3SHOP_CONF_DBNAME="$base_db"

    # Parallel run of everything except the order-coupled group.
    # NOTE: ParaTest honours only the LAST --exclude-group, so quarantine and
    # parallel-unsafe MUST be one comma-separated flag (two flags drop the first).
    vendor/bin/paratest \
        -p "$PROCESSES" \
        --runner WrapperRunner \
        --bootstrap /var/www/html/tests/paratest_bootstrap.php \
        --passthru-php="$PHP_FLAGS" \
        $CONFIG_ARG \
        --exclude-group quarantine,parallel-unsafe \
        --path "$paratest_path"
    local par_ec=$?

    # The order-coupled group, once, serially.
    echo -e "${YELLOW}Running @group parallel-unsafe tests serially...${NC}"
    O3SHOP_CONF_DBNAME="$base_db" php $PHP_FLAGS vendor/bin/phpunit \
        --bootstrap /var/www/html/vendor/o3-shop/testing-library/bootstrap.php \
        --colors=always \
        $CONFIG_ARG \
        --exclude-group quarantine \
        --group parallel-unsafe \
        $TEST_TARGETS
    local ser_ec=$?

    [ "$par_ec" -ne 0 ] && return "$par_ec"
    return "$ser_ec"
}

# Single-process coverage run (pcov). Reports go to coverage/.
run_coverage() {
    mkdir -p /var/www/html/coverage
    php $PHP_FLAGS vendor/bin/phpunit \
        --bootstrap /var/www/html/vendor/o3-shop/testing-library/bootstrap.php \
        --colors=always \
        $CONFIG_ARG \
        $GROUP_FLAGS \
        --coverage-clover /var/www/html/coverage/coverage.xml \
        --coverage-html /var/www/html/coverage/html \
        --log-junit /var/www/html/coverage/junit.xml \
        $TEST_TARGETS
}

# --- Dispatch ---------------------------------------------------------------
if [ "$COVERAGE_MODE" = true ]; then
    run_coverage
elif [ "$PARALLEL_MODE" = true ]; then
    provision_and_run_parallel
elif [ "$FAST_MODE" = true ]; then
    php $PHP_FLAGS vendor/bin/phpunit \
        --bootstrap vendor/o3-shop/testing-library/bootstrap.php \
        --colors=always \
        $CONFIG_FLAG \
        $GROUP_FLAGS \
        --no-coverage \
        $TEST_TARGETS
else
    runtests $TEST_TARGETS --colors=always $CONFIG_FLAG $GROUP_FLAGS --no-coverage
fi
TEST_EXIT_CODE=$?

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
if [ $DURATION -ge 60 ]; then
    TIME_DISPLAY="$((DURATION / 60))m $((DURATION % 60))s"
else
    TIME_DISPLAY="${DURATION}s"
fi

echo "----------------------------------------"
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}Tests completed successfully!${NC}"
else
    echo -e "${RED}Tests failed with exit code: $TEST_EXIT_CODE${NC}"
fi
echo -e "${BLUE}Test execution time: $TIME_DISPLAY${NC}"
echo "Test run completed at: $(date)"

echo "----------------------------------------"
echo -e "${YELLOW}Changing to normal config${NC}"
sed -i 's/^O3SHOP_CONF_DBNAME="o3shop-test"$/O3SHOP_CONF_DBNAME="o3shop"/' .env

exit $TEST_EXIT_CODE
