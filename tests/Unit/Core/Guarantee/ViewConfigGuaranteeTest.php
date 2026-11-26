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

    /**
     * #226 item 3: only notice-de/en ship, so every other shop language takes
     * the fallback branch — and the theme footer widget calls the getter twice
     * per render (once in the `if`, once in the body). That branch emits a
     * WARNING and does two is_file() probes, so a French shop logged 2 WARNINGs
     * per request forever for a condition the operator cannot fix.
     *
     * Asserting the probe count is the proof: the log statement sits on the
     * same branch, so if the branch is entered once, it logs once.
     */
    public function testNoticeUrlResolvesEachLanguageOnlyOncePerInstance(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        $viewConfig = $this->getMockBuilder(ViewConfig::class)
            ->onlyMethods(['guaranteeNoticeAssetExists'])
            ->getMock();
        // 'fr' missing -> falls back to 'en': 2 probes for the first call, none after.
        $viewConfig->expects($this->exactly(2))
            ->method('guaranteeNoticeAssetExists')
            ->willReturnCallback(static fn (string $abbr): bool => $abbr === 'en');

        $first = $viewConfig->getGuaranteeNoticeUrlForLanguage('fr');
        $this->assertStringEndsWith('pictures/guarantee/notice-en.png', $first);
        $this->assertSame($first, $viewConfig->getGuaranteeNoticeUrlForLanguage('fr'));
        $this->assertSame($first, $viewConfig->getGuaranteeNoticeUrlForLanguage('fr'));
    }

    /**
     * The cache is per language, not global - a multi-language shop must still
     * get its own artwork per language.
     */
    public function testNoticeUrlCacheIsKeyedPerLanguage(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        $viewConfig = oxNew(ViewConfig::class);

        $this->assertStringEndsWith('notice-de.png', $viewConfig->getGuaranteeNoticeUrlForLanguage('de'));
        $this->assertStringEndsWith('notice-en.png', $viewConfig->getGuaranteeNoticeUrlForLanguage('en'));
        $this->assertStringEndsWith('notice-en.png', $viewConfig->getGuaranteeNoticeUrlForLanguage('fr'));
        $this->assertStringEndsWith('notice-de.png', $viewConfig->getGuaranteeNoticeUrlForLanguage('de'));
    }

    /**
     * Sanitisation happens before the cache lookup, so dirty and clean spellings
     * of the same language must not occupy separate cache slots.
     */
    public function testNoticeUrlCacheKeyIsTheSanitizedLanguage(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        $viewConfig = $this->getMockBuilder(ViewConfig::class)
            ->onlyMethods(['guaranteeNoticeAssetExists'])
            ->getMock();
        $viewConfig->expects($this->once())
            ->method('guaranteeNoticeAssetExists')
            ->willReturn(true);

        $this->assertSame(
            $viewConfig->getGuaranteeNoticeUrlForLanguage('de'),
            $viewConfig->getGuaranteeNoticeUrlForLanguage('DE_2/')
        );
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
