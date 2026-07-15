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
use OxidEsales\Eshop\Core\Registry;

/**
 * EU guarantee labels (#219) - admin configuration page.
 *
 * Owns the two oxconfig switches:
 *   - blShowLegalGuaranteeNotice     (bool) - shop-global legal-guarantee
 *     notice (Reg. (EU) 2025/1960 Annex I) in the storefront footer
 *   - blShowDurabilityGuaranteeLabel (bool) - per-product durability-
 *     guarantee labels (Annex II)
 *
 * Fresh installs seed both ON (initial_data.sql); upgraded shops read the
 * code default FALSE until the operator opts in here. No cross-field rules,
 * no blocking validation - both switches are independent.
 */
class GuaranteeConfigController extends AdminDetailsController
{
    /** @var string */
    protected $_sThisTemplate = 'guarantee_config.tpl';

    private const FIELDS_BOOL = [
        'blShowLegalGuaranteeNotice',
        'blShowDurabilityGuaranteeLabel',
    ];

    /**
     * @return string admin template name to render
     */
    public function render()
    {
        $config = Registry::getConfig();
        $bag = [];
        foreach (self::FIELDS_BOOL as $key) {
            $bag[$key] = (bool) $config->getConfigParam($key, false);
        }
        $this->_aViewData['guarantee'] = $bag;

        return parent::render();
    }

    /**
     * @return void
     */
    public function save()
    {
        $request = Registry::getRequest();
        $config = Registry::getConfig();
        foreach (self::FIELDS_BOOL as $key) {
            $value = (bool) $request->getRequestParameter($key, 0);
            $config->saveShopConfVar('bool', $key, $value ? '1' : '');
        }
    }
}
