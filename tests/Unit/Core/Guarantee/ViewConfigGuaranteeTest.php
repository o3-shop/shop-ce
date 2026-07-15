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

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\TestingLibrary\UnitTestCase;

class ViewConfigGuaranteeTest extends UnitTestCase
{
    private string $noticeDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->noticeDir = Registry::getConfig()->getOutDir(true) . 'pictures/guarantee/';
    }

    public function testDurabilitySwitchGetterReflectsConfig(): void
    {
        $viewConfig = oxNew(ViewConfig::class);

        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $this->assertTrue($viewConfig->getDurabilityGuaranteeLabelsEnabled());

        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', false);
        $this->assertFalse($viewConfig->getDurabilityGuaranteeLabelsEnabled());
    }

    public function testNoticeUrlNullWhenSwitchOff(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', false);
        $this->assertNull(oxNew(ViewConfig::class)->getGuaranteeNoticeUrl());
    }

    public function testNoticeUrlUsesActiveLanguageAsset(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        Registry::getLang()->setBaseLanguage(0); // 0 = de in the test fixture

        $url = oxNew(ViewConfig::class)->getGuaranteeNoticeUrl();

        $this->assertNotNull($url);
        $this->assertStringEndsWith('pictures/guarantee/notice-de.png', $url);
    }

    public function testNoticeUrlFallsBackToEnglishWhenLanguageAssetMissing(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        // Simulate a shop language without an asset by asking for a
        // non-existent abbreviation through the test seam.
        $viewConfig = oxNew(ViewConfig::class);
        $url = $viewConfig->getGuaranteeNoticeUrlForLanguage('fr');

        $this->assertNotNull($url);
        $this->assertStringEndsWith('pictures/guarantee/notice-en.png', $url);
    }

    public function testNoticeUrlNullWhenNoAssetsAtAll(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        $viewConfig = $this->getMockBuilder(ViewConfig::class)
            ->onlyMethods(['guaranteeNoticeAssetExists'])
            ->getMock();
        $viewConfig->method('guaranteeNoticeAssetExists')->willReturn(false);

        $this->assertNull($viewConfig->getGuaranteeNoticeUrl());
    }
}
