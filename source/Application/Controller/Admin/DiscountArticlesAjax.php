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

use oxDb;
use oxField;

/**
 * Class manages discount articles
 */
class DiscountArticlesAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
{
    /**  */
    const NEW_DISCOUNT_LIST_ID = "-1";

    /**
     * If true extended column selection will be build
     *
     * @var bool
     */
    protected $_blAllowExtColumns = true;

    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = [
        // field , table, visible, multilanguage, id
        'container1' => [
            ['oxartnum', 'oxarticles', 1, 0, 0],
            ['oxtitle', 'oxarticles', 1, 1, 0],
            ['oxean', 'oxarticles', 1, 0, 0],
            ['oxmpn', 'oxarticles', 0, 0, 0],
            ['oxprice', 'oxarticles', 0, 0, 0],
            ['oxstock', 'oxarticles', 0, 0, 0],
            ['oxid', 'oxarticles', 0, 0, 1]
        ],
        'container2' => [
            ['oxartnum', 'oxarticles', 1, 0, 0],
            ['oxtitle', 'oxarticles', 1, 1, 0],
            ['oxean', 'oxarticles', 1, 0, 0],
            ['oxmpn', 'oxarticles', 0, 0, 0],
            ['oxprice', 'oxarticles', 0, 0, 0],
            ['oxstock', 'oxarticles', 0, 0, 0],
            ['oxid', 'oxobject2discount', 0, 0, 1]
        ]
    ];

    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oConfig = $this->getConfig();

        $sArticleTable = $this->_getViewName('oxarticles');
        $sO2CView = $this->_getViewName('oxobject2category');

        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sOxid = $oConfig->getRequestParameter('oxid');
        $sSynchOxid = $oConfig->getRequestParameter('synchoxid');

        // category selected or not ?
        if (!$sOxid && $sSynchOxid) {
            $sQAdd = " from $sArticleTable where 1 ";
            $sQAdd .= $oConfig->getConfigParam('blVariantsSelection') ? '' : "and $sArticleTable.oxparentid = '' ";
        } else {
            // selected category ?
            if ($sSynchOxid && $sOxid != $sSynchOxid) {
                $sQAdd = " from $sO2CView left join $sArticleTable on ";
                $sQAdd .= $oConfig->getConfigParam('blVariantsSelection') ? "($sArticleTable.oxid=$sO2CView.oxobjectid or $sArticleTable.oxparentid=$sO2CView.oxobjectid)" : " $sArticleTable.oxid=$sO2CView.oxobjectid ";
                $sQAdd .= " where $sO2CView.oxcatnid = " . $oDb->quote($sOxid) . " and $sArticleTable.oxid is not null ";

                // resetting
                $sId = null;
            } else {
                $sQAdd = " from oxobject2discount, $sArticleTable where $sArticleTable.oxid=oxobject2discount.oxobjectid ";
                $sQAdd .= " and oxobject2discount.oxdiscountid = " . $oDb->quote($sOxid) . " and oxobject2discount.oxtype = 'oxarticles' ";
            }
        }

        if ($sSynchOxid && $sSynchOxid != $sOxid) {
            // performance
            $sSubSelect = " select $sArticleTable.oxid from oxobject2discount, $sArticleTable where $sArticleTable.oxid=oxobject2discount.oxobjectid ";
            $sSubSelect .= " and oxobject2discount.oxdiscountid = " . $oDb->quote($sSynchOxid) . " and oxobject2discount.oxtype = 'oxarticles' ";

            if (stristr($sQAdd, 'where') === false) {
                $sQAdd .= ' where ';
            } else {
                $sQAdd .= ' and ';
            }
            $sQAdd .= " $sArticleTable.oxid not in ( $sSubSelect ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes selected article (articles) from discount list
     */
    public function removeDiscArt()
    {
        $aChosenArt = $this->_getActionIds('oxobject2discount.oxid');

        if ($this->getConfig()->getRequestParameter('all')) {
            $sQ = parent::_addFilter("delete oxobject2discount.* " . $this->_getQuery());
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQ);
        } elseif (is_array($aChosenArt)) {
            $sQ = "delete from oxobject2discount where oxobject2discount.oxid in (" . implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($aChosenArt)) . ") ";
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQ);
        }
    }

    /**
     * Adds selected article (articles) to discount list
     */
    public function addDiscArt()
    {
        $config = $this->getConfig();
        $articleIds = $this->_getActionIds('oxarticles.oxid');
        $discountListId = $config->getRequestParameter('synchoxid');

        // adding
        if ($config->getRequestParameter('all')) {
            $articleTable = $this->_getViewName('oxarticles');
            $articleIds = $this->_getAll(parent::_addFilter("select $articleTable.oxid " . $this->_getQuery()));
        }
        if ($discountListId && $discountListId != self::NEW_DISCOUNT_LIST_ID && is_array($articleIds)) {
            foreach ($articleIds as $articleId) {
                $this->addArticleToDiscount($discountListId, $articleId);
            }
        }
    }

    /**
     * Adds article to discount list
     *
     * @param string $discountListId
     * @param string $articleId
     */
    protected function addArticleToDiscount($discountListId, $articleId)
    {
        $object2Discount = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $object2Discount->init('oxobject2discount');
        $object2Discount->oxobject2discount__oxdiscountid = new \OxidEsales\Eshop\Core\Field($discountListId);
        $object2Discount->oxobject2discount__oxobjectid = new \OxidEsales\Eshop\Core\Field($articleId);
        $object2Discount->oxobject2discount__oxtype = new \OxidEsales\Eshop\Core\Field("oxarticles");

        $object2Discount->save();
    }
}
