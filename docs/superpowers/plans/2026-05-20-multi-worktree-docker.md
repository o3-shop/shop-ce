# Multi-Worktree Docker Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow multiple Claude agent worktrees to each run their own shop instance (shop + mailpit + adminer) against a single shared MariaDB, with no port conflicts and no manual configuration.

**Architecture:** The main repo owns MariaDB and exposes it on a persistent Docker network `o3shop-shared`. Each worktree's `docker.sh` detects it is in `.claude/worktrees/`, computes a deterministic port block from `cksum` of the directory name, writes those ports into `docker/.env`, creates its own database in the shared MariaDB, and starts only shop + mailpit + adminer (no db profile). Stopping the main repo tears down all worktree stacks first.

**Tech Stack:** Docker Compose v2, Bash, MariaDB 10.11

---

## Files Changed

- **Modify:** `docker/docker-compose.yml` — profiles, shared network, remove container_name + depends_on
- **Modify:** `docker.sh` — worktree detection, port hashing, env generation, start/stop/test updates

---

### Task 1: Update docker-compose.yml

**Files:**
- Modify: `docker/docker-compose.yml`

- [ ] **Step 1: Replace docker-compose.yml with the updated version**

Replace the entire content of `docker/docker-compose.yml` with:

```yaml
services:
  db:
    profiles: [db]
    image: mariadb:10.11
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${O3SHOP_CONF_DBROOT}
      MYSQL_DATABASE: ${O3SHOP_CONF_DBNAME}
      MYSQL_USER: ${O3SHOP_CONF_DBUSER}
      MYSQL_PASSWORD: ${O3SHOP_CONF_DBPWD}
    volumes:
      - db_data:/var/lib/mysql
      - ./database/init:/docker-entrypoint-initdb.d
    ports:
      - "3306:3306"
    networks:
      default:
      o3shop-shared:
        aliases:
          - db

  shop:
    build:
      dockerfile: Dockerfile
    volumes:
      - ./../:/var/www/html
    environment:
      PATH: /var/www/html/vendor/o3-shop/testing-library/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
      TZ: Europe/Berlin
    ports:
      - "${O3SHOP_PORT_HTTP:-8080}:80"
    working_dir: /var/www/html
    healthcheck:
      test: [ "CMD", "test", "!", "-f", "/tmp/o3setup-running" ]
      interval: 5s
      timeout: 10s
      retries: 20
      start_period: 5s
    networks:
      - default
      - o3shop-shared

  mailpit:
    image: axllent/mailpit
    restart: unless-stopped
    volumes:
      - ./mailpit/data:/data
    ports:
      - "${O3SHOP_PORT_MAILPIT:-8025}:8025"
      - "${O3SHOP_PORT_SMTP:-1025}:1025"
    environment:
      MP_MAX_MESSAGES: 5000
      MP_DATABASE: /data/mailpit.db
      MP_SMTP_AUTH_ACCEPT_ANY: 1
      MP_SMTP_AUTH_ALLOW_INSECURE: 1
    networks:
      - default
      - o3shop-shared

  adminer:
    image: adminer:latest
    restart: unless-stopped
    ports:
      - "${O3SHOP_PORT_ADMINER:-8081}:8080"
    environment:
      ADMINER_DEFAULT_SERVER: db
      MYSQL_ROOT_PASSWORD: ${O3SHOP_CONF_DBROOT}
      MYSQL_DATABASE: ${O3SHOP_CONF_DBNAME}
      MYSQL_USER: ${O3SHOP_CONF_DBUSER}
      MYSQL_PASSWORD: ${O3SHOP_CONF_DBPWD}
    networks:
      - default
      - o3shop-shared

volumes:
  db_data:

networks:
  default:
  o3shop-shared:
    external: true
    name: o3shop-shared
```

Key changes from original:
- `db` gets `profiles: [db]` and joins `o3shop-shared` with alias `db`
- All `container_name:` lines removed (prevents collision between stacks)
- All `depends_on: db` removed (db not active in worktree profile; shop handles retries via healthcheck)
- Ports on shop/mailpit/adminer use env vars with defaults
- All services join `o3shop-shared` external network
- Top-level `networks:` block declares `o3shop-shared` as external

- [ ] **Step 2: Verify compose file parses**

```bash
cd docker && docker compose config --quiet && echo "OK" && cd ..
```

Expected output: `OK` (no errors).

- [ ] **Step 3: Commit**

```bash
git add docker/docker-compose.yml
git commit -m "feat: add profile + shared network to docker-compose for multi-worktree support"
```

---

### Task 2: Replace docker.sh global bootstrap

**Files:**
- Modify: `docker.sh` lines 183–197

This block runs at script load time (before the `case` statement). It currently creates `.env` and `docker/.env` once. Replace it with worktree-aware detection and always-regenerate logic.

- [ ] **Step 1: Replace lines 183–197 in docker.sh**

Find this block (lines 183–197):

```bash
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
```

Replace with:

```bash
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
```

- [ ] **Step 2: Smoke-test env generation for main repo**

