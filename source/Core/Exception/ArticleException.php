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

namespace OxidEsales\EshopCommunity\Core\Exception;

/**
 * Exception base class for an article
 */
class ArticleException extends \OxidEsales\Eshop\Core\Exception\StandardException
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxArticleException';

    /**
     * Article number who caused this exception
     *
     * @var string
     */
    protected $_sArticleNr = null;

    /**
     * Id of product which caused this exception
     *
     * @var string
     */
    protected $_sProductId = null;

    /**
     * Sets the article number of the article which caused the exception
     *
     * @param string $sArticleNr Article who causes the exception
     */
    public function setArticleNr($sArticleNr)
    {
        $this->_sArticleNr = $sArticleNr;
    }

    /**
     * The article number of the faulty article
     *
     * @return string
     */
    public function getArticleNr()
    {
        return $this->_sArticleNr;
    }

    /**
     * Sets the product id of the article which caused the exception
     *
     * @param string $sProductId id of product who causes the exception
     */
    public function setProductId($sProductId)
    {
        $this->_sProductId = $sProductId;
    }

    /**
     * Faulty product id
     *
     * @return string
     */
    public function getProductId()
    {
        return $this->_sProductId;
    }

    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function getString()
    {
        return __CLASS__ . '-' . parent::getString() . " Faulty Article --> " . $this->_sArticleNr . "\n";
    }


    /**
     * Override of oxException::getValues()
     *
     * @return array
     */
    public function getValues()
    {
        $aRes = parent::getValues();
        $aRes['articleNr'] = $this->getArticleNr();
        $aRes['productId'] = $this->getProductId();

        return $aRes;
    }
}
