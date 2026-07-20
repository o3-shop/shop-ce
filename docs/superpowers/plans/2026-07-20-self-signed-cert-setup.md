# Self-Signed Certificate Support Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let the Setup mod_rewrite SSL self-probe succeed against a self-signed certificate when the operator opts in (`blAllowSelfSignedCertificates`, default off), and ship a dev docker container that serves HTTPS with a self-signed cert so the path is exercisable locally.

**Architecture:** Replace `@fsockopen()` in the mod_rewrite probe with `stream_socket_client()` so a stream context can relax TLS peer verification, gated behind a new default-off config flag read via `getConfigParam`. The dev docker image enables `mod_ssl`, generates a self-signed cert, and serves HTTPS on 443 (published as 8443), mirroring the existing HTTP vhost setup.

**Tech Stack:** PHP 8.2, OXID/O3 core, PHPUnit 9 (OxidTestCase), Docker (php:8.2-apache), Apache mod_ssl, Doctrine/Dotenv.

---

## File Structure

- `source/Core/SystemRequirements.php` — probe rewrite + new `_buildModRewriteStreamContext()` helper (the only behavioural code change).
- `tests/Unit/Core/SystemRequirementsTest.php` — unit tests for the context builder.
- `source/config.inc.php.dist` — map new config var from env.
- `.env.example` — dev defaults (flag on, HTTPS port, SSLShopURL guidance).
- `docker/Dockerfile` — enable mod_ssl, generate cert, configure + enable SSL vhost.
- `docker/entrypoint.sh` — enable mod_ssl at runtime (parity with `a2enmod rewrite`).
- `docker/docker-compose.yml` — publish port 8443.
- `CLAUDE.md` — document the HTTPS endpoint.

**Deviation from spec (noted):** The spec proposed a new `docker/apache/o3shop-ssl.conf`. The existing HTTP vhost is configured by sed-editing `000-default.conf` inside the Dockerfile (no `COPY`). To stay DRY and consistent — and to avoid build-context/`COPY` ambiguity (compose declares no `context:`) — this plan sed-edits the stock `default-ssl.conf` the same way instead of adding a new file.

**Naming note:** The new helper is named `_buildModRewriteStreamContext` (leading underscore + `phpcs:ignore`), matching every other protected method in this class (`_getModRewriteResponse`, `_checkModRewrite`, …). This is required so the testing-library `UNIT<Name>` magic (which maps `UNITfoo` → `_foo`, e.g. `UNITgetBytes` → `_getBytes`) can reach it from the unit test.

---

## Task 1: Permissive SSL stream context for the mod_rewrite probe

**Files:**
- Modify: `source/Core/SystemRequirements.php:615-637` (`_getModRewriteResponse`)
- Modify: `source/Core/SystemRequirements.php` (add `_buildModRewriteStreamContext`)
- Test: `tests/Unit/Core/SystemRequirementsTest.php`

- [ ] **Step 1: Write the failing tests**

Add these three methods to `tests/Unit/Core/SystemRequirementsTest.php` (inside the `SystemRequirementsTest` class, e.g. after `testGetBytes`):

