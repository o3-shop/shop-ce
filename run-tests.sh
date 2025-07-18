#!/bin/bash

# Script to run tests with timing

# Define colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Function to setup test database
setup_test_database() {
    echo -e "${CYAN}Setting up test database...${NC}"

    # Load environment variables
    if [ -f .env ]; then
        export $(cat .env | grep -v ^# | xargs)
    else
        echo -e "${RED}Error: .env file not found${NC}"
        exit 1
    fi

    # Database configuration
    DB_HOST="${O3SHOP_CONF_DBHOST:-localhost}"
    DB_USER="${O3SHOP_CONF_DBUSER}"
    DB_PASSWORD="${O3SHOP_CONF_DBPWD}"
    DB_PORT="${O3SHOP_CONF_DBPORT:-3306}"
    TEST_DB_NAME="o3shop-test"
    SCHEMA_FILE="source/Setup/Sql/database_schema.sql"
    DEMODATA_FILE="vendor/o3-shop/shop-demodata-ce/src/demodata.sql"

    # Check if required environment variables are set
    if [ -z "$DB_USER" ] || [ -z "$DB_PASSWORD" ]; then
        echo -e "${RED}Error: Database credentials not found in environment${NC}"
        exit 1
    fi

    # Check if schema file exists
    if [ ! -f "$SCHEMA_FILE" ]; then
        echo -e "${RED}Error: Schema file not found at $SCHEMA_FILE${NC}"
        exit 1
    fi

    # Check if demo data file exists
    if [ ! -f "$DEMODATA_FILE" ]; then
        echo -e "${YELLOW}Warning: Demo data file not found at $DEMODATA_FILE${NC}"
        echo -e "${YELLOW}Continuing without demo data...${NC}"
        DEMODATA_FILE=""
    fi

    # Check if MySQL client is available
    if ! command -v mysql &> /dev/null; then
        echo -e "${RED}Error: MySQL client not found${NC}"
        exit 1
    fi

    echo "Creating/updating test database: $TEST_DB_NAME"

    # Create test database if it doesn't exist
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$TEST_DB_NAME\`;" 2>/dev/null

    if [ $? -ne 0 ]; then
        echo -e "${RED}Error: Failed to create test database${NC}"
        exit 1
    fi

    # Check if test database has tables
    TABLE_COUNT=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" -D "$TEST_DB_NAME" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$TEST_DB_NAME';" -s -N 2>/dev/null)

    if [ $? -ne 0 ]; then
        echo -e "${RED}Error: Failed to check test database tables${NC}"
        exit 1
    fi

    # Import schema and demo data if database is empty or force refresh
    if [ "$TABLE_COUNT" -eq 0 ] || [ "$1" = "--force-refresh" ]; then
        echo "Importing schema into test database..."
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" "$TEST_DB_NAME" < "$SCHEMA_FILE" 2>/dev/null

        if [ $? -eq 0 ]; then
            echo -e "${GREEN}Schema imported successfully${NC}"
        else
            echo -e "${RED}Error: Failed to import schema${NC}"
            exit 1
        fi

        # Run database migrations for test database
        if [ -f "vendor/bin/oe-eshop-db_migrate" ]; then
            echo "Running database migrations for test database..."
            export O3SHOP_CONF_DBNAME_BACKUP="$O3SHOP_CONF_DBNAME"
            export O3SHOP_CONF_DBNAME="$TEST_DB_NAME"

            php vendor/bin/oe-eshop-db_migrate migrations:migrate --no-interaction >/dev/null 2>&1

            if [ $? -eq 0 ]; then
                echo -e "${GREEN}Database migrations completed successfully${NC}"
            else
                echo -e "${YELLOW}Warning: Database migrations failed${NC}"
            fi

            export O3SHOP_CONF_DBNAME="$O3SHOP_CONF_DBNAME_BACKUP"
            unset O3SHOP_CONF_DBNAME_BACKUP
        fi

        # Import demo data if file exists
        if [ -n "$DEMODATA_FILE" ]; then
            echo "Importing demo data into test database..."
            mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" "$TEST_DB_NAME" < "$DEMODATA_FILE" 2>/dev/null

            if [ $? -eq 0 ]; then
                echo -e "${GREEN}Demo data imported successfully${NC}"
            else
                echo -e "${RED}Error: Failed to import demo data${NC}"
                exit 1
            fi
        fi
    else
        echo "Test database already has schema (found $TABLE_COUNT tables)"
    fi

    # Generate database views for test database
    echo "Generating database views for test database..."
    if [ -f "vendor/bin/oe-eshop-db_views_generate" ]; then
        export O3SHOP_CONF_DBNAME_BACKUP="$O3SHOP_CONF_DBNAME"
        export O3SHOP_CONF_DBNAME="$TEST_DB_NAME"

        php vendor/bin/oe-eshop-db_views_generate >/dev/null 2>&1

        if [ $? -eq 0 ]; then
            echo -e "${GREEN}Database views generated successfully${NC}"
        else
            echo -e "${YELLOW}Warning: Database views generation failed${NC}"
        fi

        export O3SHOP_CONF_DBNAME="$O3SHOP_CONF_DBNAME_BACKUP"
        unset O3SHOP_CONF_DBNAME_BACKUP
    else
        echo -e "${YELLOW}Warning: Database views generator not found${NC}"
    fi

    # Create test_config.yml to ensure tests use the correct database
    echo "Creating test configuration..."

    echo -e "${GREEN}Test database setup completed${NC}"
}

# Check if runtests command exists
if ! command -v runtests &> /dev/null; then
    echo -e "${RED}Error: 'runtests' command not found${NC}"
    echo "Please make sure the testing framework is properly installed"
    exit 1
fi

# Check for force refresh flag
FORCE_REFRESH=""
if [ "$1" = "--force-refresh" ]; then
    FORCE_REFRESH="--force-refresh"
    shift
fi

# Display start message
echo -e "${YELLOW}Starting test preparation...${NC}"
echo "Test directory: tests/"
echo "$(date)"
echo "----------------------------------------"

# Setup test database
setup_test_database $FORCE_REFRESH

echo "----------------------------------------"
echo -e "${YELLOW}Starting tests...${NC}"

# Load environment variables and backup current database name
if [ -f .env ]; then
    export $(cat .env | grep -v ^# | xargs)
fi

# Backup original database name and set test database
export O3SHOP_CONF_DBNAME_ORIGINAL="$O3SHOP_CONF_DBNAME"
export O3SHOP_CONF_DBNAME="o3shop-test"

# Record start time
START_TIME=$(date +%s)

# Run the tests and store exit code
runtests /var/www/html/tests/Unit --colors=always "$@"
TEST_EXIT_CODE=$?

# Restore original database name
export O3SHOP_CONF_DBNAME="$O3SHOP_CONF_DBNAME_ORIGINAL"
unset O3SHOP_CONF_DBNAME_ORIGINAL

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

exit $TEST_EXIT_CODE
