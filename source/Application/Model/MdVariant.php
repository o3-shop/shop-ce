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

use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\Registry;

/**
 * Defines an element of multidimensional variant name tree structure. Contains article id, variant name, URL, price, price text, and a subset of MD variants.
 *
 */
class MdVariant extends Base
{
    /**
     * MD variant identifier
     *
     * @var string
     */
    protected $_sId;

    /**
     * Parent ID
     *
     * @var string
     */
    protected $_sParentId;

    /**
     * Corresponding article id
     *
     * @var string
     */
    protected $_sArticleId;

    /**
     * Variant name
     *
     * @var string
     */
    protected $_sName;

    /**
     * Variant URL
     *
     * @var string
     */
    protected $_sUrl;

    /**
     * Variant price
     *
     * @var double
     */
    protected $_dPrice = null;

    /**
     * Variant Price text representation. E.g. "10,00 EUR" or "from 8,00 EUR"
     *
     * @var string
     */
    protected $_sFPrice;

    /**
     * Subvariant array
     *
     * @var array[string]oxMdVariant
     */
    protected $_aSubvariants = [];

    /**
     * Sets MD variant identifier
     *
     * @param string $sId New id
     */
    public function setId($sId)
    {
        $this->_sId = $sId;
    }

    /**
     * Returns MD variant identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->_sId;
    }

    /**
     * Sets parent id
     *
     * @param string $sParentId Parent id
     */
    public function setParentId($sParentId)
    {
        $this->_sParentId = $sParentId;
    }

    /**
     * Returns parent id
     *
     * @return string
     */
    public function getParentId()
    {
        return $this->_sParentId;
    }

    /**
     * Sets MD subvariants
     *
     * @param MdVariant[] $aSubvariants Subvariants
     */
    public function setMdSubvariants($aSubvariants)
    {
        $this->_aSubvariants = $aSubvariants;
    }

    /**
     * Returns full array of subvariants
     *
     * @return array[string]OxMdSubvariants
     */
    public function getMdSubvariants()
    {
        return $this->_aSubvariants;
    }

    /**
     * Returns first MD subvariant from subvariant set or null in case variant has no subvariants.
     *
     * @return MdVariant
     */
    public function getFirstMdSubvariant()
    {
        $aMdSubvariants = $this->getMdSubvariants();
        if (count($aMdSubvariants)) {
            return reset($aMdSubvariants);
        }

        return null;
    }

    /**
     * Checks for existing MD subvariant by name. Returns existing one or in case $sName has not been found creates an empty OxMdVariant instance.
     *
     * @param string $sName Subvariant name
     *
     * @return MdVariant
     */
    public function getMdSubvariantByName($sName)
    {
        $aSubvariants = $this->getMdSubvariants();
        foreach ($aSubvariants as $oMdSubvariant) {
            if (strcasecmp($oMdSubvariant->getName(), $sName) == 0) {
                return $oMdSubvariant;
            }
        }

        $oNewSubvariant = oxNew(MdVariant::class);
        $oNewSubvariant->setName($sName);
        $oNewSubvariant->setId(md5($sName . $this->getId()));
        $oNewSubvariant->setParentId($this->getId());
        $this->_addMdSubvariant($oNewSubvariant);

        return $oNewSubvariant;
    }

    /**
     * Returns corresponding article URL or recursively first variant URL from subvariant set
     *
     * @return string
     */
    public function getLink()
    {
        $oFirstSubvariant = $this->getFirstMdSubvariant();
        if ($oFirstSubvariant) {
            return $oFirstSubvariant->getLink();
        }

        return $this->_sUrl;
    }

    /**
     * Name setter
     *
     * @param string $sName New name
     */
    public function setName($sName)
    {
        $this->_sName = $sName;
    }

    /**
     * Returns MD variant name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_sName;
    }

    /**
     * Returns price
     *
     * @return double
     */
    public function getDPrice()
    {
        return $this->_dPrice;
    }

    /**
     * Returns min price recursively selected from full subvariant tree.
     *
     * @return double
     */
    public function getMinDPrice()
    {
        $dMinPrice = $this->getDPrice();
        $aVariants = $this->getMdSubvariants();
        foreach ($aVariants as $oVariant) {
            $dMinVariantPrice = $oVariant->getMinDPrice();
            if (is_null($dMinPrice)) {
                $dMinPrice = $dMinVariantPrice;
            }
            if (!is_null($dMinVariantPrice) && $dMinVariantPrice < $dMinPrice) {
                $dMinPrice = $dMinVariantPrice;
            }
        }

        return $dMinPrice;
    }

