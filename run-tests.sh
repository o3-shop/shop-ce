#!/bin/bash

# Script to run tests with timing
#
# Usage:
#   run-tests.sh [--fast] [--coverage] [--all-failures] [test targets...]
#
# Options:
#   --fast          Skip shop install and UNC regeneration in runtests wrapper,
#                   call phpunit directly. ~3x faster for iterative development.
#                   Requires that the UNC classes and DB views were generated at
#                   least once before (e.g. by a prior full run or composer install).
#   --coverage      Generate coverage reports (clover, html, junit). Without this
#                   flag, coverage is skipped for faster execution.
#   --all-failures  Don't stop at first failure — run the full suite and collect
#                   every failure in one pass. Useful when one fix domino-effects
#                   into many test updates (seed data changes, fixture renames)
#                   so you can see the full damage list before iterating. The
#                   default tests/phpunit.xml has stopOnError/stopOnFailure="true"
#                   for fast CI feedback; this flag generates a temp config with
#                   those flipped off, then passes it via -c.
#
# Examples:
#   run-tests.sh                                        # full run, all unit tests, no coverage
#   run-tests.sh --fast tests/Unit/Core/ConfigTest.php  # fast single-file run
#   run-tests.sh --coverage tests/Unit                  # full run with coverage
#   run-tests.sh --all-failures                         # full suite, every failure reported

# Define colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

FAST_MODE=false
COVERAGE_MODE=false
QUARANTINE_MODE=false
ALL_FAILURES_MODE=false
PARALLEL_MODE=false
SEQUENTIAL_EXPLICIT=false
PROCESSES=""
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
        --fast)
            FAST_MODE=true
            ;;
        --coverage)
            COVERAGE_MODE=true
            ;;
        --quarantine)
            QUARANTINE_MODE=true
            FAST_MODE=true
            ;;
        --all-failures)
            ALL_FAILURES_MODE=true
            ;;
        --parallel)
            PARALLEL_MODE=true
            ;;
        --sequential)
            PARALLEL_MODE=false
            SEQUENTIAL_EXPLICIT=true
            ;;
        -p|--processes)
            CAPTURE_PROCESSES=true
            ;;
        -p*)
            PROCESSES="${arg#-p}"
            ;;
        --processes=*)
            PROCESSES="${arg#--processes=}"
            ;;
        *)
            PASSTHROUGH_ARGS+=("$arg")
            ;;
    esac
done

# Coverage defaults to SEQUENTIAL. On the full suite it is both faster and
# deterministic: parallel coverage makes xdebug-instrumented workers contend
# (measured ~2.8x SLOWER than sequential) and order-coupled tests go flaky.
# Opt into parallel+merged coverage explicitly with --parallel: it runs the
# suite excluding @group parallel-unsafe, a serial run of exactly those tests,
# then merges the two coverage sets into one report.

# Self-heal the vendored testing-library entry files. base.php does chdir() into
# the testing-library directory, so a test that resets the file cache against the
# current working directory deletes every *.php there (base.php, bootstrap.php,
# AllTests*.php). That leaves the NEXT run unable to bootstrap. Keep a fresh
# cache of the good files and restore them if they went missing.
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

# Check if runtests command exists (needed only for the sequential-full path)
if [ "$FAST_MODE" = false ] && [ "$PARALLEL_MODE" = false ] && ! command -v runtests &> /dev/null; then
    echo -e "${RED}Error: 'runtests' command not found${NC}"
    echo "Please make sure the testing framework is properly installed"
    exit 1
fi

# Display start message
echo -e "${YELLOW}Changing to testing config${NC}"
sed -i 's/^O3SHOP_CONF_DBNAME="o3shop"$/O3SHOP_CONF_DBNAME="o3shop-test"/' .env
echo -e "${GREEN}Changed to testing config${NC}"
echo "----------------------------------------"

# Display start message
echo -e "${YELLOW}Starting tests...${NC}"
if [ "$QUARANTINE_MODE" = true ]; then
    echo "Mode: quarantine (slow/special tests only)"
elif [ "$FAST_MODE" = true ]; then
    echo "Mode: fast (phpunit direct, no shop install)"
else
    echo "Mode: full (via runtests wrapper)"
fi
echo "$(date)"
echo "----------------------------------------"

# Record start time
START_TIME=$(date +%s)

