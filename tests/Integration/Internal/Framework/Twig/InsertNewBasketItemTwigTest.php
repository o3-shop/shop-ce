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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Twig;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class InsertNewBasketItemTwigTest extends TestCase
{
    use FixtureThemeTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installFixtureTheme();
    }

    protected function tearDown(): void
    {
        Registry::getSession()->deleteVariable('_newitem');
        $this->uninstallFixtureTheme();
        parent::tearDown();
    }

    public function testNewBasketItemSnippetRendersDuringRendering(): void
    {
        $newItem = new \stdClass();
        $newItem->sId = 'NEW-ITEM-ID';
        Registry::getSession()->setVariable('_newitem', $newItem);

        $output = ContainerFactory::getInstance()->getContainer()
            ->get('oxid_esales.templating.twig_engine')
            ->renderFragment(
                '{{ insert_new_basket_item({"type": "", "ajax": false, "tpl": "inc_newbasketitem.snippet.html.twig"}) }}',
                'page/x.html.twig'
            );

        $this->assertSame("<b>NEW-ITEM:NEW-ITEM-ID</b>\n", $output);
    }
}
