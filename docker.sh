#!/bin/bash

function getMyPath() {
  # Version 1.0.1
  source="${BASH_SOURCE[1]}"
  while [ -h "$source" ]; do
    dir="$(cd -P "$(dirname "$source")" && pwd)"
    source="$(readlink "$source")"
    [[ $source != /* ]] && source="$dir/$source"
  done
  cd -P "$(dirname "$source")" && pwd
}

check_docker_compose() {
    if command -v docker &> /dev/null && docker compose version &> /dev/null; then
        DOCKER_COMPOSE="docker compose"
    elif command -v docker-compose &> /dev/null; then
        DOCKER_COMPOSE="docker-compose"
    else
        echo "Error: Neither 'docker compose' nor 'docker-compose' found"
        exit 1
    fi
    echo "Using command: $DOCKER_COMPOSE"
}

start_containers() {
    MY_DIR=$(getMyPath)
    cd "$MY_DIR/docker" || { echo "Error: Docker directory not found"; exit 1; }
    check_docker_compose

    # Ensure the shared network exists (idempotent)
    docker network create o3shop-shared 2>/dev/null || true

    if $IS_WORKTREE; then
        MAIN_REPO_DIR=$(echo "$MY_DIR" | sed 's|/.claude/worktrees/.*||')
        MAIN_PROJECT="o3shop-$(basename "$MAIN_REPO_DIR")"
        DB_CONTAINER=$(docker ps -q \
            --filter "label=com.docker.compose.service=db" \
            --filter "label=com.docker.compose.project=$MAIN_PROJECT")
        if [ -z "$DB_CONTAINER" ]; then
            echo "ERROR: Shared MariaDB is not running."
            echo "  Start the main repo first: cd $MAIN_REPO_DIR && ./docker.sh start"
            exit 1
        fi
        DBROOT=$(grep "^O3SHOP_CONF_DBROOT=" "$MY_DIR/.env.example" | cut -d= -f2- | tr -d '"')
        echo "Creating database ${O3SHOP_CONF_DBNAME} in shared MariaDB..."
        docker exec "$DB_CONTAINER" mysql -uroot -p"${DBROOT}" -e \
            "CREATE DATABASE IF NOT EXISTS \`${O3SHOP_CONF_DBNAME}\`;
             GRANT ALL ON \`${O3SHOP_CONF_DBNAME}\`.* TO 'o3shop'@'%';" 2>/dev/null
    fi

    COMPOSE_PROFILES=""
    $IS_WORKTREE || COMPOSE_PROFILES="--profile db"

    echo "Pulling latest Docker images..."
    $DOCKER_COMPOSE pull
    echo "Starting Docker containers..."
    $DOCKER_COMPOSE $COMPOSE_PROFILES up -d
    if [ $? -eq 0 ]; then
        echo "Docker containers started successfully"
        $DOCKER_COMPOSE ps
        echo "
+----------------+------------------------------------------+
| Credentials    |                                          |
+----------------+------------------------------------------+
| Shop URL       | http://localhost:${O3SHOP_PORT_HTTP}      |
| Admin URL      | http://localhost:${O3SHOP_PORT_HTTP}/admin/ |
| Admin Login    | admin@example.com                        |
| Admin Password | admin123                                 |
+----------------+------------------------------------------+
| Mailpit URL    | http://localhost:${O3SHOP_PORT_MAILPIT}   |
+----------------+------------------------------------------+
| Adminer URL    | http://localhost:${O3SHOP_PORT_ADMINER}   |
| DB Root User   | root                                     |
| DB Root PW     | supersecret                              |
| Database       | ${O3SHOP_CONF_DBNAME}                    |
+----------------+------------------------------------------+
"
        return 0
    else
        echo "Error: Failed to start Docker containers"
        exit 1
    fi
}

stop_containers() {
    MY_DIR=$(getMyPath)
    cd "$MY_DIR/docker" || { echo "Error: Docker directory not found"; exit 1; }
    check_docker_compose

    # When stopping the main repo, tear down all worktree stacks first
    if ! $IS_WORKTREE; then
        WORKTREE_PROJECTS=$(docker ps \
            --format '{{index .Labels "com.docker.compose.project.working_dir"}}|{{index .Labels "com.docker.compose.project"}}' \
            2>/dev/null \
            | awk -F'|' '$1 ~ /\.claude\/worktrees\// {print $2}' \
            | sort -u)
        for project in $WORKTREE_PROJECTS; do
            echo "Stopping worktree stack: $project"
            $DOCKER_COMPOSE -p "$project" down
        done
    fi

    echo "Stopping Docker containers..."
    $DOCKER_COMPOSE down
    if [ $? -eq 0 ]; then
        echo "Docker containers stopped successfully"
    else
        echo "Error: Failed to stop Docker containers"
        exit 1
    fi
}

rebuild_containers() {
    MY_DIR=$(getMyPath)
    rm -f "$MY_DIR/runned.txt"
    rm -f "$MY_DIR/source/tmp/"*.txt
    rm -f "$MY_DIR/source/tmp/"*.php
    rm -f "$MY_DIR/source/tmp/smarty/"*.php
    cd "$MY_DIR/docker" || { echo "Error: Docker directory not found"; exit 1; }
    check_docker_compose

    docker network create o3shop-shared 2>/dev/null || true

    COMPOSE_PROFILES=""
    $IS_WORKTREE || COMPOSE_PROFILES="--profile db"

    echo "Pulling latest Docker images..."
    $DOCKER_COMPOSE pull
    $DOCKER_COMPOSE build --no-cache
    echo "Starting Docker containers..."
    $DOCKER_COMPOSE $COMPOSE_PROFILES up -d
    if [ $? -eq 0 ]; then
        echo "Docker containers started successfully"
        $DOCKER_COMPOSE ps
        return 0
    else
        echo "Error: Failed to start Docker containers"
        exit 1
    fi
}

run_tests() {
  GREEN='\033[0;32m'
  RED='\033[0;31m'
  NC='\033[0m'

  MY_DIR=$(getMyPath)
  cd "$MY_DIR/docker" || { echo "Error: Docker directory not found"; exit 1; }
  check_docker_compose

  if ! $DOCKER_COMPOSE ps shop 2>/dev/null | grep -q "Up\|running"; then
      echo -e "${RED} ✗ shop container is NOT running – aborting. ${NC}"
      exit 1
  fi

  echo -e "${GREEN}✓ shop container is running – executing tests${NC}"
  $DOCKER_COMPOSE exec shop ./run-tests.sh "$@"
}

run_php_cs_fixer() {
  GREEN='\033[0;32m'
  RED='\033[0;31m'
  NC='\033[0m'

  MY_DIR=$(getMyPath)
  cd "$MY_DIR/docker" || { echo "Error: Docker directory not found"; exit 1; }
  check_docker_compose

  if ! $DOCKER_COMPOSE ps shop 2>/dev/null | grep -q "Up\|running"; then
      echo -e "${RED} ✗ shop container is NOT running – aborting. ${NC}"
      exit 1
  fi

  if $DOCKER_COMPOSE exec shop php-cs-fixer --version &> /dev/null; then
      echo -e "${GREEN}✓ Running php-cs-fixer...${NC}"
      $DOCKER_COMPOSE exec shop php-cs-fixer fix || true
  else
      echo -e "${RED}php-cs-fixer not found in shop container. Please install it!${NC}"
      exit 1
  fi
}

run_quarantine_tests() {
  GREEN='\033[0;32m'
  RED='\033[0;31m'
  NC='\033[0m'

  MY_DIR=$(getMyPath)
  cd "$MY_DIR/docker" || { echo "Error: Docker directory not found"; exit 1; }
  check_docker_compose

  if ! $DOCKER_COMPOSE ps shop 2>/dev/null | grep -q "Up\|running"; then
      echo -e "${RED} ✗ shop container is NOT running – aborting. ${NC}"
      exit 1
  fi

  echo -e "${GREEN}✓ Running quarantine tests (slow / special tests)${NC}"
  $DOCKER_COMPOSE exec shop ./run-tests.sh --quarantine
}

run_full_test_with_cs_fixer() {
  run_php_cs_fixer
  echo ""
  echo "---------------------------"
  echo "Now running tests:"
  echo "---------------------------"
  run_tests
}

MY_DIR=$(getMyPath)

# Detect whether we are running inside a git worktree
IS_WORKTREE=false
[[ "$MY_DIR" == *".claude/worktrees/"* ]] && IS_WORKTREE=true

# Compose project name: unique per checkout directory
COMPOSE_PROJECT_NAME="o3shop-$(basename "$MY_DIR")"

# Port block: deterministic hash of directory name for worktrees
if $IS_WORKTREE; then
    HASH=$(echo -n "$(basename "$MY_DIR")" | cksum | cut -d' ' -f1)
    BLOCK=$(( HASH % 90 ))
    O3SHOP_PORT_HTTP=$(( 9000 + BLOCK * 10 ))
    O3SHOP_PORT_ADMINER=$(( O3SHOP_PORT_HTTP + 1 ))
    O3SHOP_PORT_MAILPIT=$(( O3SHOP_PORT_HTTP + 2 ))
    O3SHOP_PORT_SMTP=$(( O3SHOP_PORT_HTTP + 3 ))
    O3SHOP_CONF_DBNAME="o3shop_${O3SHOP_PORT_HTTP}"
else
    O3SHOP_PORT_HTTP=8080
    O3SHOP_PORT_ADMINER=8081
    O3SHOP_PORT_MAILPIT=8025
    O3SHOP_PORT_SMTP=1025
fi

# Bootstrap .env if missing
if [ ! -f "$MY_DIR/.env" ]; then
    cp "$MY_DIR/.env.example" "$MY_DIR/.env" || { echo "Failed to copy .env.example to .env"; exit 1; }
    echo "Created .env file from example"
fi

# For worktrees: patch project .env with the computed DBNAME and SHOPURL so the
# shop installer uses the right database and generates correct URLs.
if $IS_WORKTREE; then
    grep -v "^O3SHOP_CONF_DBNAME=\|^O3SHOP_CONF_SHOPURL=" "$MY_DIR/.env" > "$MY_DIR/.env.tmp"
    echo "O3SHOP_CONF_DBNAME=\"${O3SHOP_CONF_DBNAME}\"" >> "$MY_DIR/.env.tmp"
    echo "O3SHOP_CONF_SHOPURL=\"http://localhost:${O3SHOP_PORT_HTTP}\"" >> "$MY_DIR/.env.tmp"
    mv "$MY_DIR/.env.tmp" "$MY_DIR/.env"
fi

# Always regenerate docker/.env so port vars and project name are current
{
    grep "^O3SHOP_CONF_DBUSER=" "$MY_DIR/.env.example"
    grep "^O3SHOP_CONF_DBPWD=" "$MY_DIR/.env.example"
    grep "^O3SHOP_CONF_DBROOT=" "$MY_DIR/.env.example"
    if $IS_WORKTREE; then
        echo "O3SHOP_CONF_DBNAME=${O3SHOP_CONF_DBNAME}"
    else
        grep "^O3SHOP_CONF_DBNAME=" "$MY_DIR/.env.example"
    fi
    echo "O3SHOP_PORT_HTTP=${O3SHOP_PORT_HTTP}"
    echo "O3SHOP_PORT_ADMINER=${O3SHOP_PORT_ADMINER}"
    echo "O3SHOP_PORT_MAILPIT=${O3SHOP_PORT_MAILPIT}"
    echo "O3SHOP_PORT_SMTP=${O3SHOP_PORT_SMTP}"
    echo "COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME}"
} > "$MY_DIR/docker/.env"

case "$1" in
    start)
        start_containers || exit 127
        ;;
    stop)
        stop_containers || exit 127
        ;;
    rebuild)
        rebuild_containers || exit 127
        ;;
    test)
        shift
        run_tests "$@" || exit 127
        ;;
    test-all)
        run_full_test_with_cs_fixer || exit 127
        ;;
    quarantine)
        run_quarantine_tests || exit 127
        ;;
    *)
        echo "Usage: $0 <command> [options]"
        echo ""
        echo "Commands:"
        echo "  start        Start Docker containers"
        echo "  stop         Stop Docker containers"
        echo "  rebuild      Rebuild Docker containers from scratch"
        echo ""
        echo "  test         Run unit tests (pass extra args to phpunit)"
        echo "  test-all     Run php-cs-fixer, then full test suite"
        echo "  quarantine   Run slow/special @group quarantine tests only"
        echo ""
        echo "Options for 'test':"
        echo "  --fast       Skip shop install, call phpunit directly"
        echo "  --coverage   Generate coverage reports (clover, html, junit)"
        echo ""
        echo "Examples:"
        echo "  $0 start"
        echo "  $0 test --fast tests/Unit/Core/ConfigTest.php"
        echo "  $0 test-all"
        echo "  $0 quarantine"
        exit
        ;;
esac

exit 0
