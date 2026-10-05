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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Application\Component;

use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\Registry;

/**
 * Shop language manager.
 * Performs language manager function: changes template settings, modifies URLs.
 *
 * @subpackage oxcmp
 */
class LanguageComponent extends BaseController
{
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_blIsComponent = true;

    /**
     * Executes parent::render() and returns array with languages.
     *
     * @return array|void $this->aLanguages languages
     */
    public function render()
    {
        parent::render();

        // Performance
        if (Registry::getConfig()->getConfigParam('bl_perfLoadLanguages')) {
            $aLanguages = Registry::getLang()->getLanguageArray(null, true, true);
            reset($aLanguages);
            foreach ($aLanguages as $oVal) {
                $oVal->link = Registry::getConfig()->getTopActiveView()->getLink($oVal->id);
            }

            return $aLanguages;
        }
    }
}
