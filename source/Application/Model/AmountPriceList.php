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

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Article amount price list
 *
 */
class AmountPriceList extends ListModel
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
     * @var Article
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
     * @return Article $_oArticle
     */
    public function getArticle()
    {
        return $this->_oArticle;
    }

    /**
     * Article setter
     *
     * @param Article $oArticle Article
     */
    public function setArticle($oArticle)
    {
        $this->_oArticle = $oArticle;
    }

    /**
     * Load category list data
     *
     * @param Article $article Article
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
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
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadFromDb" in next major
     */
    protected function _loadFromDb() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArticleId = $this->getArticle()->getId();
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        if (Registry::getConfig()->getConfigParam('blVariantInheritAmountPrice') && $this->getArticle()->getParentId()) {
            $sArticleId = $this->getArticle()->getParentId();
        }

        $params = [
            ':oxartid' => $sArticleId
        ];

        if (Registry::getConfig()->getConfigParam('blMallInterchangeArticles')) {
            $sShopSelect = '1';
        } else {
            $sShopSelect = " `oxshopid` = :oxshopid ";
            $params[':oxshopid'] = Registry::getConfig()->getShopId();
        }

        $sSql = "SELECT * FROM `oxprice2article` 
            WHERE `oxartid` = :oxartid AND $sShopSelect ORDER BY `oxamount` ";

        return $db->getAll($sSql, $params);
    }
}
