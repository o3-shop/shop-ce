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

/**
 * Localises a {@see MissingAsset}'s remediation hint for admin presentation.
 *
 * The validator returns hints as English literals because the same DTOs feed
 * the `o3:check-templates` CLI command, which is operator-side and English-only
 * by convention. Admin trigger sites (RevocationConfigController, LanguageMain,
 * ThemeMain) want the hint in the admin's active language, so they pipe each
 * MissingAsset through this formatter before pushing it to UtilsView.
 *
 * Translation keys live in `views/admin/{lang}/lang.php` (admin domain — not
 * storefront — because OXID's Language::translateString does not fall back
 * across domains).
 */
final class MissingAssetHintTranslator
{
    public static function translate(MissingAsset $asset, Language $language): string
    {
        switch ($asset->getAssetType()) {
            case MissingAsset::TYPE_PAGE_TEMPLATE:
                return sprintf(
                    $language->translateString('O3_REVOCATION_ADMIN_GATE_HINT_PAGE_TEMPLATE'),
                    $asset->getExpectedPath()
                );
            case MissingAsset::TYPE_EMAIL_TEMPLATE:
                return sprintf(
                    $language->translateString('O3_REVOCATION_ADMIN_GATE_HINT_EMAIL_TEMPLATE'),
                    $asset->getExpectedPath()
                );
            case MissingAsset::TYPE_TRANSLATION_KEY:
                return sprintf(
                    $language->translateString('O3_REVOCATION_ADMIN_GATE_HINT_TRANSLATION_KEY'),
                    $asset->getExpectedPath(),
                    (int) $asset->getLangId()
                );
        }
        return $asset->getRemediationHint();
    }
}
