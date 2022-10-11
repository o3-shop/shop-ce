<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Console;

use OxidEsales\EshopCommunity\Internal\Framework\Console\CommandsProvider\CommandsProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @internal
 */
trait ConsoleTrait
{
    /**
     * @param Application $application
     * @param CommandsProviderInterface $commandsProvider
     * @param $input
     * @return string
     */
    protected function execute(Application $application, CommandsProviderInterface $commandsProvider, $input): string
    {
        $executor = new Executor($application, $commandsProvider);

        $output = new StreamOutput(fopen('php://memory', 'w', false));
        $executor->execute($input, $output);
        $stream = $output->getStream();
        rewind($stream);
        $display = stream_get_contents($stream);

        return $display;
    }
}
