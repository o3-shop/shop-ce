# Auto-run Database Migrations on `composer update` (#192) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Automatically run database migrations (and the view regeneration bundled inside them) on `composer update`, behind a fast-failing no-DB guard that skips loudly instead of hanging or breaking DB-less composer runs.

**Architecture:** A new root-package composer script class `OxidEsales\EshopCommunity\Core\MigrationsRunner`, wired as the last entry of `post-update-cmd`. A thin static `run(Event)` wires real collaborators and delegates to an instance `process(IOInterface)` method. All side-effecting collaborators live behind overridable `protected` seam methods so `process()` is unit-testable without a database (the same anonymous-subclass-override pattern as `IncenteevScriptHandlerWrapperTest`). The guard order is: config file exists → short-timeout DBAL probe → `isLaunched()`; the probe runs *before* `isLaunched()` because `ShopStateService` connects with no PDO timeout and would otherwise hang on an unreachable host.

**Tech Stack:** PHP 7.4/8.x, Composer scripts (`Composer\Script\Event`, `Composer\IO\IOInterface`), Doctrine DBAL ≤2.12 (`DriverManager`), `OxidEsales\Facts\Facts`, `OxidEsales\DoctrineMigrationWrapper\{MigrationsBuilder,Migrations}`, Symfony `ConsoleOutput`, PHPUnit 9 via `\OxidTestCase`.

---

## File Structure

- **Create:** `source/Core/MigrationsRunner.php` — the composer script class. One responsibility: decide whether to migrate, and if so run migrations and report results.
- **Create:** `tests/Unit/Core/MigrationsRunnerTest.php` — unit tests for the `process()` orchestration and skip/fail behavior.
- **Modify:** `composer.json` — append `MigrationsRunner::run` to `post-update-cmd`.

### Reference APIs (verified against the codebase)

- `OxidEsales\EshopCommunity\Internal\Container\BootstrapContainerFactory::getBootstrapContainer(): ContainerInterface`
- `OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ShopStateServiceInterface::isLaunched(): bool`
- `OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface::getConfigFilePath(): string`
- `OxidEsales\Facts\Facts` → `getDatabaseName()`, `getDatabaseUserName()`, `getDatabasePassword()`, `getDatabaseHost()`, `getDatabasePort()`, `getDatabaseDriver()`, `getSourcePath()`
- `OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder::build(Facts $facts = null): Migrations`
- `OxidEsales\DoctrineMigrationWrapper\Migrations::MIGRATE_COMMAND` (`'migrations:migrate'`), `::setOutput(Output)`, `::execute($command, $edition = null): int` — **also regenerates views internally** after a successful migrate.
- `Doctrine\DBAL\DriverManager::getConnection(array): Connection`; `Connection::executeQuery(string)`.

---

## Task 1: `MigrationsRunner` orchestration + skip/fail behavior (TDD)

**Files:**
- Create: `source/Core/MigrationsRunner.php`
- Test: `tests/Unit/Core/MigrationsRunnerTest.php`

The unit tests drive the orchestration in `process()`. The DB/bootstrap collaborators are `protected` seam methods overridden by an anonymous subclass in tests, so no real database is touched. `process()` calls them in guard order and reports via the real `writeSkip()` / `writeSuccess()` / `writeFailure()` methods (exercised against a mocked `IOInterface`).

- [ ] **Step 1: Write the failing test file**

