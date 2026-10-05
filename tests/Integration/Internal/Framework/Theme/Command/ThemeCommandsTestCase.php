<?php

declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Theme\Command;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Console\ConsoleTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;

class ThemeCommandsTestCase extends TestCase
{
    use ContainerTrait;
    use ConsoleTrait;

    private string $originalTheme = '';
    private string $originalCustomTheme = '';

    protected function setUp(): void
    {
        parent::setUp();
        $config = Registry::getConfig();
        $this->originalTheme = (string) $config->getConfigParam('sTheme');
        $this->originalCustomTheme = (string) $config->getConfigParam('sCustomTheme');
    }

    protected function tearDown(): void
    {
        $config = Registry::getConfig();
        $config->saveShopConfVar('str', 'sTheme', $this->originalTheme);
        $config->saveShopConfVar('str', 'sCustomTheme', $this->originalCustomTheme);
        parent::tearDown();
    }

    protected function getApplication(): Application
    {
        $application = $this->get('oxid_esales.console.symfony.component.console.application');
        $application->setAutoExit(false);
        return $application;
    }

    protected function runCommand(array $input): string
    {
        return $this->execute(
            $this->getApplication(),
            $this->get('oxid_esales.console.commands_provider.services_commands_provider'),
            new \Symfony\Component\Console\Input\ArrayInput($input)
        );
    }

    protected function setActiveTheme(string $baseTheme, string $customTheme): void
    {
        $config = Registry::getConfig();
        $config->saveShopConfVar('str', 'sTheme', $baseTheme);
        $config->saveShopConfVar('str', 'sCustomTheme', $customTheme);
    }
}
