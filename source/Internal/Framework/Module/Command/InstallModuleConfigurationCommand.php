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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Command;

use Doctrine\DBAL\DBALException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ProjectConfigurationIsEmptyException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleConfigurationRefreshServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * @deprecated command will be superseded by oe:module:install in next major
 */
class InstallModuleConfigurationCommand extends Command
{
    public const MESSAGE_INSTALLATION_WAS_SUCCESSFUL = 'Module configuration has been installed.';
    public const MESSAGE_INSTALLATION_FAILED = 'An error occurred while installing module configuration.';
    public const MESSAGE_REFRESH_FAILED = 'Refreshing the module settings failed for some shops.';
    public const MESSAGE_ROLLED_BACK = 'The module configuration installation was rolled back.';
    public const MESSAGE_REFRESH_SKIPPED = 'The database is not reachable, so the settings of active modules were '
        . 'not refreshed. Run oe:module:refresh-settings once the shop is set up.';
    public const MESSAGE_TARGET_PATH_IS_REQUIRED = 'The given module source path is not inside the shop modules ' .
        'directory. Please provide a second parameter with the modules ' .
        'target path inside the shop modules directory.';

    /**
     * @var ModuleConfigurationInstallerInterface
     */
    private $moduleConfigurationInstaller;

    /**
     * @var BasicContextInterface
     */
    private $context;

    /**
     * @var ModuleConfigurationDaoInterface
     */
    private $metadataModuleConfigurationDao;

    /**
     * @var ModuleConfigurationRefreshServiceInterface
     */
    private $moduleConfigurationRefreshService;

    /**
     * @var ProjectConfigurationDaoInterface
     */
    private $projectConfigurationDao;

    /**
     * @var ShopConfigurationBackupService
     */
    private $shopConfigurationBackupService;

