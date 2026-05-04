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

use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\TranslateFunctionLogic;

class TranslateFunctionLogicTest extends \OxidTestCase
{
    private function mockLanguage(string $translation, bool $translated = true): Language
    {
        $lang = $this->getMockBuilder(Language::class)
            ->onlyMethods(['translateString', 'getTplLanguage', 'isTranslated', 'isAdmin'])
            ->getMock();
        $lang->method('getTplLanguage')->willReturn(0);
        $lang->method('isAdmin')->willReturn(false);
        $lang->method('translateString')->willReturn($translation);
        $lang->method('isTranslated')->willReturn($translated);
        return $lang;
    }

    private function mockShop(bool $productive): Shop
    {
        $shop = $this->getMockBuilder(Shop::class)
            ->onlyMethods(['isProductiveMode'])
            ->getMock();
        $shop->method('isProductiveMode')->willReturn($productive);
        return $shop;
    }

    public function testReturnsTranslatedStringForKnownIdent(): void
    {
        $lang = $this->mockLanguage('Welcome', true);
        Registry::set(Language::class, $lang);

        $config = $this->getMock(Config::class, ['getActiveShop']);
        $config->method('getActiveShop')->willReturn($this->mockShop(false));
        Registry::set(Config::class, $config);

        $logic = new TranslateFunctionLogic();
        $this->assertSame('Welcome', $logic->getTranslation(['ident' => 'GREETING']));
    }

    public function testFormatsArgsWithSprintfWhenScalar(): void
    {
        $lang = $this->mockLanguage('Hello %s', true);
        Registry::set(Language::class, $lang);
        $config = $this->getMock(Config::class, ['getActiveShop']);
        $config->method('getActiveShop')->willReturn($this->mockShop(false));
        Registry::set(Config::class, $config);

        $logic = new TranslateFunctionLogic();
        $this->assertSame('Hello world', $logic->getTranslation(['ident' => 'GREETING', 'args' => 'world']));
    }

    public function testFormatsArgsWithVsprintfWhenArray(): void
    {
        $lang = $this->mockLanguage('Hello %s, %s', true);
        Registry::set(Language::class, $lang);
        $config = $this->getMock(Config::class, ['getActiveShop']);
        $config->method('getActiveShop')->willReturn($this->mockShop(false));
        Registry::set(Config::class, $config);

        $logic = new TranslateFunctionLogic();
        $this->assertSame(
            'Hello A, B',
            $logic->getTranslation(['ident' => 'GREETING', 'args' => ['A', 'B']])
        );
    }

    public function testFallsBackToAlternativeWhenTranslationMissing(): void
    {
        $lang = $this->mockLanguage('GREETING', false);
        Registry::set(Language::class, $lang);
        $config = $this->getMock(Config::class, ['getActiveShop']);
        $config->method('getActiveShop')->willReturn($this->mockShop(false));
        Registry::set(Config::class, $config);

        $logic = new TranslateFunctionLogic();
        $this->assertSame(
            'fallback',
            $logic->getTranslation(['ident' => 'GREETING', 'alternative' => 'fallback'])
        );
    }

    public function testReturnsErrorPlaceholderWhenMissingAndDebugAndNoAlternative(): void
    {
        $lang = $this->mockLanguage('GREETING', false);
        Registry::set(Language::class, $lang);
        $config = $this->getMock(Config::class, ['getActiveShop']);
        $config->method('getActiveShop')->willReturn($this->mockShop(false));
        Registry::set(Config::class, $config);

        $logic = new TranslateFunctionLogic();
        $this->assertStringContainsString(
            'GREETING',
            $logic->getTranslation(['ident' => 'GREETING'])
        );
        $this->assertStringContainsString('not found', $logic->getTranslation(['ident' => 'GREETING']));
    }

    public function testProductiveModeSuppressesTheErrorPlaceholderAndReturnsRawTranslation(): void
    {
        // In productive mode, missing translations don't get the error
        // marker — they fall back to whatever translateString returned.
        $lang = $this->mockLanguage('GREETING', false);
        Registry::set(Language::class, $lang);
        $config = $this->getMock(Config::class, ['getActiveShop']);
        $config->method('getActiveShop')->willReturn($this->mockShop(true));
        Registry::set(Config::class, $config);

        $logic = new TranslateFunctionLogic();
        // No alternative + missing translation + production → raw "GREETING" comes back unwrapped.
        $this->assertSame('GREETING', $logic->getTranslation(['ident' => 'GREETING']));
    }

    public function testNoErrorFlagSuppressesErrorPlaceholderEvenInDebug(): void
    {
        $lang = $this->mockLanguage('GREETING', false);
        Registry::set(Language::class, $lang);
        $config = $this->getMock(Config::class, ['getActiveShop']);
        $config->method('getActiveShop')->willReturn($this->mockShop(false));
        Registry::set(Config::class, $config);

        $logic = new TranslateFunctionLogic();
        $this->assertSame(
            'GREETING',
            $logic->getTranslation(['ident' => 'GREETING', 'noerror' => true])
        );
    }

    public function testIdentDefaultsToIdentMissingMarkerWhenAbsent(): void
    {
        $lang = $this->getMockBuilder(Language::class)
            ->onlyMethods(['translateString', 'getTplLanguage', 'isTranslated', 'isAdmin'])
            ->getMock();
        $lang->method('getTplLanguage')->willReturn(0);
        $lang->method('isAdmin')->willReturn(false);
        $lang->method('isTranslated')->willReturn(true);
        $captured = null;
        $lang->method('translateString')->willReturnCallback(function ($ident) use (&$captured) {
            $captured = $ident;
            return 'whatever';
        });
        Registry::set(Language::class, $lang);

        $config = $this->getMock(Config::class, ['getActiveShop']);
        $config->method('getActiveShop')->willReturn($this->mockShop(false));
        Registry::set(Config::class, $config);

        (new TranslateFunctionLogic())->getTranslation([]);
        $this->assertSame('IDENT MISSING', $captured);
    }
}
