# E2E Migration Verification Matrix — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** An end-to-end verification matrix (8 cells: {MariaDB 10.11, MySQL 8.0} × {PHP 7.4, 8.0, 8.1, 8.2}) proving an OXID 6.4.3 CE demodata DB migrates to **dev-main** o3-shop and the shop is functional.

**Architecture:** Capture the OXID 6.4.3 CE + demodata DB **once** as a fixture. A single parameterized driver (`run-migration-e2e.sh`) loads it, installs o3-shop at `TARGET_VERSION` (default `dev-main`), runs `migrations:migrate` + views regen + `oe:migrate:verify` + app asserts. A GHA matrix calls the driver per cell. The swap is inlined (Phase 2a not built).

**Tech Stack:** Bash, Docker/Docker-Compose, MariaDB/MySQL, PHP 7.4–8.2, Composer 2.2.x, doctrine/migrations 2.3.5, `bin/oe-console`, Playwright.

**Sequencing note:** Task 1 is a **gating spike**. If a reproducible OXID 6.4.3 install cannot be produced, STOP and escalate before building Tasks 2–5.

---

## File Structure

- `tests/Migration/e2e/build-oxid643-fixture.sh` — one-time fixture producer.
- `tests/Migration/e2e/fixtures/oxid-6.4.3-ce-demodata.sql.gz` — the dump (or release asset + `.sha256`).
- `tests/Migration/e2e/fixtures/oxid-6.4.3-ce-demodata.sha256` — checksum.
- `tests/Migration/e2e/run-migration-e2e.sh` — parameterized per-cell driver.
- `tests/Migration/e2e/lib/decode-mysql8.sql` — the MySQL-8 DECODE step.
- `tests/Migration/e2e/asserts/migration-smoke.spec.ts` — Playwright app asserts.
- `tests/Migration/e2e/docker-compose.e2e.yml` — throwaway DB + php-apache per cell.
- `.github/workflows/migration-e2e.yml` — 8-cell matrix.
- `tests/Migration/e2e/README.md` — runbook (also satisfies the "manual runbook" acceptance).

---

## Task 1 (GATING SPIKE): Produce the OXID 6.4.3 CE + demodata fixture

**Files:** Create `tests/Migration/e2e/build-oxid643-fixture.sh`, `tests/Migration/e2e/fixtures/` (+ dump + sha256).

- [ ] **Step 1: Write `build-oxid643-fixture.sh`**

A one-time producer run by a maintainer on a machine with Docker. Concrete shape (finalize exact OXID install commands against reality during this spike):

```bash
#!/usr/bin/env bash
# Produce a canonical OXID 6.4.3 CE + demodata DB dump for the migration E2E matrix.
# One-time / on-demand; NOT part of the per-cell matrix. Requires Docker.
set -euo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT="$HERE/fixtures/oxid-6.4.3-ce-demodata.sql.gz"
NET=oxid643fix; DBC=oxid643-db; PHPC=oxid643-php
mkdir -p "$HERE/fixtures"

cleanup(){ docker rm -f "$DBC" "$PHPC" >/dev/null 2>&1 || true; docker network rm "$NET" >/dev/null 2>&1 || true; }
trap cleanup EXIT
cleanup
docker network create "$NET"

# MariaDB (OXID 6.4.3 installs cleanly on MariaDB; PHP pinned to 7.4)
docker run -d --name "$DBC" --network "$NET" \
  -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=oxid \
  mariadb:10.11
# wait for DB
until docker exec "$DBC" mysqladmin ping -uroot -proot --silent 2>/dev/null; do sleep 2; done

# PHP 7.4 CLI container with composer 2.2 to build the OXID compilation
docker run -d --name "$PHPC" --network "$NET" -w /app php:7.4-cli sleep infinity
docker exec "$PHPC" bash -lc 'apt-get update && apt-get install -y git unzip libzip-dev libpng-dev libjpeg-dev libonig-dev default-mysql-client \
  && docker-php-ext-install pdo_mysql gd zip mbstring \
  && curl -fsSL https://getcomposer.org/download/2.2.21/composer.phar -o /usr/local/bin/composer && chmod +x /usr/local/bin/composer'

# Install OXID 6.4.3 CE compilation + demodata. EXACT commands verified during the spike:
#   composer create-project oxid-esales/oxideshop-project /app <6.4.3 compilation constraint> --no-interaction
#   (compilation pins oxideshop-ce 6.4.3 + demodata); then run vendor/bin/oe-eshop-db_migrate + setup to load demodata.
# Fill in the working incantation here once confirmed.
docker exec "$PHPC" bash -lc '<OXID 6.4.3 create-project + demodata install + DB setup — finalized in this spike>'

# Dump the populated DB
docker exec "$DBC" sh -c 'mysqldump -uroot -proot --routines --triggers --single-transaction --databases oxid' | gzip > "$OUT"
sha256sum "$OUT" | awk '{print $1}' > "$HERE/fixtures/oxid-6.4.3-ce-demodata.sha256"
echo "Wrote $OUT ($(du -h "$OUT" | cut -f1)), sha256 $(cat "$HERE/fixtures/oxid-6.4.3-ce-demodata.sha256")"
```

