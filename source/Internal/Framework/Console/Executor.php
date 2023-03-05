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

namespace OxidEsales\EshopCommunity\Internal\Framework\Console;

use OxidEsales\EshopCommunity\Internal\Framework\Console\CommandsProvider\CommandsProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Console\CommandsProvider\ServicesCommandsProvider;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class Executor implements ExecutorInterface
{
    const SHOP_ID_PARAMETER_OPTION_NAME = 'shop-id';

    /**
     * @var Application
     */
    private $application;

    /**
     * @var ServicesCommandsProvider
     */
    private $servicesCommandsProvider;

    /**
     * @param Application               $application
     * @param CommandsProviderInterface $commandsProvider
     */
    public function __construct(
        Application $application,
        CommandsProviderInterface $commandsProvider
    ) {
        $this->application = $application;
        $this->servicesCommandsProvider = $commandsProvider;
    }

    /**
     * Executes commands.
     *
     * @param InputInterface|null  $input
     * @param OutputInterface|null $output
     */
    public function execute(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->application->addCommands($this->servicesCommandsProvider->getCommands());
        $this->application->run($input, $output);
    }
}
