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

namespace OxidEsales\EshopCommunity\Application\Model;

use oxDb;

/**
 * Article amount price list
 *
 */
class AmountPriceList extends \OxidEsales\Eshop\Core\Model\ListModel
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName = 'oxprice2article';

    /**
     * oxArticle object
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_oArticle = null;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('oxbase');
        $this->init('oxbase', 'oxprice2article');
    }

    /**
     *  Article getter
     *
     * @return \OxidEsales\Eshop\Application\Model\Article $_oArticle
     */
    public function getArticle()
    {
        return $this->_oArticle;
    }

    /**
     * Article setter
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle Article
     */
    public function setArticle($oArticle)
    {
        $this->_oArticle = $oArticle;
    }

    /**
     * Load category list data
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article Article
     */
    public function load($article)
    {
        $this->setArticle($article);

        $aData = $this->_loadFromDb();

        $this->assignArray($aData);
    }

    /**
     * Get data from db
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadFromDb" in next major
     */
    protected function _loadFromDb() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArticleId = $this->getArticle()->getId();
        $db = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);

        if ($this->getConfig()->getConfigParam('blVariantInheritAmountPrice') && $this->getArticle()->getParentId()) {
            $sArticleId = $this->getArticle()->getParentId();
        }

        $params = [
            ':oxartid' => $sArticleId
        ];

        if ($this->getConfig()->getConfigParam('blMallInterchangeArticles')) {
            $sShopSelect = '1';
        } else {
            $sShopSelect = " `oxshopid` = :oxshopid ";
            $params[':oxshopid'] = $this->getConfig()->getShopId();
        }

        $sSql = "SELECT * FROM `oxprice2article` 
            WHERE `oxartid` = :oxartid AND $sShopSelect ORDER BY `oxamount` ";

        return $db->getAll($sSql, $params);
    }
}