```php
<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use Composer\IO\IOInterface;
use OxidEsales\EshopCommunity\Core\MigrationsRunner;

class MigrationsRunnerTest extends \OxidTestCase
{
    /**
     * Build a runner with all seams overridden. Flags decide guard outcomes;
     * $migrateReturn controls the migration result (int) or a Throwable to throw.
     */
    private function makeRunner(
        bool $configExists = true,
        bool $canConnect = true,
        bool $launched = true,
        $migrateReturn = 0
    ): MigrationsRunner {
        return new class ($configExists, $canConnect, $launched, $migrateReturn) extends MigrationsRunner {
            public bool $migrated = false;
            public bool $bootstrapped = false;
            private bool $configExists;
            private bool $canConnect;
            private bool $launched;
            private $migrateReturn;

            public function __construct($configExists, $canConnect, $launched, $migrateReturn)
            {
                $this->configExists = $configExists;
                $this->canConnect = $canConnect;
                $this->launched = $launched;
                $this->migrateReturn = $migrateReturn;
            }

            protected function configFileExists(): bool
            {
                return $this->configExists;
            }

            protected function canConnectToDatabase(): bool
            {
                return $this->canConnect;
            }

            protected function isShopLaunched(): bool
            {
                return $this->launched;
            }

            protected function loadShopBootstrap(): void
            {
                $this->bootstrapped = true;
            }

            protected function runMigrations(IOInterface $io): int
            {
                $this->migrated = true;
                if ($this->migrateReturn instanceof \Throwable) {
                    throw $this->migrateReturn;
                }
                return (int) $this->migrateReturn;
            }
        };
    }

    public function testRunIsStaticAndAcceptsComposerEvent(): void
    {
        $reflection = new \ReflectionMethod(MigrationsRunner::class, 'run');
        $this->assertTrue($reflection->isStatic(), 'run must be static');
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame(\Composer\Script\Event::class, $params[0]->getType()->getName());
    }

    public function testSkipsWhenConfigFileMissing(): void
    {
        $io = $this->createMock(IOInterface::class);
        $io->expects($this->atLeastOnce())->method('writeError');

        $runner = $this->makeRunner($configExists = false);
        $runner->process($io);

        $this->assertFalse($runner->migrated, 'Migrations must not run without a config file');
    }

    public function testSkipsWhenDatabaseUnreachable(): void
    {
        $io = $this->createMock(IOInterface::class);
        $io->expects($this->atLeastOnce())->method('writeError');

        $runner = $this->makeRunner(true, $canConnect = false);
        $runner->process($io);

        $this->assertFalse($runner->migrated, 'Migrations must not run when DB is unreachable');
    }

    public function testSkipsWhenShopNotLaunched(): void
    {
        $io = $this->createMock(IOInterface::class);
        $io->expects($this->atLeastOnce())->method('writeError');

        $runner = $this->makeRunner(true, true, $launched = false);
        $runner->process($io);

        $this->assertFalse($runner->migrated, 'Migrations must not run when shop is not launched');
    }

    public function testRunsMigrationsWhenGuardPasses(): void
    {
        $io = $this->createMock(IOInterface::class);

        $runner = $this->makeRunner(true, true, true, 0);
        $runner->process($io);

        $this->assertTrue($runner->bootstrapped, 'Shop bootstrap must be loaded before migrating');
        $this->assertTrue($runner->migrated, 'Migrations must run when the guard passes');
    }

    public function testThrowsWhenMigrationReturnsNonZero(): void
    {
        $io = $this->createMock(IOInterface::class);
        $io->expects($this->atLeastOnce())->method('writeError');

        $runner = $this->makeRunner(true, true, true, 5);

        $this->expectException(\RuntimeException::class);
        $runner->process($io);
    }

    public function testRethrowsWhenMigrationThrows(): void
    {
        $io = $this->createMock(IOInterface::class);
        $io->expects($this->atLeastOnce())->method('writeError');

        $runner = $this->makeRunner(true, true, true, new \LogicException('boom'));

        $this->expectException(\RuntimeException::class);
        $runner->process($io);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./docker.sh test --fast tests/Unit/Core/MigrationsRunnerTest.php`
Expected: FAIL — `Class "OxidEsales\EshopCommunity\Core\MigrationsRunner" not found`.

- [ ] **Step 3: Implement `MigrationsRunner`**

Create `source/Core/MigrationsRunner.php`:

