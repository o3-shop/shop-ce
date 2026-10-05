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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsUrl;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\AddUrlParametersLogic;

/**
 * AddUrlParametersLogic relies on `getStr()` and Registry::getUtilsUrl(),
 * so it needs the OxidTestCase bootstrap to function.
 */
class AddUrlParametersLogicTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // processSeoUrl returns its argument unchanged in this stub —
        // simplifies assertions about how the parameters were appended.
        $utilsUrl = $this->getMock(UtilsUrl::class, ['processSeoUrl']);
        $utilsUrl->expects($this->any())
            ->method('processSeoUrl')
            ->willReturnCallback(static fn ($url) => $url);
        Registry::set(UtilsUrl::class, $utilsUrl);
    }

    public function testReturnsBareUrlWhenParametersAreEmpty(): void
    {
        $logic = new AddUrlParametersLogic();
        $this->assertSame('https://shop.example/foo', $logic->addUrlParameters('https://shop.example/foo', ''));
    }

    public function testAppendsParametersWithQuestionMarkWhenUrlHasNoQuery(): void
    {
        $logic = new AddUrlParametersLogic();
        $this->assertSame(
            'https://shop.example/foo?bar=1',
            $logic->addUrlParameters('https://shop.example/foo', 'bar=1')
        );
    }

    public function testAppendsParametersWithAmpersandWhenUrlAlreadyHasQuery(): void
    {
        $logic = new AddUrlParametersLogic();
        $this->assertSame(
            'https://shop.example/foo?baz=2&amp;bar=1',
            $logic->addUrlParameters('https://shop.example/foo?baz=2', 'bar=1')
        );
    }

    public function testStripsLeadingQuestionMarkFromGivenParameters(): void
    {
        $logic = new AddUrlParametersLogic();
        // Leading '?' must be removed before re-prepending the join char.
        $this->assertSame(
            'https://shop.example/foo?bar=1',
            $logic->addUrlParameters('https://shop.example/foo', '?bar=1')
        );
    }

    public function testTreatsBareAmpAsEmptyParameters(): void
    {
        $logic = new AddUrlParametersLogic();
        // '&' or '&amp;' alone counts as no parameters → return bare url.
        $this->assertSame(
            'https://shop.example/foo',
            $logic->addUrlParameters('https://shop.example/foo', '&amp;')
        );
    }
}
