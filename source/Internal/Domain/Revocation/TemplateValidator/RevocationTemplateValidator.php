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

namespace OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator;

use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\Registry;

/**
 * Validates that every asset the §356a revocation feature needs is in
 * place for the active shop, theme, and active languages.
 *
 * Used by three admin trigger sites (per spec / D11):
 *   1. shop-config save with `blShowRevocationForm = 1`
 *   2. activating a new shop language while the feature is on
 *   3. switching the active storefront theme while the feature is on
 *
 * Plus the `o3:check-templates` CLI command for non-admin verification.
 *
 * The caller passes the **prospective** state explicitly (new theme,
 * new language list, current state on a plain save) so the validator
 * never has to second-guess what's about to be committed.
 *
 * Asset checks are filesystem-based (page + email templates resolved
 * against `Application/views/<themeId>/...` paths under the configured
 * shop directory) and translation-engine-based (each required key
 * looked up via `Language::translateString()`; missing keys detected
 * via `Language::isTranslated()`).
 */
class RevocationTemplateValidator
{
    /**
     * Page templates. Not language-scoped — they live under the theme
     * root regardless of the active language.
     *
     * @var string[]
     */
    private const PAGE_TEMPLATE_RELPATHS = [
        'tpl/page/revocation/revocation.tpl',
        'tpl/page/revocation/revocationreceipt.tpl',
    ];

    /**
     * Email templates. One set per theme — language-specific text comes
     * from `{oxmultilang}` lookups inside the template, NOT from a
     * per-language subdirectory. (OXID convention; mirrors `order_cust.tpl`
     * which lives at `<theme>/tpl/email/html/order_cust.tpl` and is
     * shared across all shop languages.)
     *
     * @var string[]
     */
    private const EMAIL_BODY_RELPATHS = [
        'tpl/email/html/revocation_customer_confirmation.tpl',
        'tpl/email/plain/revocation_customer_confirmation.tpl',
        'tpl/email/html/revocation_operator_notification.tpl',
        'tpl/email/plain/revocation_operator_notification.tpl',
    ];

    private const EMAIL_SUBJECT_RELPATHS = [
        'tpl/email/html/revocation_customer_confirmation_subj.tpl',
        'tpl/email/html/revocation_operator_notification_subj.tpl',
    ];

    /**
     * Translation keys the storefront, admin and email templates
     * reference. These must resolve to a non-empty value in every
     * active language; missing keys surface via `Language::isTranslated()`.
     *
     * The list mirrors the spec — see "Translation engine routing"
     * and the `O3_REVOCATION_*` examples in the proposal.
     *
     * @var string[]
     */
    /**
     * The validator only checks STOREFRONT-facing keys: form labels,
     * validation messages, and email-template strings (emails are rendered
     * in the storefront-language domain even when triggered from admin —
     * see Email::send*RevocationEmail*'s setAdminMode(false) bracket).
     *
     * Admin-only keys (`O3_REVOCATION_CONFIG_*`, `O3_REVOCATION_ADMIN_*`)
     * live in `views/admin/{lang}/lang.php` and don't gate consumer-facing
     * rendering — leaving them out keeps the validator from reporting
     * false positives in `bin/oe-console o3:check-templates` and from
     * blocking the language/theme activation gates over admin-domain keys.
     */
    private const REQUIRED_TRANSLATION_KEYS = [
        // Storefront
        'O3_REVOCATION_FOOTER_LINK',
        'O3_REVOCATION_FORM_HEADING',
        'O3_REVOCATION_FIELD_NAME_LABEL',
        'O3_REVOCATION_FIELD_ORDERNUMBER_LABEL',
        'O3_REVOCATION_FIELD_EMAIL_LABEL',
        'O3_REVOCATION_FIELD_FREETEXT_LABEL',
        'O3_REVOCATION_CONFIRM_BUTTON',
        'O3_REVOCATION_CONFIRMATION_PAGE_HEADING',
        'O3_REVOCATION_VALIDATION_REQUIRED',
        'O3_REVOCATION_VALIDATION_EMAIL_FORMAT',
        'O3_REVOCATION_VALIDATION_SESSION_EXPIRED',
        'O3_REVOCATION_VALIDATION_SPAM',
        // Email — customer
        'O3_REVOCATION_CUSTOMER_EMAIL_SUBJECT',
        'O3_REVOCATION_CUSTOMER_EMAIL_BODY_INTRO',
        'O3_REVOCATION_CUSTOMER_EMAIL_BODY_RECEIPT_NOTE',
        'O3_REVOCATION_CUSTOMER_EMAIL_BODY_FOOTER',
        // Email — operator
        'O3_REVOCATION_OPERATOR_EMAIL_SUBJECT',
        'O3_REVOCATION_OPERATOR_EMAIL_BODY',
    ];

