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
# Scope the PID file per user and checkout: two shop-ce checkouts (or two users)
# must not reuse or stop each other's tunnel.
PROJECT_HASH=$(printf '%s' "$MY_DIR" | cksum | awk '{print $1}')
PID_FILE="${TMPDIR:-/tmp}/paypal-sandbox-tunnel-$(id -u)-${PROJECT_HASH}.pid"

current_url() {
  curl -s --max-time 5 "$API" 2>/dev/null \
    | python3 -c "import sys,json; d=json.load(sys.stdin); t=d.get('tunnels') or []; print(t[0]['public_url'] if t else '')" 2>/dev/null || true
}

# Escape a value for use as a sed replacement (delimiter is |): backslashes,
# ampersands (the whole-match reference) and the delimiter itself.
escape_sed_replacement() {
  printf '%s' "$1" | sed -e 's/[\\&|]/\\&/g'
}

# The PID file can outlive ngrok (crash, or a tunnel started by an older version
# of this script), and after a crash its PID can be reused by an unrelated
# process. Only treat the recorded PID as ours while it is alive and its
# executable is actually ngrok.
pid_is_ngrok() {
  local pid="$1" exe=""
  [ -n "$pid" ] || return 1
  kill -0 "$pid" 2>/dev/null || return 1
  exe=$(readlink "/proc/$pid/exe" 2>/dev/null || true)
  if [ -z "$exe" ]; then
    exe=$(ps -p "$pid" -o comm= 2>/dev/null || true)
  fi
  exe=${exe% (deleted)}
  [ "$(basename -- "$exe")" = "ngrok" ]
}

drop_stale_pid_file() {
  [ -f "$PID_FILE" ] || return 0
  local pid
  pid=$(cat "$PID_FILE" 2>/dev/null || true)
  pid_is_ngrok "$pid" || rm -f "$PID_FILE"
}

case "${1:-}" in
  --url)
    current_url
    exit 0
    ;;
  --stop)
    if [ ! -f "$PID_FILE" ]; then
      echo "No tunnel PID recorded; nothing started by this script to stop."
      exit 0
    fi
    PID=$(cat "$PID_FILE" 2>/dev/null || true)
    if pid_is_ngrok "$PID"; then
      kill "$PID" && echo "ngrok stopped (pid $PID)"
    elif [ -n "$PID" ] && kill -0 "$PID" 2>/dev/null; then
      echo "Recorded pid $PID is not ngrok; leaving it alone." >&2
    else
      echo "Recorded ngrok process is no longer running."
    fi
    rm -f "$PID_FILE"
    exit 0
    ;;
esac

# The tunnel is only useful if the shop URLs can actually be repointed, so fail
# before starting anything when the expected .env keys are missing.
for key in O3SHOP_CONF_SHOPURL O3SHOP_CONF_SSLSHOPURL; do
  if ! grep -q "^$key=" "$ENV_FILE" 2>/dev/null; then
    echo "ERROR: $key not found in $ENV_FILE" >&2
    exit 1
  fi
done

command -v ngrok >/dev/null 2>&1 || { echo "ERROR: ngrok not found on PATH." >&2; exit 1; }

# A leftover PID file from a crashed run must not be mistaken for a running
# tunnel on a later invocation.
drop_stale_pid_file

URL=$(current_url)
if [ -z "$URL" ]; then
  echo "Starting ngrok tunnel to :$PORT ..."
  rm -f "$PID_FILE"
  # Write the PID from inside the session leader before exec'ing ngrok, so the
  # recorded PID is ngrok itself and --stop can target exactly this process.
  PID_FILE="$PID_FILE" PORT="$PORT" LOG="$LOG" setsid bash -c \
    'echo $$ > "$PID_FILE"; exec ngrok http "$PORT" --log="$LOG" --log-format=logfmt' \
    </dev/null >/dev/null 2>&1 &
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
ESCAPED_URL=$(escape_sed_replacement "$URL")
sed -i "s|^O3SHOP_CONF_SHOPURL=.*|O3SHOP_CONF_SHOPURL=\"$ESCAPED_URL\"|" "$ENV_FILE"
sed -i "s|^O3SHOP_CONF_SSLSHOPURL=.*|O3SHOP_CONF_SSLSHOPURL=\"$ESCAPED_URL\"|" "$ENV_FILE"
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
    ESCAPED_WEBHOOK_ID=$(escape_sed_replacement "$WEBHOOK_ID")
    sed -i "s|^OSC_PAYPAL_SANDBOX_WEBHOOK_ID=.*|OSC_PAYPAL_SANDBOX_WEBHOOK_ID=\"$ESCAPED_WEBHOOK_ID\"|" "$SANDBOX_ENV"
    echo "Updated $SANDBOX_ENV"
  fi
else
  echo "WARNING: webhook re-registration failed (tunnel is still up)." >&2
  sed 's/^/  /' /tmp/paypal-webhook.err >&2 || true
fi

echo
echo "Admin:   $URL/admin/"
echo "Shop:    $URL/"
