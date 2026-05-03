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

  # Clear the application cache before the suite — stale Smarty / module /
  # container caches have masked real failures in the past (a stale class map
  # let a deleted class still resolve, an old Smarty template hid a syntax
  # fix, etc.). Cheap to run; eliminates a class of false-greens.
  echo -e "${GREEN}✓ Clearing application cache (oe:cache:clear)...${NC}"
  docker exec -i "$target_container" php /var/www/html/bin/oe-console oe:cache:clear || {
      echo -e "${RED} ✗ oe:cache:clear failed – aborting before tests run. ${NC}"
      exit 1
  }

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

run_npm_audits() {
  GREEN='\033[0;32m'
  RED='\033[0;31m'
  YELLOW='\033[1;33m'
  NC='\033[0m'

  target_container="o3shop-app"

  if ! docker ps --format '{{.Names}}' | grep -q "^${target_container}$"; then
      echo -e "${RED} ✗ ${target_container} is NOT running – aborting. ${NC}"
      exit 1
  fi

  # Themes audited as part of the regular test suite. Wave-theme is intentionally
  # NOT included — it's being deprecated and pins known-vulnerable jQuery 2.1.4
  # / Bootstrap 4.1.3 by design (see .claude/memory/project_o3-theme-dep-audit.md).
  # Auditing wave here would block every test run on issues we explicitly chose
  # not to fix.
  local audit_themes=("o3-theme")

  echo "---------------------------"
  echo "Running npm audit:"
  echo "---------------------------"

  for theme in "${audit_themes[@]}"; do
      local theme_path="source/Application/views/${theme}"
      if ! docker exec "${target_container}" test -f "/var/www/html/${theme_path}/package.json"; then
          echo -e "${YELLOW}⚠ Skipping npm audit for ${theme} — no package.json found.${NC}"
          continue
      fi
      echo -e "${GREEN}✓ Auditing ${theme_path}...${NC}"
      if ! docker exec -w "/var/www/html/${theme_path}" "${target_container}" npm audit; then
          echo -e "${RED}"
          echo "================================================================================"
          echo " ✗ npm audit reported vulnerabilities in ${theme}."
          echo "================================================================================"
          echo -e "${NC}"
          echo "What to do:"
          echo ""
          echo "  1. Re-read the report above (advisory titles + affected packages)."
          echo ""
          echo "  2. Apply the auto-fix (preferred — patch/minor bumps only):"
          echo ""
          echo "       docker exec -w /var/www/html/${theme_path} \\"
          echo "                   ${target_container} npm audit fix"
          echo ""
          echo "     If only 'npm audit fix --force' resolves it, review the breaking"
          echo "     changes carefully before accepting (it may bump a major version)."
          echo ""
          echo "  3. Rebuild the theme bundle so the fix lands in the runtime CSS/JS:"
          echo ""
          echo "       docker exec -w /var/www/html/${theme_path} \\"
          echo "                   ${target_container} npx gulp prod"
          echo ""
          echo "  4. Commit the updated package-lock.json (and rebuilt out/...) inside"
          echo "     the ${theme} repo — NOT shop-ce. Themes are separate git repos."
          echo ""
          echo "  5. Re-run './docker.sh test-all' to confirm the gate is now green."
          echo ""
          echo "If a vuln cannot be fixed promptly (e.g. no patch upstream), document the"
          echo "reason in .claude/memory/project_${theme}-dep-audit.md and raise it with"
          echo "the team. Do not silence this gate."
          echo ""
          exit 1
      fi
      echo -e "${GREEN}✓ npm audit clean for ${theme}.${NC}"
  done
}

run_full_test_with_cs_fixer() {
  run_npm_audits
  run_php_cs_fixer
  echo ""
  echo "---------------------------"
  echo "Now running tests:"
  echo "---------------------------"
  run_tests
}

run_full_test_with_coverage() {
  run_npm_audits
  run_php_cs_fixer
  echo ""
  echo "---------------------------"
  echo "Now running tests with coverage:"
  echo "---------------------------"
  run_tests --coverage
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
        shift
        run_tests "$@" || exit 127
        ;;
    test-all)
        run_full_test_with_cs_fixer || exit 127
        ;;
    test-all-coverage)
        run_full_test_with_coverage || exit 127
        ;;
    quarantine)
        run_quarantine_tests || exit 127
        ;;
    cs-fixer)
        run_php_cs_fixer || exit 127
        ;;
    playwright)
        shift
        cd "$MY_DIR/tests/Acceptance/playwright" || exit 127
        if [ ! -d node_modules ]; then
            echo "Installing Playwright dependencies (one-time)..."
            npm install || exit 127
            npx playwright install chromium || exit 127
        fi
        npx playwright test "$@" || exit 127
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
        echo "  test-all-coverage  Run php-cs-fixer, then full test suite with coverage report"
        echo "  cs-fixer     Run php-cs-fixer on the entire codebase"
        echo "  quarantine   Run slow/special @group quarantine tests only"
        echo "  playwright   Run the Playwright browser test suite (auto-installs deps on first run)"
        echo ""
        echo "Options for 'test':"
        echo "  --fast           Skip shop install, call phpunit directly"
        echo "  --coverage       Generate coverage reports (clover, html, junit)"
        echo "  --all-failures   Don't stop at the first failure — run the full"
        echo "                   suite and collect every failure in one pass."
        echo "                   Use when one fix dominoes into many test"
        echo "                   updates (seed-data changes, fixture renames)."
        echo ""
        echo "Examples:"
        echo "  $0 start"
        echo "  $0 test --fast tests/Unit/Core/ConfigTest.php"
        echo "  $0 test --all-failures"
        echo "  $0 test-all"
        echo "  $0 quarantine"
        exit
        ;;
esac

exit 0
