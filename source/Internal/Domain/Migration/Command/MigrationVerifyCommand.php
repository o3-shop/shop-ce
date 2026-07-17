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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Migration\Command;

use OxidEsales\EshopCommunity\Internal\Domain\Migration\Service\ComposerLockInspectorInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Migration\Service\MigrationStateServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Post-migration health check for an OXID 6.4.3 -> o3-shop migration
 * (o3-shop/o3-shop#152). Runs a set of pass/fail checks and exits non-zero
 * if any of them fail, so it can gate a scripted or CI migration run:
 *
 *   - the migration tracking table exists (the migrator has run at all);
 *   - no shipped migration is still pending (schema fully up to date);
 *   - no upstream oxid-esales/* package is left in composer.lock (the
 *     package swap actually happened).
 *
 * Usage:
 *   bin/oe-console oe:migrate:verify
 */
final class MigrationVerifyCommand extends Command
{
    public const EXIT_OK = 0;
    public const EXIT_FAILED = 1;

    /**
     * Upstream package prefix that must be fully gone once the swap to
     * o3-shop is complete.
     */
    private const UPSTREAM_PACKAGE_PREFIX = 'oxid-esales/';

    /** @var string|null */
    protected static $defaultName = 'oe:migrate:verify';

    private MigrationStateServiceInterface $migrationStateService;
    private ComposerLockInspectorInterface $composerLockInspector;

    public function __construct(
        MigrationStateServiceInterface $migrationStateService,
        ComposerLockInspectorInterface $composerLockInspector
    ) {
        parent::__construct(null);
        $this->migrationStateService = $migrationStateService;
        $this->composerLockInspector = $composerLockInspector;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Verify the database migration finished cleanly; exits non-zero on any failure.')
            ->setHelp(
                "Runs post-migration sanity checks and prints a green/red list:\n"
                . "  - migration tracking table present\n"
                . "  - no pending migrations\n"
                . "  - no leftover oxid-esales/* packages in composer.lock\n\n"
                . 'Exits with a non-zero status if any check fails, for use in scripts and CI.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $failed = false;
        $failed = !$this->checkTrackingTable($output) || $failed;
        $failed = !$this->checkNoPendingMigrations($output) || $failed;
        $failed = !$this->checkNoUpstreamPackages($output) || $failed;

        $output->writeln('');
        if ($failed) {
            $output->writeln('<error>Migration verification failed. See the red items above.</error>');
            return self::EXIT_FAILED;
        }

        $output->writeln('<info>Migration verification passed.</info>');
        return self::EXIT_OK;
    }

    private function checkTrackingTable(OutputInterface $output): bool
    {
        if ($this->migrationStateService->isTracked()) {
            return $this->pass($output, 'Migration tracking table (oxmigrations_ce) present.');
        }

        return $this->fail(
            $output,
            'No migration tracking table (oxmigrations_ce). The migrator has not run on this database.'
        );
    }

    private function checkNoPendingMigrations(OutputInterface $output): bool
    {
        $pending = $this->migrationStateService->getPendingVersions();
        if ($pending === []) {
            return $this->pass($output, 'No pending migrations — schema is up to date.');
        }

        return $this->fail($output, sprintf(
            '%d migration(s) still pending: %s. Run vendor/bin/oe-eshop-db_migrate migrations:migrate.',
            count($pending),
            implode(', ', $pending)
        ));
    }

    private function checkNoUpstreamPackages(OutputInterface $output): bool
    {
        if (!$this->composerLockInspector->exists()) {
            return $this->skip($output, 'composer.lock not found — skipping upstream-package check.');
        }

        $leftover = $this->composerLockInspector->findInstalledPackagesByPrefix(self::UPSTREAM_PACKAGE_PREFIX);
        if ($leftover === []) {
            return $this->pass($output, 'No upstream oxid-esales/* packages left in composer.lock.');
        }

        return $this->fail($output, sprintf(
            'Upstream packages still installed: %s. The OXID -> o3-shop swap is incomplete.',
            implode(', ', $leftover)
        ));
    }

    private function pass(OutputInterface $output, string $message): bool
    {
        $output->writeln(sprintf('<info>[OK]</info>   %s', $message));
        return true;
    }

    private function fail(OutputInterface $output, string $message): bool
    {
        $output->writeln(sprintf('<error>[FAIL]</error> %s', $message));
        return false;
    }

    private function skip(OutputInterface $output, string $message): bool
    {
        $output->writeln(sprintf('<comment>[SKIP]</comment> %s', $message));
        return true;
    }
}
