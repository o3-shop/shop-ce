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


# Determine which Docker Compose command to use
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

# Function to start Docker containers
start_containers() {
    # Change to the docker directory
    MY_DIR=$(getMyPath)
    cd $MY_DIR/docker || { echo "Error: Docker directory not found"; exit 1; }

    check_docker_compose

    # Pull latest images before starting containers
    echo "Pulling latest Docker images..."
    $DOCKER_COMPOSE pull

    # Start the containers in detached mode
    echo "Starting Docker containers..."
    $DOCKER_COMPOSE up -d

    # Check if containers started successfully
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

# Function to stop Docker containers
stop_containers() {
    # Change to the docker directory
    MY_DIR=$(getMyPath)
    cd $MY_DIR/docker || { echo "Error: Docker directory not found"; exit 1; }

    check_docker_compose

    # Stop the containers
    echo "Stopping Docker containers..."
    $DOCKER_COMPOSE down

    # Check if containers stopped successfully
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


      # Pull latest images before starting containers
      echo "Pulling latest Docker images..."
      $DOCKER_COMPOSE pull

      $DOCKER_COMPOSE build --no-cache

      # Start the containers in detached mode
      echo "Starting Docker containers..."
      $DOCKER_COMPOSE up -d

      # Check if containers started successfully
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
  # Define colors for output
  GREEN='\033[0;32m'
  RED='\033[0;31m'
  NC='\033[0m' # No Color

  # Check if containers are running
  MY_DIR=$(getMyPath)

  containers=(o3shop-app o3shop-db o3shop-mailpit)
  target_container="o3shop-app"

  # ---------- check loop ----------
  for c in "${containers[@]}"; do
      if ! docker ps --format '{{.Names}}' | grep -q "^${c}$"; then
          echo -e "${RED} ✗ ${c} is NOT running – aborting. ${NC}"
          exit 1
      fi
  done

  echo -e "${GREEN}✓ All containers are running – executing tests${NC}"

  # Execute the test script inside the container using here document
  docker exec -i "$target_container" ./run-tests.sh
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

    # Extract and write only those variables
    for var in "${DOCKER_VARS[@]}"; do
        grep "^$var=" "$MY_DIR/.env.example" >> "$MY_DIR/docker/.env"
    done
fi

# Main script execution
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
    *)
        echo "Usage: $0 {start|stop}"
        echo "  start - Start Docker containers"
        echo "  stop  - Stop Docker containers"
        echo "  rebuild  - Rebuild Docker containers"
        echo "  test  - Run the tests"
        exit
        ;;
esac

exit 0