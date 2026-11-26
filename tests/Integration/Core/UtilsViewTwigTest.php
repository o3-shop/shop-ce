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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Twig\FixtureThemeTrait;
use PHPUnit\Framework\TestCase;

/**
 * CMS content stays Smarty, also on a Twig theme.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class UtilsViewTwigTest extends TestCase
{
    use FixtureThemeTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installFixtureTheme();
    }

    protected function tearDown(): void
    {
        $this->uninstallFixtureTheme();
        parent::tearDown();
    }

    public function testCmsContentIsRenderedBySmartyOnTwigTheme(): void
    {
        $this->assertSame(
            '<b>x</b> {{ 7*7 }}',
            Registry::getUtilsView()->parseThroughSmarty('<b>[{ "x" }]</b> {{ 7*7 }}')
        );
    }
}
