#!/usr/bin/env bash
#
# Regression guard for o3-shop/o3-shop#127.
#
# Verifies that ./docker.sh test* propagates a non-zero exit code when
# the underlying phpunit run fails — including the segfault case that
# triggered the original report. Maps to the chain:
#
#   docker.sh test  →  docker exec  →  /var/www/html/run-tests.sh
#     → (non-fast) runtests (testing-library binary)
#         → passthru($phpunit, $return)   ← captures real signal kills
#         → exit($return)
#     → (--fast) php vendor/bin/phpunit
#   ← TEST_EXIT_CODE captured here, preserved through cleanup, exit $TEST_EXIT_CODE
#
# When this script exits 0, every layer propagates correctly. When it
# exits non-zero, somewhere in the chain a failure is being swallowed
# and a real regression could ship past the gate.
#
# *** Pipeline-masking trap (the most likely cause of false "exit 0"
# reports — and worth knowing when you copy/paste a docker.sh
# invocation): when you pipe output anywhere, the shell's $? reflects
# the LAST command in the pipeline, not the first. So
#
#   ./docker.sh test-all | tee log
#   echo $?                              # ← always 0 if tee succeeded
#
# To preserve the inner exit, use ${PIPESTATUS[0]} or `set -o pipefail`.
# This script never pipes, so its assertions are always authoritative. ***
#
# Usage:  bin/check-exit-propagation.sh
# Exit:   0 = all assertions pass, 1 = at least one chain layer is broken
#
# Refs: o3-shop/o3-shop#127

set -u

cd "$(dirname "$0")/.."

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

CONTAINER=o3shop-app
BOGUS=tests/Unit/Nonexistent/RegressionGuardBogus.php
SEGFAULT_STUB=/tmp/check-exit-propagation-segfault-phpunit

failures=0
total=0

assert_nonzero() {
    local label="$1"; shift
    total=$((total + 1))
    "$@" > /dev/null 2>&1
    local exit_code=$?
    if [ "$exit_code" -eq 0 ]; then
        echo -e "${RED}[FAIL]${NC} $label — outer exit was 0 (chain layer swallowed the failure)"
        failures=$((failures + 1))
    else
        echo -e "${GREEN}[OK]${NC}   $label — outer exit $exit_code"
    fi
}

ensure_container_running() {
    if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}\$"; then
        echo -e "${RED}${CONTAINER} is not running — start it with ./docker.sh start${NC}"
        exit 2
    fi
}

stage_segfaulting_phpunit() {
    docker exec -i "$CONTAINER" bash -c "cat > $SEGFAULT_STUB" <<'PHP'
#!/usr/bin/env php
<?php
// Emit some progress so the output is realistic ('....') then segfault
// for real via SIGSEGV (signal 11). Mirrors the original #127 scenario:
// phpunit's child dies mid-run with 'Segmentation fault (core dumped)'.
echo str_repeat('.', 45);
posix_kill(posix_getpid(), 11);
PHP
    docker exec -i "$CONTAINER" chmod +x "$SEGFAULT_STUB"
}

cleanup_segfault_stub() {
    docker exec -i "$CONTAINER" rm -f "$SEGFAULT_STUB" 2>/dev/null || true
}

trap cleanup_segfault_stub EXIT

ensure_container_running
stage_segfaulting_phpunit

echo "Checking docker.sh exit-code propagation across failure modes..."
echo

# Mode 1 — non-fast path with a phpunit exit 1 (file not found)
assert_nonzero "non-fast, bogus file (phpunit exit 1)" \
    ./docker.sh test "$BOGUS"

# Mode 2 — --fast path with the same
assert_nonzero "--fast, bogus file (phpunit exit 1)" \
    ./docker.sh test --fast "$BOGUS"

# Mode 3 — real SIGSEGV from a phpunit replacement, through the full
# chain (testing-library runtests + passthru + run-tests.sh cleanup).
# Verifies the exact scenario from #127.
assert_nonzero "non-fast, phpunit child SIGSEGV (139) — the #127 case" \
    docker exec -i -e PHPBIN="$SEGFAULT_STUB" "$CONTAINER" \
        ./run-tests.sh tests/Unit/Application/Model/AddressTest.php

echo
if [ "$failures" -eq 0 ]; then
    echo -e "${GREEN}All $total assertions passed.${NC} Exit-code propagation is intact across docker.sh, run-tests.sh, runtests, and passthru."
    exit 0
fi

echo -e "${RED}$failures / $total assertions failed.${NC} A test-runner layer is swallowing failure exit codes — false-greens possible."
echo -e "${YELLOW}Most likely: cleanup steps overwriting \$? without first capturing it; or a missing ${YELLOW}'|| exit ...'${NC}${YELLOW} on the dispatch in docker.sh.${NC}"
exit 1
