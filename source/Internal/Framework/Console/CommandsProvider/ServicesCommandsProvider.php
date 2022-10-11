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

namespace OxidEsales\EshopCommunity\Internal\Framework\Console\CommandsProvider;

use OxidEsales\EshopCommunity\Internal\Framework\Console\AbstractShopAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ServicesCommandsProvider implements CommandsProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $commands = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        if ($this->container->hasParameter('console.command.ids')) {
            foreach ($this->container->getParameter('console.command.ids') as $id) {
                $service = $this->container->get($id);
                $this->setShopAwareCommands($service);
                $this->setNonShopAwareCommands($service);
            }
        }
        return $this->commands;
    }

    /**
     * Set commands for modules.
     *
     * @param Command $service
     */
    private function setShopAwareCommands(Command $service)
    {
        if ($service instanceof AbstractShopAwareCommand && $service->isActive()) {
            $this->commands[] = $service;
        }
    }

    /**
     * Sets commands which should be shown independently from active shop.
     *
     * @param Command $service
     */
    private function setNonShopAwareCommands(Command $service)
    {
        if (!$service instanceof AbstractShopAwareCommand && $service instanceof Command) {
            $this->commands[] = $service;
        }
    }
}
