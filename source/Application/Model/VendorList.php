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

use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Vendor list manager.
 * Collects list of vendors according to collection rules (active, etc.).
 *
 */
class VendorList extends ListModel
{
    /**
     * Vendor root.
     *
     * @var Vendor
     */
    protected $_oRoot = null;

    /**
     * Vendor tree path.
     *
     * @var array
     */
    protected $_aPath = [];

    /**
     * To show vendor article count or not
     *
     * @var bool
     */
    protected $_blShowVendorArticleCnt = false;

    /**
     * Active vendor object
     *
     * @var Vendor
     */
    protected $_oClickedVendor = null;

    /**
     * Calls parent constructor and defines if Article vendor count is shown
     */
    public function __construct()
    {
        $this->setShowVendorArticleCnt(Registry::getConfig()->getConfigParam('bl_perfShowActionCatArticleCnt'));
        parent::__construct('oxvendor');
    }

    /**
     * Enables/disables vendor article count calculation
     *
     * @param bool $blShowVendorArticleCnt to show article count or not
     */
    public function setShowVendorArticleCnt($blShowVendorArticleCnt = false)
    {
        $this->_blShowVendorArticleCnt = $blShowVendorArticleCnt;
    }

    /**
     * Loads simple vendor list
     */
    public function loadVendorList()
    {
        $oBaseObject = $this->getBaseObject();
        $sFieldList = $oBaseObject->getSelectFields();
        $sViewName = $oBaseObject->getViewName();
        $this->getBaseObject()->setShowArticleCnt($this->_blShowVendorArticleCnt);

        $sWhere = '';
        if (!$this->isAdmin()) {
            $sWhere = $oBaseObject->getSqlActiveSnippet();
            $sWhere = $sWhere ? " where $sWhere and " : ' where ';
            $sWhere .= "{$sViewName}.oxtitle != '' ";
        }

        $sSelect = "select {$sFieldList} from {$sViewName} {$sWhere} order by {$sViewName}.oxtitle";
        $this->selectString($sSelect);
    }

    /**
     * Creates fake root for vendor tree, and ads category list fields for each vendor item
     *
     * @param string $sLinkTarget  Name of class, responsible for category rendering
     * @param string $sActCat      Active category
     * @param string $sShopHomeUrl base shop url ($myConfig->getShopHomeUrl())
     */
    public function buildVendorTree($sLinkTarget, $sActCat, $sShopHomeUrl)
    {
        $sActCat = str_replace('v_', '', $sActCat);

        //Load vendor list
        $this->loadVendorList();


        //Create fake vendor root category
        $this->_oRoot = oxNew(Vendor::class);
        $this->_oRoot->load('root');

        //category fields
        $this->_addCategoryFields($this->_oRoot);
        $this->_aPath[] = $this->_oRoot;

        foreach ($this as $sVndId => $oVendor) {
            // storing active vendor object
            if ($sVndId == $sActCat) {
                $this->setClickVendor($oVendor);
            }

            $this->_addCategoryFields($oVendor);
            if ($sActCat == $oVendor->oxvendor__oxid->value) {
                $this->_aPath[] = $oVendor;
            }
        }

        $this->_seoSetVendorData();
    }

    /**
     * Root vendor list node (which usually is a manually prefilled object) getter
     *
     * @return Vendor
     */
    public function getRootCat()
    {
        return $this->_oRoot;
    }

    /**
     * Returns vendor path array
     *
     * @return array
     */
    public function getPath()
    {
        return $this->_aPath;
    }

    /**
     * Adds category specific fields to vendor object
     *
     * @param object $oVendor vendor object
     * @deprecated underscore prefix violates PSR12, will be renamed to "addCategoryFields" in next major
     */
    protected function _addCategoryFields($oVendor) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oVendor->oxcategories__oxid = new Field("v_" . $oVendor->oxvendor__oxid->value);
        $oVendor->oxcategories__oxicon = $oVendor->oxvendor__oxicon;
        $oVendor->oxcategories__oxtitle = $oVendor->oxvendor__oxtitle;
        $oVendor->oxcategories__oxdesc = $oVendor->oxvendor__oxshortdesc;

        $oVendor->setIsVisible(true);
        $oVendor->setHasVisibleSubCats(false);
    }

    /**
     * Sets active (open) vendor object
     *
     * @param Vendor $oVendor active vendor
     */
    public function setClickVendor($oVendor)
    {
        $this->_oClickedVendor = $oVendor;
    }

    /**
     * returns active (open) vendor object
     *
     * @return Vendor
     */
    public function getClickVendor()
    {
        return $this->_oClickedVendor;
    }

    /**
     * Processes vendor category URLs
     * @deprecated underscore prefix violates PSR12, will be renamed to "seoSetVendorData" in next major
     */
    protected function _seoSetVendorData() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // only when SEO id on and in front end
        if (Registry::getUtils()->seoIsActive() && !$this->isAdmin()) {
            $oEncoder = Registry::get(SeoEncoderVendor::class);

            // preparing root vendor category
            if ($this->_oRoot) {
                $oEncoder->getVendorUrl($this->_oRoot);
            }

            // encoding vendor category
            foreach ($this as $sVndId => $value) {
                $oEncoder->getVendorUrl($this->_aArray[$sVndId]);
            }
        }
    }
}
