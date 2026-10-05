# Migration E2E verification matrix

End-to-end proof that an **OXID 6.4.3 CE demodata database migrates to a
development o3-shop** and the result is healthy — across
**{MariaDB 10.11, MySQL 8.0} × {PHP 7.4, 8.0, 8.1, 8.2}** (8 cells).

Phase 3 of o3-shop/o3-shop#152 (issue #206). Runs the same way locally and in CI
(`.github/workflows/migration-e2e.yml`).

## What it does (per cell)

1. Load the committed **OXID 6.4.3 CE + demodata** fixture into a throwaway DB.
   - On **MySQL 8** the two OXID-encrypted columns (`oxconfig.OXVARVALUE`,
     `oxuserpayments.OXVALUE`) are first decoded on a **MariaDB sidecar** — MySQL
     8.0 removed `ENCODE()/DECODE()`, which is why the shop migrations
     `skipIf(MySQL80)`.
2. Install o3-shop at `TARGET_VERSION` (default `dev-b-1.7`) against that DB.
3. `vendor/bin/oe-eshop-db_migrate migrations:migrate` + `oe-eshop-db_views_generate`.
4. `bin/oe-console oe:migrate:verify` — **gate** (non-zero fails the run).
5. App-level smoke asserts (storefront + admin reachable).

## Run locally

Requires Docker.

```bash
# defaults: MariaDB 10.11, PHP 8.2, TARGET_VERSION=dev-b-1.7
tests/Migration/e2e/run-migration-e2e.sh

# a specific cell
DB_IMAGE=mysql:8.0 PHP_VERSION=8.1 tests/Migration/e2e/run-migration-e2e.sh

# verify a stable tag instead of the dev line
TARGET_VERSION=v1.7.0 tests/Migration/e2e/run-migration-e2e.sh

# keep the containers up afterwards to poke around
KEEP=1 tests/Migration/e2e/run-migration-e2e.sh
```

Parameters (env vars): `DB_IMAGE`, `PHP_VERSION`, `TARGET_VERSION`, `KEEP`.

> **Working-tree caveat:** the driver copies the shop into the container with `git archive HEAD`,
> i.e. the **committed** tree. Uncommitted local edits (e.g. a WIP `source/migration/data/Version*.php`)
> are **not** exercised — commit them first, or they'll appear to pass without being tested.

## Run in CI

`.github/workflows/migration-e2e.yml` runs the full 8-cell matrix
(`fail-fast: false`) on:
- `workflow_dispatch` — with an optional `target_version` input (default `dev-b-1.7`).
- `pull_request` — only when migration-relevant paths change (it's heavy).

## TARGET_VERSION and `dev-main`

The intent (#206) is to migrate to the **development** line so regressions
surface — hence a dev version, not a stable tag. Use **`dev-b-1.7`** (the b-1.7
dev line; `1.7.x-dev` is the equivalent numeric form).

The literal `dev-main` does **not** resolve: `o3-shop/shop-ce` is the root
package and `o3-shop/shop-composer-plugin` requires shop-ce `^1.2.0 || dev-*`; a
branch-style root self-version only satisfies that via a `branch-alias`. This
repo's `composer.json` now aliases `dev-b-1.7 → 1.7-dev` (added for #206). There
is no `main` branch on shop-ce, so `dev-main` is intentionally unsupported.

## Regenerating the fixture

The committed fixture (`fixtures/oxid-6.4.3-ce-demodata.sql.gz`, ~286 KB) is a
DB-agnostic dump of an OXID compilation **6.4.3** (metapackage `v6.4.3` →
`oxideshop-ce v6.10.3`) + demodata, with **no migration-tracking table** (genuine
pre-migration state). To rebuild it from scratch:

```bash
tests/Migration/e2e/build-oxid643-fixture.sh
```

## Limitations (honest scope)

- **The composer file-swap is not exercised.** The run starts from the o3-shop
  tree + an OXID 6.4.3 *database*, so it verifies the DB migration + app health,
  not the OXID→o3-shop composer swap. `oe:migrate:verify`'s "no leftover
  `oxid-esales/*`" check therefore passes trivially.
- **App asserts are a lean HTTP smoke** (storefront + admin reachable), not full
  browser/checkout flows. Note the OXID demodata sets `sTheme=flow`, which
  o3-shop does not ship; the driver accounts for this (see the assert step).
  Deeper Playwright checkout coverage is a follow-up.
- **Phase 2a (`o3-migrate`) is inlined**, not called — the driver runs the raw
  swap/migrate steps directly.
