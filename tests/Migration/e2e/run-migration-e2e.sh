#!/usr/bin/env bash
#
# End-to-end migration verification driver (o3-shop/o3-shop#206, Phase 3 of #152).
#
# Migrates a real OXID 6.4.3 CE demodata database (the committed fixture) up to a
# development o3-shop, then gates on `oe:migrate:verify` and app-level asserts.
# Self-contained: spins up its own throwaway DB + PHP containers, so it runs the
# same way locally and in CI (GitHub Actions ubuntu-latest has Docker).
#
# Usage:
#   run-migration-e2e.sh              # run every phase in order (local)
#   run-migration-e2e.sh <phase>      # run a single phase (CI runs one step per phase)
#
# Phases (in order): start-db · load-fixture · provision · install · configure ·
#                    migrate · verify · asserts · cleanup
#
# Parameters (env vars):
#   DB_IMAGE          DB service image            (default: mariadb:10.11)
#   PHP_VERSION       PHP version for o3-shop      (default: 8.2)
#   TARGET_VERSION    COMPOSER_ROOT_VERSION        (default: dev-b-1.7)
#   MIGRATION_E2E_RUN stable id for container names so separate phase invocations
#                     (CI steps) share the same containers (default: o3mig-$$)
#   KEEP=1            leave containers running after an all-phases run (debugging)
#
# TARGET_VERSION note: the intent (#206) is to migrate to the *development* line
# so regressions surface. `dev-b-1.7` is the b-1.7 dev line and resolves via the
# `branch-alias` in composer.json ("dev-b-1.7": "1.7-dev"), which makes the
# shop-ce root satisfy o3-shop/shop-composer-plugin's `^1.2.0`. `1.7.x-dev` is an
# equivalent numeric form. `dev-main` does NOT resolve — shop-ce has no `main`
# branch (its development line is b-1.7), so there is deliberately no alias for it.
#
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$HERE/../../.." && pwd)"

: "${DB_IMAGE:=mariadb:10.11}"
: "${PHP_VERSION:=8.2}"
: "${TARGET_VERSION:=dev-b-1.7}"
: "${MIGRATION_E2E_RUN:=o3mig-$$}"

FIXTURE="$HERE/fixtures/oxid-6.4.3-ce-demodata.sql.gz"
DBNAME=o3migrate
RUN_ID="$MIGRATION_E2E_RUN"
NET="${RUN_ID}-net"; DBC="${RUN_ID}-db"; PHPC="${RUN_ID}-php"; SIDECAR="${RUN_ID}-decode"

dbq() { docker exec "$DBC" mysql -uroot -proot -N -e "$1"; }        # scalar query helper

# ---- phases ---------------------------------------------------------------

phase_start_db() {
    [ -f "$FIXTURE" ] || { echo "Fixture not found: $FIXTURE (run build-oxid643-fixture.sh)"; exit 1; }
    docker rm -f "$DBC" >/dev/null 2>&1 || true
    docker network create "$NET" >/dev/null 2>&1 || true

    echo "Starting database: $DB_IMAGE"
    local db_extra=""
    case "$DB_IMAGE" in mariadb*) db_extra="--default-authentication-plugin=mysql_native_password" ;; esac
    docker run -d --name "$DBC" --network "$NET" \
        -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE="$DBNAME" \
        "$DB_IMAGE" $db_extra >/dev/null

    # `mysqladmin ping` answers before the root password is applied — poll a real auth'd query.
    echo "Waiting for database to become auth-ready..."
    for _ in $(seq 1 60); do
        docker exec "$DBC" mysql -uroot -proot -e "SELECT 1" >/dev/null 2>&1 && { echo "Database ready."; return 0; }
        sleep 2
    done
    echo "Database never became auth-ready"; exit 1
}