```php
    public function testBuildModRewriteStreamContextKeepsVerificationForSslWhenFlagOff()
    {
        \OxidEsales\Eshop\Core\Registry::getConfig()->setConfigParam('blAllowSelfSignedCertificates', false);
        $systemRequirements = new SystemRequirements();

        $context = $systemRequirements->UNITbuildModRewriteStreamContext(['ssl' => true]);
        $options = stream_context_get_options($context);

        $this->assertArrayNotHasKey('ssl', $options);
    }

    public function testBuildModRewriteStreamContextRelaxesVerificationForSslWhenFlagOn()
    {
        \OxidEsales\Eshop\Core\Registry::getConfig()->setConfigParam('blAllowSelfSignedCertificates', true);
        $systemRequirements = new SystemRequirements();

        $context = $systemRequirements->UNITbuildModRewriteStreamContext(['ssl' => true]);
        $options = stream_context_get_options($context);

        $this->assertFalse($options['ssl']['verify_peer']);
        $this->assertFalse($options['ssl']['verify_peer_name']);
        $this->assertTrue($options['ssl']['allow_self_signed']);
    }

    public function testBuildModRewriteStreamContextKeepsVerificationForNonSslWhenFlagOn()
    {
        \OxidEsales\Eshop\Core\Registry::getConfig()->setConfigParam('blAllowSelfSignedCertificates', true);
        $systemRequirements = new SystemRequirements();

        $context = $systemRequirements->UNITbuildModRewriteStreamContext(['ssl' => false]);
        $options = stream_context_get_options($context);

        $this->assertArrayNotHasKey('ssl', $options);
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `./docker.sh test --fast tests/Unit/Core/SystemRequirementsTest.php`
Expected: FAIL — `_buildModRewriteStreamContext` does not exist yet (error/undefined method via the `UNIT` proxy).

- [ ] **Step 3: Add the context-builder helper**

In `source/Core/SystemRequirements.php`, add this method immediately **before** `_getModRewriteResponse()` (before line 615):

```php
    /**
     * Builds the stream context for the mod_rewrite self-probe.
     *
     * When the shop is reached over TLS and the operator has explicitly opted in via the
     * blAllowSelfSignedCertificates config flag (development only, default off), peer
     * verification is relaxed so the loopback probe can complete against a self-signed
     * certificate. In every other case certificate verification stays on.
     *
     * @param array $aHostInfo host info (host, port, dir, ssl)
     *
     * @return resource stream context
     * @deprecated underscore prefix violates PSR12, will be renamed to "buildModRewriteStreamContext" in next major
     */
    protected function _buildModRewriteStreamContext($aHostInfo) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aOptions = [];
        $blAllowSelfSigned = (bool) $this->getConfig()->getConfigParam('blAllowSelfSignedCertificates');
        if (!empty($aHostInfo['ssl']) && $blAllowSelfSigned) {
            $aOptions['ssl'] = [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ];
        }

        return stream_context_create($aOptions);
    }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `./docker.sh test --fast tests/Unit/Core/SystemRequirementsTest.php`
Expected: PASS (all three new tests green, existing tests still green).

- [ ] **Step 5: Rewrite the probe to use the context via stream_socket_client**

In `source/Core/SystemRequirements.php`, replace the body of `_getModRewriteResponse()` (currently lines 615-637). Change **only** the socket-opening lines; leave the HTTP request/response loop identical.

Replace:

```php
    protected function _getModRewriteResponse($aHostInfo) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sHostname = ($aHostInfo['ssl'] ? 'ssl://' : '') . $aHostInfo['host'];
        if (!($rFp = @fsockopen($sHostname, $aHostInfo['port'], $iErrNo, $sErrStr, 10))) {
            return false;
        }
```

with:

```php
    protected function _getModRewriteResponse($aHostInfo) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sScheme = $aHostInfo['ssl'] ? 'ssl' : 'tcp';
        $sRemote = $sScheme . '://' . $aHostInfo['host'] . ':' . $aHostInfo['port'];
        $rContext = $this->_buildModRewriteStreamContext($aHostInfo);
        if (!($rFp = @stream_socket_client($sRemote, $iErrNo, $sErrStr, 10, STREAM_CLIENT_CONNECT, $rContext))) {
            return false;
        }
```

The rest of the method (the `$sReq` build, `fwrite`, `while (!feof($rFp))` loop, `fclose`, `return $sOut;`) stays exactly as-is.

- [ ] **Step 6: Run cs-fixer and the test file**

Run: `./docker.sh cs-fixer`
Then: `./docker.sh test --fast tests/Unit/Core/SystemRequirementsTest.php`
Expected: cs-fixer clean; tests PASS.

- [ ] **Step 7: Commit**

