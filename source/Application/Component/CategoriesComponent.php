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

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\CategoryList;
use OxidEsales\Eshop\Application\Model\ManufacturerList;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;

/**
 * Transparent category manager class (executed automatically).
 *
 * @subpackage oxcmp
 */
class CategoriesComponent extends BaseController
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
     * @var CategoryList
     */
    protected $_oCategoryTree = null;

    /**
     * Marking object as component
     *
     * @var ManufacturerList
     */
    protected $_oManufacturerTree = null;

    /**
     * Executes parent::init(), searches for active category in URL,
     * session, post variables ("cnid", "cdefnid"), active article
     * ("anid", usually article details), then loads article and
     * category if any of them available. Generates category/navigation
     * list.
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     */
    public function init()
    {
        parent::init();

        // Performance
        $myConfig = Registry::getConfig();
        if (
            $myConfig->getConfigParam('blDisableNavBars') &&
            $myConfig->getTopActiveView()->getIsOrderStep()
        ) {
            return;
        }

        $sActCat = $this->_getActCat();

        if ($myConfig->getConfigParam('bl_perfLoadManufacturerTree')) {
            // building Manufacturer tree
            $sActManufacturer = Registry::getRequest()->getRequestEscapedParameter('mnid');
            $this->_loadManufacturerTree($sActManufacturer);
        }

        // building category tree for all purposes (nav, search and simple category trees)
        $this->_loadCategoryTree($sActCat);
    }

    /**
     * get active article
     *
     * @return Article|void
     * @throws DatabaseConnectionException
     */
    public function getProduct()
    {
        if (($sActProduct = Registry::getRequest()->getRequestEscapedParameter('anid'))) {
            $oParentView = $this->getParent();
            if (($oProduct = $oParentView->getViewProduct())) {
                return $oProduct;
            } else {
                $oProduct = oxNew(Article::class);
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
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     * @deprecated Transitional during #107. Modules SHOULD override _getActCat()
      *             for now — internal call paths route through it. The
      *             longer-term direction (issue #108) is a template-method
      *             refactor that promotes getActCat() to the canonical override
      *             target and retires _getActCat(); until then, _getActCat() is the
      *             safe override target. Plan extension work with both stages
      *             in mind.
     */
    protected function _getActCat() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sActManufacturer = Registry::getRequest()->getRequestEscapedParameter('mnid');

        $sActCat = $sActManufacturer ? null : Registry::getRequest()->getRequestEscapedParameter('cnid');

        // loaded article - then checking additional parameters
        $oProduct = $this->getProduct();
        if ($oProduct) {
            $myConfig = Registry::getConfig();

            $sActManufacturer = $myConfig->getConfigParam('bl_perfLoadManufacturerTree') ? $sActManufacturer : null;

            $sActVendor = (Str::getStr()->preg_match('/^v_.?/i', $sActCat)) ? $sActCat : null;

            $sActCat = $this->_addAdditionalParams($oProduct, $sActCat, $sActManufacturer, $sActVendor);
        }

        // Checking for the default category
        if ($sActCat === null && !$oProduct && !$sActManufacturer) {
            // set remote cat
            $sActCat = Registry::getConfig()->getActiveShop()->oxshops__oxdefcat->value;
            if ($sActCat == 'oxrootid') {
                // means none selected
                $sActCat = null;
            }
        }

        return $sActCat;
    }

    /**
     * get active category id
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     *
     * @internal Public delegate during the #107 transition. Module subclasses
      *           SHOULD override _getActCat(), not this — internal call paths
      *           bypass this name. Issue #108 will eventually invert this and
      *           make getActCat() the canonical override target.
     */
    protected function getActCat()
    {
        return $this->_getActCat();
    }

    /**
     * Category tree loader
     *
     * @param string $sActCat active category id
     * @return null
     * @throws DatabaseConnectionException
     * @deprecated Use loadCategoryTree() instead. This underscore-prefixed name is retained
     *             only for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override
     *             _loadCategoryTree().
     */
    protected function _loadCategoryTree($sActCat) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        /** @var CategoryList $oCategoryTree */
        $oCategoryTree = oxNew(CategoryList::class);
        $oCategoryTree->buildTree($sActCat);

        $oParentView = $this->getParent();

        // setting active category tree
        $oParentView->setCategoryTree($oCategoryTree);
        $this->setCategoryTree($oCategoryTree);

        // setting active category
        $oParentView->setActiveCategory($oCategoryTree->getClickCat());
    }

    /**
     * Category tree loader
     *
     * @param string $sActCat active category id
     * @throws DatabaseConnectionException
     *
     * @internal If your override does not fully replace the behavior, call
     *           parent::loadCategoryTree() (not the deprecated _loadCategoryTree()) so
     *           downstream overrides in the class chain are preserved. Template-method
     *           refactor tracked in o3-shop/o3-shop#108.
     */
    protected function loadCategoryTree($sActCat)
    {
        $this->_loadCategoryTree($sActCat);
    }

    /**
     * Manufacturer tree loader
     *
     * @param string $sActManufacturer active Manufacturer id
     * @deprecated Use loadManufacturerTree() instead. This underscore-prefixed name is
     *             retained only for backward compatibility with module subclasses that
     *             already override it; new code, including new modules, MUST NOT call or
     *             override _loadManufacturerTree().
     */
    protected function _loadManufacturerTree($sActManufacturer) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();
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
     * Manufacturer tree loader
     *
     * @param string $sActManufacturer active Manufacturer id
     *
     * @internal If your override does not fully replace the behavior, call
     *           parent::loadManufacturerTree() (not the deprecated _loadManufacturerTree())
     *           so downstream overrides in the class chain are preserved. Template-method
     *           refactor tracked in o3-shop/o3-shop#108.
     */
    protected function loadManufacturerTree($sActManufacturer)
    {
        $this->_loadManufacturerTree($sActManufacturer);
    }

    /**
     * Executes parent::render(), loads expanded/clicked category object,
     * adds parameters template engine and returns list of category tree.
     *
     * @return CategoryList|void
     */
    public function render()
    {
        parent::render();

        // Performance
        $myConfig = Registry::getConfig();
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
     * @param Article $oProduct loaded product
     * @param string $sActCat active category id
     * @param string $sActManufacturer active manufacturer id
     * @param string $sActVendor active vendor
     *
     * @return string $sActCat
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     * @deprecated Use addAdditionalParams() instead. This underscore-prefixed name is
     *             retained only for backward compatibility with module subclasses that
     *             already override it; new code, including new modules, MUST NOT call or
     *             override _addAdditionalParams().
     */
    protected function _addAdditionalParams($oProduct, $sActCat, $sActManufacturer, $sActVendor) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sSearchPar = Registry::getRequest()->getRequestEscapedParameter('searchparam');
        $sSearchCat = Registry::getRequest()->getRequestEscapedParameter('searchcnid');
        $sSearchVnd = Registry::getRequest()->getRequestEscapedParameter('searchvendor');
        $sSearchMan = Registry::getRequest()->getRequestEscapedParameter('searchmanufacturer');
        $sListType = Registry::getRequest()->getRequestEscapedParameter('listtype');

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
     * Adds additional parameters: active category, list type and category id
     *
     * @param Article $oProduct loaded product
     * @param string $sActCat active category id
     * @param string $sActManufacturer active manufacturer id
     * @param string $sActVendor active vendor
     *
     * @return string $sActCat
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ObjectException
     *
     * @internal If your override does not fully replace the behavior, call
     *           parent::addAdditionalParams() (not the deprecated _addAdditionalParams())
     *           so downstream overrides in the class chain are preserved. Template-method
     *           refactor tracked in o3-shop/o3-shop#108.
     */
    protected function addAdditionalParams($oProduct, $sActCat, $sActManufacturer, $sActVendor)
    {
        return $this->_addAdditionalParams($oProduct, $sActCat, $sActManufacturer, $sActVendor);
    }

    /**
     * Returns array containing default list type and category (or manufacturer ir vendor) id
     *
     * @param Article $oProduct current product object
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated Use getDefaultParams() instead. This underscore-prefixed name is retained
     *             only for backward compatibility with module subclasses that already override
     *             it; new code, including new modules, MUST NOT call or override
     *             _getDefaultParams().
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
     * Returns array containing default list type and category (or manufacturer ir vendor) id
     *
     * @param Article $oProduct current product object
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     *
     * @internal If your override does not fully replace the behavior, call
     *           parent::getDefaultParams() (not the deprecated _getDefaultParams()) so
     *           downstream overrides in the class chain are preserved. Template-method
     *           refactor tracked in o3-shop/o3-shop#108.
     */
    protected function getDefaultParams($oProduct)
    {
        return $this->_getDefaultParams($oProduct);
    }

    /**
     * Setter of category tree
     *
     * @param CategoryList $oCategoryTree category list
     */
    public function setCategoryTree($oCategoryTree)
    {
        $this->_oCategoryTree = $oCategoryTree;
    }

    /**
     * Setter of manufacturer tree
     *
     * @param ManufacturerList $oManufacturerTree manufacturer list
     */
    public function setManufacturerTree($oManufacturerTree)
    {
        $this->_oManufacturerTree = $oManufacturerTree;
    }

    /**
     * @return ManufacturerList
     */
    protected function getManufacturerList()
    {
        return oxNew(ManufacturerList::class);
    }
}
