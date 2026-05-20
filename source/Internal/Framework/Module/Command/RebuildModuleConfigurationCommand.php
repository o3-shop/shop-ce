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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

class RebuildModuleConfigurationCommand extends Command
{
    private ShopConfigurationDaoInterface $shopConfigurationDao;
    private BasicContextInterface $context;
    private ModuleConfigurationDaoInterface $metadataModuleConfigurationDao;
    private ModuleConfigurationMergingServiceInterface $mergingService;

    public function __construct(
        ShopConfigurationDaoInterface $shopConfigurationDao,
        BasicContextInterface $context,
        ModuleConfigurationDaoInterface $metadataModuleConfigurationDao,
        ModuleConfigurationMergingServiceInterface $mergingService
    ) {
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->context = $context;
        $this->metadataModuleConfigurationDao = $metadataModuleConfigurationDao;
        $this->mergingService = $mergingService;

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

        $onDiskModules = $this->findOnDiskModules($output);

        $onDiskIdSet = [];
        foreach ($onDiskModules as $freshConfig) {
            $onDiskIdSet[$freshConfig->getId()] = true;
        }

        $prunedEntries = [];
        $keptIds = array_keys($onDiskIdSet);

        foreach ($this->shopConfigurationDao->getAll() as $shopId => $shopConfig) {
            foreach ($shopConfig->getModuleIdsOfModuleConfigurations() as $existingId) {
                if (!isset($onDiskIdSet[$existingId])) {
                    $existingPath = $shopConfig->getModuleConfiguration($existingId)->getPath();
                    $prunedEntries[$existingId] = $existingPath;

                    if (!$dryRun) {
                        $shopConfig->deleteModuleConfiguration($existingId);
                    }
                }
            }

            if (!$dryRun) {
                foreach ($onDiskModules as $freshConfig) {
                    $this->mergingService->merge($shopConfig, $freshConfig);
                }

                $this->shopConfigurationDao->save($shopConfig, (int) $shopId);
            }
        }

        $this->printSummary($output, $keptIds, $prunedEntries, $dryRun);

        return 0;
    }

    /** @return ModuleConfiguration[] keyed by absolute module path */
    private function findOnDiskModules(OutputInterface $output): array
    {
        $modulesPath = $this->context->getModulesPath();

        if (!is_dir($modulesPath)) {
            return [];
        }

        $result = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modulesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getFilename() === 'metadata.php') {
                $moduleDir = $fileInfo->getPath();
                try {
                    $freshConfig = $this->metadataModuleConfigurationDao->get($moduleDir);
                    $freshConfig->setPath(Path::makeRelative($moduleDir, $modulesPath));
                    $result[$moduleDir] = $freshConfig;
                } catch (\Throwable $e) {
                    $output->writeln(
                        '<comment>Skipping ' . $moduleDir . ': ' . $e->getMessage() . '</comment>'
                    );
                }
            }
        }

        return $result;
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