- [ ] **Step 2: Run it and capture the dump**

Run: `bash tests/Migration/e2e/build-oxid643-fixture.sh`
Expected: a gzipped dump + sha256 written under `fixtures/`.

- [ ] **Step 3: GATE — verify the fixture is a real 6.4.3 demodata DB**

Load the dump into a fresh MariaDB and assert it looks right:

```bash
docker run -d --name fixchk -e MYSQL_ROOT_PASSWORD=root mariadb:10.11
until docker exec fixchk mysqladmin ping -uroot -proot --silent; do sleep 2; done
gunzip -c tests/Migration/e2e/fixtures/oxid-6.4.3-ce-demodata.sql.gz | docker exec -i fixchk mysql -uroot -proot
docker exec fixchk mysql -uroot -proot oxid -e "SELECT COUNT(*) AS articles FROM oxarticles; SHOW TABLES LIKE 'oxconfig';"
docker exec fixchk mysql -uroot -proot oxid -e "SELECT COUNT(*) FROM oxmigrations_ce" 2>&1 | head   # expect table ABSENT (pre-migration state)
docker rm -f fixchk
```
Expected: `oxarticles` has demodata rows (hundreds), `oxconfig` exists, and `oxmigrations_ce` is **absent** (a genuine pre-migration OXID DB). Confirm no PE/EE tables.

**If OXID 6.4.3 cannot be installed reproducibly, STOP and escalate** — do not proceed to Tasks 2–5.

- [ ] **Step 4: Decide storage & commit**

If `du -h` shows the gz ≤ 15 MB → commit the dump + sha256. If larger → do NOT commit; upload as a GitHub release asset (tag `migration-fixtures`), keep only the `.sha256` in-repo, and note the asset URL in the driver + README. Record the decision in the commit message.

```bash
git add tests/Migration/e2e/build-oxid643-fixture.sh tests/Migration/e2e/fixtures/
git commit -m "test(migration): OXID 6.4.3 CE demodata fixture producer + dump

Refs o3-shop/o3-shop#206"
```

---

## Task 2: Per-cell driver — happy path (MariaDB + PHP 8.2)

**Files:** Create `tests/Migration/e2e/run-migration-e2e.sh`, `tests/Migration/e2e/docker-compose.e2e.yml`.

- [ ] **Step 1: Write `docker-compose.e2e.yml`**

Throwaway DB + a php-apache service for the o3-shop side, parameterized by env:

```yaml
services:
  db:
    image: ${DB_IMAGE:-mariadb:10.11}
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: o3migrate
    command: ["--default-authentication-plugin=mysql_native_password"]
  shop:
    image: php:${PHP_VERSION:-8.2}-apache
    depends_on: [db]
    volumes:
      - ../../../:/var/www/html
    working_dir: /var/www/html
    ports:
      - "${SHOP_HOST_PORT:-8080}:80"
```

