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

use oxRegistry;

/**
 * Admin dynscreen manager.
 * Returns template, that arranges two other templates ("dynscreen_list.tpl"
 * and "dyn_affiliates_about.tpl") to frame.
 *
 * @subpackage dyn
 *
 * @deprecated since v5.3 (2016-05-20); Dynpages will be removed.
 *
 */
class DynamicScreenController extends \OxidEsales\Eshop\Application\Controller\Admin\AdminListController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'dynscreen.tpl';

    /**
     * Sets up navigation for current view
     *
     * @param string $sNode None name
     * @deprecated underscore prefix violates PSR12, will be renamed to "setupNavigation" in next major
     */
    protected function _setupNavigation($sNode) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myAdminNavig = $this->getNavigation();
        $sNode = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("menu");

        // active tab
        $iActTab = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('actedit');
        $iActTab = $iActTab ? $iActTab : $this->_iDefEdit;

        $sActTab = $iActTab ? "&actedit=$iActTab" : '';

        // list url
        $this->_aViewData['listurl'] = $myAdminNavig->getListUrl($sNode) . $sActTab;

        // edit url
        $sEditUrl = $myAdminNavig->getEditUrl($sNode, $iActTab) . $sActTab;
        if (!getStr()->preg_match("/^http(s)?:\/\//", $sEditUrl)) {
            //internal link, adding path
            /** @var \OxidEsales\Eshop\Core\UtilsUrl $oUtilsUrl */
            $oUtilsUrl = \OxidEsales\Eshop\Core\Registry::getUtilsUrl();
            $sSelfLinkParameter = $this->getViewConfig()->getViewConfigParam('selflink');
            $sEditUrl = $oUtilsUrl->appendParamSeparator($sSelfLinkParameter) . $sEditUrl;
        }

        $this->_aViewData['editurl'] = $sEditUrl;

        // tabs
        $this->_aViewData['editnavi'] = $myAdminNavig->getTabs($sNode, $iActTab);

        // active tab
        $this->_aViewData['actlocation'] = $myAdminNavig->getActiveTab($sNode, $iActTab);

        // default tab
        $this->_aViewData['default_edit'] = $myAdminNavig->getActiveTab($sNode, $this->_iDefEdit);

        // passign active tab number
        $this->_aViewData['actedit'] = $iActTab;

        // buttons
        $this->_aViewData['bottom_buttons'] = $myAdminNavig->getBtn($sNode);
    }

    /**
     * Returns dyn area view id
     *
     * @return string
     */
    public function getViewId()
    {
        return 'dyn_menu';
    }
}
