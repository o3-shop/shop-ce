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

namespace OxidEsales\EshopCommunity\Application\Component;

use oxRegistry;

/**
 * Transparent category manager class (executed automatically).
 *
 * @subpackage oxcmp
 */
class CategoriesComponent extends \OxidEsales\Eshop\Core\Controller\BaseController
{
    /**
     * More category object.
     *
     * @var object
     */
    protected $_oMoreCat = null;

    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_blIsComponent = true;

    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_oCategoryTree = null;

    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_oManufacturerTree = null;

    /**
     * Executes parent::init(), searches for active category in URL,
     * session, post variables ("cnid", "cdefnid"), active article
     * ("anid", usually article details), then loads article and
     * category if any of them available. Generates category/navigation
     * list.
     *
     * @return null
     */
    public function init()
    {
        parent::init();

        // Performance
        $myConfig = $this->getConfig();
        if (
            $myConfig->getConfigParam('blDisableNavBars') &&
            $myConfig->getTopActiveView()->getIsOrderStep()
        ) {
            return;
        }

        $sActCat = $this->_getActCat();

        if ($myConfig->getConfigParam('bl_perfLoadManufacturerTree')) {
            // building Manufacturer tree
            $sActManufacturer = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('mnid');
            $this->_loadManufacturerTree($sActManufacturer);
        }

        // building category tree for all purposes (nav, search and simple category trees)
        $this->_loadCategoryTree($sActCat);
    }

    /**
     * get active article
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function getProduct()
    {
        if (($sActProduct = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('anid'))) {
            $oParentView = $this->getParent();
            if (($oProduct = $oParentView->getViewProduct())) {
                return $oProduct;
            } else {
                $oProduct = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
                if ($oProduct->load($sActProduct)) {
                    // storing for reuse
                    $oParentView->setViewProduct($oProduct);

                    return $oProduct;
                }
            }
        }
    }

    /**
     * get active category id
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getActCat" in next major
     */
    protected function _getActCat() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sActManufacturer = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('mnid');

        $sActCat = $sActManufacturer ? null : \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('cnid');

        // loaded article - then checking additional parameters
        $oProduct = $this->getProduct();
        if ($oProduct) {
            $myConfig = $this->getConfig();

            $sActManufacturer = $myConfig->getConfigParam('bl_perfLoadManufacturerTree') ? $sActManufacturer : null;

            $sActVendor = (getStr()->preg_match('/^v_.?/i', $sActCat)) ? $sActCat : null;

            $sActCat = $this->_addAdditionalParams($oProduct, $sActCat, $sActManufacturer, $sActVendor);
        }

        // Checking for the default category
        if ($sActCat === null && !$oProduct && !$sActManufacturer) {
            // set remote cat
            $sActCat = $this->getConfig()->getActiveShop()->oxshops__oxdefcat->value;
            if ($sActCat == 'oxrootid') {
                // means none selected
                $sActCat = null;
            }
        }

