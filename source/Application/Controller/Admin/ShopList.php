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
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Admin shop list manager.
 * Performs collection and managing (such as filtering or deleting) function.
 * Admin Menu: Main Menu -> Core Settings.
 */
class ShopList extends AdminListController
{
    /** New Shop indicator. */
    const NEW_SHOP_ID = '-1';

    /**
     * Forces main frame update is set TRUE
     *
     * @var bool
     */
    protected $_blUpdateMain = false;

    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_sDefSortField = 'oxname';

    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxshop';

    /**
     * Navigation frame reload marker
     *
     * @var bool
     */
    protected $_blUpdateNav = null;

    /**
     * Executes parent method parent::render() and returns name of template
     * file "shop_list.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        $myConfig = Registry::getConfig();

        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != self::NEW_SHOP_ID) {
            // load object
            $oShop = oxNew(Shop::class);
            if (!$oShop->load($soxId)) {
                $soxId = $myConfig->getBaseShopId();
                $oShop->load($soxId);
            }
            $this->_aViewData['editshop'] = $oShop;
        }

        // default page number 1
        $this->_aViewData['default_edit'] = 'shop_main';
        $this->_aViewData['updatemain'] = $this->_blUpdateMain;

        $this->updateNavigation();

        if ($this->_aViewData['updatenav']) {
            //skipping requirements checking when reloading nav frame
            Registry::getSession()->setVariable("navReload", true);
        }

        //making sure we really change shops on low level
        if ($soxId && $soxId != self::NEW_SHOP_ID) {
            $myConfig->setShopId($soxId);
            Registry::getSession()->setVariable('currentadminshop', $soxId);
        }

        return 'shop_list.tpl';
    }

    /**
     * Sets SQL WHERE condition. Returns array of conditions.
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function buildWhere()
    {
        // we override this to add our shop if we are not malladmin
        $this->_aWhere = parent::buildWhere();
        if (!Registry::getSession()->getVariable('malladmin')) {
            // we only allow to see our shop
            $this->_aWhere[Registry::get(TableViewNameGenerator::class)->getViewName("oxshops") . ".oxid"] = Registry::getSession()->getVariable("actshop");
        }

        return $this->_aWhere;
    }

    /**
     * Set to view data if update navigation menu.
     */
    protected function updateNavigation()
    {
    }
}
