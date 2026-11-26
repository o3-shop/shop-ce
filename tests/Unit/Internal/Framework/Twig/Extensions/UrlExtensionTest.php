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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\Extensions;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\UrlExtension;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\AddUrlParametersLogic;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\SeoUrlLogic;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * seo_url and add_url_parameters return plain URLs, so autoescaping encodes them exactly once.
 */
class UrlExtensionTest extends TestCase
{
    public function testSeoUrlIsEncodedOnceForHtml(): void
    {
        $seo = $this->createMock(SeoUrlLogic::class);
        $seo->method('seoUrl')->willReturn('http://shop/cl?a=1&amp;b=2');
        $add = $this->createMock(AddUrlParametersLogic::class);
        $add->method('addUrlParameters')->willReturn('http://shop/cl?a=1&amp;b=2&amp;p=3');

        $this->assertSame(
            'http://shop/cl?a=1&amp;b=2&amp;p=3',
            $this->render("{{ seo_url({ident: 'x', params: 'p=3'}) }}", $seo, $add)
        );
    }

    public function testHostileUrlPartsAreEscaped(): void
    {
        $seo = $this->createMock(SeoUrlLogic::class);
        $seo->method('seoUrl')->willReturn('http://shop/?q="><script>alert(1)</script>');
        $add = $this->createMock(AddUrlParametersLogic::class);
        $add->method('addUrlParameters')->willReturn('http://shop/?q="><script>alert(1)</script>');

        $escaped = 'http://shop/?q=&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;';
        $this->assertSame($escaped, $this->render("{{ seo_url({ident: 'x'}) }}", $seo, $add));
        $this->assertSame($escaped, $this->render("{{ 'u'|add_url_parameters('p') }}", $seo, $add));
    }

    private function render(string $template, SeoUrlLogic $seo, AddUrlParametersLogic $add): string
    {
        $twig = new Environment(new ArrayLoader(['main' => $template]), ['autoescape' => 'html', 'cache' => false]);
        $twig->addExtension(new UrlExtension($seo, $add));

        return $twig->render('main');
    }
}