    /**
     * @param ModuleConfigurationInstallerInterface $moduleConfigurationInstaller
     * @param BasicContextInterface $context
     * @param ModuleConfigurationDaoInterface $metadataModuleConfigurationDao
     * @param ModuleConfigurationRefreshServiceInterface $moduleConfigurationRefreshService
     * @param ProjectConfigurationDaoInterface $projectConfigurationDao
     * @param ShopConfigurationBackupService $shopConfigurationBackupService
     */
    public function __construct(
        ModuleConfigurationInstallerInterface $moduleConfigurationInstaller,
        BasicContextInterface $context,
        ModuleConfigurationDaoInterface $metadataModuleConfigurationDao,
        ModuleConfigurationRefreshServiceInterface $moduleConfigurationRefreshService,
        ProjectConfigurationDaoInterface $projectConfigurationDao,
        ShopConfigurationBackupService $shopConfigurationBackupService
    ) {
        $this->moduleConfigurationInstaller = $moduleConfigurationInstaller;
        $this->context = $context;
        $this->metadataModuleConfigurationDao = $metadataModuleConfigurationDao;
        $this->moduleConfigurationRefreshService = $moduleConfigurationRefreshService;
        $this->projectConfigurationDao = $projectConfigurationDao;
        $this->shopConfigurationBackupService = $shopConfigurationBackupService;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setName(
                'oe:module:install-configuration'
            )
            ->setDescription(
                'Install module configuration into project configuration file.'
                . 'Module configuration already present in the project configuration file will be overwritten.'
            )
            ->addArgument(
                'module-source-path',
                InputArgument::REQUIRED,
                'Path to module source, e.g. vendor/my_vendor/my_module;'
                . ' If the module source path is in source/modules directory, e.g. source/modules/vendor/my_module 
                then module-target-path is optional'
            )
            ->addArgument(
                'module-target-path',
                InputArgument::OPTIONAL,
                'Path to module target, e.g. source/modules/vendor/my_module'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lockHandle = $this->shopConfigurationBackupService->acquireLock($output);
        if ($lockHandle === null) {
            return 1;
        }

        $backupDirectory = null;

        try {
            try {
                $moduleSourcePath = $this->getModuleSourcePath($input);
                $this->validatePath($moduleSourcePath);

                $moduleTargetPath = $this->getModuleTargetPath($input);
                $this->validatePath($moduleTargetPath);

                [$backupOk, $backupDirectory] = $this->backupProjectConfiguration($output);
                if (!$backupOk) {
                    return 1;
                }

                $this->moduleConfigurationInstaller->install($moduleSourcePath, $moduleTargetPath);
                $output->writeln('<info>' . self::MESSAGE_INSTALLATION_WAS_SUCCESSFUL . '</info>');

                $touchedModules = $this->refreshActiveModuleConfigurations($moduleSourcePath, $output);
                if ($touchedModules !== null) {
                    $this->rollBack($backupDirectory, $touchedModules, $output);

                    return 1;
                }

                $this->shopConfigurationBackupService->discard($backupDirectory);

                return 0;
            } catch (ModuleTargetPathIsMissingException $exception) {
                $output->writeln('<error>' . self::MESSAGE_TARGET_PATH_IS_REQUIRED . '</error>');
                $this->rollBack($backupDirectory, [], $output);

                return 1;
            } catch (\Throwable $throwable) {
                $output->writeln('<error>' . self::MESSAGE_INSTALLATION_FAILED . '</error>');
                $this->rollBack($backupDirectory, [], $output);

                throw $throwable;
            }
        } finally {
            $this->shopConfigurationBackupService->releaseLock($lockHandle);
        }
    }

    /**
     * Installing the configuration of a module that is already active would otherwise
     * leave the shop on the settings it derived from the previous configuration: the
     * stored module settings are only written when a module is activated.
     *
     * Without a reachable database (image build, provisioning before oe:setup) no module
     * can be active yet, so the refresh is skipped and the install stays file-only.
     *
     * @param string          $moduleSourcePath
     * @param OutputInterface $output
     *
     * @return array<int, string[]>|null null on success, otherwise the module ids per shop
     *                                    whose DB state the refresh touched
     */
    private function refreshActiveModuleConfigurations(string $moduleSourcePath, OutputInterface $output): ?array
    {
        try {
            $moduleId = $this->metadataModuleConfigurationDao->get($moduleSourcePath)->getId();
            $result = $this->moduleConfigurationRefreshService->refreshActiveModuleWithFailures($moduleId);
        } catch (DBALException $exception) {
            $output->writeln('<comment>' . self::MESSAGE_REFRESH_SKIPPED . '</comment>');
            $output->writeln(sprintf('<comment>  - %s</comment>', $exception->getMessage()));

            return null;
        } catch (\Throwable $exception) {
            $output->writeln('<error>' . self::MESSAGE_REFRESH_FAILED . '</error>');
            $output->writeln(sprintf('<error>  - %s</error>', $exception->getMessage()));

            return [];
        }

        foreach ($result['refreshed'] as $shopId) {
            $output->writeln(sprintf('<info>Refreshed settings of %s in shop %d.</info>', $moduleId, $shopId));
        }

        if ($result['failures'] === []) {
            return null;
        }

        $output->writeln('<error>' . self::MESSAGE_REFRESH_FAILED . '</error>');
        foreach ($result['failures'] as $shopId => $message) {
            $output->writeln(sprintf('<error>  - shop %d: %s</error>', $shopId, $message));
        }

        // A failed refresh may have written some DB rows before it threw, so the failed
        // shops are reverted too, not only the ones that were refreshed completely.
        $touchedModules = [];
        foreach (array_merge($result['refreshed'], array_keys($result['failures'])) as $shopId) {
            $touchedModules[$shopId] = [$moduleId];
        }

        return $touchedModules;
    }

    /**
     * @param array<int, string[]> $touchedModules
     */
    private function rollBack(?string $backupDirectory, array $touchedModules, OutputInterface $output): void
    {
        if ($backupDirectory === null) {
            return;
        }

        if ($this->shopConfigurationBackupService->restore($backupDirectory, $touchedModules, $output)) {
            $output->writeln('<comment>' . self::MESSAGE_ROLLED_BACK . '</comment>');
        }
    }

    /**
     * @return array{0: bool, 1: string|null} [success, backup directory]
     */
    private function backupProjectConfiguration(OutputInterface $output): array
    {
        try {
            $projectConfiguration = $this->projectConfigurationDao->getConfiguration();
        } catch (ProjectConfigurationIsEmptyException $exception) {
            // First install: there is no configuration to back up yet.
            return [true, null];
        }

        $backupDirectory = $this->shopConfigurationBackupService->backup($projectConfiguration);
        if ($backupDirectory === null) {
            $output->writeln('<error>Could not back up the shop configuration; nothing was written.</error>');

            return [false, null];
        }

        foreach ($this->shopConfigurationBackupService->backedUpFiles($backupDirectory) as $backup) {
            $output->writeln(sprintf('<comment>Backed up %s</comment>', $backup));
        }

        return [true, $backupDirectory];
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    private function getModuleSourcePath(InputInterface $input): string
    {
        return $this->getAbsolutePath($input->getArgument('module-source-path'));
    }

    /**
     * @param InputInterface $input
     * @return string
     * @throws ModuleTargetPathIsMissingException
     */
    private function getModuleTargetPath(InputInterface $input): string
    {
        $moduleTargetPath = $input->getArgument('module-target-path');
        if ($moduleTargetPath !== null) {
            return $this->getAbsolutePath($moduleTargetPath);
        }

        $moduleSourcePath = $this->getModuleSourcePath($input);
        if ($this->isDirectoryInsideShopModulesDirectory($moduleSourcePath)) {
            return $moduleSourcePath;
        }

        throw new ModuleTargetPathIsMissingException();
    }

    /**
     * @param string $path
     * @return bool
     */
    private function isDirectoryInsideShopModulesDirectory(string $path): bool
    {
        if (Path::isRelative($path)) {
            $path = Path::join($this->context->getShopRootPath(), $path);
        }

        return Path::isBasePath($this->context->getModulesPath(), $path);
    }

    /**
     * @param string $path
     */
    private function validatePath(string $path)
    {
        if (!file_exists($path) || !is_dir($path)) {
            throw new \InvalidArgumentException($path . ' directory doesn\'t exist');
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function getAbsolutePath(string $path): string
    {
        return Path::isRelative($path)
            ? Path::makeAbsolute($path, getcwd())
            : $path;
    }
}
