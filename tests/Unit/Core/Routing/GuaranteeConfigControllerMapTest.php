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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Routing;

use OxidEsales\Eshop\Core\Routing\ShopControllerMapProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for the admin EU guarantee-label configuration screen
 * routing (issue #219). Same rationale as `CaptchaConfigControllerMapTest`:
 * a new core admin controller needs both a map entry AND an existing class
 * behind it, or the admin menu silently falls back to the shop main page.
 */
class GuaranteeConfigControllerMapTest extends TestCase
{
    public function testGuaranteeConfigMapsToAnExistingController(): void
    {
        $map = (new ShopControllerMapProvider())->getControllerMap();

        $this->assertArrayHasKey(
            'guarantee_config',
            $map,
            'The admin guarantee-label config screen key must be registered in the controller map.'
        );
        $this->assertTrue(
            class_exists($map['guarantee_config']),
            "guarantee_config must map to an existing controller class '{$map['guarantee_config']}'."
        );
    }
}
