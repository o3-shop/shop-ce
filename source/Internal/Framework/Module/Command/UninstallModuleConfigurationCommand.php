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

use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstallerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated command will be superseded by oe:module:uninstall in next major
 */
class UninstallModuleConfigurationCommand extends Command
{
    const MESSAGE_REMOVE_WAS_SUCCESSFULL = 'Module configuration for module %s has been removed.';
    const MESSAGE_REMOVE_FAILED = 'An error occurred while removing module %s configuration.';

    /**
     * @var ModuleConfigurationInstallerInterface
     */
    private $moduleConfigurationInstaller;

    /**
     * @param ModuleConfigurationInstallerInterface $moduleConfigurationInstaller
     */
    public function __construct(
        ModuleConfigurationInstallerInterface $moduleConfigurationInstaller
    ) {
        $this->moduleConfigurationInstaller = $moduleConfigurationInstaller;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setName(
                'oe:module:uninstall-configuration'
            )
            ->setDescription(
                'Uninstall module configuration from project configuration file.'
            )
            ->addArgument('module-id', InputArgument::REQUIRED, 'Module ID, it can be found on moduleRootPath/metadata.php');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $moduleId = $input->getArgument('module-id');
            $this->moduleConfigurationInstaller->uninstallById($moduleId);
            $output->writeln('<info>' . sprintf(self::MESSAGE_REMOVE_WAS_SUCCESSFULL, $moduleId) . '</info>');
        } catch (\Throwable $throwable) {
            $output->writeln('<error>' . sprintf(self::MESSAGE_REMOVE_FAILED, $moduleId) . '</error>');

            throw $throwable;
        }
    }
}
