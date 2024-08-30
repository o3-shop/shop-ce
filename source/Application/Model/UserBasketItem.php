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

use OxidEsales\Eshop\Core\Exception\ArticleException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Shopping basket item manager.
 * Manager class for shopping basket item (class may be overriden).
 *
 */
class UserBasketItem extends BaseModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxuserbasketitem';

    /**
     * Article object assigned to userbasketitem
     *
     * @var Article
     */
    protected $_oArticle = null;

    /**
     * Variant parent "buyable" status
     *
     * @var bool
     */
    protected $_blParentBuyable = false;

    /**
     * Basket item selection list
     *
     * @var array
     */
    protected $_aSelList = null;

    /**
     * Basket item persistent parameters
     *
     * @var array
     */
    protected $_aPersParam = null;

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        $this->setVariantParentBuyable(Registry::getConfig()->getConfigParam('blVariantParentBuyable'));
        parent::__construct();
        $this->init('oxuserbasketitems');
    }

    /**
     * Variant parent "buyable" status setter
     *
     * @param bool $blBuyable parent "buyable" status
     */
    public function setVariantParentBuyable($blBuyable = false)
    {
        $this->_blParentBuyable = $blBuyable;
    }

    /**
     * Loads and returns the article for that basket item
     *
     * @param string $sItemKey the key that will be given to oxarticle setItemKey
     *
     * @throws ArticleException article exception
     *
     * @return Article|bool
     */
    public function getArticle($sItemKey)
    {
        if (!$this->oxuserbasketitems__oxartid->value) {
            //this exception may not be caught, anyhow this is a critical exception
            $oEx = oxNew(ArticleException::class);
            $oEx->setMessage('EXCEPTION_ARTICLE_NOPRODUCTID');
            throw $oEx;
        }

        if ($this->_oArticle === null) {
            $this->_oArticle = oxNew(Article::class);

            // performance
            /* removed due to #4178
             if ( $this->_blParentBuyable ) {
                $this->_oArticle->setNoVariantLoading( true );
            }
            */

            if (!$this->_oArticle->load($this->oxuserbasketitems__oxartid->value)) {
                return false;
            }

            $aSelList = $this->getSelList();
            if (($aSelectlist = $this->_oArticle->getSelectLists()) && is_array($aSelList)) {
                foreach ($aSelList as $iKey => $iSel) {
                    if (isset($aSelectlist[$iKey][$iSel])) {
                        // cloning select list information
                        $aSelectlist[$iKey][$iSel] = clone $aSelectlist[$iKey][$iSel];
                        $aSelectlist[$iKey][$iSel]->selected = 1;
                    }
                }
                $this->_oArticle->setSelectlist($aSelectlist);
            }

            // generating item key
            $this->_oArticle->setItemKey($sItemKey);
        }

        return $this->_oArticle;
    }

    /**
     * Does not return _oArticle var on serialisation
     *
     * @return array
     */
    public function __sleep()
    {
        $aRet = [];
        foreach (get_object_vars($this) as $sKey => $sVar) {
            if ($sKey != '_oArticle') {
                $aRet[] = $sKey;
            }
        }

        return $aRet;
    }

    /**
     * Basket item selection list getter
     *
     * @return array
     */
    public function getSelList()
    {
        if ($this->_aSelList == null && $this->oxuserbasketitems__oxsellist->value) {
            $this->_aSelList = unserialize($this->oxuserbasketitems__oxsellist->value);
        }

        return $this->_aSelList;
    }

    /**
     * Basket item selection list setter
     *
     * @param array $aSelList selection list
     */
    public function setSelList($aSelList)
    {
        $this->oxuserbasketitems__oxsellist = new Field(serialize($aSelList), Field::T_RAW);
    }

    /**
     * Basket item persistent parameters getter
     *
     * @return array
     */
    public function getPersParams()
    {
        if ($this->_aPersParam == null && $this->oxuserbasketitems__oxpersparam->value) {
            $this->_aPersParam = unserialize($this->oxuserbasketitems__oxpersparam->value);
        }

        return $this->_aPersParam;
    }

    /**
     * Basket item persistent parameters setter
     *
     * @param array $aPersParams persistent parameters
     */
    public function setPersParams($aPersParams)
    {
        $this->oxuserbasketitems__oxpersparam = new Field(serialize($aPersParams), Field::T_RAW);
    }

    /**
     * Sets data field value
     *
     * @param string $sFieldName index OR name (eg. 'oxarticles__oxtitle') of a data field to set
     * @param string $sValue     value of data field
     * @param int    $iDataType  field type
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "setFieldData" in next major
     */
    protected function _setFieldData($sFieldName, $sValue, $iDataType = Field::T_TEXT) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (
            'oxsellist' === strtolower($sFieldName) || 'oxuserbasketitems__oxsellist' === strtolower($sFieldName)
            || 'oxpersparam' === strtolower($sFieldName) || 'oxuserbasketitems__oxpersparam' === strtolower($sFieldName)
        ) {
            $iDataType = Field::T_RAW;
        }

        return parent::_setFieldData($sFieldName, $sValue, $iDataType);
    }
}