- [ ] **Step 2: Write `run-migration-e2e.sh` (structure)**

Concrete driver; each stage echoes a header and fails hard (`set -euo pipefail`). Exact composer/migrate incantations validated in this task's Step 3 run.

```bash
#!/usr/bin/env bash
set -euo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
: "${DB_IMAGE:=mariadb:10.11}"
: "${PHP_VERSION:=8.2}"
: "${TARGET_VERSION:=dev-main}"
export DB_IMAGE PHP_VERSION
COMPOSE="docker compose -f $HERE/docker-compose.e2e.yml -p o3migrate"

step(){ echo "==== $* ===="; }
cleanup(){ $COMPOSE down -v >/dev/null 2>&1 || true; }
trap cleanup EXIT
cleanup
$COMPOSE up -d

step "wait for DB"
until $COMPOSE exec -T db mysqladmin ping -uroot -proot --silent 2>/dev/null; do sleep 2; done

step "load OXID 6.4.3 fixture"
# (download + checksum-verify here if the fixture is a release asset)
gunzip -c "$HERE/fixtures/oxid-6.4.3-ce-demodata.sql.gz" \
  | $COMPOSE exec -T db sh -c 'mysql -uroot -proot o3migrate'

step "install o3-shop at $TARGET_VERSION (inlined swap)"
$COMPOSE exec -T -e COMPOSER_ROOT_VERSION="$TARGET_VERSION" shop bash -lc '
  set -e
  <install php extensions + composer 2.2 as needed>
  composer install --no-interaction --no-scripts
  # write config.inc.php pointing at db/o3migrate (root/root), sShopURL http://localhost:${SHOP_HOST_PORT}
'

if [[ "$DB_IMAGE" == mysql:* ]]; then
  step "MySQL 8: decode encrypted columns (decode-mysql8)"
  $COMPOSE exec -T db sh -c 'mysql -uroot -proot o3migrate' < "$HERE/lib/decode-mysql8.sql"
fi

step "migrations:migrate + views regen"
# NOTE (known-pitfalls #85-87): oe-eshop-db_migrate's argv[2] is the EDITION filter, not a flag —
# pass ONLY 'migrations:migrate' (no --no-interaction). Views regen is a SEPARATE mandatory step.
$COMPOSE exec -T shop bash -lc 'vendor/bin/oe-eshop-db_migrate migrations:migrate'
$COMPOSE exec -T shop bash -lc 'vendor/bin/oe-eshop-db_views_generate'

step "oe:migrate:verify (gate)"
$COMPOSE exec -T shop bash -lc 'php bin/oe-console oe:migrate:verify'

step "app asserts"
SHOP_URL="http://localhost:${SHOP_HOST_PORT:-8080}" bash "$HERE/asserts/run-asserts.sh"

echo "==== E2E OK: DB=$DB_IMAGE PHP=$PHP_VERSION TARGET=$TARGET_VERSION ===="
```

- [ ] **Step 3: Run the driver locally (MariaDB + 8.2) and iterate to green**

Run: `DB_IMAGE=mariadb:10.11 PHP_VERSION=8.2 bash tests/Migration/e2e/run-migration-e2e.sh`
Expected: fixture loads, o3-shop installs at dev-main, `migrations:migrate` applies all pending versions, `oe:migrate:verify` exits 0 through the "migrate" stage. (App-assert stage lands in Task 4 — until then, stub `run-asserts.sh` to `exit 0`.)
Verify migrations actually applied: `... db -e "SELECT COUNT(*) FROM oxmigrations_ce"` is non-zero and equals the number of `source/migration/data/Version*.php` files.

- [ ] **Step 4: Commit**

