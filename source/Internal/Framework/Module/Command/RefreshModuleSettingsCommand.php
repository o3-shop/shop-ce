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
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service\ModuleConfigurationMergingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface as MetaDataModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolverInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleConfigurationHandlingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleConfigurationRefreshServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Re-applies the module configuration a shop is running on.
 *
 * Module settings derived from metadata.php (templates, blocks, controllers, class
 * extensions, events, services) are only written when a module is activated, so a module
 * whose metadata.php changed in place - an update, or a locally patched module - keeps
 * serving the old registration until it is deactivated and activated again. That cycle
 * deletes and re-seeds the module settings configured by the shop owner, which is why it
 * is not a usable refresh. This command re-derives the stored settings without touching
 * the activation state or the configured settings.
 */
class RefreshModuleSettingsCommand extends Command
{
    /**
     * @var ProjectConfigurationDaoInterface
     */
    private $projectConfigurationDao;

    /**
     * @var ShopConfigurationCacheInterface
     */
    private $shopConfigurationCache;

    /**
     * @var ModuleConfigurationHandlingServiceInterface
     */
    private $moduleConfigurationHandlingService;

    /**
     * @var ModuleConfigurationMergingServiceInterface
     */
    private $moduleConfigurationMergingService;

    /**
     * @var ModuleStateServiceInterface
     */
    private $moduleStateService;

    /**
     * @var ModuleConfigurationRefreshServiceInterface
     */
    private $moduleConfigurationRefreshService;

    /**
     * @var ModulePathResolverInterface
     */
    private $modulePathResolver;

    /**
     * @var MetaDataModuleConfigurationDaoInterface
     */
    private $metadataModuleConfigurationDao;

    /**
     * @var BasicContextInterface
     */
    private $context;

    /**
     * @param ProjectConfigurationDaoInterface               $projectConfigurationDao
     * @param ModuleConfigurationMergingServiceInterface     $moduleConfigurationMergingService
     * @param ModuleStateServiceInterface                    $moduleStateService
     * @param ModuleConfigurationRefreshServiceInterface     $moduleConfigurationRefreshService
     * @param ModulePathResolverInterface                    $modulePathResolver
     * @param MetaDataModuleConfigurationDaoInterface        $metadataModuleConfigurationDao
     * @param BasicContextInterface                          $context
     * @param ModuleConfigurationHandlingServiceInterface    $moduleConfigurationHandlingService
     * @param ShopConfigurationCacheInterface                $shopConfigurationCache
     */
    public function __construct(
        ProjectConfigurationDaoInterface $projectConfigurationDao,
        ModuleConfigurationMergingServiceInterface $moduleConfigurationMergingService,
        ModuleStateServiceInterface $moduleStateService,
        ModuleConfigurationRefreshServiceInterface $moduleConfigurationRefreshService,
        ModulePathResolverInterface $modulePathResolver,
        MetaDataModuleConfigurationDaoInterface $metadataModuleConfigurationDao,
        BasicContextInterface $context,
        ModuleConfigurationHandlingServiceInterface $moduleConfigurationHandlingService,
        ShopConfigurationCacheInterface $shopConfigurationCache
    ) {
        parent::__construct();

        $this->projectConfigurationDao = $projectConfigurationDao;
        $this->moduleConfigurationMergingService = $moduleConfigurationMergingService;
        $this->moduleStateService = $moduleStateService;
        $this->moduleConfigurationRefreshService = $moduleConfigurationRefreshService;
        $this->modulePathResolver = $modulePathResolver;
        $this->metadataModuleConfigurationDao = $metadataModuleConfigurationDao;
        $this->context = $context;
        $this->moduleConfigurationHandlingService = $moduleConfigurationHandlingService;
        $this->shopConfigurationCache = $shopConfigurationCache;
    }

