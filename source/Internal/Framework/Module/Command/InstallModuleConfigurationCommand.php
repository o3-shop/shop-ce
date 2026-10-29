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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
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
    public const MESSAGE_REFRESH_FAILED = 'Module configuration was installed, but refreshing its settings ' .
        'failed for some shops.';
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
     * @param ModuleConfigurationInstallerInterface $moduleConfigurationInstaller
     * @param BasicContextInterface $context
     * @param ModuleConfigurationDaoInterface $metadataModuleConfigurationDao
     * @param ModuleConfigurationRefreshServiceInterface $moduleConfigurationRefreshService
     * @param ProjectConfigurationDaoInterface $projectConfigurationDao
     */
    public function __construct(
        ModuleConfigurationInstallerInterface $moduleConfigurationInstaller,
        BasicContextInterface $context,
        ModuleConfigurationDaoInterface $metadataModuleConfigurationDao,
        ModuleConfigurationRefreshServiceInterface $moduleConfigurationRefreshService,
        ProjectConfigurationDaoInterface $projectConfigurationDao
    ) {
        $this->moduleConfigurationInstaller = $moduleConfigurationInstaller;
        $this->context = $context;
        $this->metadataModuleConfigurationDao = $metadataModuleConfigurationDao;
        $this->moduleConfigurationRefreshService = $moduleConfigurationRefreshService;
        $this->projectConfigurationDao = $projectConfigurationDao;

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
        $lockHandle = $this->acquireLock($output);
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

                if ($this->refreshActiveModuleConfigurations($moduleSourcePath, $output) !== 0) {
                    $this->restoreAndReport($backupDirectory, $output);

                    return 1;
                }

                $this->deleteBackup($backupDirectory);

                return 0;
            } catch (ModuleTargetPathIsMissingException $exception) {
                $output->writeln('<error>' . self::MESSAGE_TARGET_PATH_IS_REQUIRED . '</error>');
                $this->restoreAndReport($backupDirectory, $output);

                return 1;
            } catch (\Throwable $throwable) {
                $output->writeln('<error>' . self::MESSAGE_INSTALLATION_FAILED . '</error>');
                $this->restoreAndReport($backupDirectory, $output);

                throw $throwable;
            }
        } finally {
            $this->releaseLock($lockHandle);
        }
    }

    /**
     * Installing the configuration of a module that is already active would otherwise
     * leave the shop on the settings it derived from the previous configuration: the
     * stored module settings are only written when a module is activated.
     *
     * @param string          $moduleSourcePath
     * @param OutputInterface $output
     */
    private function refreshActiveModuleConfigurations(string $moduleSourcePath, OutputInterface $output): int
    {
        try {
            $moduleId = $this->metadataModuleConfigurationDao->get($moduleSourcePath)->getId();
            $result = $this->moduleConfigurationRefreshService->refreshActiveModuleWithFailures($moduleId);
        } catch (\Throwable $exception) {
            $output->writeln('<error>' . self::MESSAGE_REFRESH_FAILED . '</error>');
            $output->writeln(sprintf('<error>  - %s</error>', $exception->getMessage()));

            return 1;
        }

        foreach ($result['refreshed'] as $shopId) {
            $output->writeln(sprintf('<info>Refreshed settings of %s in shop %d.</info>', $moduleId, $shopId));
        }

        if ($result['failures'] !== []) {
            $output->writeln('<error>' . self::MESSAGE_REFRESH_FAILED . '</error>');
            foreach ($result['failures'] as $shopId => $message) {
                $output->writeln(sprintf('<error>  - shop %d: %s</error>', $shopId, $message));
            }

            return 1;
        }

        return 0;
    }

    /**
     * @return resource|null
     */
    private function acquireLock(OutputInterface $output)
    {
        // Same lock key as oe:module:refresh-settings: both write the same
        // project configuration and must not interleave.
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
    private function releaseLock($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
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

        $backupDirectory = $this->backupShopConfigurations($projectConfiguration);
        if ($backupDirectory === null) {
            $output->writeln('<error>Could not back up the shop configuration; nothing was written.</error>');

            return [false, null];
        }

        foreach ($this->backedUpFiles($backupDirectory) as $backup) {
            $output->writeln(sprintf('<comment>Backed up %s</comment>', $backup));
        }

        return [true, $backupDirectory];
    }

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
            @chmod($backupDirectory . '/' . $shopId . '.yaml', 0600);
        }

        return $backupDirectory;
    }

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

    private function restoreAndReport(?string $backupDirectory, OutputInterface $output): void
    {
        if ($backupDirectory === null) {
            return;
        }

        $output->writeln(
            $this->restoreShopConfigurations($backupDirectory)
                ? '<comment>Restored the shop configuration from the backup.</comment>'
                : sprintf(
                    '<error>Could not restore the shop configuration; a copy of the previous state is in %s.</error>',
                    $backupDirectory
                )
        );
    }

    private function deleteBackup(?string $backupDirectory): void
    {
        if ($backupDirectory === null || !is_dir($backupDirectory)) {
            return;
        }

        foreach ($this->backedUpFiles($backupDirectory) as $backupPath) {
            @unlink($backupPath);
        }

        @rmdir($backupDirectory);
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
