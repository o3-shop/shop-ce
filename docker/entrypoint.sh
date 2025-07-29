#!/bin/bash
set -e

# Define colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Log function
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

# Error handling function
handle_error() {
    echo -e "${RED}[ERROR] $1${NC}"
    exit 1
}

# Function to set up environment files
setup_environment() {
    log "${YELLOW}Setting up environment files...${NC}"
    
    if [ ! -f .env ]; then
        cp .env.example .env || handle_error "Failed to copy .env.example to .env"
        log "Created .env file from example"
    else
        log ".env file already exists"
    fi
    
    if [ ! -f source/config.inc.php ]; then
        cp source/config.inc.php.dist source/config.inc.php || handle_error "Failed to copy config.inc.php.dist"
        log "Created config.inc.php from distribution template"
    else
        log "config.inc.php already exists"
    fi
}

# Function to download and install theme
install_theme() {
    log "${YELLOW}Installing Wave theme...${NC}"
    
    # Check if theme is already installed
    if [ -d "source/Application/views/wave" ] && [ "$(ls -A source/Application/views/wave)" ]; then
        log "Wave theme appears to be already installed, skipping"
        return 0
    fi
    
    # Download theme
    log "Downloading theme from GitHub..."
    wget -q https://github.com/o3-shop/wave-theme/archive/refs/heads/main.zip -O main.zip || handle_error "Failed to download theme"
    
    # Extract theme
    log "Extracting theme..."
    unzip -q main.zip || handle_error "Failed to extract theme archive"
    
    # Copy theme files
    log "Copying theme files to appropriate directories..."
    mkdir -p source/out/ || handle_error "Failed to create out directory"
    cp -r wave-theme-main/out/* source/out/ || handle_error "Failed to copy theme out files"
    
    mkdir -p source/Application/views/wave || handle_error "Failed to create theme views directory"
    rm -rf wave-theme-main/out
    cp -r wave-theme-main/* source/Application/views/wave || handle_error "Failed to copy theme view files"
    
    # Clean up
    log "Cleaning up temporary files..."
    rm -rf wave-theme-main
    rm main.zip
    
    log "${GREEN}Wave theme installed successfully${NC}"
}

# Function to install dependencies
install_dependencies() {
    log "${YELLOW}Installing Composer dependencies...${NC}"
    COMPOSER_ROOT_VERSION=dev-main composer install --no-interaction --optimize-autoloader || handle_error "Composer installation failed"
    log "${GREEN}Dependencies installed successfully${NC}"
}

# Function to configure and start Apache
start_apache() {
    log "${YELLOW}Configuring and starting Apache...${NC}"
    
    # Enable Apache modules
    a2enmod rewrite || handle_error "Failed to enable Apache rewrite module"
    
    log "${GREEN}Starting Apache...${NC}"
    rm /tmp/o3setup-running

    apache2-foreground
}

install_demodata() {
    if [ -d "vendor/o3-shop/shop-demodata-ce" ] && [ "$(ls -A vendor/o3-shop/shop-demodata-ce)" ]; then
      log "${GREEN}Demodata is already downloaded. Skipping download."
      return 0
    fi

    log "${YELLOW}Downloading demo data"

    cd /tmp
    git clone https://github.com/o3-shop/shop-demodata-ce
    rm -rf shop-demodata-ce/.git

    log "Moving demo data into target directory 'vendor/o3-shop'"
    cp -r shop-demodata-ce /var/www/html/vendor/o3-shop

    # rm -rf shop-demodata-ce
    log "${GREEN}Installed demo data package"

    cd /var/www/html
}

setup_db() {
  log "${YELLOW}Setting up the database"

  # Database connection parameters - match your PHP setup
  local DB_HOST="db"
  local DB_USER="o3shop"
  local DB_PWD="o3shop"
  local DB_PORT="3306"

  log "${YELLOW}Waiting for database container (timeout 2 mins)..."
  local timeout=120
  local start_time=$(date +%s)

  while ! mysqladmin ping -h "$DB_HOST" -u "$DB_USER" -p"$DB_PWD" --port "$DB_PORT" --silent; do
    log "${YELLOW}Database not ready - retrying in 5s..."
    sleep 5

    # Timeout check
    local current_time=$(date +%s)
    if [ $((current_time - start_time)) -ge $timeout ]; then
      log "${RED}Timeout reached - database not responding"
      exit 1
    fi
  done

  log "${GREEN}Database connection established"
  bin/o3-setup
}

clear_cache() {
    log "${YELLOW}Clearing cache...${NC}"
    /var/www/html/bin/oe-console oe:cache:clear || handle_error "Failed to clear cache"
    log "${GREEN}Cache cleared successfully${NC}"
}


# Main execution
main() {
    echo "setup is running" > /tmp/o3setup-running

    log "${GREEN}Starting shop setup...${NC}"

    setup_environment || exit 127
    install_dependencies || exit 127
    install_demodata || exit 127
    setup_db || exit 127
    install_theme || exit 127
    clear_cache || exit 127
    start_apache || exit 127
}

# Run the script
main

# This line should never be reached as apache2-foreground should keep the container running
exit 1