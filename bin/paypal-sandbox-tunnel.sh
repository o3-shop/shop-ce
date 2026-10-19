#!/bin/bash
# Starts (or reuses) an ngrok HTTPS tunnel to the local shop on :8080 and points
# the shop's O3SHOP_CONF_SHOPURL / O3SHOP_CONF_SSLSHOPURL at the public URL.
#
# Usage:
#   ./bin/paypal-sandbox-tunnel.sh          # start tunnel + repoint shop
#   ./bin/paypal-sandbox-tunnel.sh --url    # print the current public URL only
#   ./bin/paypal-sandbox-tunnel.sh --stop   # stop the tunnel
#
# Requires: ngrok installed + authtoken configured (ngrok config add-authtoken ...)

set -euo pipefail

MY_DIR=$(cd -P "$(dirname "$0")/.." && pwd)
ENV_FILE="$MY_DIR/.env"
CONTAINER="${SHOP_CONTAINER:-o3shop-shop-ce-shop-1}"
PORT="${SHOP_PORT:-8080}"
API="http://127.0.0.1:4040/api/tunnels"
LOG="/tmp/ngrok.log"

current_url() {
  curl -s --max-time 5 "$API" 2>/dev/null \
    | python3 -c "import sys,json; d=json.load(sys.stdin); t=d.get('tunnels') or []; print(t[0]['public_url'] if t else '')" 2>/dev/null || true
}

case "${1:-}" in
  --url)
    current_url
    exit 0
    ;;
  --stop)
    pkill -f "ngrok http" 2>/dev/null && echo "ngrok stopped" || echo "ngrok not running"
    exit 0
    ;;
esac

command -v ngrok >/dev/null 2>&1 || { echo "ERROR: ngrok not found on PATH." >&2; exit 1; }

URL=$(current_url)
if [ -z "$URL" ]; then
  echo "Starting ngrok tunnel to :$PORT ..."
  setsid ngrok http "$PORT" --log="$LOG" --log-format=logfmt </dev/null >/dev/null 2>&1 &
  disown 2>/dev/null || true
  for _ in $(seq 1 15); do
    sleep 1
    URL=$(current_url)
    [ -n "$URL" ] && break
  done
fi

[ -n "$URL" ] || { echo "ERROR: could not obtain ngrok URL. See $LOG" >&2; exit 1; }

echo "Public URL: $URL"

# Repoint the shop at the tunnel (both plain and SSL URL — the PayPal module
# builds its redirect/webhook URLs from getSslShopUrl()).
sed -i "s|^O3SHOP_CONF_SHOPURL=.*|O3SHOP_CONF_SHOPURL=\"$URL\"|" "$ENV_FILE"
sed -i "s|^O3SHOP_CONF_SSLSHOPURL=.*|O3SHOP_CONF_SSLSHOPURL=\"$URL\"|" "$ENV_FILE"
echo "Updated $ENV_FILE"

docker exec "$CONTAINER" bin/oe-console oe:cache:clear 2>&1 | grep -v "Deprecated:" || true

# The PayPal webhook endpoint is built from the shop's SSL URL, so the webhook
# registered for the previous tunnel now points at a dead host. Re-register (or
# reuse) it for the new URL. Best effort: a failure here does not tear down the
# tunnel, it only leaves the webhook stale.
echo "Re-registering PayPal webhook for $URL ..."
if WEBHOOK_ID=$(docker exec "$CONTAINER" php /var/www/html/bin/paypal-sandbox-webhook.php 2>/tmp/paypal-webhook.err); then
  echo "Webhook id: $WEBHOOK_ID"
  SANDBOX_ENV="$MY_DIR/.paypal-sandbox.env"
  if [ -f "$SANDBOX_ENV" ] && grep -q '^OSC_PAYPAL_SANDBOX_WEBHOOK_ID=' "$SANDBOX_ENV"; then
    sed -i "s|^OSC_PAYPAL_SANDBOX_WEBHOOK_ID=.*|OSC_PAYPAL_SANDBOX_WEBHOOK_ID=\"$WEBHOOK_ID\"|" "$SANDBOX_ENV"
    echo "Updated $SANDBOX_ENV"
  fi
else
  echo "WARNING: webhook re-registration failed (tunnel is still up)." >&2
  sed 's/^/  /' /tmp/paypal-webhook.err >&2 || true
fi

echo
echo "Admin:   $URL/admin/"
echo "Shop:    $URL/"
