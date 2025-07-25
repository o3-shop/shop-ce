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
| Credentials    |
| -------------- | ---------------------------- |
| Shop URL       | http://localhost:8080        |
| Admin URL      | http://localhoat:8080/admin/ |
| Admin Login    | admin@example.com            |
| Admin Password | admin123                     |
| -------------- | ---------------------------- |
| Adminer URL    | http://localhost:8081        |
| DB Root User   | root                         |
| DB Root PW     | supersecret                  |
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
  docker exec -i "$target_container" ./run-tests.sh
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

run_full_test_with_cs_fixer() {
  run_php_cs_fixer
  echo ""
  echo "---------------------------"
  echo "Now running tests:"
  echo "---------------------------"
  run_tests
}

MY_DIR=$(getMyPath)

if [ ! -f "$MY_DIR/.env" ]; then
    cp .env.example .env || handle_error "Failed to copy .env.example to .env"
    echo "Created .env file from example"
else
    echo ".env file already exists"
fi

if [ ! -f "$MY_DIR/docker/.env" ]; then
    DOCKER_VARS=("O3SHOP_CONF_DBUSER" "O3SHOP_CONF_DBPWD" "O3SHOP_CONF_DBROOT" "O3SHOP_CONF_DBNAME")
    for var in "${DOCKER_VARS[@]}"; do
        grep "^$var=" "$MY_DIR/.env.example" >> "$MY_DIR/docker/.env"
    done
fi

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
        run_tests || exit 127
        ;;
    test-all)
        run_full_test_with_cs_fixer || exit 127
        ;;
    *)
        echo "Usage: $0 {start|stop|rebuild|test|testall}"
        echo "  start    - Start Docker containers"
        echo "  stop     - Stop Docker containers"
        echo "  rebuild  - Rebuild Docker containers"
        echo "  test     - Run the tests"
        echo "  test-all  - Run php-cs-fixer and then tests"
        exit
        ;;
esac

exit 0
