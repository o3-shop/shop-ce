<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Cache\ShopConfigurationCacheInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleConfigurationRefreshServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lock, backup and rollback of var/configuration/shops/ shared by the module
 * configuration commands that rewrite it (oe:module:install-configuration and
 * oe:module:refresh-settings), so both roll back the same way.
 */
class ShopConfigurationBackupService
{
    /**
     * @var BasicContextInterface
     */
    private $context;

    /**
     * @var ProjectConfigurationDaoInterface
     */
    private $projectConfigurationDao;

    /**
     * @var ShopConfigurationCacheInterface
     */
    private $shopConfigurationCache;

    /**
     * @var ModuleStateServiceInterface
     */
    private $moduleStateService;

    /**
     * @var ModuleConfigurationRefreshServiceInterface
     */
    private $moduleConfigurationRefreshService;

    public function __construct(
        BasicContextInterface $context,
        ProjectConfigurationDaoInterface $projectConfigurationDao,
        ShopConfigurationCacheInterface $shopConfigurationCache,
        ModuleStateServiceInterface $moduleStateService,
        ModuleConfigurationRefreshServiceInterface $moduleConfigurationRefreshService
    ) {
        $this->context = $context;
        $this->projectConfigurationDao = $projectConfigurationDao;
        $this->shopConfigurationCache = $shopConfigurationCache;
        $this->moduleStateService = $moduleStateService;
        $this->moduleConfigurationRefreshService = $moduleConfigurationRefreshService;
    }

    /**
     * Both commands read, merge and write the same project configuration; two runs must
     * not interleave or the second save silently drops the first one's changes. The lock
     * lives outside the project configuration directory, which may be read-only.
     *
     * @return resource|null the lock handle, or null when the lock is not available
     */
    public function acquireLock(OutputInterface $output)
    {
        $lockFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'o3-refresh-settings-'
            . md5($this->context->getProjectConfigurationDirectory())
            . '.lock';
        $handle = @fopen($lockFile, 'c');

        if ($handle === false) {
            $output->writeln(sprintf('<error>Could not open the lock file %s.</error>', $lockFile));

            return null;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            $output->writeln('<error>Another module configuration command is already in progress.</error>');

            return null;
        }

        return $handle;
    }

