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

# Clones a theme repo into source/Application/views/<theme> and symlinks
# source/out/<theme> at its in-tree out/<theme> so Apache serves assets
# directly out of the working tree.
#
# Args: <theme-name> <git-url>
install_theme_from_git() {
    local theme="$1"
    local repo_url="$2"
    local view_dir="source/Application/views/${theme}"
    local out_dir="source/out/${theme}"

    log "${YELLOW}Installing ${theme} theme...${NC}"

    if [ -d "$view_dir" ] && [ "$(ls -A "$view_dir")" ]; then
        if [ ! -d "$view_dir/.git" ]; then
            handle_error "$(cat <<EOF

Detected old detached snapshot at ${view_dir} (no .git/ subdirectory).
This is the layout the previous wget/unzip bootstrap produced. The entrypoint
now expects a git working tree there so you can pull/commit/push to
${repo_url} directly.

If you have NO uncommitted edits in ${view_dir}, run:

    ./docker.sh stop
    rm -rf ${view_dir} ${out_dir}
    ./docker.sh start

If you DO have uncommitted edits there — be careful: ${view_dir} is gitignored,
so nothing is version-controlled by anything. Steps:

    1. Copy your edits somewhere safe OUTSIDE ${view_dir} (e.g. ~/${theme}-edits/).
    2. Run the three commands above.
    3. After ./docker.sh start, ${view_dir} is a real ${theme} working tree.
       Replay your edits there, then commit and push to ${repo_url}.

Aborting so no work is destroyed.
EOF
            )"
        fi
        log "${theme}: working tree already present, skipping clone"
    else
        log "Cloning ${theme} from ${repo_url}..."
        git clone --branch main "$repo_url" "$view_dir" \
            || handle_error "Failed to clone ${theme} from ${repo_url}"
    fi

    if [ -e "$out_dir" ] && [ ! -L "$out_dir" ]; then
        handle_error "$(cat <<EOF

${out_dir} exists and is a regular directory.
Expected: a symlink pointing to ../Application/views/${theme}/out/${theme}
(so Apache serves theme assets directly out of the working tree).

This is leftover content from the previous wget/unzip bootstrap. Safe to remove:
the canonical assets live under ${view_dir}/out/${theme} now.

Run:

    ./docker.sh stop
    rm -rf ${view_dir} ${out_dir}
    ./docker.sh start

(Removing ${view_dir} too keeps both paths in sync — the next start re-clones
the working tree and re-creates the symlink in one shot.)

Aborting.
EOF
        )"
    fi
    if [ ! -e "$out_dir" ]; then
        mkdir -p source/out || handle_error "Failed to create source/out"
        ln -s "../Application/views/${theme}/out/${theme}" "$out_dir" \
            || handle_error "Failed to create symlink ${out_dir} -> ../Application/views/${theme}/out/${theme}"
    fi

    log "${GREEN}${theme} theme ready${NC}"
}

install_theme() {
    install_theme_from_git wave https://github.com/o3-shop/wave-theme.git
}

install_o3_theme() {
    install_theme_from_git o3-theme https://github.com/o3-shop/o3-Theme.git
}

# Function to install dependencies
install_dependencies() {
    log "${YELLOW}Installing Composer dependencies...${NC}"
    COMPOSER_ROOT_VERSION=dev-b-1.6 composer install --no-interaction --optimize-autoloader || handle_error "Composer installation failed"
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
    local repo_url="https://github.com/o3-shop/shop-demodata-ce.git"
    local satellite_dir="shop-demodata-ce"
    local symlink_path="vendor/o3-shop/shop-demodata-ce"

    log "${YELLOW}Installing shop-demodata-ce...${NC}"

    # The satellite working tree lives at the project top level, not under
    # vendor/, so PhpStorm's Composer integration doesn't auto-exclude it
    # and developers see modified-file indicators inside it without per-IDE
    # config. We then symlink vendor/o3-shop/shop-demodata-ce -> ../../<sat>
    # so composer's autoloader and every code path that hardcodes the vendor
    # location (bin/o3-setup, Setup/Utilities::getActiveEditionDemodataPackagePath)
    # keeps working unchanged.

    if [ -e "$symlink_path" ] && [ ! -L "$symlink_path" ]; then
        handle_error "$(cat <<EOF

Detected an old non-symlink directory at ${symlink_path}.
This is the previous layout (real working tree inside vendor/). The entrypoint
now keeps the working tree at the project top level (./${satellite_dir}/) and
symlinks ${symlink_path} -> ../../${satellite_dir} so PhpStorm sees the
satellite outside vendor/.

If you have NO uncommitted edits in ${symlink_path}, run:

    ./docker.sh stop
    rm -rf ${symlink_path}
    ./docker.sh start

If you DO have uncommitted edits there — be careful: ${symlink_path} is
gitignored, so nothing is version-controlled by anything. Steps:

    1. Copy your edits somewhere safe OUTSIDE ${symlink_path}.
    2. Run the three commands above.
    3. After ./docker.sh start, ./${satellite_dir}/ is a real shop-demodata-ce
       working tree. Replay your edits there, commit, push to ${repo_url}.

Aborting so no work is destroyed.
EOF
        )"
    fi

    if [ -d "$satellite_dir" ] && [ "$(ls -A "$satellite_dir")" ]; then
        if [ ! -d "$satellite_dir/.git" ]; then
            handle_error "$(cat <<EOF

Detected detached snapshot at ./${satellite_dir}/ (no .git/ subdirectory).
The entrypoint expects a git working tree there so demodata tweaks can be
committed and pushed back to ${repo_url} directly.

Run:

    ./docker.sh stop
    rm -rf ${satellite_dir}
    ./docker.sh start

(Removing the symlink at ${symlink_path} too if it points to a stale target.)

Aborting so no work is destroyed.
EOF
            )"
        fi
        log "shop-demodata-ce: satellite working tree already present, skipping clone"
    else
        log "Cloning shop-demodata-ce from ${repo_url}..."
        git clone --branch main "$repo_url" "$satellite_dir" \
            || handle_error "Failed to clone shop-demodata-ce from ${repo_url}"
    fi

    if [ ! -e "$symlink_path" ]; then
        mkdir -p "$(dirname "$symlink_path")" \
            || handle_error "Failed to create $(dirname "$symlink_path")"
        ln -s "../../${satellite_dir}" "$symlink_path" \
            || handle_error "Failed to symlink ${symlink_path} -> ../../${satellite_dir}"
    fi

    log "${GREEN}shop-demodata-ce ready${NC}"
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



# Main execution
main() {
    echo "setup is running" > /tmp/o3setup-running

    log "${GREEN}Starting shop setup...${NC}"

    setup_environment || exit 127
    install_dependencies || exit 127
    install_demodata || exit 127
    setup_db || exit 127
    install_theme || exit 127
    install_o3_theme || exit 127
    start_apache || exit 127
}

# Run the script
main

# This line should never be reached as apache2-foreground should keep the container running
exit 1