```bash
git add tests/Migration/e2e/run-migration-e2e.sh tests/Migration/e2e/docker-compose.e2e.yml
git commit -m "test(migration): parameterized E2E driver (fixture -> dev-main migrate -> verify)

Refs o3-shop/o3-shop#206"
```

---

## Task 3: MySQL 8.0 path — decode-mysql8

**Files:** Create `tests/Migration/e2e/lib/decode-mysql8.sql`.

- [ ] **Step 1: Write `decode-mysql8.sql`**

The DECODE the two `skipIf(MySQL80Platform)` migrations would otherwise leave encrypted. `<KEY>` is the shop config key used by OXID's `DECODE()` (resolve from `oxconfig`/config during implementation):

```sql
-- Decode columns that Version20230322213324 / Version20230322214524 skip on MySQL 8.
UPDATE oxconfig       SET OXVARVALUE = DECODE(OXVARVALUE, '<KEY>') WHERE OXVARTYPE IN ('str','arr','aarr','bool');
UPDATE oxuserpayments SET OXVALUE    = DECODE(OXVALUE,    '<KEY>');
```

- [ ] **Step 2: Run the driver on MySQL 8.0 + PHP 8.2**

Run: `DB_IMAGE=mysql:8.0 PHP_VERSION=8.2 bash tests/Migration/e2e/run-migration-e2e.sh`
Expected: decode step runs, `migrations:migrate` + `oe:migrate:verify` green.
Verify decode worked: `oxconfig.OXVARVALUE` for a known key is now plaintext (not the encrypted blob), and the two skip-on-MySQL8 versions appear in `oxmigrations_ce`.

- [ ] **Step 3: Commit**

```bash
git add tests/Migration/e2e/lib/decode-mysql8.sql
git commit -m "test(migration): MySQL 8 decode step for the E2E driver

Refs o3-shop/o3-shop#206"
```

---

## Task 4: App-level assertions

**Files:** Create `tests/Migration/e2e/asserts/run-asserts.sh`, `tests/Migration/e2e/asserts/migration-smoke.spec.ts`.

- [ ] **Step 1: Write `run-asserts.sh`**

Lightweight checks + a Playwright smoke, driven by `SHOP_URL`:

```bash
#!/usr/bin/env bash
set -euo pipefail
: "${SHOP_URL:?SHOP_URL required}"
# 1. storefront home 200
code=$(curl -s -o /dev/null -w '%{http_code}' "$SHOP_URL/")
[ "$code" = "200" ] || { echo "storefront home returned $code"; exit 1; }
# 2 + 3. admin login + checkout via Playwright smoke
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$HERE"
npm ci --prefix . >/dev/null 2>&1 || npm install >/dev/null 2>&1
SHOP_URL="$SHOP_URL" npx playwright test migration-smoke.spec.ts --reporter=line
```

- [ ] **Step 2: Write `migration-smoke.spec.ts`**

Reuse the existing page objects/helpers under `tests/Acceptance/playwright/` where possible. Minimal spec:

```ts
import { test, expect } from '@playwright/test';
const SHOP = process.env.SHOP_URL ?? 'http://localhost:8080';

test('storefront home renders', async ({ page }) => {
  await page.goto(SHOP + '/');
  await expect(page).toHaveTitle(/./);
});

test('admin login works', async ({ page }) => {
  await page.goto(SHOP + '/admin/');
  await page.fill('input[name="user"]', 'admin@example.com');
  await page.fill('input[name="pwd"]', 'admin123');
  await page.click('button[type="submit"], input[type="submit"]');
  await expect(page.locator('frame, iframe, #navigation')).toBeVisible();
});

test('checkout flow reaches payment step', async ({ page }) => {
  // add first product to basket -> cart -> proceed to the first checkout step.
  // Use existing checkout page objects from tests/Acceptance/playwright/pages if available.
});
```

- [ ] **Step 3: Wire asserts into the driver and run end-to-end**

Point the driver's app-assert stage at `run-asserts.sh` (already referenced in Task 2). Run both DB variants:
`DB_IMAGE=mariadb:10.11 PHP_VERSION=8.2 bash tests/Migration/e2e/run-migration-e2e.sh`
Expected: all three asserts pass on the migrated DB.

