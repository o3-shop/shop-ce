#!/bin/bash

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
    cd docker || { echo "Error: Docker directory not found"; exit 1; }

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
| Shop URL       | http://127.0.0.1:8080        |
| Admin URL      | http://127.0.0.1:8080/admin/ |
| Admin Login    | admin@example.com            |
| Admin Password | admin123                     |"
    else
        echo "Error: Failed to start Docker containers"
        exit 1
    fi
}

# Function to stop Docker containers
stop_containers() {
    # Change to the docker directory
    cd docker || { echo "Error: Docker directory not found"; exit 1; }

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
  cd docker || { echo "Error: Docker directory not found"; exit 1; }

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
      else
          echo "Error: Failed to start Docker containers"
          exit 1
      fi
}

# Main script execution
case "$1" in
    start)
        start_containers
        ;;
    stop)
        stop_containers
        ;;
    rebuild)
        rebuild_containers
        ;;
    *)
        echo "Usage: $0 {start|stop}"
        echo "  start - Start Docker containers"
        echo "  stop  - Stop Docker containers"
        echo "  rebuild  - Rebuild Docker containers"
        exit
        ;;
esac

exit 0