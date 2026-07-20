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

use OxidEsales\EshopCommunity\Internal\Domain\Migration\Service\MigrationStateServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reports the database migration state: the current applied schema version,
 * the latest version this source tree ships, and any migrations still
 * pending. Read-only — a safe pre- and post-check when migrating an OXID
 * 6.4.3 install up to current o3-shop (o3-shop/o3-shop#152).
 *
 * Usage:
 *   bin/oe-console oe:migrate:status
 */
final class MigrationStatusCommand extends Command
{
    public const EXIT_OK = 0;

    /** @var string|null */
    protected static $defaultName = 'oe:migrate:status';

    private MigrationStateServiceInterface $migrationStateService;

    public function __construct(MigrationStateServiceInterface $migrationStateService)
    {
        parent::__construct(null);
        $this->migrationStateService = $migrationStateService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Show the database migration state (current, latest and pending versions).')
            ->setHelp(
                "Read-only report of how far the database schema has been migrated.\n"
                . "Compares the migration files shipped in source/migration/data against\n"
                . "the versions recorded as applied in the oxmigrations_ce table.\n\n"
                . 'Use oe:migrate:verify for a pass/fail health check with a non-zero exit.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->migrationStateService->isTracked()) {
            $output->writeln(
                '<comment>No migration tracking table (oxmigrations_ce) found. '
                . 'This database has not been migrated yet.</comment>'
            );
        }

        $current = $this->migrationStateService->getCurrentVersion();
        $latest = $this->migrationStateService->getLatestAvailableVersion();
        $pending = $this->migrationStateService->getPendingVersions();
        $unknown = $this->migrationStateService->getUnknownVersions();

        $output->writeln(sprintf('Current applied version: <info>%s</info>', $current ?? '(none)'));
        $output->writeln(sprintf('Latest available version: <info>%s</info>', $latest ?? '(none)'));
        $output->writeln(sprintf('Applied migrations: <info>%d</info>', count($this->migrationStateService->getExecutedVersions())));
        $output->writeln(sprintf('Available migrations: <info>%d</info>', count($this->migrationStateService->getAvailableVersions())));

        if ($pending === []) {
            $output->writeln('Pending migrations: <info>none</info> — schema is up to date.');
        } else {
            $output->writeln(sprintf('<comment>Pending migrations: %d</comment>', count($pending)));
            foreach ($pending as $version) {
                $output->writeln(sprintf('  - %s', $version));
            }
        }

        if ($unknown !== []) {
            $output->writeln(sprintf(
                '<comment>Applied versions not present in this source tree: %s</comment>',
                implode(', ', $unknown)
            ));
        }

        return self::EXIT_OK;
    }
}
