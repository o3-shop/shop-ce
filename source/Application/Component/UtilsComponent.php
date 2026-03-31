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

use Exception;
use OxidEsales\Eshop\Application\Model\ContentList;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\Registry;

/**
 * Transparent shop utilities class.
 * Some specific utilities, such as fetching article info, etc. (Class may be used
 * for overriding).
 *
 * @subpackage oxcmp
 */
class UtilsComponent extends BaseController
{
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_blIsComponent = true;

    /**
     * Adds/removes chosen article to/from article comparison list
     *
     * @param object $sProductId product id
     * @param double $dAmount    amount
     * @param array  $aSel       (default null)
     * @param bool   $blOverride allow override
     * @param bool   $blBundle   bundled
     */
    public function toCompareList(
        $sProductId = null,
        $dAmount = null,
        $aSel = null,
        $blOverride = false,
        $blBundle = false
    ) {
        $view = $this->getParent();

        if ($view === null) {
            return;
        }

        $viewConfig = method_exists($view, 'getViewConfig') ? $view->getViewConfig() : null;

        if ($viewConfig === null
            || !method_exists($viewConfig, 'getShowCompareList')
            || !$viewConfig->getShowCompareList()
        ) {
            return;
        }

        if (Registry::getUtils()->isSearchEngine()) {
            return;
        }

        $config = Registry::getConfig();
        $blAddCompare = $config->getRequestParameter('addcompare');
        $blRemoveCompare = $config->getRequestParameter('removecompare');
        $sProductId = $sProductId ?: $config->getRequestParameter('aid');

        if ((!$blAddCompare && !$blRemoveCompare) || !$sProductId) {
            return;
        }

        $session = Registry::getSession();
        $aItems = $session->getVariable('aFiltcompproducts');

        if (!is_array($aItems)) {
            $aItems = [];
        }

        if ($blAddCompare && !isset($aItems[$sProductId])) {
            $aItems[$sProductId] = true;
        }

        if ($blRemoveCompare) {
            unset($aItems[$sProductId]);
        }

        $session->setVariable('aFiltcompproducts', $aItems);

        // Update in-memory product flags so templates show the correct
        // comparison-list state without requiring a page reload.
        if (method_exists($view, 'getViewProduct')) {
            $oProduct = $view->getViewProduct();
            if ($oProduct !== null && method_exists($oProduct, 'getId')) {
                $oProduct->setOnComparisonList(isset($aItems[$oProduct->getId()]));
            }
        }

        if (method_exists($view, 'getViewProductList')) {
            $aViewProds = $view->getViewProductList();
            if (is_array($aViewProds)) {
                foreach ($aViewProds as $oProduct) {
                    if (method_exists($oProduct, 'getId')) {
                        $oProduct->setOnComparisonList(isset($aItems[$oProduct->getId()]));
                    }
                }
            }
        }
    }