    /**
     * @param resource $handle
     */
    public function releaseLock($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * ProjectConfigurationDao::save() deletes the whole shops configuration directory
     * before rewriting it, so the backups live next to it, not inside it.
     *
     * @return string|null the backup directory, or null if the backup could not be created
     */
    public function backup(ProjectConfiguration $projectConfiguration): ?string
    {
        $shopsDirectory = $this->getShopsDirectory();
        $backupDirectory = $this->createUniqueBackupDirectory($shopsDirectory);

        if ($backupDirectory === null) {
            return null;
        }

        foreach ($projectConfiguration->getShopConfigurationIds() as $shopId) {
            $yamlPath = $shopsDirectory . '/' . $shopId . '.yaml';
            if (!is_file($yamlPath)) {
                continue;
            }
            if (!copy($yamlPath, $backupDirectory . '/' . $shopId . '.yaml')) {
                return null;
            }
            // The backup can contain secrets (PayPal client secret etc.); the
            // directory is already 0700, this keeps the files owner-only too.
            @chmod($backupDirectory . '/' . $shopId . '.yaml', 0600);
        }

        return $backupDirectory;
    }

    /**
     * @return string[]
     */
    public function backedUpFiles(string $backupDirectory): array
    {
        $files = [];
        foreach (scandir($backupDirectory) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $files[] = $backupDirectory . '/' . $entry;
        }

        return $files;
    }

    /**
     * Copies the backed up shop files over the live ones. When modules were already
     * refreshed from the configuration being rolled back, their DB rows (templates,
     * blocks, events, extensions, ...) are re-derived from the restored files too, so
     * the database matches the project configuration again.
     *
     * @param array<int, string[]> $refreshedModules module ids per shop id that were refreshed
     *
     * @return bool whether the files were restored
     */
    public function restore(string $backupDirectory, array $refreshedModules, OutputInterface $output): bool
    {
        if (!$this->restoreFiles($backupDirectory)) {
            $output->writeln(sprintf(
                '<error>Could not restore the shop configuration; a copy of the previous state is in %s.</error>',
                $backupDirectory
            ));

            return false;
        }

        $output->writeln('<comment>Restored the shop configuration from the backup.</comment>');
        $this->reapplyRestoredConfiguration($refreshedModules, $output);

        return true;
    }

    public function discard(?string $backupDirectory): void
    {
        if ($backupDirectory === null || !is_dir($backupDirectory)) {
            return;
        }

        foreach ($this->backedUpFiles($backupDirectory) as $backupPath) {
            @unlink($backupPath);
        }

        @rmdir($backupDirectory);
    }

    private function getShopsDirectory(): string
    {
        return $this->context->getProjectConfigurationDirectory() . 'shops';
    }

    /**
     * The directory name is second precise. Two runs in the same second (a script, a retry)
     * must not write into - and overwrite - the same backup directory, so an existing one is
     * never reused. The suffix is unbounded: an artificial cap would fail the run even though
     * a free name is still available.
     */
    private function createUniqueBackupDirectory(string $shopsDirectory): ?string
    {
        $timestamp = date('Ymd-His');
        $attempt = 0;

        while (true) {
            $suffix = $attempt === 0 ? '' : '-' . $attempt;
            $backupDirectory = $shopsDirectory . '.bak.' . $timestamp . $suffix;
            $attempt++;

            if (@mkdir($backupDirectory, 0700, true)) {
                return $backupDirectory;
            }

            // A concurrent run took this name; try the next suffix. Any other failure
            // (permissions, missing parent, disk full) is fatal.
            if (!is_dir($backupDirectory)) {
                return null;
            }
        }
    }

    /**
     * save() deletes the whole shops/ directory before rewriting it, so the directory is
     * recreated here when it is gone. The backup directory stays the source of truth when
     * a copy fails.
     */
    private function restoreFiles(string $backupDirectory): bool
    {
        $shopsDirectory = $this->getShopsDirectory();
        if (!is_dir($shopsDirectory) && !@mkdir($shopsDirectory, 0700, true) && !is_dir($shopsDirectory)) {
            return false;
        }

        $restored = true;

        foreach ($this->backedUpFiles($backupDirectory) as $backupPath) {
            if (!@copy($backupPath, $shopsDirectory . '/' . basename($backupPath))) {
                $restored = false;
            }
        }

        return $restored;
    }

    /**
     * @param array<int, string[]> $refreshedModules
     */
    private function reapplyRestoredConfiguration(array $refreshedModules, OutputInterface $output): void
    {
        if ($refreshedModules === []) {
            return;
        }

        // The refresh repopulated the in-memory shop-configuration cache from the
        // configuration that is being rolled back, so it has to be dropped before the
        // restored files are read.
        foreach (array_keys($refreshedModules) as $shopId) {
            $this->shopConfigurationCache->evict($shopId);
        }

        try {
            $restoredConfiguration = $this->projectConfigurationDao->getConfiguration();
        } catch (\Throwable $exception) {
            $output->writeln(sprintf(
                '<comment>Could not re-read the restored configuration: %s</comment>',
                $exception->getMessage()
            ));

            return;
        }

        foreach ($refreshedModules as $shopId => $moduleIds) {
            foreach ($moduleIds as $id) {
                try {
                    $shopConfiguration = $restoredConfiguration->getShopConfiguration($shopId);
                    if (
                        !$shopConfiguration->hasModuleConfiguration($id)
                        || !$this->moduleStateService->isActive($id, $shopId)
                    ) {
                        continue;
                    }

                    $this->moduleConfigurationRefreshService->refresh(
                        $shopConfiguration->getModuleConfiguration($id),
                        $shopId
                    );
                    $output->writeln(sprintf(
                        '<comment>Reverted %s in shop %d to the backed-up configuration.</comment>',
                        $id,
                        $shopId
                    ));
                } catch (\Throwable $exception) {
                    $output->writeln(sprintf(
                        '<error>Could not revert %s in shop %d: %s</error>',
                        $id,
                        $shopId,
                        $exception->getMessage()
                    ));
                }
            }
        }
    }
}