# Determine test targets (use remaining arguments if provided, else default to tests/Unit)
if [ ${#PASSTHROUGH_ARGS[@]} -eq 0 ]; then
    TEST_TARGETS="/var/www/html/tests/Unit"
else
    TEST_TARGETS=""
    for arg in "${PASSTHROUGH_ARGS[@]}"; do
        if [[ "$arg" == /* ]] || [[ "$arg" == -* ]]; then
            TEST_TARGETS="$TEST_TARGETS $arg"
        else
            # Prefix with /var/www/html/ if not an absolute path
            TEST_TARGETS="$TEST_TARGETS /var/www/html/$arg"
        fi
    done
fi

# Build coverage flags
COVERAGE_FLAGS=""
if [ "$COVERAGE_MODE" = true ]; then
    COVERAGE_FLAGS="--coverage-clover /var/www/html/coverage/coverage.xml --coverage-html /var/www/html/coverage/html --log-junit /var/www/html/coverage/junit.xml"
else
    COVERAGE_FLAGS="--no-coverage"
fi

# Build group filter
if [ "$QUARANTINE_MODE" = true ]; then
    GROUP_FLAGS="--group quarantine"
else
    GROUP_FLAGS="--exclude-group quarantine"
fi

# Build config flag for --all-failures mode. The default tests/phpunit.xml has
# stopOnError/stopOnFailure="true" (fast CI feedback); we generate a sibling
# config with those flipped off so the suite runs to completion. The temp
# config lives in tests/ so the relative bootstrap="bootstrap.php" still
# resolves; trap cleans it up no matter how we exit.
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

# Provision N isolated databases + project-config dirs and run ParaTest in
# parallel. Each worker gets its own "<base>_<token>" DB (installed fresh by the
# per-worker bootstrap), its own compile dir and its own var/configuration copy,
# so the workers never corrupt each other's shared state. See
# tests/paratest_bootstrap.php for how TEST_TOKEN maps to those resources.
provision_and_run_parallel() {
    local base_db dbroot dbhost dbuser cpu t

    base_db=$(grep '^O3SHOP_CONF_DBNAME=' /var/www/html/.env | head -1 | cut -d'"' -f2)
    dbroot=$(grep '^O3SHOP_CONF_DBROOT=' /var/www/html/.env | head -1 | cut -d'"' -f2)
    dbhost=$(grep '^O3SHOP_CONF_DBHOST=' /var/www/html/.env | head -1 | cut -d'"' -f2)
    dbuser=$(grep '^O3SHOP_CONF_DBUSER=' /var/www/html/.env | head -1 | cut -d'"' -f2)

    # Worker count: bound to keep per-worker DB install cost sane. Default
    # min(4, cpu-2), floor 2.
    if [ -z "$PROCESSES" ]; then
        cpu=$(nproc 2>/dev/null || echo 4)
        PROCESSES=$(( cpu - 2 ))
        [ "$PROCESSES" -gt 4 ] && PROCESSES=4
        [ "$PROCESSES" -lt 2 ] && PROCESSES=2
    fi

    # Determine a single target path for ParaTest (it takes one --path).
    local paratest_path="/var/www/html/tests/Unit"
    if [ ${#PASSTHROUGH_ARGS[@]} -gt 0 ]; then
        local first="${PASSTHROUGH_ARGS[0]}"
        if [[ "$first" == /* ]]; then
            paratest_path="$first"
        else
            paratest_path="/var/www/html/$first"
        fi
    fi

    echo -e "${YELLOW}Parallel mode: ParaTest -p ${PROCESSES} (base DB '${base_db}')${NC}"

    # 1. Patch the testing-library for per-worker config isolation (idempotent).
    php /var/www/html/tests/bin/apply-parallel-patches.php || return 1

    # 2. Regenerate the shared unified-namespace classes ONCE, up front. The
    #    per-worker bootstrap intentionally skips this (it does a
    #    delete-then-regenerate that races across workers).
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

    # 4. Run. Export the base DB name so the per-worker bootstrap can derive
    #    "<base>_<token>" (Dotenv::createImmutable in config.inc.php keeps it).
    #    --all-failures already supplies its own "-c <tmp config>"; otherwise use
    #    the default phpunit.xml.
    local config_arg="$CONFIG_FLAG"
    [ -z "$config_arg" ] && config_arg="-c /var/www/html/tests/phpunit.xml"

    export O3SHOP_CONF_DBNAME="$base_db"

    if [ "$COVERAGE_MODE" != true ]; then
        vendor/bin/paratest \
            -p "$PROCESSES" \
            --runner WrapperRunner \
            --bootstrap /var/www/html/tests/paratest_bootstrap.php \
            $config_arg \
            $GROUP_FLAGS \
            --path "$paratest_path"
        return $?
    fi

    # --- Parallel + merged coverage ---------------------------------------
    # The ~handful of order-coupled tests are tagged @group parallel-unsafe.
    # They MUST NOT be gambled in parallel (they only pass in a stable order),
    # so we split the run: everything else in parallel, those tests serially,
    # then merge the two coverage sets into one report. This keeps the coverage
    # gate deterministic.
    local cov_dir="/var/www/html/coverage"
    mkdir -p "$cov_dir"
    rm -f "$cov_dir/_parallel.cov" "$cov_dir/_serial.cov" \
          "$cov_dir/_junit_parallel.xml" "$cov_dir/_junit_serial.xml"

    # Coverage-specific config: turn OFF includeUncoveredFiles/processUncoveredFiles
    # so each worker (and the serial pass) measures ONLY executed code — no
    # whole-source-tree rescan paid N times (xdebug makes that very expensive).
    # The uncovered files are added back EXACTLY ONCE in merge-coverage.php, so the
    # final report's denominator is unchanged. Derived from the in-use base config
    # (the --all-failures temp config if present, else tests/phpunit.xml) and kept
    # in tests/ so its relative bootstrap="bootstrap.php" and ../source paths still
    # resolve. Removed on exit.
    local base_config="/var/www/html/tests/phpunit.xml"
    [ -n "$TMP_CONFIG" ] && base_config="$TMP_CONFIG"
    local cov_config
    cov_config=$(mktemp /var/www/html/tests/phpunit-coverage-XXXXXX.xml)
    sed -e 's/includeUncoveredFiles="true"/includeUncoveredFiles="false"/g' \
        -e 's/processUncoveredFiles="true"/processUncoveredFiles="false"/g' \
        "$base_config" > "$cov_config"
    local cov_config_flag="-c $cov_config"

    echo -e "${YELLOW}[coverage 1/3] Parallel run (--exclude-group parallel-unsafe)...${NC}"
    vendor/bin/paratest \
        -p "$PROCESSES" \
        --runner WrapperRunner \
        --bootstrap /var/www/html/tests/paratest_bootstrap.php \
        $cov_config_flag \
        $GROUP_FLAGS \
        --exclude-group parallel-unsafe \
        --coverage-php "$cov_dir/_parallel.cov" \
        --log-junit "$cov_dir/_junit_parallel.xml" \
        --path "$paratest_path"
    local par_ec=$?

    echo -e "${YELLOW}[coverage 2/3] Serial run (--group parallel-unsafe)...${NC}"
    # Runs against the base test DB with the stock bootstrap (no per-worker
    # isolation needed for a single process).
    O3SHOP_CONF_DBNAME="$base_db" php vendor/bin/phpunit \
        --bootstrap /var/www/html/vendor/o3-shop/testing-library/bootstrap.php \
        --colors=always \
        $cov_config_flag \
        --group parallel-unsafe \
        --coverage-php "$cov_dir/_serial.cov" \
        --log-junit "$cov_dir/_junit_serial.xml" \
        $TEST_TARGETS
    local ser_ec=$?

    echo -e "${YELLOW}[coverage 3/3] Merging coverage + JUnit into coverage/ (adds uncovered files once)...${NC}"
    php /var/www/html/tests/bin/merge-coverage.php
    local merge_ec=$?

    rm -f "$cov_config"

    # Fail if any stage failed.
    if [ "$par_ec" -ne 0 ]; then return "$par_ec"; fi
    if [ "$ser_ec" -ne 0 ]; then return "$ser_ec"; fi
    return "$merge_ec"
}

# Run the tests and store exit code
if [ "$PARALLEL_MODE" = true ]; then
    provision_and_run_parallel
elif [ "$FAST_MODE" = true ]; then
    # Fast mode: call phpunit directly, skipping the runtests wrapper's
    # redundant UNC regeneration. Uses the bootstrap which handles shop init.
    php vendor/bin/phpunit \
        --bootstrap vendor/o3-shop/testing-library/bootstrap.php \
        --colors=always \
        $CONFIG_FLAG \
        $GROUP_FLAGS \
        $COVERAGE_FLAGS \
        $TEST_TARGETS
else
    runtests $TEST_TARGETS --colors=always $CONFIG_FLAG $GROUP_FLAGS $COVERAGE_FLAGS
fi
TEST_EXIT_CODE=$?

# Record end time
END_TIME=$(date +%s)

# Calculate duration
DURATION=$((END_TIME - START_TIME))

# Convert to human-readable format
if [ $DURATION -ge 3600 ]; then
    HOURS=$((DURATION / 3600))
    MINUTES=$(((DURATION % 3600) / 60))
    SECONDS=$((DURATION % 60))
    TIME_DISPLAY="${HOURS}h ${MINUTES}m ${SECONDS}s"
elif [ $DURATION -ge 60 ]; then
    MINUTES=$((DURATION / 60))
    SECONDS=$((DURATION % 60))
    TIME_DISPLAY="${MINUTES}m ${SECONDS}s"
else
    TIME_DISPLAY="${DURATION}s"
fi

echo "----------------------------------------"

# Check test results
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}Tests completed successfully!${NC}"
else
    echo -e "${RED}Tests failed with exit code: $TEST_EXIT_CODE${NC}"
fi

# Print timing information
echo -e "${BLUE}Test execution time: $TIME_DISPLAY${NC}"

# Print timestamp
echo "Test run completed at: $(date)"

# Display start message
echo "----------------------------------------"
echo -e "${YELLOW}Changing to normal config${NC}"
sed -i 's/^O3SHOP_CONF_DBNAME="o3shop-test"$/O3SHOP_CONF_DBNAME="o3shop"/' .env
echo -e "${GREEN}Changed to normal config${NC}"
echo "----------------------------------------"

exit $TEST_EXIT_CODE
