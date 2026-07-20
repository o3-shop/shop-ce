# End-to-end migration verification matrix

**Issue:** o3-shop/o3-shop#206 — "End-to-end migration verification matrix" (Phase 3 of #152)
**Branch:** `206-migration-e2e-matrix`
**Date:** 2026-07-20

## Problem

There is no automated proof that an **OXID 6.4.3 CE → current o3-shop** migration
actually succeeds. The migration is a multi-step ritual (composer swap → cumulative
`migrations:migrate` → views regen), with data-sensitive steps that silently skip on
MySQL 8 (`Version20230322213324`, `Version20230322214524` — the `DECODE()` of
`oxconfig.OXVARVALUE` / `oxuserpayments.OXVALUE`). Phase 2b shipped the inspection
CLI (`oe:migrate:status`, `oe:migrate:verify`, shop-ce#196). Phase 3 (this issue)
closes the loop with an end-to-end verification matrix.

## Goals

1. Prove, across the supported config matrix, that a real OXID 6.4.3 CE demodata
   database migrates to current o3-shop and the resulting shop is functional.
2. Matrix: **{MariaDB 10.11, MySQL 8.0} × {PHP 7.4, 8.0, 8.1, 8.2}** = 8 cells.
3. Runnable **locally** (single config) and in **CI** (full matrix, `fail-fast: false`).
4. Double as the "manual runbook" acceptance item via a README.

## Decisions (from brainstorming)

- **Full 8-cell GHA matrix** is the target (not just a runbook / single config).
- **Inline the swap.** Phase 2a (`o3-shop/migration-tool` — `o3-migrate check|decode-mysql8|swap`)
  does not exist yet. The driver runs the raw composer/SQL/migrate steps directly.
  When Phase 2a ships, the inline block is replaced with tool calls.
- **Capture-once SQL-dump fixture** for the OXID 6.4.3 starting state. OXID 6.4.3
  officially supports only PHP 7.4–8.0, so a live install on the 8.1/8.2 cells would
  fail. Instead: install OXID 6.4.3 CE + demodata **once** (PHP 7.4), capture a
  canonical DB dump as a fixture; every cell loads that dump and migrates under its
  own PHP. The migration under test is DB-centric, so this is faithful.

## Architecture

### One-time fixture producer — `tests/Migration/e2e/build-oxid643-fixture.sh`

Run once by a maintainer (not in the per-cell matrix). Steps:
1. Spin up PHP 7.4 + MariaDB in throwaway containers.
2. Install OXID 6.4.3 CE (compilation) + demodata; run OXID setup to populate the DB.
3. `mysqldump` the populated DB → `tests/Migration/e2e/fixtures/oxid-6.4.3-ce-demodata.sql.gz`.
4. Record the dump's SHA256 in `fixtures/oxid-6.4.3-ce-demodata.sha256`.

**Storage rule:** if the gzipped dump is ≤ ~15 MB, commit it under `fixtures/`.
If larger, publish it as a GitHub **release asset** (`migration-fixtures/oxid-6.4.3`)
and have the driver download + checksum-verify it. Decide on the actual measured size.

**Risk:** this is a spike. A live OXID 6.4.3 compilation install can be fragile
(packagist availability of the exact tag, `oxid-esales/oxideshop-composer-plugin`
version, demodata package). Do this FIRST to fail fast; if the install is
unreproducible, escalate before building the matrix.

### Per-cell driver — `tests/Migration/e2e/run-migration-e2e.sh`

Parameterized by environment: `DB_IMAGE` (e.g. `mariadb:10.11` / `mysql:8.0`) and
`PHP_VERSION`. Idempotent, self-contained, and the single source of truth shared by
local runs and CI. Steps:

1. Start the DB service; wait for readiness.
2. Load the OXID 6.4.3 dump (download + checksum if it's a release asset).
3. Configure o3-shop (this repo, at the branch under test) to use that DB — the
   inlined "swap".
4. **MySQL 8 only:** run the `decode-mysql8` SQL (`DECODE()` of `oxconfig.OXVARVALUE`
   and `oxuserpayments.OXVALUE`) so the two `skipIf(MySQL80)` migrations are no-ops.
   Idempotent.
5. `vendor/bin/oe-eshop-db_migrate migrations:migrate` then `oe-eshop-db_views_regenerate`.
6. `bin/oe-console oe:migrate:verify` — must exit 0.
7. Bring up the app (apache/php) and run **app-level asserts**:
   - storefront home returns 200,
   - admin login succeeds,
   - a minimal checkout flow completes.
   Browser-level asserts reuse the existing Playwright harness
   (`tests/Acceptance/playwright/`); non-browser checks use console/curl.

### CI — `.github/workflows/migration-e2e.yml`

- `strategy: { fail-fast: false, matrix: { db: [mariadb:10.11, mysql:8.0], php: [7.4, 8.0, 8.1, 8.2] } }`
- Each cell checks out the repo, restores the fixture, and runs
  `run-migration-e2e.sh` with the cell's `DB_IMAGE` / `PHP_VERSION`.
- Trigger: `workflow_dispatch` + `pull_request` on the migration paths (scoped so it
  doesn't run on every PR — it's heavy). Concurrency group per ref.

### Runbook — `tests/Migration/e2e/README.md`

Human-readable steps to run a single config locally (satisfies the "manual runbook"
acceptance) plus how to (re)produce the fixture and how the matrix maps to cells.

## Reference: migrations applied during the jump

(from #152 — the cumulative `migrations:migrate` applies these)

| Version | What |
|---|---|
| `20230322213324` | decode `oxconfig.OXVARVALUE` (skips on MySQL 8 — decode-mysql8 covers) |
| `20230322214524` | decode `oxuserpayments.OXVALUE` (skips on MySQL 8 — decode-mysql8 covers) |
| `20230405094126` | add `oxcontents.OXISPLAIN` |
| `20230405121448` | backfill `OXISPLAIN=1` |
| `20230730131836` | create rights-roles tables |
| `20231219085936` | rights-roles → latin1 |
| `20250924111451` | add `OXNODELETE` to `oxactions` + `oxcontents` |
| `20260427090000` | create `o3revocation` + seed §356a CMS snippet |

## Honest limitations (documented, not hidden)

1. **Composer file-swap not exercised.** Approach A starts from the o3-shop tree +
   an OXID 6.4.3 *DB*, so it verifies the DB migration and app boot — the core value
   — but not the OXID→o3-shop composer file swap. `oe:migrate:verify`'s "no leftover
   `oxid-esales/*` in composer.lock" check therefore passes trivially. Full swap
   mechanics are Phase 2a's concern.
2. **Fixture production is a spike** with real risk (see above).
3. **The 8-cell matrix will need CI iteration** — GHA matrices can't be fully proven
   locally; expect push-and-watch cycles. The driver itself is locally verifiable and
   is where correctness is nailed first.

## Out of scope

- Reverse / cross-edition migration (per #152).
- The `o3-migrate` pre-swap tool (Phase 2a) — inlined here, built separately.
- A web UI.

## Acceptance (maps to #152 Phase 3)

- [ ] `run-migration-e2e.sh` completes green for at least one config locally
      (one verified end-to-end run).
- [ ] `.github/workflows/migration-e2e.yml` defines the 8-cell matrix.
- [ ] `oe:migrate:verify` is invoked and gates the run (non-zero → fail).
- [ ] App asserts (admin login, storefront, checkout) pass on the migrated DB.
- [ ] README runbook present.
