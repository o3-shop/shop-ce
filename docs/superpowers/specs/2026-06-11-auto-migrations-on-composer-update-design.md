# Auto-run database migrations on `composer update` (#192)

**Date:** 2026-06-11
**Issue:** [#192](https://github.com/o3-shop/shop-ce/issues/192) — *Run database migrations automatically on composer update (with no-DB guard)*
**Branch:** `192-auto-migrations`

## Problem

`composer update` only syncs files and runs `ShopVersionGenerator` / parameter handling. Database
migrations and view regeneration are a separate, manual, easily-forgotten step:

```bash
composer update --no-dev
rm -rf source/tmp/*
vendor/bin/oe-eshop-db_migrate migrations:migrate
vendor/bin/oe-eshop-db_views_regenerate
```

Forgetting the migrate/views step after an update leaves the shop broken. Migrations were historically
*not* hooked into composer because composer regularly runs without a usable database (`create-project`
before the Setup wizard, CI artifact builds, the "build locally / copy to server" workflow).

## Goal

Run database migrations automatically on `post-update-cmd`, behind a guard that safely skips when no
database is available — never hanging, never failing a legitimately DB-less composer run.

## Scope decisions (confirmed)

- **Home:** a script class in **shop-ce** (`Core\MigrationsRunner`), mirroring `ShopVersionGenerator`.
  Not in `shop-composer-plugin` (external package, out of reach from this repo).
- **Trigger:** `post-update-cmd` **only**. `post-install-cmd` is left untouched.
- **Guard:** `isLaunched()` **plus** a short-timeout DBAL probe (see Guard ordering below).
- **View regeneration:** bundled automatically — no separate step (see Findings).
- **Skip notice:** a prominent, multi-line boxed warning via composer IO.

## Key findings from codebase investigation

1. **View regeneration is already bundled in the migration path.**
   `vendor/o3-shop/shop-doctrine-migration-wrapper/src/Migrations.php::execute()` runs
   `migrations:migrate` and then, when `oxconfig` is populated, `exec()`s
   `bin/oe-eshop-db_views_generate`. So reusing `MigrationsBuilder->build()->execute(MIGRATE_COMMAND)`
   regenerates views for free. The manual step's `oe-eshop-db_views_regenerate` bin is explicitly
   **deprecated** in favor of `oe-eshop-db_views_generate` — the one the wrapper already calls.

2. **`isLaunched()` can hang on an unreachable host.**
   `ShopStateService::getConnection()` builds its `\PDO` with **no** `PDO::ATTR_TIMEOUT`, and
   `isLaunched()` runs `SELECT 1 FROM oxconfig`. In the "config exists but MySQL is not up" scenario
   (dev container, artifact build) this blocks on the default connect timeout. Therefore the
   short-timeout probe **must run before** `isLaunched()`, inverting the order in the issue text. This
   is a deliberate, documented divergence.

3. **Bootstrapping the shop from a composer process — proven pattern.**
   `shop-composer-plugin`'s `Plugin.php` does: `require vendor/autoload.php` →
   `BootstrapContainerFactory::getBootstrapContainer()->get(ShopStateServiceInterface::class)->isLaunched()`
   → only if launched, `require source/bootstrap.php`. We mirror this. The lightweight bootstrap
   container is enough for `isLaunched()`; the full `source/bootstrap.php` (Registry/ConfigFile) is
   required only before running migrations.

4. **Listener ordering.** Composer dispatches plugin event-subscribers (the file sync in
   `Plugin::updatePackages()`) before root-package script listeners on the same `post-update-cmd`
   event. So `source/` is synced before our script runs. **To be verified empirically** with a real
   `composer update` during implementation.

## Implementation correction (2026-06-11): run migrations in a subprocess, not in-process

The original plan reused `MigrationsBuilder->build()->execute(MIGRATE_COMMAND)` **in-process** and
relied on the wrapper's bundled view regeneration. Integration testing proved this fails:

> `The command defined in "Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand" cannot have an empty name.`

**Root cause:** `post-update-cmd` scripts execute inside Composer's own PHP process, which already has
Composer's bundled *modern* symfony/console loaded into the class space. Building the Doctrine
Migrations console application in-process picks up that version instead of the shop's symfony/console
3.4. doctrine/migrations 2.x declares command names via the legacy static `$defaultName` property,
which modern symfony/console ignores (it requires the `#[AsCommand]` attribute), so command names
resolve to empty and `Application::add()` throws. The standalone `vendor/bin/oe-eshop-db_migrate` works
because it runs in a separate PHP process with only the shop's symfony/console 3.4 loaded. This is, in
part, the deeper historical reason migrations were kept out of composer.

**Resolution:** `MigrationsRunner::runMigrations()` runs the existing bins as **separate PHP
subprocesses** via pure-PHP `passthru` (no symfony in our process): `oe-eshop-db_migrate
migrations:migrate --no-interaction`, then `oe-eshop-db_views_generate` (run explicitly so a
migrate-without-views half-update cannot happen). Consequently `loadShopBootstrap()` is removed — the
subprocesses self-bootstrap, and the guard checks (`isLaunched()`, the DBAL probe) already work in
Composer's process without it. The guard ordering, skip semantics, and failure semantics are unchanged.

## Architecture

A new class `OxidEsales\EshopCommunity\Core\MigrationsRunner` (`source/Core/MigrationsRunner.php`),
wired as the **last** entry of `post-update-cmd` in `composer.json`:

```json
"post-update-cmd": [
    "OxidEsales\\EshopCommunity\\Core\\ShopVersionGenerator::generate",
    "OxidEsales\\EshopCommunity\\Core\\IncenteevScriptHandlerWrapper::buildParameters",
    "@oe:ide-helper:generate",
    "OxidEsales\\EshopCommunity\\Core\\MigrationsRunner::run"
]
```

### Public entry point

```php
public static function run(\Composer\Script\Event $event): void
```

A thin static wrapper that wires real collaborators and delegates to instance logic. This keeps the
untestable glue (`BootstrapContainerFactory`, DBAL `DriverManager`, `MigrationsBuilder`) minimal and
isolated, following the `IncenteevScriptHandlerWrapper` convention (overridable protected members let
unit tests substitute doubles on an anonymous subclass).

### Collaborators (injectable / overridable for tests)

- **Config locator** — returns config file path + config table name (from
  `BasicContextInterface` via the bootstrap container).
- **Connectivity prober** — opens a DBAL connection with `driverOptions => [PDO::ATTR_TIMEOUT => 2]`
  and runs `SELECT 1`. Returns bool; never throws to the caller.
- **Shop-state check** — `ShopStateServiceInterface::isLaunched()`.
- **Migration executor** — `(new MigrationsBuilder())->build()->execute(Migrations::MIGRATE_COMMAND)`
  with a `Symfony\Component\Console\Output\ConsoleOutput`. Returns the integer error code.
- **Notifier** — writes the boxed skip warning / failure error via composer `IOInterface`.

## Control flow

```
run(Event):
  require vendor/autoload.php            # defensive; composer usually has it
  configFile = locator.configFilePath()

  if not file_exists(configFile):        # fresh create-project, no shop yet
      notifier.skip("No config file — shop not set up.")   # boxed warning
      return                             # exit 0

  if not prober.canConnect(2s):          # DB unreachable / not up — FAST fail
      notifier.skip("Database not reachable.")             # boxed warning
      return                             # exit 0

  if not shopState.isLaunched():         # authoritative; safe now (host reachable)
      notifier.skip("Shop not launched.")                  # boxed warning
      return                             # exit 0

  require source/bootstrap.php           # Registry/ConfigFile for views regen
  code = migrations.execute(MIGRATE_COMMAND)   # views regen happens inside
  if code != 0:
      notifier.fail("Migration failed (exit code N).")     # loud error
      throw RuntimeException             # non-zero composer exit
```

Exceptions from the executor are caught, reported via `notifier.fail()` with a message that clearly
distinguishes *"your migration failed"* from *"composer is broken"*, and rethrown so composer exits
non-zero.

## Error handling / failure semantics

| Situation | Behaviour | Exit |
|---|---|---|
| Config file missing (fresh `create-project`) | Boxed skip warning, listing manual commands | 0 |
| DB host unreachable / not up (probe fails in ≤2s) | Boxed skip warning, listing manual commands | 0 |
| `isLaunched()` false (namespaces/table not ready) | Boxed skip warning, listing manual commands | 0 |
| Migration returns non-zero or throws | Loud error ("migration failed", not "composer broken") + rethrow | non-zero |
| Guard passes, migration succeeds | Migrate + bundled view regen; success line | 0 |

The boxed skip warning always states **migrations were NOT executed** and lists the exact commands to
run on the target environment.

## Testing (TDD)

`tests/Unit/Core/MigrationsRunnerTest.php`, extending `\OxidTestCase`, mirroring
`IncenteevScriptHandlerWrapperTest` (anonymous subclass + injected doubles + mocked composer
`Event`/`IOInterface`). No real DB required.

Cases:
1. Method `run` exists and is static, accepts `Composer\Script\Event`.
2. Config file missing → executor **never** called, skip notice written, no exception.
3. Probe fails → executor **never** called, skip notice written, no exception.
4. `isLaunched()` false → executor **never** called, skip notice written, no exception.
5. Guard fully passes → executor **called once** with `MIGRATE_COMMAND`.
6. Executor returns non-zero → failure notice written **and** exception thrown.
7. Executor throws → failure notice written **and** exception rethrown.

The thin real-wiring path (bootstrap container, DBAL, `MigrationsBuilder`) is exercised manually via a
real `./docker.sh` `composer update` to confirm migrations run and to **verify listener ordering**
(finding #4).

## Compatibility

- **Fresh `create-project`:** config file absent → guard skips → unchanged behaviour.
- **CI / artifact builds / build-without-DB:** probe fails fast → guard skips with explicit
  "not migrated" notice → unchanged behaviour, louder.
- **Normal server-side `composer update`:** migrations + views run automatically.

This deliberately diverges from upstream OXID behaviour. The O3 docs' documented update procedure
should be updated accordingly (follow-up, out of scope for this change).

## Out of scope

- Changes to `shop-composer-plugin`.
- Touching `post-install-cmd`.
- Documentation updates to the O3 update procedure (separate follow-up).
