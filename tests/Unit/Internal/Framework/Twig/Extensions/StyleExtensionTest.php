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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\StyleExtension;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\StyleLogic;
use PHPUnit\Framework\TestCase;

class StyleExtensionTest extends TestCase
{
    /**
     * @covers       \OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\StyleLogic::collectStyleSheets
     * @dataProvider dataProvider
     *
     * @param $params
     * @param $isDynamic
     */
    public function testCollectStyleSheets($params, $isDynamic)
    {
        $styleExtension = $this->getStyleExtensionMock($params, $isDynamic);
        $env = $this->getTwigEnvironment($isDynamic);
        $styleExtension->style($env, $params);
    }

    public function dataProvider()
    {
        return [
            [['foo' => 'bar', '__oxid_include_dynamic' => true], true],
            [['foo' => 'bar', '__oxid_include_dynamic' => false], false],
            [['foo' => 'bar'], false],
        ];
    }

    private function getTwigEnvironment($isDynamic)
    {
        /** @var \Twig\Loader\LoaderInterface $loader */
        $loader = $this->createMock(\Twig\Loader\LoaderInterface::class);
        $env = new \Twig\Environment($loader, []);
        $env->addGlobal('__oxid_include_dynamic', $isDynamic);
        return $env;
    }

    /**
     * @param array $params
     * @param bool  $isDynamic
     *
     * @return StyleExtension
     */
    private function getStyleExtensionMock($params, $isDynamic)
    {
        /** @var StyleLogic $styleLogic */
        $styleLogic = $this->getMockBuilder('OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\StyleLogic')->disableOriginalConstructor()->getMock();
        $styleLogic->method('collectStyleSheets')->willReturn([]);
        $styleLogic->expects($this->once())->method('collectStyleSheets')->with($params, $isDynamic);
        $styleExtension = new StyleExtension($styleLogic);

        return $styleExtension;
    }
}