```bash
bash -c 'source ./docker.sh 2>/dev/null; cat docker/.env'
```

Expected: file contains `COMPOSE_PROJECT_NAME=o3shop-shop-ce`, `O3SHOP_PORT_HTTP=8080`, `O3SHOP_CONF_DBNAME=o3shop`.

- [ ] **Step 3: Smoke-test env generation for simulated worktree**

```bash
# Temporarily test the hash logic by hand
HASH=$(echo -n "124-fix-foo" | cksum | cut -d' ' -f1)
BLOCK=$(( HASH % 90 ))
HTTP=$(( 9000 + BLOCK * 10 ))
echo "Worktree 124-fix-foo → HTTP port $HTTP"
```

Expected: a port in range 9000–9890.

- [ ] **Step 4: Commit**

```bash
git add docker.sh
git commit -m "feat: add worktree detection and deterministic port assignment to docker.sh"
```

---

### Task 3: Update start_containers

**Files:**
- Modify: `docker.sh` — `start_containers` function (lines 26–58)

- [ ] **Step 1: Replace start_containers function**

Find:

```bash
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
```

Replace with:

```bash
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
```

- [ ] **Step 2: Commit**

```bash
git add docker.sh
git commit -m "feat: start_containers creates shared network, skips db profile in worktrees, auto-creates per-worktree database"
```

---

### Task 4: Update stop_containers

**Files:**
- Modify: `docker.sh` — `stop_containers` function (lines 60–72)

- [ ] **Step 1: Replace stop_containers function**

Find:

```bash
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
```

Replace with:

```bash
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
            docker compose -p "$project" down
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
```

- [ ] **Step 2: Commit**

```bash
git add docker.sh
git commit -m "feat: stop_containers tears down all worktree stacks when called from main repo"
```

---

### Task 5: Update rebuild_containers

**Files:**
- Modify: `docker.sh` — `rebuild_containers` function (lines 74–107)

- [ ] **Step 1: Replace rebuild_containers function**

Find:

```bash
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
```

Replace with:

```bash
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
```

- [ ] **Step 2: Commit**

```bash
git add docker.sh
git commit -m "fix: rebuild_containers uses profile flag and shared network"
```

---

### Task 6: Fix container name references in test helpers

**Files:**
- Modify: `docker.sh` — `run_tests`, `run_php_cs_fixer`, `run_quarantine_tests` functions

These functions currently check for hardcoded container names (`o3shop-app`, `o3shop-db`, `o3shop-mailpit`) and call `docker exec o3shop-app`. With `container_name` removed from the compose file, containers are auto-named by project. Switch to `docker compose exec shop` which uses labels to find the right container regardless of name.

- [ ] **Step 1: Replace run_tests function**

Find:

```bash
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
```

Replace with:

```bash
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
```

- [ ] **Step 2: Replace run_php_cs_fixer function**

Find:

```bash
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
```

Replace with:

```bash
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
```

- [ ] **Step 3: Replace run_quarantine_tests function**

Find:

```bash
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
```

Replace with:

```bash
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
```

- [ ] **Step 4: Commit**

```bash
git add docker.sh
git commit -m "fix: replace hardcoded container names with docker compose exec in test helpers"
```

---

### Task 7: Smoke test

No automated tests exist for the docker.sh shell logic. Verify manually:

- [ ] **Step 1: Validate docker.sh syntax**

```bash
bash -n docker.sh && echo "Syntax OK"
```

Expected: `Syntax OK`

- [ ] **Step 2: Verify docker/.env is generated correctly for main repo**

```bash
bash docker.sh help 2>&1; cat docker/.env
```

Expected `docker/.env` contains:
```
O3SHOP_CONF_DBUSER=...
O3SHOP_CONF_DBPWD=...
O3SHOP_CONF_DBROOT=...
O3SHOP_CONF_DBNAME=o3shop
O3SHOP_PORT_HTTP=8080
O3SHOP_PORT_ADMINER=8081
O3SHOP_PORT_MAILPIT=8025
O3SHOP_PORT_SMTP=1025
COMPOSE_PROJECT_NAME=o3shop-shop-ce
```

- [ ] **Step 3: Verify docker-compose.yml is valid with and without the db profile**

```bash
cd docker
docker compose --env-file .env config --quiet && echo "No-profile OK"
docker compose --env-file .env --profile db config --quiet && echo "With-db-profile OK"
cd ..
```

Expected: both print `OK` with no errors.

- [ ] **Step 4: Verify db service absent without profile, present with profile**

```bash
cd docker
docker compose --env-file .env config --services
echo "---"
docker compose --env-file .env --profile db config --services
cd ..
```

Expected first block: `shop`, `mailpit`, `adminer` (no `db`).  
Expected second block: `db`, `shop`, `mailpit`, `adminer`.

- [ ] **Step 5: Commit**

```bash
git add docker.sh docker/docker-compose.yml
git commit -m "chore: verify multi-worktree docker setup is functional" --allow-empty
```

Actually only commit if there are uncommitted changes remaining:

```bash
git status --short
```

If clean, skip the commit.