```php
<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Core;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Doctrine\DBAL\DriverManager;
use OxidEsales\DoctrineMigrationWrapper\Migrations;
use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use OxidEsales\EshopCommunity\Internal\Container\BootstrapContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ShopStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\Facts\Facts;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Composer post-update script: runs database migrations (and the bundled view
 * regeneration) automatically, but only when a usable database is available.
 *
 * The guard probes the database with a short connect timeout BEFORE calling
 * ShopStateService::isLaunched(), because that service connects with no PDO
 * timeout and would otherwise block the whole composer run on an unreachable host.
 */
class MigrationsRunner
{
    /** Connect timeout (seconds) for the database availability probe. */
    private const CONNECT_TIMEOUT_SECONDS = 2;

    /**
     * Composer script entry point.
     */
    public static function run(Event $event): void
    {
        (new static())->process($event->getIO());
    }

    /**
     * Orchestrates the guard and migration run. Public for unit testing.
     */
    public function process(IOInterface $io): void
    {
        if (!$this->configFileExists()) {
            $this->writeSkip($io, 'No config file found — the shop is not set up yet.');
            return;
        }

        if (!$this->canConnectToDatabase()) {
            $this->writeSkip($io, 'The database is not reachable.');
            return;
        }

        if (!$this->isShopLaunched()) {
            $this->writeSkip($io, 'The shop is not launched yet.');
            return;
        }

        $this->loadShopBootstrap();

        try {
            $exitCode = $this->runMigrations($io);
        } catch (\Throwable $exception) {
            $this->writeFailure($io, 'The migration command threw an exception: ' . $exception->getMessage());
            throw new \RuntimeException(
                'O3-Shop database migration failed: ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        if ($exitCode !== 0) {
            $this->writeFailure($io, 'The migration command returned exit code ' . $exitCode . '.');
            throw new \RuntimeException('O3-Shop database migration failed with exit code ' . $exitCode . '.');
        }

        $io->write('<info>O3-Shop: database migrations and views are up to date.</info>');
    }

    /**
     * @return bool whether the shop config file is present.
     */
    protected function configFileExists(): bool
    {
        $context = BootstrapContainerFactory::getBootstrapContainer()->get(BasicContextInterface::class);

        return file_exists($context->getConfigFilePath());
    }

    /**
     * Opens a DBAL connection with a short connect timeout and runs a trivial
     * query. Never throws — any failure means "no usable database".
     *
     * @return bool whether the database is reachable.
     */
    protected function canConnectToDatabase(): bool
    {
        $facts = new Facts();

        try {
            $connection = DriverManager::getConnection([
                'dbname' => $facts->getDatabaseName(),
                'user' => $facts->getDatabaseUserName(),
                'password' => $facts->getDatabasePassword(),
                'host' => $facts->getDatabaseHost(),
                'port' => $facts->getDatabasePort(),
                'driver' => $facts->getDatabaseDriver(),
                'driverOptions' => [\PDO::ATTR_TIMEOUT => self::CONNECT_TIMEOUT_SECONDS],
            ]);
            $connection->executeQuery('SELECT 1');
            $connection->close();
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * @return bool whether the shop reports itself as launched.
     */
    protected function isShopLaunched(): bool
    {
        return BootstrapContainerFactory::getBootstrapContainer()
            ->get(ShopStateServiceInterface::class)
            ->isLaunched();
    }

    /**
     * Loads the full shop bootstrap (Registry / ConfigFile) needed by the
     * migration wrapper's bundled view regeneration.
     */
    protected function loadShopBootstrap(): void
    {
        require_once (new Facts())->getSourcePath() . DIRECTORY_SEPARATOR . 'bootstrap.php';
    }

    /**
     * Runs the migration command. View regeneration is bundled inside execute().
     *
     * @return int migration exit code (0 = success).
     */
    protected function runMigrations(IOInterface $io): int
    {
        $migrations = (new MigrationsBuilder())->build();
        $migrations->setOutput(new ConsoleOutput());

        return (int) $migrations->execute(Migrations::MIGRATE_COMMAND);
    }

    /**
     * Writes a prominent, unmissable warning that migrations were NOT executed,
     * listing the manual commands to run on the target environment.
     */
    protected function writeSkip(IOInterface $io, string $reason): void
    {
        $border = str_repeat('!', 70);
        $io->writeError('<warning>' . $border . '</warning>');
        $io->writeError('<warning>!! O3-Shop: DATABASE MIGRATIONS WERE *NOT* EXECUTED.</warning>');
        $io->writeError('<warning>!! Reason: ' . $reason . '</warning>');
        $io->writeError('<warning>!! Run these on the target environment after deployment:</warning>');
        $io->writeError('<warning>!!   vendor/bin/oe-eshop-db_migrate migrations:migrate</warning>');
        $io->writeError('<warning>!!   vendor/bin/oe-eshop-db_views_generate</warning>');
        $io->writeError('<warning>' . $border . '</warning>');
    }

    /**
     * Writes a loud error distinguishing a failed migration from a broken composer run.
     */
    protected function writeFailure(IOInterface $io, string $detail): void
    {
        $border = str_repeat('!', 70);
        $io->writeError('<error>' . $border . '</error>');
        $io->writeError('<error>!! O3-Shop: DATABASE MIGRATION FAILED (this is NOT a composer error).</error>');
        $io->writeError('<error>!! ' . $detail . '</error>');
        $io->writeError('<error>!! The shop database may be in an inconsistent state — investigate before use.</error>');
        $io->writeError('<error>' . $border . '</error>');
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `./docker.sh test --fast tests/Unit/Core/MigrationsRunnerTest.php`
Expected: PASS — all 7 tests green.

- [ ] **Step 5: Run cs-fixer**

Run: `./docker.sh cs-fixer`
Expected: No violations in `source/Core/MigrationsRunner.php` / the test file (or auto-fixed).

- [ ] **Step 6: Commit**

```bash
git add source/Core/MigrationsRunner.php tests/Unit/Core/MigrationsRunnerTest.php
git commit -m "feat: add MigrationsRunner composer script with no-DB guard (#192)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: Wire `MigrationsRunner::run` into `post-update-cmd`

