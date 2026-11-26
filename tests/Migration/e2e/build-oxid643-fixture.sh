#!/usr/bin/env bash
#
# Produce the canonical OXID 6.4.3 CE + demodata database dump used as the
# starting state for the migration E2E matrix (o3-shop/o3-shop#206).
#
# Run this ONCE / on demand on a machine with Docker. It is NOT part of the
# per-cell matrix — the matrix consumes the committed dump under fixtures/.
#
# What it does (the reproducible sequence proven during the #206 spike):
#   1. Throwaway MariaDB 10.11 + PHP 7.4 CLI (Composer 2.2, required extensions).
#   2. `composer create-project` the OXID compilation, pinned to
#      oxid-esales/oxideshop-metapackage-ce:v6.4.3 (=> oxideshop-ce v6.10.3).
#   3. Import OXID's base schema (source/Setup/Sql/database_schema.sql) and the
#      demodata SQL shipped in oxid-esales/oxideshop-demodata-ce.
#   4. Generate the oxv_* views.
#   5. mysqldump (DB-agnostic — no CREATE DATABASE) -> fixtures/*.sql.gz + .sha256.
#
# The resulting DB deliberately has NO migration-tracking table (`oxmigrations*`)
# — that is the genuine pre-migration state the E2E then migrates forward.
#
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT="$HERE/fixtures/oxid-6.4.3-ce-demodata.sql.gz"
SHA="$HERE/fixtures/oxid-6.4.3-ce-demodata.sha256"
NET=oxid643fix
DBC=oxid643-db
PHPC=oxid643-php
METAPACKAGE_VERSION="${METAPACKAGE_VERSION:-v6.4.3}"

mkdir -p "$HERE/fixtures"

cleanup() {
    docker rm -f "$DBC" "$PHPC" >/dev/null 2>&1 || true
    docker network rm "$NET" >/dev/null 2>&1 || true
}
trap cleanup EXIT
cleanup
docker network create "$NET" >/dev/null

echo "==> Starting MariaDB + PHP 7.4 containers"
docker run -d --name "$DBC" --network "$NET" \
    -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=oxid \
    mariadb:10.11 --default-authentication-plugin=mysql_native_password >/dev/null
docker run -d --name "$PHPC" --network "$NET" -w /app php:7.4-cli sleep infinity >/dev/null

echo "==> Waiting for MariaDB"
until docker exec "$DBC" mysqladmin ping -uroot -proot --silent 2>/dev/null; do sleep 2; done

echo "==> Provisioning PHP 7.4 (extensions + Composer 2.2)"
docker exec "$PHPC" bash -lc '
    set -e
    apt-get update -qq
    apt-get install -y -qq git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libxml2-dev default-mysql-client >/dev/null
    docker-php-ext-configure gd --with-freetype --with-jpeg >/dev/null
    docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip mbstring soap bcmath >/dev/null
    curl -fsSL https://getcomposer.org/download/2.2.21/composer.phar -o /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
'

echo "==> Installing OXID compilation (metapackage $METAPACKAGE_VERSION)"
docker exec "$PHPC" bash -lc "
    set -e
    cd /app && rm -rf shop
    composer create-project --stability dev --no-install --no-interaction --no-scripts \
        oxid-esales/oxideshop-project shop
    cd /app/shop
    composer require --no-update --no-interaction \
        oxid-esales/oxideshop-metapackage-ce:$METAPACKAGE_VERSION
    COMPOSER_MEMORY_LIMIT=-1 composer update --no-interaction --no-dev --no-scripts
"

echo "==> Configuring shop + loading base schema and demodata"
docker exec "$PHPC" bash -lc '
    set -e
    cd /app/shop
    cp source/config.inc.php.dist source/config.inc.php
    sed -i "s|<dbHost>|oxid643-db|; s|<dbName>|oxid|; s|<dbUser>|root|; s|<dbPwd>|root|; \
            s|<sShopURL>|http://localhost/|; s|<sShopDir>|/app/shop/source/|; \
            s|<sCompileDir>|/app/shop/source/tmp/|" source/config.inc.php
    mkdir -p source/tmp && chmod -R 777 source/tmp
    mysql -h oxid643-db -uroot -proot -e "DROP DATABASE IF EXISTS oxid; CREATE DATABASE oxid CHARACTER SET utf8;"
    mysql -h oxid643-db -uroot -proot oxid < source/Setup/Sql/database_schema.sql
    mysql -h oxid643-db -uroot -proot oxid < vendor/oxid-esales/oxideshop-demodata-ce/src/demodata.sql
    php vendor/bin/oe-eshop-db_views_generate
'

echo "==> Sanity check (expect demodata rows, no oxmigrations table)"
docker exec "$DBC" mysql -uroot -proot oxid -e "
    SELECT (SELECT COUNT(*) FROM oxarticles) AS articles,
           (SELECT COUNT(*) FROM oxcategories) AS categories;
    SHOW TABLES LIKE 'oxmigrations%';"

echo "==> Dumping (DB-agnostic) -> $OUT"
docker exec "$DBC" sh -c 'mysqldump -uroot -proot --no-tablespaces --single-transaction \
    --routines --triggers --add-drop-table oxid' | gzip > "$OUT"

# sha256 (portable: sha256sum or shasum)
if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$OUT" | awk '{print $1}' > "$SHA"
else
    shasum -a 256 "$OUT" | awk '{print $1}' > "$SHA"
fi

echo "==> Done: $OUT ($(du -h "$OUT" | cut -f1)), sha256 $(cat "$SHA")"