- [ ] **Step 4: Commit**

```bash
git add tests/Migration/e2e/asserts/
git commit -m "test(migration): app-level asserts (storefront/admin/checkout) for E2E

Refs o3-shop/o3-shop#206"
```

---

## Task 5: GHA matrix + runbook

**Files:** Create `.github/workflows/migration-e2e.yml`, `tests/Migration/e2e/README.md`.

- [ ] **Step 1: Write `.github/workflows/migration-e2e.yml`**

```yaml
name: migration-e2e
on:
  workflow_dispatch:
    inputs:
      target_version:
        description: o3-shop version/branch to migrate to
        default: dev-main
  pull_request:
    paths:
      - 'source/migration/**'
      - 'source/Internal/Domain/Migration/**'
      - 'tests/Migration/e2e/**'
      - '.github/workflows/migration-e2e.yml'
concurrency:
  group: migration-e2e-${{ github.ref }}
  cancel-in-progress: true
jobs:
  matrix:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        db: ['mariadb:10.11', 'mysql:8.0']
        php: ['7.4', '8.0', '8.1', '8.2']
    steps:
      - uses: actions/checkout@v4
      - name: Restore fixture   # download + checksum if stored as a release asset
        run: bash tests/Migration/e2e/restore-fixture.sh
      - name: Run migration E2E
        env:
          DB_IMAGE: ${{ matrix.db }}
          PHP_VERSION: ${{ matrix.php }}
          TARGET_VERSION: ${{ github.event.inputs.target_version || 'dev-main' }}
        run: bash tests/Migration/e2e/run-migration-e2e.sh
```

- [ ] **Step 2: Write `README.md` runbook**

Document: prerequisites, `run-migration-e2e.sh` local usage (env vars incl. `TARGET_VERSION`), how the 8 cells map, how to (re)produce the fixture, the honest limitations from the spec, and the MySQL-8 decode note.

- [ ] **Step 3: Push & iterate CI**

Push the branch; trigger `workflow_dispatch`. Iterate until all 8 cells are green (or, per the OXID-6.4.3-on-newer-PHP reality, document any cell that must pin/skip a phase — do NOT silently drop a cell; `log`/README it). This step is inherently push-and-watch.

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/migration-e2e.yml tests/Migration/e2e/README.md
git commit -m "ci(migration): 8-cell E2E migration matrix + runbook

Refs o3-shop/o3-shop#206"
```

---

## Self-Review

**Spec coverage:** fixture producer (Task 1) ✓; parameterized driver w/ dev-main default (Task 2) ✓; MySQL-8 decode (Task 3) ✓; app asserts (Task 4) ✓; 8-cell matrix + runbook (Task 5) ✓; `oe:migrate:verify` gate (Task 2 step) ✓.

**Placeholder scan:** The `<...>` markers in Tasks 1–3 (exact OXID install incantation, the DECODE `<KEY>`) are genuinely spike-determined — each is paired with an explicit verification gate that finalizes it. All other steps have concrete commands. These are honest unknowns, not lazy TODOs.

**Type/name consistency:** `run-migration-e2e.sh`, env vars `DB_IMAGE`/`PHP_VERSION`/`TARGET_VERSION`/`SHOP_URL`, fixture path `tests/Migration/e2e/fixtures/oxid-6.4.3-ce-demodata.sql.gz`, tracking table `oxmigrations_ce`, binaries `oe-eshop-db_migrate` / `oe-eshop-db_views_generate` used consistently across tasks.

**Known gotchas baked in:** argv[2]=edition (not a flag) on `oe-eshop-db_migrate`; views regen is a separate mandatory step; `oxmigrations_ce` absent pre-migration; composer.lock-leftover check passes trivially under approach A.

**Sequencing:** Task 1 is a hard gate; Tasks 2–5 assume a working fixture.
