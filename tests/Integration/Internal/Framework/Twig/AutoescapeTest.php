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
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): adopted from OXID twig-component v1.1.0, ported to Twig 3.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Twig;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigEngine;
use PHPUnit\Framework\TestCase;

/**
 * Decision D2: storefront templates are autoescaped (html), plain-text mail templates are not.
 */
class AutoescapeTest extends TestCase
{
    public function testViewValueIsEscaped(): void
    {
        $this->assertSame(
            '&lt;script&gt;x&lt;/script&gt;',
            $this->getEngine()->renderFragment('{{ value }}', 'page/x.html.twig', ['value' => '<script>x</script>'])
        );
    }

    public function testParsedCmsContentIsNotDoubleEscaped(): void
    {
        $content = Registry::getUtilsView()->parseThroughSmarty('<b>[{ "x" }]</b>');

        $this->assertSame('<b>x</b>', $this->getEngine()->renderFragment('{{ content|raw }}', 'page/x.html.twig', ['content' => $content]));
    }

    /**
     * @dataProvider plainMailNameProvider
     */
    public function testPlainMailNotEscaped(string $name): void
    {
        $this->assertSame('a & b', $this->getEngine()->renderFragment('{{ value }}', $name, ['value' => 'a & b']));
    }

    public function plainMailNameProvider(): array
    {
        return [
            'logical name' => ['email/plain/order_cust.html.twig'],
            'module chain name' => ['@mymodule/email/plain/order.html.twig'],
        ];
    }

    public function testHtmlMailStaysEscaped(): void
    {
        $this->assertSame('a &amp; b', $this->getEngine()->renderFragment('{{ value }}', 'email/html/order_cust.html.twig', ['value' => 'a & b']));
    }

    public function testSafeHelperNotEscaped(): void
    {
        $this->assertSame(
            '<a href="mailto:a@example.com" >a@example.com</a>',
            $this->getEngine()->renderFragment('{{ mailto("a@example.com") }}', 'page/x.html.twig')
        );
    }

    private function getEngine(): TwigEngine
    {
        return ContainerFactory::getInstance()->getContainer()->get('oxid_esales.templating.twig_engine');
    }
}
