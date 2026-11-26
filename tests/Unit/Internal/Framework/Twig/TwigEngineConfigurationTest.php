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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigEngineConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TwigEngineConfigurationTest extends TestCase
{
    public function testGetParameters(): void
    {
        $engineConfiguration = $this->getEngineConfiguration();
        $this->assertEquals(['debug' => true, 'cache' => 'dummy_cache_dir'], $engineConfiguration->getParameters());
        $this->assertNotEquals(['debug' => 'foo', 'cache' => 'foo'], $engineConfiguration->getParameters());
    }

    private function getEngineConfiguration(): TwigEngineConfiguration
    {
        /** @var TwigContextInterface|MockObject $context */
        $context = $this->getMockBuilder('OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigContextInterface')->getMock();
        $context->method('getIsDebug')->willReturn(true);
        $context->method('getCacheDir')->willReturn('dummy_cache_dir');
        return new TwigEngineConfiguration($context);
    }
}
