#!/bin/bash

# Script to run tests

# Define colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if runtests command exists
if ! command -v runtests &> /dev/null; then
    echo -e "${RED}Error: 'runtests' command not found${NC}"
    echo "Please make sure the testing framework is properly installed"
    exit 1
fi

# Display start message
echo -e "${YELLOW}Starting tests...${NC}"
echo "Test directory: tests/"
echo "$(date)"
echo "----------------------------------------"

# Run the tests and store exit code
runtests tests/
TEST_EXIT_CODE=$?

echo "----------------------------------------"

# Check test results
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}Tests completed successfully!${NC}"
else
    echo -e "${RED}Tests failed with exit code: $TEST_EXIT_CODE${NC}"
fi

# Print timestamp
echo "Test run completed at: $(date)"

exit $TEST_EXIT_CODE