    /**
     * Gets max subvariant depth. 0 means no deeper subvariants.
     *
     * @return int
     */
    public function getMaxDepth()
    {
        $aSubvariants = $this->getMdSubvariants();

        if (!count($aSubvariants)) {
            return 0;
        }

        $iMaxDepth = 0;
        foreach ($aSubvariants as $oSubvariant) {
            if ($oSubvariant->getMaxDepth() > $iMaxDepth) {
                $iMaxDepth = $oSubvariant->getMaxDepth();
            }
        }

        return $iMaxDepth + 1;
    }

    /**
     * Returns MD variant price as a text.
     *
     * @return string|void
     */
    public function getFPrice()
    {
        $myConfig = Registry::getConfig();
        // 0002030 No need to return price if it disabled for better performance.
        if (!$myConfig->getConfigParam('bl_perfLoadPrice')) {
            return;
        }

        if ($this->_sFPrice) {
            return $this->_sFPrice;
        }

        $sFromPrefix = '';

        if (!$this->_isFixedPrice()) {
            $sFromPrefix = Registry::getLang()->translateString('PRICE_FROM') . ' ';
        }

        $dMinPrice = $this->getMinDPrice();
        $sFMinPrice = Registry::getLang()->formatCurrency($dMinPrice);
        $sCurrency = ' ' . Registry::getConfig()->getActShopCurrencyObject()->sign;
        $this->_sFPrice = $sFromPrefix . $sFMinPrice . $sCurrency;

        return $this->_sFPrice;
    }

    /**
     * Inits MD variant by name. In case $aNames parameter has more than one element addNames recursively adds names for subvariants.
     *
     * @param string $sArtId Article ID
     * @param array  $aNames Expected array of $sKey=>$sName pairs.
     * @param double $dPrice Price as double
     * @param string $sUrl   Article URL
     */
    public function addNames($sArtId, $aNames, $dPrice, $sUrl)
    {
        $iCount = count($aNames);
        $sName = array_shift($aNames);

        if ($iCount) {
            //get required subvariant
            $oVariant = $this->getMdSubvariantByName($sName);
            //add remaining names
            $oVariant->addNames($sArtId, $aNames, $dPrice, $sUrl);
        } else {
            //means we have the deepest element and assign other attributes
            $this->_sArticleId = $sArtId;
            $this->_dPrice = $dPrice;
            $this->_sUrl = $sUrl;
        }
    }

    /**
     * Returns corresponding article id or recursively first variant id from subvariant set
     *
     * @return string
     */
    public function getArticleId()
    {
        $oFirstSubvariant = $this->getFirstMdSubvariant();

        if ($oFirstSubvariant) {
            return $oFirstSubvariant->getArticleId();
        }

        return $this->_sArticleId;
    }

    /**
     * Checks whether $sArtId is one of subtree article ids.
     *
     * @param string $sArtId Article ID
     *
     * @return bool
     */
    public function hasArticleId($sArtId)
    {
        if ($this->getArticleId() == $sArtId) {
            return true;
        }

        $aSubvariants = $this->getMdSubvariants();
        foreach ($aSubvariants as $oSubvariant) {
            if ($oSubvariant->hasArticleId($sArtId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds one subvariant to subvariant set
     *
     * @param MdVariant $oSubvariant Subvariant
     * @deprecated underscore prefix violates PSR12, will be renamed to "addMdSubvariant" in next major
     */
    protected function _addMdSubvariant($oSubvariant) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->_aSubvariants[$oSubvariant->getId()] = $oSubvariant;
    }

    /**
     * Checks if variant price is fixed or not ("from" price)
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "isFixedPrice" in next major
     */
    protected function _isFixedPrice() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $dPrice = $this->getDPrice();
        $aVariants = $this->getMdSubvariants();
        foreach ($aVariants as $oVariant) {
            $dVariantPrice = $oVariant->getDPrice();
            if (is_null($dPrice)) {
                $dPrice = $dVariantPrice;
            }
            if (!is_null($dVariantPrice) && $dVariantPrice != $dPrice) {
                return false;
            }
            if (!$oVariant->_isFixedPrice()) {
                return false;
            }
        }

        return true;
    }
}