    /**
     * If session user is set loads user notice-list (\OxidEsales\Eshop\Application\Model\User::GetBasket())
     * and adds article to it.
     *
     * @param string $sProductId Product/article ID (default null)
     * @param double $dAmount amount of good (default null)
     * @param array $aSel product selection list (default null)
     * @throws Exception
     */
    public function toNoticeList($sProductId = null, $dAmount = null, $aSel = null)
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            return;
        }

        $this->toList('noticelist', $sProductId, $dAmount, $aSel);
    }

    /**
     * If session user is set loads user wishlist (\OxidEsales\Eshop\Application\Model\User::GetBasket()) and
     * adds article to it.
     *
     * @param string $sProductId Product/article ID (default null)
     * @param double $dAmount amount of good (default null)
     * @param array $aSel product selection list (default null)
     * @throws Exception
     */
    public function toWishList($sProductId = null, $dAmount = null, $aSel = null)
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            return;
        }

        // only if enabled
        if ($this->getViewConfig()->getShowWishlist()) {
            $this->toList('wishlist', $sProductId, $dAmount, $aSel);
        }
    }

    /**
     * Adds chosen product to defined user list. if amount is 0, item is removed from the list
     *
     * @param string $sListType user product list type
     * @param string $sProductId product id
     * @param double $dAmount product amount
     * @param array $aSel product selection list
     * @throws Exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "toList" in next major
     */
    protected function _toList($sListType, $sProductId, $dAmount, $aSel) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->toList($sListType, $sProductId, $dAmount, $aSel);
    }

    /**
     * Adds chosen product to defined user list. if amount is 0, item is removed from the list
     *
     * @param string $sListType user product list type
     * @param string $sProductId product id
     * @param double $dAmount product amount
     * @param array $aSel product selection list
     * @throws Exception
     */
    protected function toList($sListType, $sProductId, $dAmount, $aSel)
    {
        // only if user is logged in
        if ($oUser = $this->getUser()) {
            $sProductId = ($sProductId) ? $sProductId : Registry::getRequest()->getRequestEscapedParameter('itmid');
            $sProductId = ($sProductId) ? $sProductId : Registry::getRequest()->getRequestEscapedParameter('aid');
            $dAmount = isset($dAmount) ? $dAmount : Registry::getRequest()->getRequestEscapedParameter('am');
            $aSel = $aSel ? $aSel : Registry::getRequest()->getRequestEscapedParameter('sel');

            // processing amounts
            $dAmount = str_replace(',', '.', $dAmount);
            if (!Registry::getConfig()->getConfigParam('blAllowUnevenAmounts')) {
                $dAmount = round((string) $dAmount);
            }

            $oBasket = $oUser->getBasket($sListType);
            $oBasket->addItemToBasket($sProductId, abs($dAmount), $aSel, ($dAmount == 0));

            // recalculate basket count
            $oBasket->getItemCount(true);

            Registry::getLogger()->debug('toList: listType=' . $sListType . ' productId=' . $sProductId . ' amount=' . $dAmount);

            // push the updated in-list state onto any article objects already loaded in the view,
            // so the heart icon reflects the change without a DB re-query (mirrors toCompareList())
            $blInList = ($dAmount != 0);
            if (!$blInList) {
                // article was removed from this list — check if it is still in the other list
                $otherList = ($sListType === 'noticelist') ? 'wishlist' : 'noticelist';
                foreach ($oUser->getBasket($otherList)->getItems() as $oItem) {
                    if ($oItem->oxuserbasketitems__oxartid->value === $sProductId) {
                        $blInList = true;
                        break;
                    }
                }
            }
            Registry::getLogger()->debug('toList: blInList=' . ($blInList ? 'true' : 'false'));

            $oParentView = $this->getParent();
            if ($oParentView) {
                if (($oProduct = $oParentView->getViewProduct()) && $oProduct->getId() === $sProductId) {
                    Registry::getLogger()->debug('toList: setIsInList on viewProduct id=' . $oProduct->getId());
                    $oProduct->setIsInList($blInList);
                }
                $aViewProds = $oParentView->getViewProductList();
                Registry::getLogger()->debug('toList: viewProductList count=' . (is_array($aViewProds) ? count($aViewProds) : 0));
                if (is_array($aViewProds)) {
                    foreach ($aViewProds as $oProduct) {
                        if ($oProduct->getId() === $sProductId) {
                            Registry::getLogger()->debug('toList: setIsInList on listProduct id=' . $oProduct->getId());
                            $oProduct->setIsInList($blInList);
                        }
                    }
                }
            }
        }
    }

    /**
     *  Set view data, call parent::render
     *
     * @return void
     */
    public function render()
    {
        parent::render();

        $oParentView = $this->getParent();

        // add content for main menu
        $oContentList = oxNew(ContentList::class);
        $oContentList->loadMainMenulist();
        $oParentView->setMenueList($oContentList);
    }
}
