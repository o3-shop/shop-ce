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
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\RevocationTemplateValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see RevocationTemplateValidator}.
 *
 * Strategy: build a temporary on-disk theme tree (`tempnam` + dirs) so the
 * filesystem checks run against real files we control. Inject a fake
 * Language object that returns canned translateString results, so the
 * translation-key path runs without touching real lang files.
 */
class RevocationTemplateValidatorTest extends TestCase
{
    /** @var string the synthetic shop directory used for each test */
    private string $shopDir = '';

    protected function setUp(): void
    {
        $this->shopDir = sys_get_temp_dir() . '/revtest_' . uniqid('', true);
        mkdir($this->shopDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->shopDir);
    }

    public function testReturnsEmptyArrayWhenAllAssetsPresent(): void
    {
        $themeId = 'wave';
        $langIds = [0, 1];

        $this->seedFullThemeTree($themeId, $langIds);
        $language = $this->buildLanguageStub($langIds, /* allKeysPresent */ true);

        $validator = new RevocationTemplateValidator($this->shopDir, $language);
        $missing = $validator->validate(1, $themeId, $langIds);

        $this->assertSame([], $missing, 'A fully-seeded theme tree must validate clean.');
    }

    public function testReportsMissingPageTemplate(): void
    {
        $themeId = 'wave';
        $langIds = [0];

        $this->seedFullThemeTree($themeId, $langIds);
        // Knock out one page template.
        unlink($this->shopDir . '/Application/views/wave/tpl/page/revocation/revocation.tpl');
        $language = $this->buildLanguageStub($langIds, true);

        $missing = (new RevocationTemplateValidator($this->shopDir, $language))
            ->validate(1, $themeId, $langIds);

        $this->assertCount(1, $missing);
        $this->assertSame(MissingAsset::TYPE_PAGE_TEMPLATE, $missing[0]->getAssetType());
        $this->assertStringEndsWith('revocation.tpl', $missing[0]->getExpectedPath());
        $this->assertNull($missing[0]->getLangId(), 'Page templates are not language-scoped.');
    }

    public function testReportsMissingEmailTemplateAtThemeRoot(): void
    {
        $themeId = 'wave';
        $langIds = [0, 1];

        $this->seedFullThemeTree($themeId, $langIds);
        // Knock out one email template at the theme root (OXID convention:
        // email templates are NOT per-language; per-language text comes
        // from `{oxmultilang}` inside the template).
        unlink(
            $this->shopDir . '/Application/views/wave/tpl/email/html/revocation_customer_confirmation.tpl'
        );
        $language = $this->buildLanguageStub($langIds, true);

        $missing = (new RevocationTemplateValidator($this->shopDir, $language))
            ->validate(1, $themeId, $langIds);

        $emailMissing = array_values(array_filter(
            $missing,
            fn (MissingAsset $a) => $a->getAssetType() === MissingAsset::TYPE_EMAIL_TEMPLATE
        ));
        $this->assertCount(
            1,
            $emailMissing,
            'A single missing email template surfaces exactly once — it is not duplicated across languages.'
        );
        $this->assertNull($emailMissing[0]->getLangId(), 'Email templates are not language-scoped.');
    }

    public function testReportsMissingTranslationKey(): void
    {
        $themeId = 'wave';
        $langIds = [0];

        $this->seedFullThemeTree($themeId, $langIds);
        // Mark exactly one key as missing.
        $language = $this->buildLanguageStub(
            $langIds,
            true,
            ['O3_REVOCATION_FOOTER_LINK']
        );

        $missing = (new RevocationTemplateValidator($this->shopDir, $language))
            ->validate(1, $themeId, $langIds);

        $keyMissing = array_values(array_filter(
            $missing,
            fn (MissingAsset $a) => $a->getAssetType() === MissingAsset::TYPE_TRANSLATION_KEY
        ));
        $this->assertCount(1, $keyMissing);
        $this->assertSame('O3_REVOCATION_FOOTER_LINK', $keyMissing[0]->getExpectedPath());
        $this->assertSame(0, $keyMissing[0]->getLangId());
    }

    public function testReportsAllMissingAssetsTogether(): void
    {
        $themeId = 'wave';
        $langIds = [0];

        // Empty theme tree (nothing seeded) + all keys missing.
        $language = $this->buildLanguageStub($langIds, /* allKeysPresent */ false);

        $missing = (new RevocationTemplateValidator($this->shopDir, $language))
            ->validate(1, $themeId, $langIds);

        $this->assertGreaterThanOrEqual(
            2 + 6 + 18, // 2 page templates + 6 email files (one set per theme) + 18 storefront/email keys
            count($missing),
            'A completely-empty installation should surface every required asset as missing.'
        );
    }

    /**
     * Build the on-disk theme tree the validator expects: page templates
     * and email templates as zero-byte placeholders. Email templates live
     * at `<theme>/tpl/email/...` (not per-language) per OXID convention.
     *
     * @param int[] $langIds  unused for file seeding, retained to keep
     *                        the test signature aligned with the
     *                        validator API
     */
    private function seedFullThemeTree(string $themeId, array $langIds): void
    {
        $themeRoot = $this->shopDir . '/Application/views/' . $themeId . '/';

        $pageDir = $themeRoot . 'tpl/page/revocation/';
        mkdir($pageDir, 0777, true);
        touch($pageDir . 'revocation.tpl');
        touch($pageDir . 'revocationreceipt.tpl');

        $emailHtml = $themeRoot . 'tpl/email/html/';
        $emailPlain = $themeRoot . 'tpl/email/plain/';
        mkdir($emailHtml, 0777, true);
        mkdir($emailPlain, 0777, true);
        foreach ([
            $emailHtml . 'revocation_customer_confirmation.tpl',
            $emailPlain . 'revocation_customer_confirmation.tpl',
            $emailHtml . 'revocation_customer_confirmation_subj.tpl',
            $emailHtml . 'revocation_operator_notification.tpl',
            $emailPlain . 'revocation_operator_notification.tpl',
            $emailHtml . 'revocation_operator_notification_subj.tpl',
        ] as $file) {
            touch($file);
        }
    }

    /**
     * Build a Language stub. By default, every key is "translated"
     * (returned with a non-empty value). `$missingKeys` overrides
     * specific keys to behave as untranslated.
     *
     * @param int[]    $langIds
     * @param bool     $allKeysPresent  global default; false means all keys missing
     * @param string[] $missingKeys     override list (relative to $allKeysPresent)
     */
    private function buildLanguageStub(array $langIds, bool $allKeysPresent, array $missingKeys = []): Language
    {
        $language = $this->createMock(Language::class);

        $abbrs = [0 => 'de', 1 => 'en'];
        $language->method('getLanguageAbbr')->willReturnCallback(
            fn ($langId) => $abbrs[$langId] ?? null
        );

        // OXID's Language sets isTranslated() AS A SIDE EFFECT of translateString().
        // Replicate that behaviour with a shared state variable.
        $isTranslatedFlag = ['v' => true];
        $language->method('translateString')->willReturnCallback(
            function ($key, $langId = null, $blAdminMode = null) use ($allKeysPresent, $missingKeys, &$isTranslatedFlag) {
                $present = $allKeysPresent && !in_array($key, $missingKeys, true);
                $isTranslatedFlag['v'] = $present;
                return $present ? ($key . '_value') : $key;
            }
        );
        $language->method('isTranslated')->willReturnCallback(
            fn () => $isTranslatedFlag['v']
        );

        return $language;
    }

    private function rmrf(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }
        $entries = scandir($path);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->rmrf($path . '/' . $entry);
        }
        rmdir($path);
    }
}
