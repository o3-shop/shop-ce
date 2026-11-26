# Fix setup with self-signed certificates

**Issue:** o3-shop/shop-ce#27 (see also #189) — "Fix setup with self signed certificates"
**Branch:** `27-self-signed-cert-setup`
**Date:** 2026-07-20

## Problem

During Setup, `OxidEsales\EshopCommunity\Core\SystemRequirements::checkModRewrite()`
probes the shop over its own URL to detect whether `mod_rewrite` is active. The
probe (`_getModRewriteResponse()`, `source/Core/SystemRequirements.php:615`) opens
a socket back to the shop with `@fsockopen()`. When the shop is served over HTTPS
with a **self-signed** certificate, PHP's default TLS peer verification rejects the
certificate, the `ssl://` socket never opens, the probe returns `false`, and the
mod_rewrite check degrades incorrectly — blocking or falsely warning during setup.
This makes installation over `https://` on a dev box with a self-signed cert fail,
while the same install over `http://` succeeds.

`#195` previously fixed a *different* failure in the same check (reverse-proxy /
DDEV TLS-terminator false-negative). That work is unaffected by this change.

## Goals

1. Let the mod_rewrite SSL self-probe succeed against a self-signed certificate,
   **only** when the operator opts in — production stays strict by default.
2. Ship a standard O3 developer docker container that actually serves HTTPS with a
   self-signed certificate, so the SSL code path can be exercised locally.

## Non-goals

- No change to how the shop verifies certificates for any purpose other than the
  local mod_rewrite loopback probe.
- No change to `#195`'s reverse-proxy detection or the three-way response
  classification.
- Not forcing the dev container to redirect all traffic to HTTPS by default (keeps
  existing http-based acceptance/e2e green).

## Design

### 1. New config variable

- **`blAllowSelfSignedCertificates`** — boolean, **default `false`**.
- Read in code via `getConfig()->getConfigParam('blAllowSelfSignedCertificates')`.
  An unset/missing value is falsy, so any production install that never sets it
  keeps strict verification — the safe default.
- Added to `source/config.inc.php.dist`, mapped from
  `$_ENV['O3SHOP_CONF_ALLOWSELFSIGNEDCERTIFICATES']` with a falsy fallback so a
  missing env var does not error and evaluates to `false`.
- Added to `.env.example`; the dev container ships it **on** (`=1`).

### 2. Code fix — `source/Core/SystemRequirements.php`

Replace `@fsockopen()` in `_getModRewriteResponse()` with `stream_socket_client()`,
which accepts a stream context:

- Remote address becomes `ssl://host:port` (or `tcp://host:port` for plain HTTP) —
  the port moves from a separate argument into the address string.
- Extract a small, **unit-testable** protected helper
  `buildModRewriteStreamContext(array $aHostInfo)` returning the stream context
  resource:
  - When `blAllowSelfSignedCertificates` is **on** *and* `$aHostInfo['ssl']` is
    true, set `ssl => ['verify_peer' => false, 'verify_peer_name' => false,
    'allow_self_signed' => true]`.
  - Otherwise return a default context (verification stays on).
- Keep the 10s timeout, the `@` error suppression, and every downstream
  response-parsing branch untouched. `_checkModRewrite()`'s three-way
  classification is unaffected.

Rationale for `stream_socket_client` over cURL: minimal change to a working
method, no new dependency, and the ssl stream-context option is exactly the knob
the issue asks for. cURL would be a larger rewrite of request handling for no
added benefit here.

### 3. Docker dev container — HTTPS with a self-signed certificate

- **`docker/Dockerfile`:**
  - `a2enmod ssl`.
  - Generate a self-signed cert at build time:
    `openssl req -x509 -nodes -days 3650 -newkey rsa:2048`
    into `/etc/apache2/ssl/o3shop.crt` / `o3shop.key`, CN=`localhost`, with
    `subjectAltName=DNS:localhost`.
  - Enable the SSL vhost (`a2ensite`).
- **`docker/apache/o3shop-ssl.conf`** (new): `SSLEngine on`, the cert/key paths,
  `DocumentRoot /var/www/html/source`, and `AllowOverride All` so `.htaccess` /
  mod_rewrite is honoured over TLS — the exact thing the probe verifies.
- **`docker/entrypoint.sh`:** add `a2enmod ssl` next to the existing
  `a2enmod rewrite` so it is guaranteed enabled at runtime as well.
- **`docker/docker-compose.yml`:** publish `"${O3SHOP_PORT_HTTPS:-8443}:443"` on
  the web service (alongside the existing `8080:80`).
- **`.env.example`:**
  - Add `O3SHOP_CONF_ALLOWSELFSIGNEDCERTIFICATES=1`.
  - Document `O3SHOP_PORT_HTTPS=8443`.
  - Leave `O3SHOP_CONF_SSLSHOPURL` defaulting to the http URL to keep existing
    acceptance/e2e tests green, with a commented one-liner showing how to flip it
    to `https://localhost:8443` to exercise the SSL probe. Because the flag ships
    on, that flip "just works" with the self-signed cert.
- **`CLAUDE.md`** Quick Start: note the new `https://localhost:8443` endpoint.

### 4. Tests (TDD)

`tests/Unit/Core/SystemRequirementsTest.php`, driving
`buildModRewriteStreamContext()` directly (no real socket needed), asserting via
`stream_context_get_options()`:

- flag **off** + ssl host → no permissive ssl options present (verification on).
- flag **on** + ssl host → `verify_peer=false`, `verify_peer_name=false`,
  `allow_self_signed=true`.
- flag **on** + non-ssl host → no ssl options added.

Verify with `./docker.sh test --fast tests/Unit/Core/SystemRequirementsTest.php`,
then the full `/finish` gate (`./docker.sh test-all-coverage`).

## Files touched

- `source/Core/SystemRequirements.php` — probe rewrite + context helper.
- `source/config.inc.php.dist` — new config var mapping.
- `.env.example` — new env var + HTTPS port docs.
- `docker/Dockerfile` — ssl module, cert generation, vhost enable.
- `docker/apache/o3shop-ssl.conf` — new SSL vhost.
- `docker/entrypoint.sh` — runtime `a2enmod ssl`.
- `docker/docker-compose.yml` — publish 8443.
- `CLAUDE.md` — document HTTPS endpoint.
- `tests/Unit/Core/SystemRequirementsTest.php` — context-builder tests.

## Risks / notes

- Self-signed certs still trigger browser warnings on `https://localhost:8443` —
  expected for a dev container; the flag only relaxes the *server-side loopback
  probe*, not the browser.
- The flag must never default to on in any production template. Only `.env.example`
  (dev container) sets it; `getConfigParam` returning null keeps prod strict.
