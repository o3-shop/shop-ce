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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Revocation\TemplateValidator;

use OxidEsales\Eshop\Core\Language;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\MissingAsset;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\MissingAssetHintTranslator;
use PHPUnit\Framework\TestCase;

class MissingAssetHintTranslatorTest extends TestCase
{
    private function makeLanguage(string $template): Language
    {
        $lang = $this->createMock(Language::class);
        $lang->method('translateString')->willReturn($template);
        return $lang;
    }

    public function testTranslatesPageTemplateHintWithExpectedPath(): void
    {
        $asset = new MissingAsset(
            MissingAsset::TYPE_PAGE_TEMPLATE,
            '/var/www/html/source/Application/views/wave/tpl/page/revocation/revocation.tpl',
            null,
            'fallback hint'
        );
        $lang = $this->makeLanguage('Install the missing page template at: %s');

        $this->assertSame(
            'Install the missing page template at: /var/www/html/source/Application/views/wave/tpl/page/revocation/revocation.tpl',
            MissingAssetHintTranslator::translate($asset, $lang)
        );
    }

    public function testTranslatesEmailTemplateHintWithExpectedPath(): void
    {
        $asset = new MissingAsset(
            MissingAsset::TYPE_EMAIL_TEMPLATE,
            '/path/email/revocation_customer_confirmation.tpl',
            null,
            'fallback hint'
        );
        $lang = $this->makeLanguage('Email template missing: %s');

        $this->assertStringContainsString(
            '/path/email/revocation_customer_confirmation.tpl',
            MissingAssetHintTranslator::translate($asset, $lang)
        );
    }

    public function testTranslatesTranslationKeyHintWithKeyAndLanguageId(): void
    {
        $asset = new MissingAsset(
            MissingAsset::TYPE_TRANSLATION_KEY,
            'O3_REVOCATION_FOOTER_LINK',
            1,
            'fallback hint'
        );
        $lang = $this->makeLanguage('Add translation %s in language %d');

        $result = MissingAssetHintTranslator::translate($asset, $lang);
        $this->assertStringContainsString('O3_REVOCATION_FOOTER_LINK', $result);
        $this->assertStringContainsString('1', $result);
    }

    public function testFallsBackToRemediationHintForUnknownAssetType(): void
    {
        // MissingAsset is final, so we use the constructor directly with an
        // unknown asset-type string — the switch falls through to the default
        // "return $asset->getRemediationHint()" branch.
        $asset = new MissingAsset('UNKNOWN_TYPE', '/some/path', null, 'this is the fallback hint');
        $lang = $this->makeLanguage('should-not-be-used');

        $this->assertSame(
            'this is the fallback hint',
            MissingAssetHintTranslator::translate($asset, $lang)
        );
    }
}
