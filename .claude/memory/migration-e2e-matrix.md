---
name: migration-e2e-matrix
description: How the OXID 6.4.3 -> o3-shop migration E2E matrix works + its non-obvious gotchas (#206)
type: reference
---

# Migration E2E verification matrix (#206, Phase 3 of #152)

`tests/Migration/e2e/` — proves an OXID 6.4.3 CE demodata DB migrates to dev
o3-shop across {MariaDB 10.11, MySQL 8.0} × PHP {7.4,8.0,8.1,8.2}. CI:
`.github/workflows/migration-e2e.yml` (8-cell matrix, one step per driver phase).

Driver `run-migration-e2e.sh` is phase-based: `run-migration-e2e.sh` (no arg) runs
all phases locally; `run-migration-e2e.sh <phase>` runs one (CI = one step/phase).
Phases: start-db · load-fixture · provision · install · configure · migrate ·
verify · asserts · cleanup. Container names derive from `MIGRATION_E2E_RUN` so
separate phase invocations share containers (CI sets it to `o3mig-ci`).

## Non-obvious, hard-won facts (don't re-derive)

- **Building the OXID 6.4.3 fixture** (`build-oxid643-fixture.sh`): the
  `oxid-esales/oxideshop-project` package is **dev-only** — `composer create-project`
  needs `--stability dev`. Pin the compilation with
  `oxid-esales/oxideshop-metapackage-ce:v6.4.3` (=> oxideshop-ce **v6.10.3**).
  The demodata **installer no-ops**; import `vendor/oxid-esales/oxideshop-demodata-ce/src/demodata.sql`
  directly on top of `source/Setup/Sql/database_schema.sql`. Dump **DB-agnostic**
  (no `--databases`) so it loads into any target DB name. A correct fixture has
  **no `oxmigrations*` table** (genuine pre-migration state).
- **`dev-main` / `dev-b-1.7` didn't resolve** for shop-ce-as-root: o3-shop/shop-composer-plugin
  requires shop-ce `^1.2.0 || dev-*`, and a branch-style root self-version only
  satisfies it via a `branch-alias`. composer.json's alias was stale (`dev-b-1.6`
  only). Fixed by adding `"dev-b-1.7": "1.7-dev"`. Use `TARGET_VERSION=dev-b-1.7`
  (or `1.7.x-dev`). `dev-main` stays unsupported — shop-ce has no `main` branch.
- **MySQL 8 removed `ENCODE()/DECODE()`** — the exact reason the two migrations
  `Version20230322213324` / `...214524` do `skipIf(MySQL80)`. So the encrypted
  `oxconfig.OXVARVALUE` / `oxuserpayments.OXVALUE` can't be decoded on a MySQL 8
  target. The driver decodes on a **MariaDB sidecar** (replaying the migrations'
  up() SQL + `Config::DEFAULT_CONFIG_KEY` = `fq45QS09_fqyx09239QQ`), then loads the
  decoded data into MySQL 8. `lib/decode-mysql8.sql` needs `SET SESSION sql_mode=''`
  because the decoded bytes are latin1-ish (not valid UTF-8) — strict mode rejects
  them otherwise (ER_TRUNCATED_WRONG_VALUE 1366).
- **DB readiness**: `mysqladmin ping` answers *before* the root password is applied
  — poll a real `mysql -uroot -proot -e "SELECT 1"` instead.
- **Scope**: verifies the DB migration + app-boot (front controllers non-5xx), NOT
  the composer file-swap (starts from the o3-shop tree + an OXID DB). The OXID
  demodata sets `sTheme=flow`, which o3-shop doesn't ship — so storefront returns a
  canonical/SEO redirect rather than a rendered page; deep Playwright/checkout
  asserts are a documented follow-up. Phase 2a (`o3-migrate`) is inlined, not called.

Related: [[migration-status-verify-commands]] (the oe:migrate:verify gate),
[[known-pitfalls]] (oe-eshop-db_migrate argv2=edition; Setup-time DB-less config).