phase_load_fixture() {
    echo "Fixture: $(basename "$FIXTURE") ($(du -h "$FIXTURE" | cut -f1) gzipped)"
    case "$DB_IMAGE" in
        mysql:*)
            # MySQL 8.0 removed ENCODE()/DECODE(), so the OXID-encrypted oxconfig /
            # oxuserpayments columns can't be decoded on the target. Decode them on a
            # throwaway MariaDB sidecar (which still has DECODE()), then load the
            # decoded result into MySQL 8. The o3-shop decode migrations then
            # skipIf(MySQL80) harmlessly — the data is already plaintext.
            echo "MySQL 8 target -> decoding OXID columns via MariaDB sidecar ($SIDECAR)"
            docker rm -f "$SIDECAR" >/dev/null 2>&1 || true
            docker run -d --name "$SIDECAR" --network "$NET" \
                -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE="$DBNAME" \
                mariadb:10.11 --default-authentication-plugin=mysql_native_password >/dev/null
            for _ in $(seq 1 60); do
                docker exec "$SIDECAR" mysql -uroot -proot -e "SELECT 1" >/dev/null 2>&1 && break
                sleep 2
            done
            echo "  - loading fixture into sidecar"
            gunzip -c "$FIXTURE" | docker exec -i "$SIDECAR" mysql -uroot -proot "$DBNAME"
            echo "  - decoding oxconfig.OXVARVALUE / oxuserpayments.OXVALUE"
            docker exec -i "$SIDECAR" mysql -uroot -proot "$DBNAME" < "$HERE/lib/decode-mysql8.sql"
            echo "  - transferring decoded data to MySQL 8 (--force tolerates MariaDB view defs; views are regenerated later)"
            docker exec "$SIDECAR" mysqldump -uroot -proot --no-tablespaces --single-transaction "$DBNAME" \
                | docker exec -i "$DBC" mysql -uroot -proot --force "$DBNAME"
            docker rm -f "$SIDECAR" >/dev/null 2>&1 || true
            ;;
        *)
            echo "Importing fixture into $DBNAME ..."
            gunzip -c "$FIXTURE" | docker exec -i "$DBC" mysql -uroot -proot "$DBNAME"
            ;;
    esac
    # Make the import unmistakably visible in the log.
    echo "Imported OXID 6.4.3 database:"
    echo "  articles      = $(dbq "SELECT COUNT(*) FROM $DBNAME.oxarticles")"
    echo "  categories    = $(dbq "SELECT COUNT(*) FROM $DBNAME.oxcategories")"
    echo "  tables+views  = $(dbq "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DBNAME'")"
    echo "  oxmigrations  = $(dbq "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DBNAME' AND table_name LIKE 'oxmigrations%'") (expected 0 — pre-migration state)"
}

phase_provision() {
    echo "Starting PHP $PHP_VERSION container + provisioning (extensions, Composer 2.2)"
    docker rm -f "$PHPC" >/dev/null 2>&1 || true
    docker run -d --name "$PHPC" --network "$NET" -w /app "php:${PHP_VERSION}-cli" sleep infinity >/dev/null
    docker exec "$PHPC" bash -lc '
        set -e
        export DEBIAN_FRONTEND=noninteractive
        apt-get update -qq
        apt-get install -y -qq git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
            libonig-dev libxml2-dev libicu-dev libxslt1-dev default-mysql-client >/dev/null
        docker-php-ext-configure gd --with-freetype --with-jpeg >/dev/null
        docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip mbstring soap bcmath intl exif calendar >/dev/null 2>&1
        curl -fsSL https://getcomposer.org/download/2.2.21/composer.phar -o /usr/local/bin/composer
        chmod +x /usr/local/bin/composer
    '
    # git archive keeps the working tree clean (no vendor/ written into the repo).
    echo "Copying o3-shop tree into container (git archive HEAD)"
    git -C "$REPO_ROOT" archive HEAD | docker exec -i "$PHPC" tar x -C /app
}

phase_install() {
    echo "composer update (COMPOSER_ROOT_VERSION=$TARGET_VERSION)"
    # --no-scripts: skip the post-update auto-migrate hook so we migrate explicitly below.
    docker exec -e COMPOSER_ROOT_VERSION="$TARGET_VERSION" -e COMPOSER_MEMORY_LIMIT=-1 "$PHPC" \
        composer update --no-interaction --no-scripts -d /app
}

