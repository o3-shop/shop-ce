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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Service\ModuleConfigurationMergingServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Webmozart\PathUtil\Path;

class RebuildModuleConfigurationCommand extends Command
{
    /** Maximum directory depth to search for metadata.php (vendor/module = 2 levels). */
    private const SCAN_MAX_DEPTH = 2;

    private ShopConfigurationDaoInterface $shopConfigurationDao;
    private BasicContextInterface $context;
    private ModuleConfigurationDaoInterface $metadataModuleConfigurationDao;
    private ModuleConfigurationMergingServiceInterface $mergingService;
    private LoggerInterface $logger;

    public function __construct(
        ShopConfigurationDaoInterface $shopConfigurationDao,
        BasicContextInterface $context,
        ModuleConfigurationDaoInterface $metadataModuleConfigurationDao,
        ModuleConfigurationMergingServiceInterface $mergingService,
        LoggerInterface $logger
    ) {
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->context = $context;
        $this->metadataModuleConfigurationDao = $metadataModuleConfigurationDao;
        $this->mergingService = $mergingService;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('oe:module:rebuild-configuration')
            ->setDescription(
                'Rebuilds the project configuration YAML from on-disk modules. '
                . 'Takes the filesystem as source of truth: prunes YAML entries whose '
                . 'metadata.php no longer exists, keeps settings for surviving modules.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Preview what would be kept / pruned without writing.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');

        [$onDiskModules, $skippedCount] = $this->findOnDiskModules($output);
        ksort($onDiskModules);

        $onDiskIdSet = [];
        foreach ($onDiskModules as $freshConfig) {
            $onDiskIdSet[$freshConfig->getId()] = true;
        }

        $shopConfigurations = $this->shopConfigurationDao->getAll();
        $prunedEntries = [];
        $keptIds = array_keys($onDiskIdSet);

        if (!$dryRun && $input->isInteractive()) {
            $output->writeln('This will rewrite the following configuration file(s):');
            foreach (array_keys($shopConfigurations) as $shopId) {
                $output->writeln(sprintf(
                    '  - %sshops/%d.yaml (backup will be created beforehand)',
                    $this->context->getProjectConfigurationDirectory(),
                    $shopId
                ));
            }
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue? [y/N] ', false);
            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        foreach ($shopConfigurations as $shopId => $shopConfig) {
            foreach ($shopConfig->getModuleIdsOfModuleConfigurations() as $existingId) {
                if (!isset($onDiskIdSet[$existingId])) {
                    $existingPath = $shopConfig->getModuleConfiguration($existingId)->getPath();
                    $prunedEntries[$existingId] = $existingPath;

                    if (!$dryRun) {
                        $this->logger->info(
                            __METHOD__ . " - Pruned phantom module '$existingId' (path: '$existingPath')."
                        );
                        $shopConfig->deleteModuleConfiguration($existingId);
                    }
                }
            }

            if (!$dryRun) {
                foreach ($onDiskModules as $freshConfig) {
                    $this->mergingService->merge($shopConfig, $freshConfig);
                }

                $this->backupShopConfig((int) $shopId);
                $this->shopConfigurationDao->save($shopConfig, (int) $shopId);
            }
        }

        $this->logger->info(
            __METHOD__ . " - Rebuild complete. Kept '" . count($keptIds)
            . "' module(s), pruned '" . count($prunedEntries) . "'."
        );

        $this->printSummary($output, $keptIds, $prunedEntries, $dryRun);

        return $skippedCount > 0 ? 1 : 0;
    }

    /**
     * @return array{0: ModuleConfiguration[], 1: int}
     */
    private function findOnDiskModules(OutputInterface $output): array
    {
        $modulesPath = $this->context->getModulesPath();

        if (!is_dir($modulesPath)) {
            return [[], 0];
        }

        $result = [];
        $skippedCount = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modulesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $iterator->setMaxDepth(self::SCAN_MAX_DEPTH);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getFilename() === 'metadata.php') {
                $moduleDir = $fileInfo->getPath();
                try {
                    $freshConfig = $this->metadataModuleConfigurationDao->get($moduleDir);
                    $freshConfig->setPath(Path::makeRelative($moduleDir, $modulesPath));
                    $result[$moduleDir] = $freshConfig;
                } catch (\Throwable $e) {
                    $skippedCount++;
                    $this->logger->warning(
                        __METHOD__ . " - Skipping module at '$moduleDir': '" . $e->getMessage() . "'."
                    );
                    $output->writeln(
                        '<comment>Skipping ' . $moduleDir . ': ' . $e->getMessage() . '</comment>'
                    );
                }
            }
        }

        return [$result, $skippedCount];
    }

    private function backupShopConfig(int $shopId): void
    {
        $yamlPath = $this->context->getProjectConfigurationDirectory() . 'shops/' . $shopId . '.yaml';
        if (file_exists($yamlPath)) {
            copy($yamlPath, $yamlPath . '.bak.' . date('Ymd-His'));
        }
    }

    private function printSummary(
        OutputInterface $output,
        array $keptIds,
        array $prunedEntries,
        bool $dryRun
    ): void {
        $prefix = $dryRun ? '[dry-run] ' : '';

        $output->writeln(sprintf('<info>%sKept: %d module(s).</info>', $prefix, count($keptIds)));

        if ($prunedEntries) {
            $output->writeln(sprintf(
                '<comment>%sPruned: %d module(s):</comment>',
                $prefix,
                count($prunedEntries)
            ));
            foreach ($prunedEntries as $id => $path) {
                $output->writeln(sprintf('<comment>  - %s (path: %s)</comment>', $id, $path));
            }
        } else {
            $output->writeln(sprintf('<info>%sPruned: 0 module(s).</info>', $prefix));
        }
    }
}
