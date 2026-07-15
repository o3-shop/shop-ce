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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Registry;

/**
 * EU harmonised guarantee labels (EmpCo Directive (EU) 2024/825, issue #219)
 * — admin configuration page.
 *
 * Owns the three `oxconfig` settings the operator interacts with:
 *   - `blShowLegalGuaranteeNotice`     (bool) — shop-global notice on/off
 *   - `blShowDurabilityGuaranteeLabel` (bool) — product label master switch
 *   - `sLegalGuaranteeNoticePlacement` (str)  — where the global notice renders
 *
 * Save semantics — all-or-nothing: an invalid placement value rejects the
 * entire form save (no row updated); the form re-renders with the submitted
 * values pre-filled (per `feedback_form-input-preservation.md`).
 */
class GuaranteeConfigController extends AdminDetailsController
{
    /** @var string */
    protected $_sThisTemplate = 'guarantee_config.tpl';

    public const PLACEMENT_FOOTER = 'footer';
    public const PLACEMENT_PAGE = 'page';

    private const ALLOWED_PLACEMENTS = [self::PLACEMENT_FOOTER, self::PLACEMENT_PAGE];

    /** @var array<string,mixed> values the operator submitted (used to re-render on rejection) */
    private array $submittedValues = [];

    /** @var array<string,string> per-field validation errors set by save() on rejection */
    private array $validationErrors = [];

    /** @var bool whether the label master switch is on with zero qualifying products */
    private bool $noQualifyingProductsHint = false;

    /**
     * @return string admin template name to render
     */
    public function render()
    {
        $config = Registry::getConfig();

        $bag = $this->submittedValues !== [] ? $this->submittedValues : [
            'blShowLegalGuaranteeNotice'     => (bool) $config->getConfigParam('blShowLegalGuaranteeNotice', false),
            'blShowDurabilityGuaranteeLabel' => (bool) $config->getConfigParam('blShowDurabilityGuaranteeLabel', false),
            'sLegalGuaranteeNoticePlacement' => (string) $config->getConfigParam('sLegalGuaranteeNoticePlacement', self::PLACEMENT_FOOTER),
        ];

        $this->_aViewData['guarantee'] = $bag;
        $this->_aViewData['guaranteeErrors'] = $this->validationErrors;
        $this->_aViewData['guaranteeNoQualifyingProductsHint'] = $this->noQualifyingProductsHint;

        return parent::render();
    }

    /**
     * Save handler. All-or-nothing: an invalid placement rejects the save.
     *
     * @return void
     */
    public function save()
    {
        $request = Registry::getRequest();
        $submitted = [
            'blShowLegalGuaranteeNotice'     => (bool) $request->getRequestParameter('blShowLegalGuaranteeNotice', 0),
            'blShowDurabilityGuaranteeLabel' => (bool) $request->getRequestParameter('blShowDurabilityGuaranteeLabel', 0),
            'sLegalGuaranteeNoticePlacement' => trim((string) $request->getRequestParameter('sLegalGuaranteeNoticePlacement', self::PLACEMENT_FOOTER)),
        ];
        $this->submittedValues = $submitted;

        if (!in_array($submitted['sLegalGuaranteeNoticePlacement'], self::ALLOWED_PLACEMENTS, true)) {
            $this->fail('sLegalGuaranteeNoticePlacement', 'O3_GUARANTEE_ADMIN_VALIDATION_PLACEMENT_INVALID');
            return;
        }

        $config = Registry::getConfig();
        $config->saveShopConfVar('bool', 'blShowLegalGuaranteeNotice', $submitted['blShowLegalGuaranteeNotice'] ? '1' : '');
        $config->saveShopConfVar('bool', 'blShowDurabilityGuaranteeLabel', $submitted['blShowDurabilityGuaranteeLabel'] ? '1' : '');
        $config->saveShopConfVar('str', 'sLegalGuaranteeNoticePlacement', $submitted['sLegalGuaranteeNoticePlacement']);

        if ($submitted['blShowDurabilityGuaranteeLabel'] && !$this->hasQualifyingProduct()) {
            $this->noQualifyingProductsHint = true;
        }

        parent::save();
    }

    private function fail(string $field, string $translationKey): void
    {
        $this->validationErrors[$field] = $translationKey;

        $error = oxNew(DisplayError::class);
        $error->setMessage($translationKey);
        Registry::getUtilsView()->addErrorToDisplay($error);
    }

    /**
     * Informational check (not blocking): does at least one product qualify
     * for the durability-guarantee label in the active shop?
     *
     * Mirrors {@see \OxidEsales\Eshop\Application\Model\Article::isDurabilityGuaranteeLabelEligible()}:
     * a product qualifies only when the durability guarantee exceeds 24 months
     * AND a guarantor is resolvable (the article's own field, or — when empty —
     * the linked manufacturer's title). A duration-only check would be wrong in
     * both directions: it reports products that can never render a label (no
     * guarantor) and, once the guarantor condition is added, must still honour
     * variant field inheritance. That inheritance is applied at load time, not
     * stored in the variant row (an empty/zero child field falls back to the
     * parent), so the query resolves the effective value via a parent self-join
     * rather than reading the raw variant row.
     *
     * Scoped by `oxshopid` — a multi-shop installation must not let one shop's
     * qualifying products trigger or suppress the hint for another shop.
     */
    protected function hasQualifyingProduct(): bool
    {
        $database = DatabaseProvider::getDb();
        $count = $database->getOne(
            'SELECT 1'
            . ' FROM `oxarticles` AS `a`'
            . ' LEFT JOIN `oxarticles` AS `p` ON `a`.`OXPARENTID` = `p`.`OXID`'
            . ' LEFT JOIN `oxmanufacturers` AS `m`'
            . "   ON `m`.`OXID` = CASE WHEN `a`.`OXMANUFACTURERID` <> ''"
            . '        THEN `a`.`OXMANUFACTURERID` ELSE `p`.`OXMANUFACTURERID` END'
            . ' WHERE `a`.`OXSHOPID` = :oxshopid'
            . '   AND (CASE WHEN `a`.`O3GUARANTEEDURATIONMONTHS` > 0'
            . '        THEN `a`.`O3GUARANTEEDURATIONMONTHS`'
            . '        ELSE COALESCE(`p`.`O3GUARANTEEDURATIONMONTHS`, 0) END) > 24'
            . '   AND ('
            . "        (CASE WHEN `a`.`O3GUARANTEEGUARANTOR` <> ''"
            . '         THEN `a`.`O3GUARANTEEGUARANTOR`'
            . "         ELSE COALESCE(`p`.`O3GUARANTEEGUARANTOR`, '') END) <> ''"
            . "        OR COALESCE(`m`.`OXTITLE`, '') <> ''"
            . '   )'
            . ' LIMIT 1',
            [':oxshopid' => Registry::getConfig()->getShopId()]
        );

        return (bool) $count;
    }

    /**
     * Test seam: assert internal state after a save() call without touching the DB.
     *
     * @return array<string,string>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Test seam: assert internal state after a save() call.
     *
     * @return array<string,mixed>
     */
    public function getSubmittedValues(): array
    {
        return $this->submittedValues;
    }

    public function getNoQualifyingProductsHint(): bool
    {
        return $this->noQualifyingProductsHint;
    }
}