**Files:**
- Modify: `composer.json` (`scripts.post-update-cmd`)

- [ ] **Step 1: Append the script as the last `post-update-cmd` entry**

Change the `post-update-cmd` array from:

```json
        "post-update-cmd": [
            "OxidEsales\\EshopCommunity\\Core\\ShopVersionGenerator::generate",
            "OxidEsales\\EshopCommunity\\Core\\IncenteevScriptHandlerWrapper::buildParameters",
            "@oe:ide-helper:generate"
        ],
```

to:

```json
        "post-update-cmd": [
            "OxidEsales\\EshopCommunity\\Core\\ShopVersionGenerator::generate",
            "OxidEsales\\EshopCommunity\\Core\\IncenteevScriptHandlerWrapper::buildParameters",
            "@oe:ide-helper:generate",
            "OxidEsales\\EshopCommunity\\Core\\MigrationsRunner::run"
        ],
```

Leave `post-install-cmd` unchanged (update-only, per the spec).

- [ ] **Step 2: Validate composer.json**

Run: `./docker.sh start` (if not running), then
`docker exec o3shop-192-auto-migrations-1 composer validate --no-check-publish`
Expected: `./composer.json is valid` (warnings about version/license are acceptable).

- [ ] **Step 3: Commit**

```bash
git add composer.json
git commit -m "feat: run database migrations on composer update (#192)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Manual verification & listener-ordering check

This task proves the real wiring works and confirms finding #4 (the plugin's file sync runs before our script). No code changes unless a problem is found.

- [ ] **Step 1: Ensure the environment is up**

Run: `./docker.sh start`
Expected: containers running; shop reachable at http://localhost:8080.

- [ ] **Step 2: Run a real composer update and capture output**

Run: `docker exec o3shop-192-auto-migrations-1 composer update --no-interaction 2>&1 | tee /tmp/cu.log`
Expected: the run completes; near the end you see migration output and
`O3-Shop: database migrations and views are up to date.`

- [ ] **Step 3: Verify listener ordering (plugin sync before migration)**

Run: `grep -nE "Installing|Updating|migrations:migrate|migrations and views are up to date|DATABASE MIGRATIONS" /tmp/cu.log`
Expected: the `shop-composer-plugin` package install/update lines appear **before** the migration/success line. If the migration line appears first, the script ran before file sync — stop and re-plan ordering (e.g. move logic into a plugin subscriber). Document the observed order in the PR.

- [ ] **Step 4: Verify the no-DB skip path (guard works)**

Run: `./docker.sh stop` then
`docker compose -f docker/docker-compose.yml run --rm --no-deps <php-service> composer update --no-interaction 2>&1 | tee /tmp/cu-nodb.log` *(use the project's php/cli service name; the point is to run composer with MySQL down)*.
Expected: within a few seconds (≤ the 2s probe timeout, no long hang) the run finishes and prints the boxed
`O3-Shop: DATABASE MIGRATIONS WERE *NOT* EXECUTED.` warning, exit code 0.
*If running composer with the DB down is impractical in this environment, instead document that the skip path is covered by `testSkipsWhenDatabaseUnreachable` and note the manual step as not run.*

- [ ] **Step 5: Run the full quality gate**

Run: `./docker.sh test-all`
Expected: cs-fixer clean + full unit suite green.

- [ ] **Step 6: Commit any fixes (only if Step 3/4 forced changes)**

```bash
git add -A
git commit -m "fix: adjust auto-migration wiring after verification (#192)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Self-Review notes

- **Spec coverage:** guard order inverted (Task 1 `process()`), short-timeout probe (Task 1 `canConnectToDatabase`), `isLaunched()` (Task 1 `isShopLaunched`), reuse of `MigrationsBuilder->build()->execute(MIGRATE_COMMAND)` with bundled views (Task 1 `runMigrations`), boxed skip warning (Task 1 `writeSkip`), loud distinct failure + non-zero exit (Task 1 `writeFailure` + rethrow), `post-update-cmd`-only wiring (Task 2), listener-ordering verification (Task 3 Step 3), compatibility/skip-path verification (Task 3 Step 4). All spec requirements map to a task.
- **Method-name consistency:** `process`, `configFileExists`, `canConnectToDatabase`, `isShopLaunched`, `loadShopBootstrap`, `runMigrations`, `writeSkip`, `writeFailure` are used identically in the test doubles (Task 1 Step 1) and the implementation (Task 1 Step 3).
- **Container name** `o3shop-192-auto-migrations-1` follows the worktree-branch naming convention; adjust if the actual container name differs.
