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
    cd $MY_DIR/docker || { echo "Error: Docker directory not found"; exit 1; }
    check_docker_compose
    echo "Pulling latest Docker images..."
    $DOCKER_COMPOSE pull
    echo "Starting Docker containers..."
    $DOCKER_COMPOSE up -d
    if [ $? -eq 0 ]; then
        echo "Docker containers started successfully"
        $DOCKER_COMPOSE ps
        echo "
+----------------+------------------------------+
| Credentials    |                              |
+----------------+------------------------------+
| Shop URL       | http://localhost:8080        |
| Admin URL      | http://localhost:8080/admin/ |
| Admin Login    | admin@example.com            |
| Admin Password | admin123                     |
+----------------+------------------------------+
| Mailpit URL    | http://localhost:8025        |
+----------------+------------------------------+
| Adminer URL    | http://localhost:8081        |
| DB Root User   | root                         |
| DB Root PW     | supersecret                  |
+----------------+------------------------------+
"
      return 0
    else
        echo "Error: Failed to start Docker containers"
        exit 1
    fi
}

stop_containers() {
    MY_DIR=$(getMyPath)
    cd $MY_DIR/docker || { echo "Error: Docker directory not found"; exit 1; }
    check_docker_compose
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
      rm -f $MY_DIR/runned.txt
      rm -f $MY_DIR/source/tmp/*.txt
      rm -f $MY_DIR/source/tmp/*.php
      rm -f $MY_DIR/source/tmp/smarty/*.php
      cd $MY_DIR/docker || { echo "Error: Docker directory not found"; exit 1; }
      check_docker_compose
      echo "Pulling latest Docker images..."
      $DOCKER_COMPOSE pull
      $DOCKER_COMPOSE build --no-cache
      echo "Starting Docker containers..."
      $DOCKER_COMPOSE up -d
      if [ $? -eq 0 ]; then
          echo "Docker containers started successfully"
          $DOCKER_COMPOSE ps
          echo "
| Credentials    |
| -------------- | ---------------------------- |
| Shop URL       | http://localhost:8080        |
| Admin URL      | http://localhost:8080/admin/ |
| Admin Login    | admin@example.com            |
| Admin Password | admin123                     |
| -------------- | ---------------------------- |
| Adminer URL    | http://localhost:8081        |
| DB Root User   | root                         |
| DB Root PW     | supersecret                  |
          "
          return 0;
      else
          echo "Error: Failed to start Docker containers"
          exit 1
      fi
}

run_tests() {
  GREEN='\033[0;32m'
  RED='\033[0;31m'
  NC='\033[0m' # No Color

  MY_DIR=$(getMyPath)
  containers=(o3shop-app o3shop-db o3shop-mailpit)
  target_container="o3shop-app"

  for c in "${containers[@]}"; do
      if ! docker ps --format '{{.Names}}' | grep -q "^${c}$"; then
          echo -e "${RED} ✗ ${c} is NOT running – aborting. ${NC}"
          exit 1
      fi
  done

  echo -e "${GREEN}✓ All containers are running – executing tests${NC}"
  docker exec -i "$target_container" ./run-tests.sh "$@"
}

run_php_cs_fixer() {
  GREEN='\033[0;32m'
  RED='\033[0;31m'
  NC='\033[0m'

    containers=(o3shop-app)
    target_container="o3shop-app"

    for c in "${containers[@]}"; do
        if ! docker ps --format '{{.Names}}' | grep -q "^${c}$"; then
            echo -e "${RED} ✗ ${c} is NOT running – aborting. ${NC}"
            exit 1
        fi
    done

  # You may need to adjust path/to/php-cs-fixer and working directory if necessary
  if docker exec -i "$target_container" php-cs-fixer --version &> /dev/null; then
      echo -e "${GREEN}✓ Running php-cs-fixer...${NC}"
      docker exec -i "$target_container" php-cs-fixer fix || true
  else
      echo -e "${RED}php-cs-fixer not found in $target_container. Please install it!${NC}"
      exit 1
  fi
}

run_quarantine_tests() {
  GREEN='\033[0;32m'
  RED='\033[0;31m'
  NC='\033[0m'

  MY_DIR=$(getMyPath)
  containers=(o3shop-app o3shop-db o3shop-mailpit)
  target_container="o3shop-app"

  for c in "${containers[@]}"; do
      if ! docker ps --format '{{.Names}}' | grep -q "^${c}$"; then
          echo -e "${RED} ✗ ${c} is NOT running – aborting. ${NC}"
          exit 1
      fi
  done

  echo -e "${GREEN}✓ Running quarantine tests (slow / special tests)${NC}"
  docker exec -i "$target_container" ./run-tests.sh --quarantine
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