    private ?string $shopDir;
    private ?Language $language;

    public function __construct(?string $shopDir = null, ?Language $language = null)
    {
        // Resolve from Registry lazily — this service is constructed during
        // console bootstrap, before the DB connection is available.
        $this->shopDir = $shopDir;
        $this->language = $language;
    }

    private function getShopDir(): string
    {
        if ($this->shopDir === null) {
            $this->shopDir = (string) Registry::getConfig()->getConfigParam('sShopDir');
        }
        return $this->shopDir;
    }

    private function getLanguage(): Language
    {
        if ($this->language === null) {
            $this->language = Registry::getLang();
        }
        return $this->language;
    }

    /**
     * Inspect the prospective state and return the list of missing
     * assets. Empty array means "ready to activate".
     *
     * @param int      $shopId        owning shop ID (validator does not
     *                                currently key off this; reserved
     *                                for multi-shop installs)
     * @param string   $themeId       active or prospective theme directory
     *                                under `Application/views/`
     * @param int[]    $activeLangIds language IDs to validate against
     *
     * @return MissingAsset[]
     */
    public function validate(int $shopId, string $themeId, array $activeLangIds): array
    {
        $missing = [];

        $themeRoot = $this->resolveThemeRoot($themeId);

        // Page templates — not language-scoped.
        foreach (self::PAGE_TEMPLATE_RELPATHS as $relpath) {
            $absolute = $themeRoot . $relpath;
            if (!is_file($absolute)) {
                $missing[] = new MissingAsset(
                    MissingAsset::TYPE_PAGE_TEMPLATE,
                    $absolute,
                    null,
                    "Install the missing page template under the active theme: $absolute"
                );
            }
        }

        // Email templates — one set per theme; language-specific text comes
        // from `{oxmultilang}` calls inside the template (OXID convention).
        foreach (array_merge(self::EMAIL_BODY_RELPATHS, self::EMAIL_SUBJECT_RELPATHS) as $relpath) {
            $absolute = $themeRoot . $relpath;
            if (!is_file($absolute)) {
                $missing[] = new MissingAsset(
                    MissingAsset::TYPE_EMAIL_TEMPLATE,
                    $absolute,
                    null,
                    "Install the missing email template under the active theme: $absolute"
                );
            }
        }

        // Translation keys — every key must resolve to a non-empty value
        // in every active language.
        foreach ($activeLangIds as $langId) {
            foreach (self::REQUIRED_TRANSLATION_KEYS as $key) {
                if (!$this->translationExists($key, $langId)) {
                    $missing[] = new MissingAsset(
                        MissingAsset::TYPE_TRANSLATION_KEY,
                        $key,
                        $langId,
                        "Add a non-empty translation for '$key' in the language-$langId lang file."
                    );
                }
            }
        }

        return $missing;
    }

    private function resolveThemeRoot(string $themeId): string
    {
        $base = rtrim($this->getShopDir(), '/');
        return $base . '/Application/views/' . $themeId . '/';
    }

    private function translationExists(string $key, int $langId): bool
    {
        $language = $this->getLanguage();
        $language->translateString($key, $langId, false);
        if (!$language->isTranslated()) {
            return false;
        }
        // A key that resolves but to an empty string still counts as missing
        // — operators copying lang files often leave keys present-but-empty.
        $value = $language->translateString($key, $langId, false);
        return $value !== '' && $value !== $key;
    }
}
