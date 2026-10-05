---
name: architecture_composer-auto-migrations
description: composer post-update-cmd now auto-runs DB migrations + view regeneration behind a guard (config exists → DB reachable → shop launched); skips safely otherwise
type: reference
---

# `composer update` auto-runs DB migrations (behind a guard)

Since #192, `post-update-cmd` runs
`OxidEsales\EshopCommunity\Core\MigrationsRunner::run` as its last step, so a
server-side `composer update` migrates the database and regenerates views
automatically — but only when a usable database is actually present.

**This reverses the older "composer never auto-migrates" policy.** It was a
deliberate decision (see PR #171 / issue #192): the previous split forced every
deployer to remember a separate manual migrate step, which was routinely
forgotten. The safety concerns behind the old policy are addressed by the guard
rather than by refusing to migrate.

## How it is wired

- `MigrationsRunner::run` is listed in `post-update-cmd` in **both**
  `composer.json` files, because Composer runs scripts only from the **root**
  package:
  - `shop-ce/composer.json` — fires when developing inside a shop-ce checkout.
  - `o3-shop/o3-shop` (the distribution, `type: project`) — the root package in
    a real deployment; without the entry here the step would never fire for
    end users. This mirrors the sibling entries (`ShopVersionGenerator::generate`
    etc.), which are declared in both for the same reason.
- Migrations run in a **separate PHP subprocess** (`passthru` of the shop's
  `oe-eshop-db_migrate` / `oe-eshop-db_views_generate` bins), never in-process —
  a composer script runs inside Composer's own runtime with its bundled modern
  symfony/console, which breaks doctrine/migrations 2.x command resolution. See
  `known-pitfalls.md`.

## The guard (order matters)

`MigrationsRunner::process()` skips (exit 0, calm one-line notice) unless all hold:

1. `configFileExists()` — the shop is set up.
2. `canConnectToDatabase()` — a short-timeout DBAL probe (`PDO::ATTR_TIMEOUT`
   = 2s). Runs **before** `isLaunched()` on purpose: `ShopStateService`
   connects with no PDO timeout and would otherwise hang the whole composer run
   on an unreachable host.
3. `isShopLaunched()` — the shop reports itself launched.

A genuine migration failure (non-zero exit / thrown) emits a loud boxed error
(`writeFailure()`) and fails the composer run with a non-zero exit. A guard miss
emits only a quiet `<comment>` notice (`writeSkip()`), because "no DB yet" is the
expected state for `composer create-project`, CI artifact builds, and the build
step of a build-once/deploy-many pipeline.

## Caveat: build-once / deploy-many

Because the guard fires whenever a DB is *reachable + launched* at
`composer update` time, a build host that happens to have a launched DB in reach
would be migrated there instead of at each deploy target. For build-once/
deploy-many artifact builds, keep the build environment DB-less (the guard then
skips) and run migrations per environment at deploy time as before:
`vendor/bin/oe-eshop-db_migrate migrations:migrate` then
`vendor/bin/oe-eshop-db_views_generate`.

`o3-shop/shop-composer-plugin` (`Plugin::updatePackages`) still only copies
package files into `source/`; it never touches the DB. The migration step must
run *after* that file sync so migrations execute against the updated file set.
