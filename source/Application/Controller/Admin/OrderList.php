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
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin order list manager.
 * Performs collection and managing (such as filtering or deleting) function.
 * Admin Menu: Orders -> Display Orders.
 */
class OrderList extends AdminListController
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = 'oxorder';

    /**
     * Enable/disable sorting by DESC (SQL) (default false - disable).
     *
     * @var bool
     */
    protected $_blDesc = true;

    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_sDefSortField = "oxorderdate";

    /**
     * Executes parent method parent::render() and returns name of template
     * file "order_list.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();

        $folders = Registry::getConfig()->getConfigParam('aOrderfolder');
        $folder = Registry::getRequest()->getRequestEscapedParameter('folder');
        // first display new orders
        if (!$folder && is_array($folders)) {
            $names = array_keys($folders);
            $folder = $names[0];
        }

        $search = ['oxorderarticles' => 'ARTID', 'oxpayments' => 'PAYMENT'];
        $searchQuery = Registry::getRequest()->getRequestEscapedParameter('addsearch');
        $searchField = Registry::getRequest()->getRequestEscapedParameter('addsearchfld');

        $this->_aViewData["folder"] = $folder ? $folder : -1;
        $this->_aViewData["addsearchfld"] = $searchField ? $searchField : -1;
        $this->_aViewData["asearch"] = $search;
        $this->_aViewData["addsearch"] = $searchQuery;
        $this->_aViewData["afolder"] = $folders;

        return "order_list.tpl";
    }

    /**
     * Cancels order and its order articles
     *
     * @deprecated since 6.0 (2015-09-17); use self::cancelOrder().
     */
    public function storno()
    {
        $this->cancelOrder();
    }

    /**
     * Cancels order and its order articles
     * Calls init() to reload list items after cancellation.
     */
    public function cancelOrder()
    {
        $order = oxNew(Order::class);
        if ($order->load($this->getEditObjectId())) {
            $order->cancelOrder();
        }

        $this->resetContentCache();

        $this->init();
    }

    /**
     * Returns sorting fields array
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    public function getListSorting()
    {
        $sorting = parent::getListSorting();
        if (isset($sorting["oxorder"]["oxbilllname"])) {
            $this->_blDesc = false;
        }

        return $sorting;
    }

    /**
     * Adding folder check
     *
     * @param array $whereQuery SQL condition array
     * @param string $fullQuery SQL query string
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareWhereQuery" in next major
     */
    protected function _prepareWhereQuery($whereQuery, $fullQuery) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $database = DatabaseProvider::getDb();
        $query = parent::_prepareWhereQuery($whereQuery, $fullQuery);
        $config = Registry::getConfig();
        $folders = $config->getConfigParam('aOrderfolder');
        $folder = Registry::getRequest()->getRequestEscapedParameter('folder');
        // Searching for empty oxfolder fields
        if ($folder && $folder != '-1') {
            $query .= " and ( oxorder.oxfolder = " . $database->quote($folder) . " )";
        } elseif (!$folder && is_array($folders)) {
            $folderNames = array_keys($folders);
            $query .= " and ( oxorder.oxfolder = " . $database->quote($folderNames[0]) . " )";
        }

        return $query;
    }

    /**
     * Builds and returns SQL query string. Adds additional order check.
     *
     * @param null $listObject list main object
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "buildSelectString" in next major
     */
    protected function _buildSelectString($listObject = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $query = parent::_buildSelectString($listObject);
        $database = DatabaseProvider::getDb();

        $searchQuery = Registry::getRequest()->getRequestEscapedParameter('addsearch');
        $searchQuery = trim($searchQuery);
        $searchField = Registry::getRequest()->getRequestEscapedParameter('addsearchfld');

        if ($searchQuery) {
            switch ($searchField) {
                case 'oxorderarticles':
                    $queryPart = "oxorder left join oxorderarticles on oxorderarticles.oxorderid=oxorder.oxid where ( oxorderarticles.oxartnum like " . $database->quote("%{$searchQuery}%") . " or oxorderarticles.oxtitle like " . $database->quote("%{$searchQuery}%") . " ) and ";
                    break;
                case 'oxpayments':
                    $queryPart = "oxorder left join oxpayments on oxpayments.oxid=oxorder.oxpaymenttype where oxpayments.oxdesc like " . $database->quote("%{$searchQuery}%") . " and ";
                    break;
                default:
                    $queryPart = "oxorder where oxorder.oxpaid like " . $database->quote("%{$searchQuery}%") . " and ";
                    break;
            }
            $query = str_replace('oxorder where', $queryPart, $query);
        }

        return $query;
    }
}