    protected function configure(): void
    {
        $this
            ->setName('oe:module:refresh-settings')
            ->setDescription(
                'Re-applies the installed module configuration to the shop without changing '
                . 'module activation state. Settings configured in the admin are kept.'
            )
            ->addArgument(
                'module-id',
                InputArgument::OPTIONAL,
                'Module to refresh. Without it every module configured for the shop is refreshed.'
            )
            ->addOption(
                'shop-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Shop to refresh. Defaults to all shops.'
            )
            ->addOption(
                'from-metadata',
                null,
                InputOption::VALUE_NONE,
                'Re-read metadata.php from the module directory instead of using the installed '
                . 'configuration, for modules whose metadata.php changed on disk.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Read-merge-write must not interleave with another run: both would read
        // the same project configuration and the second save would silently drop
        // the first one's changes.
        $lockHandle = $this->acquireLock($output);
        if ($lockHandle === null) {
            return 1;
        }

        try {
            return $this->executeLocked($input, $output);
        } finally {
            $this->releaseLock($lockHandle);
        }
    }

    private function executeLocked(InputInterface $input, OutputInterface $output): int
    {
        $moduleId = $input->getArgument('module-id');
        $fromMetadata = (bool)$input->getOption('from-metadata');
        $projectConfiguration = $this->projectConfigurationDao->getConfiguration();
        $shopIds = $this->getShopIds($input, $output, $projectConfiguration);

        if ($shopIds === []) {
            return 1;
        }

        $refreshTargets = [];
        $foundConfigured = false;
        $skipped = [];

        foreach ($shopIds as $shopId) {
            $shopConfiguration = $projectConfiguration->getShopConfiguration($shopId);

            foreach ($this->getModuleIdsToRefresh($shopConfiguration, $moduleId) as $id) {
                $foundConfigured = true;

                if (!$this->moduleStateService->isActive($id, $shopId)) {
                    $output->writeln(
                        sprintf('<comment>Skipped %s in shop %d: module is not active.</comment>', $id, $shopId)
                    );
                    continue;
                }

                if ($fromMetadata) {
                    // Merge the on-disk metadata into the project configuration first: the
                    // class-extension chain is rebuilt from the project configuration, so
                    // refreshing it from metadata without this step would leave aModules stale.
                    // A module with unreadable metadata must not stop the other modules from
                    // being refreshed, so it is reported and skipped instead.
                    try {
                        $metadataModuleConfiguration = $this->getModuleConfiguration($shopConfiguration, $id, $shopId, true);
                        // A metadata.php that was edited to a different id would be merged as a
                        // second module next to the installed one and the installed id would still
                        // be refreshed, silently wiring up a duplicate. Bail out instead.
                        if ($metadataModuleConfiguration->getId() !== $id) {
                            throw new \RuntimeException(sprintf(
                                'metadata.php declares id "%s" but the module is installed as "%s"',
                                $metadataModuleConfiguration->getId(),
                                $id
                            ));
                        }
                        $this->moduleConfigurationMergingService->merge($shopConfiguration, $metadataModuleConfiguration);
                    } catch (\Throwable $exception) {
                        $skipped[] = sprintf('%s (shop %d): %s', $id, $shopId, $exception->getMessage());
                        $output->writeln(sprintf(
                            '<comment>Skipped %s in shop %d: %s</comment>',
                            $id,
                            $shopId,
                            $exception->getMessage()
                        ));
                        continue;
                    }
                }

                $refreshTargets[$shopId][] = $id;
            }
        }

        // Validate every merged configuration before anything is written. The validators
        // run again inside refresh(), but by then the file has already been saved and a
        // failure would leave invalid configuration on disk.
        if ($fromMetadata && $refreshTargets !== []) {
            $invalid = $this->validateMergedModules($projectConfiguration, $refreshTargets);
            if ($invalid !== []) {
                foreach ($invalid as $failure) {
                    $output->writeln(sprintf('<error>%s</error>', $failure));
                }
                $output->writeln('<error>Nothing was written.</error>');

                return 1;
            }
        }

        // Persist the merged metadata once per run. ProjectConfigurationDao::save()
        // rewrites every shop file (delete-all, then save each shop), so calling it
        // inside the per-module loop would wipe the configuration written by the
        // previous iterations. Back the files up first, then write once. The backup is
        // the only way back if the save or a refresh fails, so a failed backup must stop
        // the run before anything is written.
        $backupDirectory = null;
        if ($fromMetadata && $refreshTargets !== []) {
            $backupDirectory = $this->backupShopConfigurations($projectConfiguration);
            if ($backupDirectory === null) {
                $output->writeln('<error>Could not back up the shop configuration; nothing was written.</error>');
                return 1;
            }
            foreach ($this->backedUpFiles($backupDirectory) as $backup) {
                $output->writeln(sprintf('<comment>Backed up %s</comment>', $backup));
            }
            try {
                $this->projectConfigurationDao->save($projectConfiguration);
            } catch (\Throwable $exception) {
                // save() deletes the whole shops/ directory before it rewrites it, so a
                // failure here can leave the configuration empty or half-written. Put the
                // backup back before reporting the error.
                $output->writeln(sprintf(
                    '<error>Could not write the shop configuration: %s</error>',
                    $exception->getMessage()
                ));
                $output->writeln(
                    $this->restoreShopConfigurations($backupDirectory)
                        ? '<comment>Restored the shop configuration from the backup.</comment>'
                        : sprintf(
                            '<error>Could not restore the shop configuration; a copy of the previous state is in %s.</error>',
                            $backupDirectory
                        )
                );

                return 1;
            }
        }

        $refreshedCount = 0;
        $refreshFailures = [];

        foreach ($refreshTargets as $shopId => $moduleIds) {
            foreach ($moduleIds as $id) {
                // The configuration was already written above, so a module whose refresh
                // fails at handler level must not stop the remaining modules. It is
                // reported and the run rolls the written state back afterwards.
                try {
                    $moduleConfiguration = $projectConfiguration
                        ->getShopConfiguration($shopId)
                        ->getModuleConfiguration($id);

                    $this->moduleConfigurationRefreshService->refresh($moduleConfiguration, $shopId);
                    $refreshedCount++;

                    $output->writeln(sprintf('<info>Refreshed settings of %s in shop %d.</info>', $id, $shopId));
                } catch (\Throwable $exception) {
                    $refreshFailures[$shopId][$id] = $exception->getMessage();
                    $output->writeln(sprintf(
                        '<error>Failed to refresh %s in shop %d: %s</error>',
                        $id,
                        $shopId,
                        $exception->getMessage()
                    ));
                }
            }
        }

        if ($refreshFailures !== [] && $backupDirectory !== null) {
            $output->writeln(
                $this->restoreShopConfigurations($backupDirectory)
                    ? '<comment>Restored the shop configuration from the backup.</comment>'
                    : sprintf(
                        '<error>Could not restore the shop configuration; a copy of the previous state is in %s.</error>',
                        $backupDirectory
                    )
            );
            // The refresh that ran before the failure already wrote DB rows (templates,
            // blocks, events, extensions, ...). Re-apply the restored configuration so the
            // database matches the restored files again.
            $this->reapplyRestoredConfiguration($refreshTargets, $output);
        }

        if ($moduleId !== null && !$foundConfigured) {
            $output->writeln(sprintf('<error>Module %s is not configured for any shop.</error>', $moduleId));
            return 1;
        }

        if ($skipped !== []) {
            $output->writeln(sprintf('<error>Skipped %d module(s):</error>', count($skipped)));
            foreach ($skipped as $skippedModule) {
                $output->writeln(sprintf('<error>  - %s</error>', $skippedModule));
            }
        }

        $output->writeln(sprintf('Modules refreshed: %d', $refreshedCount));

        return $skipped === [] && $refreshFailures === [] ? 0 : 1;
    }

    /**
     * @return resource|null
     */
    private function acquireLock(OutputInterface $output)
    {
        // Keep the lock out of the project configuration directory: that directory
        // may be read-only, and the lock must still work for the backup-failure path.
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
            $output->writeln('<error>Another oe:module:refresh-settings run is already in progress.</error>');

            return null;
        }

        return $handle;
    }

