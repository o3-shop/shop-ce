#!/bin/bash

# Script to run tests with timing
#
# Usage:
#   run-tests.sh [--fast] [--coverage] [test targets...]
#
# Options:
#   --fast      Skip shop install and UNC regeneration in runtests wrapper,
#               call phpunit directly. ~3x faster for iterative development.
#               Requires that the UNC classes and DB views were generated at
#               least once before (e.g. by a prior full run or composer install).
#   --coverage  Generate coverage reports (clover, html, junit). Without this
#               flag, coverage is skipped for faster execution.
#
# Examples:
#   run-tests.sh                                        # full run, all unit tests, no coverage
#   run-tests.sh --fast tests/Unit/Core/ConfigTest.php  # fast single-file run
#   run-tests.sh --coverage tests/Unit                  # full run with coverage

# Define colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

FAST_MODE=false
COVERAGE_MODE=false
QUARANTINE_MODE=false
PASSTHROUGH_ARGS=()

# Parse arguments
for arg in "$@"; do
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
        *)
            PASSTHROUGH_ARGS+=("$arg")
            ;;
    esac
done

# Check if runtests command exists (needed for non-fast mode)
if [ "$FAST_MODE" = false ] && ! command -v runtests &> /dev/null; then
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

# Run the tests and store exit code
if [ "$FAST_MODE" = true ]; then
    # Fast mode: call phpunit directly, skipping the runtests wrapper's
    # redundant UNC regeneration. Uses the bootstrap which handles shop init.
    php vendor/bin/phpunit \
        --bootstrap vendor/o3-shop/testing-library/bootstrap.php \
        --colors=always \
        $GROUP_FLAGS \
        $COVERAGE_FLAGS \
        $TEST_TARGETS
else
    runtests $TEST_TARGETS --colors=always $GROUP_FLAGS $COVERAGE_FLAGS
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
