#!/bin/bash

# Script to run tests with timing

# Define colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if runtests command exists
if ! command -v runtests &> /dev/null; then
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
echo "Test directory: tests/"
echo "$(date)"
echo "----------------------------------------"

# Record start time
START_TIME=$(date +%s)

# Run the tests and store exit code
runtests /var/www/html/tests/Unit --colors=always --coverage-clover /var/www/html/coverage/coverage.xml --coverage-html /var/www/html/coverage/html --log-junit /var/www/html/coverage/junit.xml
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
