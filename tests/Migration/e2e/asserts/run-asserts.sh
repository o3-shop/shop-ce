#!/usr/bin/env bash
#
# App-level smoke asserts for the migration E2E (o3-shop/o3-shop#206).
#
# Called by run-migration-e2e.sh after oe:migrate:verify passes, with the PHP
# container name as $1. Proves the migrated shop's front controllers BOOT against
# the migrated database — i.e. no fatal PHP error, a valid HTTP response.
#
# Deliberately a boot-smoke, not a rendering/checkout assertion: the OXID demodata
# sets sTheme=flow (which o3-shop does not ship) and sShopURL doesn't match the
# test port, so the storefront legitimately answers with a SEO/canonical redirect
# (3xx) rather than a rendered 200. A non-5xx response from both the storefront
# and the admin front controller is the reliable, theme-independent signal that
# the migrated shop is alive. Deeper Playwright coverage is a follow-up (see README).
#
set -euo pipefail

PHPC="${1:?PHP container name required}"
PORT=8888

fail() { echo "ASSERT FAIL: $*" >&2; exit 1; }

# Start PHP's built-in server on the shop front controller (idempotent).
if ! docker exec "$PHPC" bash -lc "curl -s -o /dev/null http://localhost:$PORT/ 2>/dev/null"; then
    docker exec -d "$PHPC" bash -lc "cd /app/source && php -S 0.0.0.0:$PORT index.php"
    for _ in $(seq 1 15); do
        docker exec "$PHPC" bash -lc "curl -s -o /dev/null http://localhost:$PORT/ 2>/dev/null" && break
        sleep 1
    done
fi

# $1 = path, returns the HTTP status code (000 on no response)
http_code() {
    docker exec "$PHPC" bash -lc "curl -s -o /dev/null -w '%{http_code}' 'http://localhost:$PORT/$1'"
}

echo "-- storefront front controller --"
sf=$(http_code "index.php?cl=start")
echo "storefront HTTP $sf"
case "$sf" in
    2??|3??) ;;                       # 2xx render or 3xx canonical/SEO redirect = booted
    *) fail "storefront returned '$sf' (expected 2xx/3xx — a 5xx/000 means a fatal boot error)" ;;
esac

echo "-- admin front controller --"
ad=$(http_code "admin/index.php")
echo "admin HTTP $ad"
case "$ad" in
    2??|3??) ;;
    *) fail "admin returned '$ad' (expected 2xx/3xx)" ;;
esac

echo "App smoke passed: storefront=$sf admin=$ad (front controllers boot on the migrated DB)."
