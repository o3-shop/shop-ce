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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Guarantee;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Tests for the `ViewConfig` getters backing the shop-global switches of the
 * EU harmonised guarantee labels feature (EmpCo Directive (EU) 2024/825,
 * issue #219): `getLegalGuaranteeNoticeVisible()`,
 * `getLegalGuaranteeNoticePlacement()`, `getDurabilityGuaranteeLabelVisible()`.
 */
class ViewConfigGuaranteeTest extends UnitTestCase
{
    /** @var array<string, mixed> oxconfig values returned by the mocked Config */
    private array $configValues = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->configValues = [];
        $this->mockRegistry();
    }

    public function testLegalGuaranteeNoticeHiddenByDefault(): void
    {
        $this->assertFalse((new ViewConfig())->getLegalGuaranteeNoticeVisible());
    }

    public function testLegalGuaranteeNoticeVisibleWhenSwitchOn(): void
    {
        $this->configValues['blShowLegalGuaranteeNotice'] = true;
        $this->assertTrue((new ViewConfig())->getLegalGuaranteeNoticeVisible());
    }

    public function testDurabilityGuaranteeLabelHiddenByDefault(): void
    {
        $this->assertFalse((new ViewConfig())->getDurabilityGuaranteeLabelVisible());
    }

    public function testDurabilityGuaranteeLabelVisibleWhenSwitchOn(): void
    {
        $this->configValues['blShowDurabilityGuaranteeLabel'] = true;
        $this->assertTrue((new ViewConfig())->getDurabilityGuaranteeLabelVisible());
    }

    public function testNoticePlacementDefaultsToFooter(): void
    {
        $this->assertSame('footer', (new ViewConfig())->getLegalGuaranteeNoticePlacement());
    }

    public function testNoticePlacementReflectsConfiguredValue(): void
    {
        $this->configValues['sLegalGuaranteeNoticePlacement'] = 'page';
        $this->assertSame('page', (new ViewConfig())->getLegalGuaranteeNoticePlacement());
    }

    private function mockRegistry(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getConfigParam')->willReturnCallback(
            fn ($name, $default = null) => $this->configValues[$name] ?? $default
        );
        Registry::set(Config::class, $config);
    }
}
