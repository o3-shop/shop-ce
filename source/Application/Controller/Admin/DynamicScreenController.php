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

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\Eshop\Core\UtilsUrl;

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
class DynamicScreenController extends AdminListController
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
     * @deprecated Use setupNavigation() instead. This underscore-prefixed name is retained only
     *             for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override _setupNavigation().
     */
    protected function _setupNavigation($sNode) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myAdminNavig = $this->getNavigation();
        $sNode = Registry::getRequest()->getRequestEscapedParameter('menu');

        // active tab
        $iActTab = Registry::getRequest()->getRequestEscapedParameter('actedit');
        $iActTab = $iActTab ? $iActTab : $this->_iDefEdit;

        $sActTab = $iActTab ? "&actedit=$iActTab" : '';

        // list url
        $this->_aViewData['listurl'] = $myAdminNavig->getListUrl($sNode) . $sActTab;

        // edit url
        $sEditUrl = $myAdminNavig->getEditUrl($sNode, $iActTab) . $sActTab;
        if (!Str::getStr()->preg_match("/^http(s)?:\/\//", $sEditUrl)) {
            //internal link, adding path
            /** @var UtilsUrl $oUtilsUrl */
            $oUtilsUrl = Registry::getUtilsUrl();
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

        // passing active tab number
        $this->_aViewData['actedit'] = $iActTab;

        // buttons
        $this->_aViewData['bottom_buttons'] = $myAdminNavig->getBtn($sNode);
    }

    /**
     * Sets up navigation for current view
     *
     * @param string $sNode None name
     *
     * @internal If your override does not fully replace the behavior, call parent::setupNavigation()
     *           (not the deprecated _setupNavigation()) so downstream overrides in the class chain
     *           are preserved. Template-method refactor tracked in o3-shop/o3-shop#108.
     */
    protected function setupNavigation($sNode)
    {
        $this->_setupNavigation($sNode);
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
