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

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\ListObject;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * Admin order article manager.
 * Collects order articles information, updates it on user submit, etc.
 * Admin Menu: Orders -> Display Orders -> Articles.
 */
class OrderArticle extends AdminDetailsController
{
    /**
     * Product which was currently found by search
     *
     * @var Article
     */
    protected $_oSearchProduct = null;

    /**
     * Product list:
     *  - if product is not variant - list contains only product which was found by search;
     *  - if product is variant - list consist with variant parent and its variants
     *
     * @var ListObject
     */
    protected $_oSearchProductList = null;

    /**
     * Product found by search. If product is variant - it keeps parent object
     *
     * @var Article
     */
    protected $_oMainSearchProduct = null;

    /**
     * Active order object
     *
     * @var Order
     */
    protected $_oEditObject = null;

    /**
     * Executes parent method parent::render(), creates Order and VoucherList
     * objects, appends VoucherList information to order object and passes data
     * to Smarty engine, returns name of template file "order_article.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        if ($oOrder = $this->getEditObject()) {
            $this->_aViewData["edit"] = $oOrder;
            $this->_aViewData["aProductVats"] = $oOrder->getProductVats(true);
        }

        return "order_article.tpl";
    }

    /**
     * Returns editable order object
     *
     * @return Order
     */
    public function getEditObject()
    {
        $soxId = $this->getEditObjectId();
        if ($this->_oEditObject === null && isset($soxId) && $soxId != "-1") {
            $this->_oEditObject = oxNew(Order::class);
            $this->_oEditObject->load($soxId);
        }

        return $this->_oEditObject;
    }

    /**
     * Returns user written product number
     *
     * @return string
     */
    public function getSearchProductArtNr()
    {
        return Registry::getRequest()->getRequestEscapedParameter('sSearchArtNum');
    }

    /**
     * If possible returns searched/found oxarticle object
     *
     * @return Article | false
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getSearchProduct()
    {
        if ($this->_oSearchProduct === null) {
            $this->_oSearchProduct = false;
            $sSearchArtNum = $this->getSearchProductArtNr();

            foreach ($this->getProductList() as $oProduct) {
                if ($oProduct->oxarticles__oxartnum->value == $sSearchArtNum) {
                    $this->_oSearchProduct = $oProduct;
                    break;
                }
            }
        }

        return $this->_oSearchProduct;
    }

    /**
     * Returns product found by search. If product is variant - returns parent object
     *
     * @return object
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getMainProduct()
    {
        if ($this->_oMainSearchProduct === null && ($sArtNum = $this->getSearchProductArtNr())) {
            $this->_oMainSearchProduct = false;

            //get article id
            $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
            $sTable = Registry::get(TableViewNameGenerator::class)->getViewName("oxarticles");
            $sQ = "select oxid, oxparentid from $sTable where oxartnum = :oxartnum limit 1";

            $rs = $oDb->select($sQ, [
                ':oxartnum' => $sArtNum
            ]);
            if ($rs && $rs->count() > 0) {
                $sArtId = $rs->fields['OXPARENTID'] ? $rs->fields['OXPARENTID'] : $rs->fields['OXID'];

                $oProduct = oxNew(Article::class);
                if ($oProduct->load($sArtId)) {
                    $this->_oMainSearchProduct = $oProduct;
                }
            }
        }

        return $this->_oMainSearchProduct;
    }

    /**
     * Returns product list containing searchable product or its parent and its variants
     *
     * @return ListObject
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getProductList()
    {
        if ($this->_oSearchProductList === null) {
            $this->_oSearchProductList = oxNew(ListModel::class);

            // main search product is found?
            if ($oMainSearchProduct = $this->getMainProduct()) {
                // storing self to first list position
                $this->_oSearchProductList->offsetSet($oMainSearchProduct->getId(), $oMainSearchProduct);

                // adding variants..
                foreach ($oMainSearchProduct->getVariants() as $oVariant) {
                    $this->_oSearchProductList->offsetSet($oVariant->getId(), $oVariant);
                }
            }
        }

        return $this->_oSearchProductList;
    }

    /**
     * Adds article to order list.
     */
    public function addThisArticle()
    {
        $sOxid = Registry::getRequest()->getRequestEscapedParameter('aid');
        $dAmount = Registry::getRequest()->getRequestEscapedParameter('am');
        $oProduct = oxNew(Article::class);

        if ($sOxid && $dAmount && $oProduct->load($sOxid)) {
            $sOrderId = $this->getEditObjectId();
            $oOrder = oxNew(Order::class);
            if ($sOrderId && $oOrder->load($sOrderId)) {
                $oOrderArticle = oxNew(\OxidEsales\Eshop\Application\Model\OrderArticle::class);
                $oOrderArticle->oxorderarticles__oxartid = new Field($oProduct->getId());
                $oOrderArticle->oxorderarticles__oxartnum = new Field($oProduct->oxarticles__oxartnum->value);
                $oOrderArticle->oxorderarticles__oxamount = new Field($dAmount);
                $oOrderArticle->oxorderarticles__oxselvariant = new Field(
                    Registry::getRequest()->getRequestEscapedParameter('sel'));
                $oOrder->recalculateOrder([$oOrderArticle]);
            }
        }
    }

