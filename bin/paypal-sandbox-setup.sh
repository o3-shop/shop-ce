#!/bin/bash
# Configures the local PayPal module path repositories for development.
#
# The two path repositories are deliberately NOT committed: /local-modules/ is
# gitignored and absent on fresh clones and CI, and Composer 2.2 fails on a
# literal path repository whose directory does not exist. Run this script once
# in a checkout that has local-modules/paypal-checkout and
# local-modules/paypal-client; it writes the repositories to the local,
# uncommitted composer.json.
#
# After that:
#   composer run oe:install:module:paypal
# installs the module into the shop.

set -euo pipefail

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

if [ -z "$MODULE_VERSION" ]; then
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

if [ -z "$MODULE_VERSION" ]; then
  MODULE_VERSION=$(git -C local-modules/paypal-checkout describe --tags --abbrev=0 2>/dev/null) || MODULE_VERSION=""
  MODULE_VERSION="${MODULE_VERSION#v}"
fi

if [ -z "$CLIENT_VERSION" ]; then
  CLIENT_VERSION=$(git -C local-modules/paypal-client describe --tags --abbrev=0 2>/dev/null) || CLIENT_VERSION=""
  CLIENT_VERSION="${CLIENT_VERSION#v}"
fi

if [ -z "$MODULE_VERSION" ] || [ -z "$CLIENT_VERSION" ]; then
  echo "ERROR: could not determine the version to pin for the PayPal path repositories." >&2
  echo "Set PAYPAL_MODULE_VERSION and PAYPAL_CLIENT_VERSION and re-run." >&2
  exit 1
fi

composer config repositories.paypal-checkout \
  "{\"type\":\"path\",\"url\":\"local-modules/paypal-checkout\",\"options\":{\"symlink\":true,\"versions\":{\"o3-shop/paypal-checkout\":\"$MODULE_VERSION\"}}}"
composer config repositories.paypal-client \
  "{\"type\":\"path\",\"url\":\"local-modules/paypal-client\",\"options\":{\"symlink\":true,\"versions\":{\"o3-shop/paypal-client\":\"$CLIENT_VERSION\"}}}"

echo "Configured local PayPal path repositories in composer.json (uncommitted)."
echo "Next: composer run oe:install:module:paypal"
