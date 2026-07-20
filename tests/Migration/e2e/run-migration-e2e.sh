#!/usr/bin/env bash
#
# End-to-end migration verification driver (o3-shop/o3-shop#206, Phase 3 of #152).
#
# Migrates a real OXID 6.4.3 CE demodata database (the committed fixture) up to a
# development o3-shop, then gates on `oe:migrate:verify` and app-level asserts.
# Self-contained: spins up its own throwaway DB + PHP containers, so it runs the
# same way locally and in CI (GitHub Actions ubuntu-latest has Docker).
#
# Parameters (env vars):
#   DB_IMAGE        DB service image           (default: mariadb:10.11)
#   PHP_VERSION     PHP version for o3-shop     (default: 8.2)
#   TARGET_VERSION  COMPOSER_ROOT_VERSION used to resolve the o3-shop dev graph
#                   (default: dev-b-1.7)
#   KEEP=1          leave containers running afterwards (debugging)
#
# TARGET_VERSION note: the intent (#206) is to migrate to the *development* line
# so regressions surface, hence a dev version (not a stable tag). `dev-b-1.7` is
# the b-1.7 dev line and resolves via the `branch-alias` in composer.json
# ("dev-b-1.7": "1.7-dev"), which makes the shop-ce root satisfy
# o3-shop/shop-composer-plugin's `^1.2.0` constraint. `1.7.x-dev` is an equivalent
# numeric form. `dev-main` does NOT resolve — shop-ce has no `main` branch (its
# development line is b-1.7), so there is deliberately no alias for it.
#
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$HERE/../../.." && pwd)"

: "${DB_IMAGE:=mariadb:10.11}"
: "${PHP_VERSION:=8.2}"
: "${TARGET_VERSION:=dev-b-1.7}"

FIXTURE="$HERE/fixtures/oxid-6.4.3-ce-demodata.sql.gz"
DBNAME=o3migrate
RUN_ID="o3mig-$$"
NET="${RUN_ID}-net"; DBC="${RUN_ID}-db"; PHPC="${RUN_ID}-php"

step() { printf '\n==== %s ====\n' "$*"; }
cleanup() {
    [ "${KEEP:-0}" = "1" ] && { echo "KEEP=1 -> leaving $DBC / $PHPC up"; return; }
    docker rm -f "$DBC" "$PHPC" >/dev/null 2>&1 || true
    docker network rm "$NET" >/dev/null 2>&1 || true
}
trap cleanup EXIT

[ -f "$FIXTURE" ] || { echo "Fixture not found: $FIXTURE (run build-oxid643-fixture.sh)"; exit 1; }
docker network create "$NET" >/dev/null

step "Start database ($DB_IMAGE)"
db_extra=""
case "$DB_IMAGE" in mariadb*) db_extra="--default-authentication-plugin=mysql_native_password" ;; esac
docker run -d --name "$DBC" --network "$NET" \
    -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE="$DBNAME" \
    "$DB_IMAGE" $db_extra >/dev/null

step "Wait for DB auth-ready"
# NOTE: `mysqladmin ping` answers before the root password is applied — poll a real auth'd query.
ready=0
for _ in $(seq 1 60); do
    if docker exec "$DBC" mysql -uroot -proot -e "SELECT 1" >/dev/null 2>&1; then ready=1; break; fi
    sleep 2
done
[ "$ready" = "1" ] || { echo "Database never became auth-ready"; exit 1; }

step "Load OXID 6.4.3 fixture into $DBNAME"
gunzip -c "$FIXTURE" | docker exec -i "$DBC" mysql -uroot -proot "$DBNAME"

step "Start PHP $PHP_VERSION container + provision"
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

step "Copy o3-shop tree (git archive HEAD)"
# git archive keeps the working tree clean (no vendor/ written into the repo).
git -C "$REPO_ROOT" archive HEAD | docker exec -i "$PHPC" tar x -C /app

step "composer update (COMPOSER_ROOT_VERSION=$TARGET_VERSION)"
# --no-scripts: skip the post-update auto-migrate hook so we migrate explicitly below.
docker exec -e COMPOSER_ROOT_VERSION="$TARGET_VERSION" -e COMPOSER_MEMORY_LIMIT=-1 "$PHPC" \
    composer update --no-interaction --no-scripts -d /app

step "Configure o3-shop (.env + config.inc.php) -> $DBC/$DBNAME"
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

case "$DB_IMAGE" in
    mysql:*)
        step "MySQL 8: decode encrypted columns (decode-mysql8)"
        docker exec -i "$DBC" mysql -uroot -proot "$DBNAME" < "$HERE/lib/decode-mysql8.sql"
        ;;
esac

step "migrations:migrate + views generate"
# NOTE (known-pitfalls): oe-eshop-db_migrate's 2nd arg is the EDITION filter, not a flag —
# pass ONLY 'migrations:migrate'. Views regeneration is a separate, mandatory step.
docker exec "$PHPC" bash -lc 'cd /app && php vendor/bin/oe-eshop-db_migrate migrations:migrate'
docker exec "$PHPC" bash -lc 'cd /app && php vendor/bin/oe-eshop-db_views_generate'

step "oe:migrate:verify (gate — non-zero fails the run)"
docker exec "$PHPC" bash -lc 'cd /app && php bin/oe-console oe:migrate:verify'

if [ -x "$HERE/asserts/run-asserts.sh" ]; then
    step "App-level asserts"
    "$HERE/asserts/run-asserts.sh" "$PHPC" "$DBC" "$NET"
else
    echo "(app asserts not present yet — skipping)"
fi

step "E2E OK  DB=$DB_IMAGE  PHP=$PHP_VERSION  TARGET=$TARGET_VERSION"
