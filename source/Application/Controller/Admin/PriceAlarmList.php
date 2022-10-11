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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

/**
 * Admin pricealarm list manager.
 * Performs collection and managing (such as filtering or deleting) function.
 * Admin Menu: Customer Info -> pricealarm.
 */
class PriceAlarmList extends \OxidEsales\Eshop\Application\Controller\Admin\AdminListController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'pricealarm_list.tpl';

    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxpricealarm';

    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_sDefSortField = "oxuserid";

    /**
     * Modifying SQL query to load additional article and customer data
     *
     * @param object $oListObject list main object
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "buildSelectString" in next major
     */
    protected function _buildSelectString($oListObject = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sViewName = getViewName("oxarticles", (int) $this->getConfig()->getConfigParam("sDefaultLang"));
        $sSql = "select oxpricealarm.*, {$sViewName}.oxtitle AS articletitle, ";
        $sSql .= "oxuser.oxlname as userlname, oxuser.oxfname as userfname ";
        $sSql .= "from oxpricealarm left join {$sViewName} on {$sViewName}.oxid = oxpricealarm.oxartid ";
        $sSql .= "left join oxuser on oxuser.oxid = oxpricealarm.oxuserid WHERE 1 ";

        return $sSql;
    }

    /**
     * Builds and returns array of SQL WHERE conditions
     *
     * @return array
     */
    public function buildWhere()
    {
        $this->_aWhere = parent::buildWhere();
        $sViewName = getViewName("oxpricealarm");
        $sArtViewName = getViewName("oxarticles");

        // updating price fields values for correct search in DB
        if (isset($this->_aWhere[$sViewName . '.oxprice'])) {
            $sPriceParam = (double) str_replace(['%', ','], ['', '.'], $this->_aWhere[$sViewName . '.oxprice']);
            $this->_aWhere[$sViewName . '.oxprice'] = '%' . $sPriceParam . '%';
        }

        if (isset($this->_aWhere[$sArtViewName . '.oxprice'])) {
            $sPriceParam = (double) str_replace(['%', ','], ['', '.'], $this->_aWhere[$sArtViewName . '.oxprice']);
            $this->_aWhere[$sArtViewName . '.oxprice'] = '%' . $sPriceParam . '%';
        }

        return $this->_aWhere;
    }
}
