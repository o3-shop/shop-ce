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
use OxidEsales\EshopCommunity\Internal\Container\BootstrapContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ShopStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\Facts\Facts;

/**
 * Composer post-update script: runs database migrations and view regeneration
 * automatically, but only when a usable database is available.
 *
 * Two deliberate design points:
 *  - The database is probed with a short connect timeout BEFORE calling
 *    ShopStateService::isLaunched(), because that service connects with no PDO
 *    timeout and would otherwise block the whole composer run on an unreachable host.
 *  - Migrations and view regeneration run in SEPARATE PHP subprocesses, never
 *    in-process. Composer executes post-update scripts inside its own runtime,
 *    which already has Composer's bundled (modern) symfony/console loaded.
 *    Building the Doctrine Migrations console application in-process would pick up
 *    that version instead of the shop's symfony/console 3.4 — and
 *    doctrine/migrations 2.x command names (declared via the legacy static
 *    `$defaultName` property) resolve to empty there, throwing "command cannot
 *    have an empty name". A fresh subprocess loads only the shop's autoloader.
 *    See runMigrations().
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
            $this->writeSkip($io, 'no config file found, the shop is not set up yet');
            return;
        }

        if (!$this->canConnectToDatabase()) {
            $this->writeSkip($io, 'no database is reachable');
            return;
        }

        if (!$this->isShopLaunched()) {
            $this->writeSkip($io, 'the shop is not launched yet');
            return;
        }

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
        $connection = null;

        try {
            $connection = DriverManager::getConnection([
                'dbname' => $facts->getDatabaseName(),
                'user' => $facts->getDatabaseUserName(),
                'password' => $facts->getDatabasePassword(),
                'host' => $facts->getDatabaseHost(),
                'port' => $facts->getDatabasePort(),
                'driver' => $facts->getDatabaseDriver(),
                // pdo_mysql honors PDO::ATTR_TIMEOUT as the connect timeout in this stack;
                // PDO::MYSQL_ATTR_CONNECT_TIMEOUT is not defined in this PHP build. Verified empirically.
                'driverOptions' => [\PDO::ATTR_TIMEOUT => self::CONNECT_TIMEOUT_SECONDS],
            ]);
            $connection->executeQuery('SELECT 1');
        } catch (\Throwable $exception) {
            return false;
        } finally {
            if ($connection !== null) {
                $connection->close();
            }
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
     * Runs the database migration, then view regeneration, as SEPARATE PHP
     * subprocesses (see the class docblock for why in-process execution is unsafe
     * from a composer script).
     *
     * Two important details:
     *  - The migrate command is passed with NO edition argument, so migrations for
     *    ALL editions and modules run. The bin reads its second argv as an edition
     *    filter; passing an unrecognised value (e.g. a flag) silently filters every
     *    migration out.
     *  - View regeneration is run explicitly. The migrate command tries to
     *    regenerate views itself, but its internal oxconfig probe builds an invalid
     *    PDO DSN from the Doctrine driver name ('pdo_mysql:...' instead of
     *    'mysql:...'), so that step silently no-ops. Running the views bin
     *    explicitly guarantees a "migrate without views" half-update cannot happen.
     *
     * @return int exit code of the first failing step, or 0 when both succeed.
     */
    protected function runMigrations(IOInterface $io): int
    {
        $io->write('<info>O3-Shop: running database migrations and view regeneration ...</info>');

        $migrateExitCode = $this->runShopBinary('oe-eshop-db_migrate', ['migrations:migrate']);
        if ($migrateExitCode !== 0) {
            return $migrateExitCode;
        }

        return $this->runShopBinary('oe-eshop-db_views_generate', []);
    }

    /**
     * Runs a shop vendor binary in a fresh PHP subprocess, streaming its output.
     *
     * @param string   $binaryName name of the executable in vendor/bin.
     * @param string[] $arguments  arguments to pass to the binary.
     *
     * @return int the subprocess exit code.
     */
    protected function runShopBinary(string $binaryName, array $arguments): int
    {
        $binaryPath = (new Facts())->getVendorPath() . '/bin/' . $binaryName;

        $commandParts = array_merge([PHP_BINARY, $binaryPath], $arguments);
        $command = implode(' ', array_map('escapeshellarg', $commandParts));

        $exitCode = 0;
        passthru($command, $exitCode);

        return $exitCode;
    }

    /**
     * Writes a calm single-line notice that migrations were skipped, naming the
     * reason and the manual commands to run on the target environment.
     *
     * A skip is the *expected* state for many composer runs (no DB in CI, on a
     * dev box, or in the build step of a build-once/deploy-many pipeline), so it
     * is a quiet notice rather than a loud box — the box is reserved for genuine
     * failures (see writeFailure()).
     */
    protected function writeSkip(IOInterface $io, string $reason): void
    {
        $io->writeError(
            '<comment>O3-Shop: skipping database migrations (' . $reason . '). '
            . 'Run "vendor/bin/oe-eshop-db_migrate migrations:migrate" and '
            . '"vendor/bin/oe-eshop-db_views_generate" on the target environment after deployment.</comment>'
        );
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
