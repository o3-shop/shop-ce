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

namespace OxidEsales\EshopCommunity\Application\Component\Widget;

/**
 * Recomendation list.
 * Forms recomendation list.
 */
class ServiceMenu extends \OxidEsales\Eshop\Application\Component\Widget\WidgetController
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * User component used in template.
     *
     * @var array
     */
    protected $_aComponentNames = ['oxcmp_user' => 1];

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'widget/header/servicemenu.tpl';

    /**
     * Template variable getter. Returns article list count in comparison.
     *
     * @deprecated since v6.1.0 (2017-12-12); Use OxidEsales\Eshop\Application\Controller\FrontendController::getCompareItemsCnt().
     *
     * @return integer
     */
    public function getCompareItemsCnt()
    {
        return parent::getCompareItemsCnt();
    }

    /**
     * Template variable getter. Returns comparison article list.
     *
     * @param bool $blJson return json encoded array
     *
     * @return array
     */
    public function getCompareItems($blJson = false)
    {
        $oCompare = oxNew(\OxidEsales\Eshop\Application\Controller\CompareController::class);
        $aCompareItems = $oCompare->getCompareItems();

        if ($blJson) {
            $aCompareItems = json_encode($aCompareItems);
        }

        return $aCompareItems;
    }
}