```bash
git add source/Core/SystemRequirements.php tests/Unit/Core/SystemRequirementsTest.php
git commit -m "fix(setup): accept self-signed certs in mod_rewrite probe via opt-in flag

Replace fsockopen with stream_socket_client so a stream context can relax TLS
peer verification for the local mod_rewrite self-probe, gated behind the new
default-off blAllowSelfSignedCertificates config flag.

Refs o3-shop/shop-ce#27"
```

---

## Task 2: Wire the config variable into the templates

**Files:**
- Modify: `source/config.inc.php.dist:49` (after `sAdminSSLURL`)
- Modify: `.env.example`

- [ ] **Step 1: Add the config var to `config.inc.php.dist`**

In `source/config.inc.php.dist`, add this line immediately after the `$this->sAdminSSLURL = ...;` line (line 49):

```php
// Development only: accept self-signed SSL certificates for the setup mod_rewrite self-probe. Default off; never enable in production.
$this->blAllowSelfSignedCertificates = filter_var($_ENV['O3SHOP_CONF_ALLOWSELFSIGNEDCERTIFICATES'] ?? false, FILTER_VALIDATE_BOOLEAN);
```

(`?? false` keeps installs without the env var working and evaluates to `false`; `getConfigParam` returning null on real installs also stays falsy — production remains strict.)

- [ ] **Step 2: Add the env var + HTTPS docs to `.env.example`**

In `.env.example`, under `## URL and Paths` (after the `O3SHOP_CONF_COMPILEDIR` line), add:

```bash

## HTTPS (dev): the container serves https://localhost:8443 with a self-signed cert.
O3SHOP_PORT_HTTPS=8443
# To exercise the SSL mod_rewrite probe locally, point the SSL shop URL at the HTTPS endpoint:
# O3SHOP_CONF_SSLSHOPURL="https://localhost:8443"
```

Then, under `## MISC` (after the `O3SHOP_CONF_SKIPVIEWUSAGE=0` line), add:

```bash
O3SHOP_CONF_ALLOWSELFSIGNEDCERTIFICATES=1 # dev only: accept self-signed SSL cert for the setup mod_rewrite probe. Never enable in production.
```

- [ ] **Step 3: Commit**

```bash
git add source/config.inc.php.dist .env.example
git commit -m "feat(setup): expose blAllowSelfSignedCertificates config + dev env defaults

Refs o3-shop/shop-ce#27"
```

---

## Task 3: Serve HTTPS with a self-signed cert from the dev container

**Files:**
- Modify: `docker/Dockerfile:79-83` (after the `a2enmod rewrite` / DocumentRoot block)
- Modify: `docker/entrypoint.sh` (`start_apache`, after `a2enmod rewrite`)
- Modify: `docker/docker-compose.yml:33-34` (shop `ports`)

- [ ] **Step 1: Enable mod_ssl, generate cert, configure SSL vhost in the Dockerfile**

In `docker/Dockerfile`, immediately after the existing block that ends at line 83 (the `sed ... /<VirtualHost \*:80>/a ...` command that configures `000-default.conf`), add:

```dockerfile

# Enable Apache mod_ssl and serve HTTPS on 443 (published as 8443) with a
# self-signed certificate, so the setup mod_rewrite self-probe can exercise the
# SSL code path locally. Pair with O3SHOP_CONF_ALLOWSELFSIGNEDCERTIFICATES=1.
# Mirror the DocumentRoot/Directory tweaks applied to the HTTP vhost above.
RUN a2enmod ssl \
    && mkdir -p /etc/apache2/ssl \
    && openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
        -keyout /etc/apache2/ssl/o3shop.key \
        -out /etc/apache2/ssl/o3shop.crt \
        -subj "/CN=localhost" \
        -addext "subjectAltName=DNS:localhost" \
    && sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/source|g' /etc/apache2/sites-available/default-ssl.conf \
    && sed -i 's|/etc/ssl/certs/ssl-cert-snakeoil.pem|/etc/apache2/ssl/o3shop.crt|' /etc/apache2/sites-available/default-ssl.conf \
    && sed -i 's|/etc/ssl/private/ssl-cert-snakeoil.key|/etc/apache2/ssl/o3shop.key|' /etc/apache2/sites-available/default-ssl.conf \
    && sed -i '/<VirtualHost _default_:443>/a <Directory /var/www/html/source>\n    Options Indexes FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>' /etc/apache2/sites-available/default-ssl.conf \
    && a2ensite default-ssl
```