        return $sActCat;
    }

    /**
     * Category tree loader
     *
     * @param string $sActCat active category id
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadCategoryTree" in next major
     */
    protected function _loadCategoryTree($sActCat) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        /** @var \OxidEsales\Eshop\Application\Model\CategoryList $oCategoryTree */
        $oCategoryTree = oxNew(\OxidEsales\Eshop\Application\Model\CategoryList::class);
        $oCategoryTree->buildTree($sActCat);

        $oParentView = $this->getParent();

        // setting active category tree
        $oParentView->setCategoryTree($oCategoryTree);
        $this->setCategoryTree($oCategoryTree);

        // setting active category
        $oParentView->setActiveCategory($oCategoryTree->getClickCat());
    }

    /**
     * Manufacturer tree loader
     *
     * @param string $sActManufacturer active Manufacturer id
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadManufacturerTree" in next major
     */
    protected function _loadManufacturerTree($sActManufacturer) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = $this->getConfig();
        if ($myConfig->getConfigParam('bl_perfLoadManufacturerTree')) {
            $oManufacturerTree = $this->getManufacturerList();
            $shopHomeURL = $myConfig->getShopHomeUrl();
            $oManufacturerTree->buildManufacturerTree('manufacturerlist', $sActManufacturer, $shopHomeURL);

            $oParentView = $this->getParent();

            // setting active Manufacturer list
            $oParentView->setManufacturerTree($oManufacturerTree);
            $this->setManufacturerTree($oManufacturerTree);

            // setting active Manufacturer
            if (($oManufacturer = $oManufacturerTree->getClickManufacturer())) {
                $oParentView->setActManufacturer($oManufacturer);
            }
        }
    }

    /**
     * Executes parent::render(), loads expanded/clicked category object,
     * adds parameters template engine and returns list of category tree.
     *
     * @return \OxidEsales\Eshop\Application\Model\CategoryList
     */
    public function render()
    {
        parent::render();

        // Performance
        $myConfig = $this->getConfig();
        $oParentView = $this->getParent();

        if ($myConfig->getConfigParam('bl_perfLoadManufacturerTree') && $this->_oManufacturerTree) {
            $oParentView->setManufacturerlist($this->_oManufacturerTree);
            $oParentView->setRootManufacturer($this->_oManufacturerTree->getRootCat());
        }

        if ($this->_oCategoryTree) {
            return $this->_oCategoryTree;
        }
    }

    /**
     * Adds additional parameters: active category, list type and category id
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oProduct         loaded product
     * @param string                                      $sActCat          active category id
     * @param string                                      $sActManufacturer active manufacturer id
     * @param string                                      $sActVendor       active vendor
     *
     * @return string $sActCat
     * @deprecated underscore prefix violates PSR12, will be renamed to "addAdditionalParams" in next major
     */
    protected function _addAdditionalParams($oProduct, $sActCat, $sActManufacturer, $sActVendor) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSearchPar = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('searchparam');
        $sSearchCat = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('searchcnid');
        $sSearchVnd = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('searchvendor');
        $sSearchMan = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('searchmanufacturer');
        $sListType = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('listtype');

        // search ?
        if ((!$sListType || $sListType == 'search') && ($sSearchPar || $sSearchCat || $sSearchVnd || $sSearchMan)) {
            // setting list type directly
            $sListType = 'search';
        } else {
            // such Manufacturer is available ?
            if ($sActManufacturer && ($sActManufacturer == $oProduct->getManufacturerId())) {
                // setting list type directly
                $sListType = 'manufacturer';
                $sActCat = $sActManufacturer;
            } elseif ($sActVendor && (substr($sActVendor, 2) == $oProduct->getVendorId())) {
                // such vendor is available ?
                $sListType = 'vendor';
                $sActCat = $sActVendor;
            } elseif ($sActCat && $oProduct->isAssignedToCategory($sActCat)) {
                // category ?
            } else {
                list($sListType, $sActCat) = $this->_getDefaultParams($oProduct);
            }
        }

        $oParentView = $this->getParent();
        //set list type and category id
        $oParentView->setListType($sListType);
        $oParentView->setCategoryId($sActCat);

        return $sActCat;
    }

    /**
     * Returns array containing default list type and category (or manufacturer ir vendor) id
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oProduct current product object
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getDefaultParams" in next major
     */
    protected function _getDefaultParams($oProduct) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sListType = null;
        $aArticleCats = $oProduct->getCategoryIds(true);
        if (is_array($aArticleCats) && count($aArticleCats)) {
            $sActCat = reset($aArticleCats);
        } elseif (($sActCat = $oProduct->getManufacturerId())) {
            // not assigned to any category ? maybe it is assigned to Manufacturer ?
            $sListType = 'manufacturer';
        } elseif (($sActCat = $oProduct->getVendorId())) {
            // not assigned to any category ? maybe it is assigned to vendor ?
            $sListType = 'vendor';
        } else {
            $sActCat = null;
        }

        return [$sListType, $sActCat];
    }

    /**
     * Setter of category tree
     *
     * @param \OxidEsales\Eshop\Application\Model\CategoryList $oCategoryTree category list
     */
    public function setCategoryTree($oCategoryTree)
    {
        $this->_oCategoryTree = $oCategoryTree;
    }

    /**
     * Setter of manufacturer tree
     *
     * @param \OxidEsales\Eshop\Application\Model\ManufacturerList $oManufacturerTree manufacturer list
     */
    public function setManufacturerTree($oManufacturerTree)
    {
        $this->_oManufacturerTree = $oManufacturerTree;
    }

    /**
     * @return \OxidEsales\Eshop\Application\Model\ManufacturerList
     */
    protected function getManufacturerList()
    {
        return oxNew(\OxidEsales\Eshop\Application\Model\ManufacturerList::class);
    }
}
