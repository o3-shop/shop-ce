---
name: migration-status-verify-commands
description: oe:migrate:status / oe:migrate:verify CLI + how CE migration state is read (oxmigrations_ce)
type: reference
---

# oe:migrate:status / oe:migrate:verify (#205, part of #152)

Two read-only console commands to inspect/verify DB migration state when moving an OXID 6.4.3 install up to current o3-shop. Live in `source/Internal/Domain/Migration/` (Command / Service / Repository), wired via `Domain/Migration/services.yaml` imported from `Domain/services.yaml`.

- `oe:migrate:status` — current applied version, latest available, pending + unknown versions (always exits 0).
- `oe:migrate:verify` — pass/fail health check, exits non-zero on failure: tracking table present, no pending migrations, no leftover `oxid-esales/*` in composer.lock. For scripts/CI.

## How CE migration state is computed (non-obvious)
- **Executed** versions come from the tracking table **`oxmigrations_ce`** (name set in `source/migration/migrations.yml` → `table_name`). doctrine/migrations is **2.3.5**, which stores the **bare version timestamp** in the `version` column (e.g. `20230322213324`) — NOT the FQCN.
- **Available** versions = the `Version<TIMESTAMP>.php` files under `source/migration/data/` (parse the digits out of the filename; matches the stored `version` directly).
- Pending = available − executed; unknown = executed − available.
- A pre-migration DB legitimately has NO `oxmigrations_ce` table — commands must handle that (status warns, verify fails the tracking-table check). Use `getSchemaManager()->tablesExist([...])` via `QueryBuilderFactoryInterface->create()->getConnection()`.
- composer.lock is gitignored ([[ci-lockless-composer-vendor-cache]]) but exists at runtime in a real install; the leftover-package check reads it from `BasicContext::getShopRootPath().'/composer.lock'` and SKIPs (not fails) when absent.

Related: [[console-commands-and-php-floor]] (how to register a command), [[architecture_composer-auto-migrations]].
