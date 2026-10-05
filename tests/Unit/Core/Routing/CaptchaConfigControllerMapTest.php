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
 * Regression test for the admin CAPTCHA configuration screen routing.
 *
 * Two things must hold for `cl=captcha_config` to resolve in the admin instead
 * of falling back to the shop main page:
 *   1. the controller key is registered in the shop controller map, and
 *   2. the mapped class actually exists.
 *
 * (2) is the subtle one: the `OxidEsales\Eshop\...` unified-namespace alias is
 * NOT generated for brand-new core classes — it is release-pinned in the
 * unified-namespace-generator package — so the map must reference the concrete
 * `OxidEsales\EshopCommunity\...` class. A `class_exists()` assertion catches a
 * regression to the non-existent alias.
 */
class CaptchaConfigControllerMapTest extends TestCase
{
    public function testCaptchaConfigMapsToAnExistingController(): void
    {
        $map = (new ShopControllerMapProvider())->getControllerMap();

        $this->assertArrayHasKey(
            'captcha_config',
            $map,
            'The admin CAPTCHA screen key must be registered in the controller map.'
        );
        $this->assertTrue(
            class_exists($map['captcha_config']),
            "captcha_config must map to an existing controller class '{$map['captcha_config']}'."
        );
    }
}
