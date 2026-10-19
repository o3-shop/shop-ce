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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ProjectConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ProjectConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface as MetaDataModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolverInterface;
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
     * @param ProjectConfigurationDaoInterface       $projectConfigurationDao
     * @param ModuleStateServiceInterface            $moduleStateService
     * @param ModuleConfigurationRefreshServiceInterface $moduleConfigurationRefreshService
     * @param ModulePathResolverInterface            $modulePathResolver
     * @param MetaDataModuleConfigurationDaoInterface $metadataModuleConfigurationDao
     * @param BasicContextInterface                  $context
     */
    public function __construct(
        ProjectConfigurationDaoInterface $projectConfigurationDao,
        ModuleStateServiceInterface $moduleStateService,
        ModuleConfigurationRefreshServiceInterface $moduleConfigurationRefreshService,
        ModulePathResolverInterface $modulePathResolver,
        MetaDataModuleConfigurationDaoInterface $metadataModuleConfigurationDao,
        BasicContextInterface $context
    ) {
        parent::__construct();

        $this->projectConfigurationDao = $projectConfigurationDao;
        $this->moduleStateService = $moduleStateService;
        $this->moduleConfigurationRefreshService = $moduleConfigurationRefreshService;
        $this->modulePathResolver = $modulePathResolver;
        $this->metadataModuleConfigurationDao = $metadataModuleConfigurationDao;
        $this->context = $context;
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
        $moduleId = $input->getArgument('module-id');
        $fromMetadata = (bool)$input->getOption('from-metadata');
        $projectConfiguration = $this->projectConfigurationDao->getConfiguration();
        $shopIds = $this->getShopIds($input, $output, $projectConfiguration);

        if ($shopIds === []) {
            return 1;
        }

        $refreshedCount = 0;
        $foundConfigured = false;

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

                $this->moduleConfigurationRefreshService->refresh(
                    $this->getModuleConfiguration($shopConfiguration, $id, $shopId, $fromMetadata),
                    $shopId
                );
                $refreshedCount++;

                $output->writeln(sprintf('<info>Refreshed settings of %s in shop %d.</info>', $id, $shopId));
            }
        }

        if ($moduleId !== null && !$foundConfigured) {
            $output->writeln(sprintf('<error>Module %s is not configured for any shop.</error>', $moduleId));
            return 1;
        }

        $output->writeln(sprintf('Modules refreshed: %d', $refreshedCount));

        return 0;
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
            return $this->context->getAllShopIds();
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
