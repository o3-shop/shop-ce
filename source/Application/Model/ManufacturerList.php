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
 * Manufacturer list manager.
 * Collects list of manufacturers according to collection rules (active, etc.).
 *
 */
class ManufacturerList extends ListModel
{
    /**
     * Manufacturer root.
     *
     * @var Manufacturer
     */
    protected $_oRoot = null;

    /**
     * Manufacturer tree path.
     *
     * @var array
     */
    protected $_aPath = [];

    /**
     * To show manufacturer article count or not
     *
     * @var bool
     */
    protected $_blShowManufacturerArticleCnt = false;

    /**
     * Active manufacturer object
     *
     * @var Manufacturer
     */
    protected $_oClickedManufacturer = null;

    /**
     * Calls parent constructor and defines if Article vendor count is shown
     */
    public function __construct()
    {
        $this->setShowManufacturerArticleCnt(Registry::getConfig()->getConfigParam('bl_perfShowActionCatArticleCnt'));
        parent::__construct('oxmanufacturer');
    }

    /**
     * Enables/disables manufacturer article count calculation
     *
     * @param bool $blShowManufacturerArticleCnt to show article count or not
     */
    public function setShowManufacturerArticleCnt($blShowManufacturerArticleCnt = false)
    {
        $this->_blShowManufacturerArticleCnt = $blShowManufacturerArticleCnt;
    }

    /**
     * Loads simple manufacturer list
     */
    public function loadManufacturerList()
    {
        $oBaseObject = $this->getBaseObject();

        $sFieldList = $oBaseObject->getSelectFields();
        $sViewName = $oBaseObject->getViewName();
        $this->getBaseObject()->setShowArticleCnt($this->_blShowManufacturerArticleCnt);

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
     * Creates fake root for manufacturer tree and adds category list fields for each manufacturer item
     *
     * @param string $sLinkTarget  Name of class, responsible for category rendering
     * @param string $sActCat      Active category
     * @param string $sShopHomeUrl base shop url ($myConfig->getShopHomeUrl())
     */
    public function buildManufacturerTree($sLinkTarget, $sActCat, $sShopHomeUrl)
    {
        //Load manufacturer list
        $this->loadManufacturerList();


        //Create fake manufacturer root category
        $this->_oRoot = oxNew(Manufacturer::class);
        $this->_oRoot->load("root");

        //category fields
        $this->_addCategoryFields($this->_oRoot);
        $this->_aPath[] = $this->_oRoot;

        foreach ($this as $sVndId => $oManufacturer) {
            // storing active manufacturer object
            if ((string)$sVndId === $sActCat) {
                $this->setClickManufacturer($oManufacturer);
            }

            $this->_addCategoryFields($oManufacturer);
            if ($sActCat == $oManufacturer->oxmanufacturers__oxid->value) {
                $this->_aPath[] = $oManufacturer;
            }
        }

        $this->_seoSetManufacturerData();
    }

    /**
     * Root manufacturer list node (which usually is a manually prefilled object) getter
     *
     * @return Manufacturer
     */
    public function getRootCat()
    {
        return $this->_oRoot;
    }

    /**
     * Returns manufacturer path array
     *
     * @return array
     */
    public function getPath()
    {
        return $this->_aPath;
    }

    /**
     * Adds category specific fields to manufacturer object
     *
     * @param object $oManufacturer manufacturer object
     * @deprecated underscore prefix violates PSR12, will be renamed to "addCategoryFields" in next major
     */
    protected function _addCategoryFields($oManufacturer) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oManufacturer->oxcategories__oxid = new Field($oManufacturer->oxmanufacturers__oxid->value);
        $oManufacturer->oxcategories__oxicon = $oManufacturer->oxmanufacturers__oxicon;
        $oManufacturer->oxcategories__oxtitle = $oManufacturer->oxmanufacturers__oxtitle;
        $oManufacturer->oxcategories__oxdesc = $oManufacturer->oxmanufacturers__oxshortdesc;

        $oManufacturer->setIsVisible(true);
        $oManufacturer->setHasVisibleSubCats(false);
    }

    /**
     * Sets active (open) manufacturer object
     *
     * @param Manufacturer $oManufacturer active manufacturer
     */
    public function setClickManufacturer($oManufacturer)
    {
        $this->_oClickedManufacturer = $oManufacturer;
    }

    /**
     * returns active (open) manufacturer object
     *
     * @return Manufacturer
     */
    public function getClickManufacturer()
    {
        return $this->_oClickedManufacturer;
    }

    /**
     * Processes manufacturer category URLs
     * @deprecated underscore prefix violates PSR12, will be renamed to "seoSetManufacturerData" in next major
     */
    protected function _seoSetManufacturerData() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // only when SEO id on and in front end
        if (Registry::getUtils()->seoIsActive() && !$this->isAdmin()) {
            $oEncoder = Registry::get(SeoEncoderManufacturer::class);

            // preparing root manufacturer category
            if ($this->_oRoot) {
                $oEncoder->getManufacturerUrl($this->_oRoot);
            }

            // encoding manufacturer category
            foreach ($this as $sVndId => $value) {
                $oEncoder->getManufacturerUrl($this->_aArray[$sVndId]);
            }
        }
    }
}
