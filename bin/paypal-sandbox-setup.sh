#!/bin/bash
# Configures the local PayPal module path repositories for development.
#
# Run from the host: when composer is not on PATH, the script re-executes itself
# inside the shop container as the current user, so composer.json keeps its
# ownership. Running it inside the container also works.
#
# The two path repositories are deliberately NOT committed: /local-modules/ is
# gitignored and absent on fresh clones and CI, and Composer 2.2 fails on a
# literal path repository whose directory does not exist. Run this script once
# in a checkout that has local-modules/paypal-checkout and
# local-modules/paypal-client; it writes the repositories to the local,
# uncommitted composer.json.
#
# After that, inside the container:
#   composer run oe:install:module:paypal
# installs the module into the shop.

set -euo pipefail

if ! command -v composer >/dev/null 2>&1; then
  CONTAINER="${SHOP_CONTAINER:-o3shop-shop-ce-shop-1}"
  exec docker exec -u "$(id -u):$(id -g)" \
    -e COMPOSER_HOME=/tmp/composer \
    -e PAYPAL_MODULE_VERSION="${PAYPAL_MODULE_VERSION:-}" \
    -e PAYPAL_CLIENT_VERSION="${PAYPAL_CLIENT_VERSION:-}" \
    -w /var/www/html "$CONTAINER" \
    bash /var/www/html/bin/paypal-sandbox-setup.sh
fi

MY_DIR=$(cd -P "$(dirname "$0")/.." && pwd)
cd "$MY_DIR"

for dir in local-modules/paypal-checkout local-modules/paypal-client; do
  if [ ! -d "$dir" ]; then
    echo "ERROR: $dir not found; check out the PayPal module/client repositories there first." >&2
    exit 1
  fi
done

# The path repositories need an explicit version. Read the module version from its
# metadata.php (the source of truth), and the client version from the checkout's
# newest tag; both can be overridden with PAYPAL_MODULE_VERSION / PAYPAL_CLIENT_VERSION.
MODULE_VERSION="${PAYPAL_MODULE_VERSION:-}"
CLIENT_VERSION="${PAYPAL_CLIENT_VERSION:-}"

# When running on the host with PHP available, prefer the module metadata on disk.
# If reading it fails or gives nothing, try to read inside the container so the
# environment matches the shop; as a last resort read an exact tag from git.
if [ -z "$MODULE_VERSION" ] && command -v php >/dev/null 2>&1; then
  MODULE_VERSION=$(
    PAYPAL_MODULE_METADATA="$MY_DIR/local-modules/paypal-checkout/metadata.php" php -r '
      $sMetadataVersion = null;
      $aModule = null;
      ob_start();
      include getenv("PAYPAL_MODULE_METADATA");
      ob_end_clean();
      echo is_array($aModule) && isset($aModule["version"]) ? $aModule["version"] : "";
    ' 2>/dev/null
  ) || MODULE_VERSION=""
fi

if [ -z "$MODULE_VERSION" ] && [ -d /var/www/html ] && command -v docker >/dev/null 2>&1; then
  CONTAINER="${SHOP_CONTAINER:-o3shop-shop-ce-shop-1}"
  MODULE_VERSION=$(docker exec -u "$(id -u):$(id -g)" -w /var/www/html "$CONTAINER" php -r '
    $sMetadataVersion = null;
    $aModule = null;
    ob_start();
    include "/var/www/html/local-modules/paypal-checkout/metadata.php";
    ob_end_clean();
    echo is_array($aModule) && isset($aModule["version"]) ? $aModule["version"] : "";
  ' 2>/dev/null) || MODULE_VERSION=""
fi

if [ -z "$MODULE_VERSION" ]; then
  MODULE_VERSION=$(git -C local-modules/paypal-checkout describe --tags --exact-match 2>/dev/null) || MODULE_VERSION=""
  MODULE_VERSION="${MODULE_VERSION#v}"
fi

if [ -z "$CLIENT_VERSION" ]; then
  CLIENT_VERSION=$(git -C local-modules/paypal-client describe --tags --exact-match 2>/dev/null) || CLIENT_VERSION=""
  CLIENT_VERSION="${CLIENT_VERSION#v}"
fi

if [ -z "$MODULE_VERSION" ] || [ -z "$CLIENT_VERSION" ]; then
  echo "ERROR: could not determine the version to pin for the PayPal path repositories." >&2
  echo "The checkout must be at an exact tag, or set PAYPAL_MODULE_VERSION and PAYPAL_CLIENT_VERSION and re-run." >&2
  exit 1
fi

composer config repositories.paypal-checkout \
  "{\"type\":\"path\",\"url\":\"local-modules/paypal-checkout\",\"options\":{\"symlink\":true,\"versions\":{\"o3-shop/paypal-checkout\":\"$MODULE_VERSION\"}}}"
composer config repositories.paypal-client \
  "{\"type\":\"path\",\"url\":\"local-modules/paypal-client\",\"options\":{\"symlink\":true,\"versions\":{\"o3-shop/paypal-client\":\"$CLIENT_VERSION\"}}}"

echo "Configured local PayPal path repositories in composer.json (uncommitted)."
echo "Next (in the container): composer run oe:install:module:paypal"