phase_configure() {
    echo "Configuring o3-shop (.env + config.inc.php) -> $DBC/$DBNAME"
    docker exec "$PHPC" bash -lc "
        set -e; cd /app
        cp .env.example .env
        sed -i \
          -e 's|^O3SHOP_CONF_DBHOST=.*|O3SHOP_CONF_DBHOST=\"$DBC\"|' \
          -e 's|^O3SHOP_CONF_DBNAME=.*|O3SHOP_CONF_DBNAME=\"$DBNAME\"|' \
          -e 's|^O3SHOP_CONF_DBUSER=.*|O3SHOP_CONF_DBUSER=\"root\"|' \
          -e 's|^O3SHOP_CONF_DBPWD=.*|O3SHOP_CONF_DBPWD=\"root\"|' \
          -e 's|^O3SHOP_CONF_SHOPDIR=.*|O3SHOP_CONF_SHOPDIR=\"/app/source\"|' \
          -e 's|^O3SHOP_CONF_COMPILEDIR=.*|O3SHOP_CONF_COMPILEDIR=\"/app/source/tmp\"|' \
          -e 's|^O3SHOP_CONF_SHOPURL=.*|O3SHOP_CONF_SHOPURL=\"http://localhost/\"|' \
          .env
        cp source/config.inc.php.dist source/config.inc.php
        mkdir -p source/tmp source/log && chmod -R 777 source/tmp source/log
    "
}

phase_migrate() {
    # NOTE (known-pitfalls): oe-eshop-db_migrate's 2nd arg is the EDITION filter, not a flag —
    # pass ONLY 'migrations:migrate'. Views regeneration is a separate, mandatory step.
    echo "Running migrations:migrate"
    docker exec "$PHPC" bash -lc 'cd /app && php vendor/bin/oe-eshop-db_migrate migrations:migrate'
    echo "Regenerating database views"
    docker exec "$PHPC" bash -lc 'cd /app && php vendor/bin/oe-eshop-db_views_generate'
    echo "Applied migrations recorded: $(dbq "SELECT COUNT(*) FROM $DBNAME.oxmigrations_ce")"
}

phase_verify() {
    echo "oe:migrate:verify (gate — non-zero fails the run)"
    docker exec "$PHPC" bash -lc 'cd /app && php bin/oe-console oe:migrate:verify'
}

phase_asserts() {
    if [ -x "$HERE/asserts/run-asserts.sh" ]; then
        "$HERE/asserts/run-asserts.sh" "$PHPC" "$DBC" "$NET"
    else
        echo "(app asserts script not present — skipping)"
    fi
}

phase_cleanup() {
    if [ "${KEEP:-0}" = "1" ]; then echo "KEEP=1 -> leaving containers up"; return 0; fi
    echo "Removing containers + network"
    docker rm -f "$DBC" "$PHPC" "$SIDECAR" >/dev/null 2>&1 || true
    docker network rm "$NET" >/dev/null 2>&1 || true
}

# ---- dispatch -------------------------------------------------------------

run_phase() {
    case "$1" in
        start-db)     phase_start_db ;;
        load-fixture) phase_load_fixture ;;
        provision)    phase_provision ;;
        install)      phase_install ;;
        configure)    phase_configure ;;
        migrate)      phase_migrate ;;
        verify)       phase_verify ;;
        asserts)      phase_asserts ;;
        cleanup)      phase_cleanup ;;
        *) echo "Unknown phase: $1"; echo "Phases: start-db load-fixture provision install configure migrate verify asserts cleanup"; exit 2 ;;
    esac
}

if [ "$#" -ge 1 ] && [ "$1" != "all" ]; then
    # Single-phase mode (CI runs one step per phase; no trap — cleanup is its own step).
    run_phase "$1"
else
    # All-phases mode (local): run in order with cleanup on exit.
    trap phase_cleanup EXIT
    for p in start-db load-fixture provision install configure migrate verify asserts; do
        printf '\n==== %s ====\n' "$p"
        run_phase "$p"
    done
    printf '\n==== E2E OK  DB=%s  PHP=%s  TARGET=%s ====\n' "$DB_IMAGE" "$PHP_VERSION" "$TARGET_VERSION"
fi
