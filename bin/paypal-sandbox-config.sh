#!/bin/bash
# Applies PayPal Sandbox credentials from ./.paypal-sandbox.env into the
# running shop's module configuration, and turns on sandbox mode.
#
# Usage:  ./bin/paypal-sandbox-config.sh
#
# The credentials file is gitignored and never committed. This script talks to
# the running o3shop-shop-ce-shop-1 container and writes oxconfig rows via the
# shop's own console (config is cached, so we clear it afterwards).

set -euo pipefail

MY_DIR=$(cd -P "$(dirname "$0")/.." && pwd)
ENV_FILE="$MY_DIR/.paypal-sandbox.env"
CONTAINER="${SHOP_CONTAINER:-o3shop-shop-ce-shop-1}"

if [ ! -f "$ENV_FILE" ]; then
  echo "ERROR: $ENV_FILE not found. Copy .paypal-sandbox.env.example and fill it in." >&2
  exit 1
fi

# shellcheck disable=SC1090
set -a; source "$ENV_FILE"; set +a

: "${OSC_PAYPAL_SANDBOX_CLIENT_ID:?Set OSC_PAYPAL_SANDBOX_CLIENT_ID in .paypal-sandbox.env}"
: "${OSC_PAYPAL_SANDBOX_CLIENT_SECRET:?Set OSC_PAYPAL_SANDBOX_CLIENT_SECRET in .paypal-sandbox.env}"

for v in OSC_PAYPAL_SANDBOX_CLIENT_ID OSC_PAYPAL_SANDBOX_CLIENT_SECRET \
         OSC_PAYPAL_SANDBOX_MERCHANT_ID OSC_PAYPAL_SANDBOX_WEBHOOK_ID; do
  if [ -z "${!v}" ]; then
    echo "WARNING: $v is empty — skipping."
  fi
done

echo "Applying sandbox credentials to module osc_paypal in $CONTAINER ..."

docker exec \
  -e "OSC_PAYPAL_SANDBOX_CLIENT_ID=${OSC_PAYPAL_SANDBOX_CLIENT_ID:-}" \
  -e "OSC_PAYPAL_SANDBOX_CLIENT_SECRET=${OSC_PAYPAL_SANDBOX_CLIENT_SECRET:-}" \
  -e "OSC_PAYPAL_SANDBOX_MERCHANT_ID=${OSC_PAYPAL_SANDBOX_MERCHANT_ID:-}" \
  -e "OSC_PAYPAL_SANDBOX_WEBHOOK_ID=${OSC_PAYPAL_SANDBOX_WEBHOOK_ID:-}" \
  "$CONTAINER" php -r '
require "/var/www/html/vendor/autoload.php";
require "/var/www/html/source/bootstrap.php";
$settings = \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory::getInstance()
    ->getContainer()->get(\OxidSolutionCatalysts\PayPal\Service\ModuleSettings::class);
$settings->save("oscPayPalSandboxMode", true);
$settings->save("oscPayPalSandboxClientId", getenv("OSC_PAYPAL_SANDBOX_CLIENT_ID") ?: "");
$settings->save("oscPayPalSandboxClientSecret", getenv("OSC_PAYPAL_SANDBOX_CLIENT_SECRET") ?: "");
$settings->save("oscPayPalSandboxClientMerchantId", getenv("OSC_PAYPAL_SANDBOX_MERCHANT_ID") ?: "");
$settings->save("oscPayPalSandboxWebhookId", getenv("OSC_PAYPAL_SANDBOX_WEBHOOK_ID") ?: "");
echo "isSandbox: ".var_export($settings->isSandbox(), true)."\n";
echo "clientId: ".(string)$settings->getClientId()."\n";
echo "merchant: ".(string)$settings->getMerchantId()."\n";
echo "webhook: ".(string)$settings->getWebhookId()."\n";
' 2>&1 | grep -v "Deprecated:"

echo "Clearing shop cache ..."
docker exec "$CONTAINER" bin/oe-console oe:cache:clear 2>&1 | grep -v "Deprecated:" || true

echo
echo "Verify in admin: Extensions > Modules > PayPal Checkout"
echo "  http://localhost:8080/admin/"