    /**
     * @param resource $handle
     */
    private function releaseLock($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * @param array<int, string[]> $refreshTargets
     *
     * @return string[]
     */
    private function validateMergedModules(
        ProjectConfiguration $projectConfiguration,
        array $refreshTargets
    ): array {
        $invalid = [];

        foreach ($refreshTargets as $shopId => $moduleIds) {
            foreach ($moduleIds as $id) {
                try {
                    $this->moduleConfigurationHandlingService->validate(
                        $projectConfiguration->getShopConfiguration($shopId)->getModuleConfiguration($id),
                        $shopId
                    );
                } catch (\Throwable $exception) {
                    $invalid[] = sprintf('%s (shop %d): %s', $id, $shopId, $exception->getMessage());
                }
            }
        }

        return $invalid;
    }

    /**
     * @param array<int, string[]> $refreshTargets
     */
    private function reapplyRestoredConfiguration(
        array $refreshTargets,
        OutputInterface $output
    ): void {
        // The refresh loop repopulated the in-memory shop-configuration cache from the
        // configuration that was just written. The files restored above are the old ones,
        // so the cache has to be dropped before the configuration is read again.
        foreach (array_keys($refreshTargets) as $shopId) {
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

        foreach ($refreshTargets as $shopId => $moduleIds) {
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

    /**
     * ProjectConfigurationDao::save() deletes the whole shops configuration directory
     * before rewriting it, so the backups must live next to it, not inside it.
     *
     * @return string|null the backup directory, or null if the backup could not be created
     */
    private function backupShopConfigurations(ProjectConfiguration $projectConfiguration): ?string
    {
        $shopsDirectory = $this->context->getProjectConfigurationDirectory() . 'shops';
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
     * @return string[]
     */
    private function backedUpFiles(string $backupDirectory): array
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
     * Copies the backed up shop files over the live ones, undoing a save() that failed
     * halfway. The saved files are kept in place when a copy fails so the backup
     * directory remains the source of truth. save() deletes the whole shops/ directory
     * before rewriting it, so the directory is recreated here when it is gone.
     */
    private function restoreShopConfigurations(string $backupDirectory): bool
    {
        $shopsDirectory = $this->context->getProjectConfigurationDirectory() . 'shops';
        if (!is_dir($shopsDirectory) && !@mkdir($shopsDirectory, 0700, true) && !is_dir($shopsDirectory)) {
            return false;
        }

        $restored = true;

        foreach ($this->backedUpFiles($backupDirectory) as $backupPath) {
            $target = $shopsDirectory . '/' . basename($backupPath);
            if (!@copy($backupPath, $target)) {
                $restored = false;
            }
        }

        return $restored;
    }

    /**
     * @param InputInterface      $input
     * @param OutputInterface     $output
     * @param ProjectConfiguration $projectConfiguration
     *
     * @return int[]
     */
    private function getShopIds(
        InputInterface $input,
        OutputInterface $output,
        ProjectConfiguration $projectConfiguration
    ): array {
        $shopId = $input->getOption('shop-id');

        if ($shopId === null) {
            $configuredShopIds = $projectConfiguration->getShopConfigurationIds();
            $shopIds = [];

            foreach ($this->context->getAllShopIds() as $contextShopId) {
                $contextShopId = (int)$contextShopId;
                if (in_array($contextShopId, $configuredShopIds, true)) {
                    $shopIds[] = $contextShopId;
                } else {
                    // The database knows this shop but var/configuration/shops/ has no
                    // file for it, so getShopConfiguration() would throw. Skip it.
                    $output->writeln(sprintf(
                        '<comment>Skipping shop %d: no project configuration found.</comment>',
                        $contextShopId
                    ));
                }
            }

            return $shopIds;
        }

        if (!in_array((int)$shopId, $projectConfiguration->getShopConfigurationIds(), true)) {
            $output->writeln(sprintf('<error>Shop %d does not exist.</error>', (int)$shopId));
            return [];
        }

        return [(int)$shopId];
    }

    /**
     * @param ShopConfiguration $shopConfiguration
     * @param string|null       $moduleId
     *
     * @return string[]
     */
    private function getModuleIdsToRefresh(ShopConfiguration $shopConfiguration, ?string $moduleId): array
    {
        if ($moduleId !== null) {
            return $shopConfiguration->hasModuleConfiguration($moduleId) ? [$moduleId] : [];
        }

        return $shopConfiguration->getModuleIdsOfModuleConfigurations();
    }

    /**
     * @param ShopConfiguration $shopConfiguration
     * @param string            $moduleId
     * @param int               $shopId
     * @param bool              $fromMetadata
     *
     * @return ModuleConfiguration
     */
    private function getModuleConfiguration(
        ShopConfiguration $shopConfiguration,
        string $moduleId,
        int $shopId,
        bool $fromMetadata
    ): ModuleConfiguration {
        $moduleConfiguration = $shopConfiguration->getModuleConfiguration($moduleId);

        if (!$fromMetadata) {
            return $moduleConfiguration;
        }

        $metadataModuleConfiguration = $this->metadataModuleConfigurationDao->get(
            $this->modulePathResolver->getFullModulePathFromConfiguration($moduleId, $shopId)
        );
        // The module path is not part of metadata.php; keep the installed one so the
        // refreshed settings keep pointing at the directory the shop loads the module from.
        $metadataModuleConfiguration->setPath($moduleConfiguration->getPath());

        return $metadataModuleConfiguration;
    }
}
