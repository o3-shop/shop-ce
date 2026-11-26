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

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\IncludeExtension;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\IncludeDynamicLogic;
use PHPUnit\Framework\TestCase;

/**
 * Class IncludeExtensionTest
 */
class IncludeExtensionTest extends TestCase
{
    /** @var IncludeExtension */
    private $includeExtension;

    public function setUp(): void
    {
        $this->includeExtension = new IncludeExtension(new IncludeDynamicLogic());
    }

    /**
     * @param array $parameters
     * @param array $expected
     *
     * @covers       IncludeExtension::includeDynamicPrefix
     * @dataProvider getIncludeDynamicPrefixTests
     */
    public function testIncludeDynamicPrefix(array $parameters, array $expected): void
    {
        $this->assertEquals($this->includeExtension->includeDynamicPrefix($parameters), $expected);
    }

    /**
     * @param array  $parameters
     * @param string $expected
     *
     * @covers       IncludeExtension::renderForCache
     * @dataProvider getRenderForCacheTests
     */
    public function testRenderForCache(array $parameters, string $expected): void
    {
        $this->assertEquals($this->includeExtension->renderForCache($parameters), $expected);
    }

    /**
     * @return array
     */
    public function getIncludeDynamicPrefixTests(): array
    {
        return [
            [[], []],
            [['param1' => 'val1', 'param2' => 2], ['_param1' => 'val1', '_param2' => 2]],
            [['type' => 'custom'], []],
            [['type' => 'custom', 'param1' => 'val1', 'param2' => 2], ['_custom_param1' => 'val1', '_custom_param2' => 2]],
            [['type' => 'custom', 'file' => 'file.tpl'], []],
            [['type' => 'custom', 'file' => 'file.tpl', 'param' => 'val'], ['_custom_param' => 'val']],
        ];
    }

    /**
     * @return array
     */
    public function getRenderForCacheTests(): array
    {
        return [
            [[], '<oxid_dynamic></oxid_dynamic>'],
            [['param1' => 'val1', 'param2' => 2], '<oxid_dynamic> param1=\'dmFsMQ==\' param2=\'Mg==\'</oxid_dynamic>'],
        ];
    }
}