- [ ] **Step 2: Enable mod_ssl at runtime in the entrypoint**

In `docker/entrypoint.sh`, in the `start_apache()` function, after the line:

```bash
    a2enmod rewrite || handle_error "Failed to enable Apache rewrite module"
```

add:

```bash
    a2enmod ssl || handle_error "Failed to enable Apache ssl module"
```

- [ ] **Step 3: Publish port 8443 in compose**

In `docker/docker-compose.yml`, in the `shop` service `ports:` block (currently just the `8080:80` mapping at lines 33-34), change it to:

```yaml
    ports:
      - "${O3SHOP_PORT_HTTP:-8080}:80"
      - "${O3SHOP_PORT_HTTPS:-8443}:443"
```

- [ ] **Step 4: Rebuild the container and verify HTTPS + the probe**

Run:

```bash
./docker.sh rebuild
```

Then verify (from the host) that HTTPS is served with the self-signed cert and mod_rewrite works over TLS:

```bash
curl -kIsS https://localhost:8443/ | head -n 1
```

Expected: an HTTP status line (e.g. `HTTP/1.1 200 OK` or a shop redirect), served over TLS. `curl` **without** `-k` should fail with a self-signed-cert error (proves it is genuinely self-signed).

- [ ] **Step 5: Commit**

```bash
git add docker/Dockerfile docker/entrypoint.sh docker/docker-compose.yml
git commit -m "build(docker): serve HTTPS on 8443 with a self-signed cert in dev container

Refs o3-shop/shop-ce#27"
```

---

## Task 4: Document the HTTPS endpoint and run the full quality gate

**Files:**
- Modify: `CLAUDE.md` (Quick Start section)

- [ ] **Step 1: Document the HTTPS endpoint in CLAUDE.md**

In `CLAUDE.md`, in the Quick Start block, update the shop URL line so it reads (add the HTTPS endpoint):

```
Shop: http://localhost:8080 (or https://localhost:8443, self-signed) | Admin: http://localhost:8080/admin/ (admin@example.com / admin123)
```

- [ ] **Step 2: Run the full finish gate**

Run: `./docker.sh test-all-coverage`
Expected: cs-fixer clean, full unit suite green (including the three new tests), coverage report generated in `coverage/`.

- [ ] **Step 3: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: note dev HTTPS endpoint (https://localhost:8443)

Refs o3-shop/shop-ce#27"
```

---

## Self-Review

**Spec coverage:**
- Config var `blAllowSelfSignedCertificates` (default off) → Task 1 (read) + Task 2 (template/env). ✓
- Permissive SSL probe via `stream_socket_client` + context → Task 1. ✓
- Dev docker HTTPS + self-signed cert on 8443 → Task 3. ✓
- Flag shipped on in dev, `SSLShopURL` left http with documented switch → Task 2. ✓
- Unit tests for all three flag/ssl combinations → Task 1. ✓
- CLAUDE.md endpoint note → Task 4. ✓
- Full `/finish` gate → Task 4. ✓

**Placeholder scan:** No TBD/TODO/"handle edge cases"; every code step shows exact code. ✓

**Type consistency:** Helper `_buildModRewriteStreamContext` used consistently in Task 1 (definition, probe call, and tests via `UNITbuildModRewriteStreamContext`). Config key `blAllowSelfSignedCertificates` / env `O3SHOP_CONF_ALLOWSELFSIGNEDCERTIFICATES` spelled identically across Tasks 1-2. ✓

**Deviation logged:** separate `o3shop-ssl.conf` → sed-edit `default-ssl.conf` (DRY with existing HTTP vhost; avoids build-context concern). ✓
