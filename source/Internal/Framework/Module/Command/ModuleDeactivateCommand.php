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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleActivationServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command deactivates module by module id.
 */
class ModuleDeactivateCommand extends Command
{
    const MESSAGE_MODULE_DEACTIVATED = 'Module - "%s" has been deactivated.';

    const MESSAGE_NOT_POSSIBLE_TO_DEACTIVATE =
        'It was not possible to deactivate module - "%s", maybe it was not active?';

    const MESSAGE_MODULE_NOT_FOUND = 'Module - "%s" not found.';

    const ARGUMENT_MODULE_ID = 'module-id';

    /**
     * @var ShopConfigurationDaoInterface
     */
    private $shopConfigurationDao;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var ModuleActivationServiceInterface
     */
    private $moduleActivationService;

    /**
     * @param ShopConfigurationDaoInterface    $shopConfigurationDao
     * @param ContextInterface                 $context
     * @param ModuleActivationServiceInterface $moduleActivationService
     */
    public function __construct(
        ShopConfigurationDaoInterface $shopConfigurationDao,
        ContextInterface $context,
        ModuleActivationServiceInterface $moduleActivationService
    ) {
        parent::__construct(null);

        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->context = $context;
        $this->moduleActivationService = $moduleActivationService;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Deactivates a module.')
            ->addArgument(static::ARGUMENT_MODULE_ID, InputArgument::REQUIRED, 'Module ID')
            ->setHelp('Command deactivates module by defined module ID.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $moduleId = $input->getArgument('module-id');

        if ($this->isInstalled($moduleId)) {
            $this->deactivateModule($output, $moduleId);
        } else {
            $output->writeLn('<error>' . sprintf(static::MESSAGE_MODULE_NOT_FOUND, $moduleId) . '</error>');
        }
    }

    /**
     * @param OutputInterface $output
     * @param string          $moduleId
     */
    protected function deactivateModule(OutputInterface $output, string $moduleId)
    {
        try {
            $this->moduleActivationService->deactivate($moduleId, $this->context->getCurrentShopId());
            $output->writeLn(
                '<info>' . sprintf(static::MESSAGE_MODULE_DEACTIVATED, $moduleId) . '</info>'
            );
        } catch (ModuleSetupException $exception) {
            $output->writeLn(
                '<info>' . sprintf(static::MESSAGE_NOT_POSSIBLE_TO_DEACTIVATE, $moduleId) . '</info>'
            );
        }
    }

    /**
     * @param string $moduleId
     * @return bool
     */
    private function isInstalled(string $moduleId): bool
    {
        $shopConfiguration = $this
            ->shopConfigurationDao
            ->get(
                $this->context->getCurrentShopId()
            );

        return $shopConfiguration->hasModuleConfiguration($moduleId);
    }
}
