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
    composer install --no-interaction --optimize-autoloader || handle_error "Composer installation failed"
    log "${GREEN}Dependencies installed successfully${NC}"
}

# Function to set proper permissions
set_permissions() {
    log "${YELLOW}Setting proper file permissions...${NC}"
    
    directories=(
        "/var/www/html/var"
        "/var/www/html/source"
        "/var/www/html/.env"
    )
    
    for dir in "${directories[@]}"; do
        if [ -e "$dir" ]; then
            log "Setting ownership for $dir"
            chown -R root:www-data "$dir" || handle_error "Failed to set ownership for $dir"
            
            log "Setting permissions for $dir"
            chmod -R 2775 "$dir" || handle_error "Failed to set permissions for $dir"
        else
            log "${YELLOW}Warning: $dir does not exist${NC}"
        fi
    done
    
    log "${GREEN}Permissions set successfully${NC}"
}

# Function to configure and start Apache
start_apache() {
    log "${YELLOW}Configuring and starting Apache...${NC}"
    
    # Enable Apache modules
    a2enmod rewrite || handle_error "Failed to enable Apache rewrite module"
    
    log "${GREEN}Starting Apache...${NC}"
    apache2-foreground
}

# Main execution
main() {
    log "${GREEN}Starting shop setup...${NC}"
    
    setup_environment
    install_theme
    install_dependencies
    set_permissions
    start_apache
}

# Run the script
main

# This line should never be reached as apache2-foreground should keep the container running
exit 1