    /**
     * Removes article from order list.
     */
    public function deleteThisArticle()
    {
        // get article id
        $sOrderArtId = Registry::getRequest()->getRequestEscapedParameter('sArtID');
        $sOrderId = $this->getEditObjectId();

        $oOrderArticle = oxNew(\OxidEsales\Eshop\Application\Model\OrderArticle::class);
        $oOrder = oxNew(Order::class);

        // order and order article exits?
        if ($oOrderArticle->load($sOrderArtId) && $oOrder->load($sOrderId)) {
            // deleting record
            $oOrderArticle->delete();

            // recalculating order
            $oOrder->recalculateOrder();
        }
    }

    /**
     * Cancels order item
     */
    public function storno()
    {
        $myConfig = Registry::getConfig();

        $sOrderArtId = Registry::getRequest()->getRequestEscapedParameter('sArtID');
        $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\OrderArticle::class);
        $oArticle->load($sOrderArtId);

        if ($oArticle->oxorderarticles__oxstorno->value == 1) {
            $oArticle->oxorderarticles__oxstorno->setValue(0);
            $sStockSign = -1;
        } else {
            $oArticle->oxorderarticles__oxstorno->setValue(1);
            $sStockSign = 1;
        }

        // stock information
        if ($myConfig->getConfigParam('blUseStock')) {
            $oArticle->updateArticleStock($oArticle->oxorderarticles__oxamount->value * $sStockSign, $myConfig->getConfigParam('blAllowNegativeStock'));
        }

        $oDb = DatabaseProvider::getDb();
        $sQ = "update oxorderarticles set oxstorno = :oxstorno where oxid = :oxid";
        $oDb->execute($sQ, [':oxstorno' => $oArticle->oxorderarticles__oxstorno->value, ':oxid' => $sOrderArtId]);

        //get article id
        $sQ = "select oxartid from oxorderarticles where oxid = :oxid";
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        if ((DatabaseProvider::getMaster()->getOne($sQ, [':oxid' => $sOrderArtId]))) {
            $oOrder = oxNew(Order::class);
            if ($oOrder->load($this->getEditObjectId())) {
                $oOrder->recalculateOrder();
            }
        }
    }

    /**
     * Updates order articles stock and recalculates order
     */
    public function updateOrder()
    {
        $aOrderArticles = Registry::getRequest()->getRequestEscapedParameter('aOrderArticles');

        $oOrder = oxNew(Order::class);
        if (is_array($aOrderArticles) && $oOrder->load($this->getEditObjectId())) {
            $myConfig = Registry::getConfig();
            $oOrderArticles = $oOrder->getOrderArticles(true);

            $blUseStock = $myConfig->getConfigParam('blUseStock');
            foreach ($oOrderArticles as $oOrderArticle) {
                $sItemId = $oOrderArticle->getId();
                if (isset($aOrderArticles[$sItemId])) {
                    // update stock
                    if ($blUseStock) {
                        $oOrderArticle->setNewAmount($aOrderArticles[$sItemId]['oxamount']);
                    } else {
                        $oOrderArticle->assign($aOrderArticles[$sItemId]);
                        $oOrderArticle->save();
                    }
                }
            }

            // recalculating order
            $oOrder->recalculateOrder();
        }
    }
}
