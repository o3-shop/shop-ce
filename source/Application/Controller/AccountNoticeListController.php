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

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\Registry;

/**
 * Current user notice list manager.
 * When user is logged-in in this manager window he can modify
 * his notice list status - remove articles from notice list or
 * store them to shopping basket, view detail information.
 * O3-Shop -> MY ACCOUNT -> Newsletter.
 */
class AccountNoticeListController extends AccountController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/account/noticelist.tpl';

    /**
     * Check if there is a product in the noticelist.
     *
     * @var array
     */
    protected $_aNoticeProductList = null;

    /**
     * return the similar products from the notice list.
     *
     * @var array
     */
    protected $_aSimilarProductList = null;

    /**
     * return the recommlist
     *
     * @var array
     */
    protected $_aRecommList = null;

    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_iViewIndexState = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;

    /**
     * Array of id to form recommendation list.
     *
     * @var array
     */
    protected $_aSimilarRecommListIds = null;

    /**
     * If user is not logged in - returns name of template
     * AccountNoticeListController::_sThisLoginTemplate, or if user is already
     * logged in - loads notice list articles (articles may be accessed
     * by User::getBasket()), loads similar articles (if available) for
     * the last article in list Article::GetSimilarProducts() and returns name of
     * template to render AccountNoticeListController::_sThisTemplate
     *
     * @return string current template file name
     */
    public function render()
    {
        parent::render();

        // is logged in ?
        $oUser = $this->getUser();
        if (!$oUser) {
            return $this->_sThisTemplate = $this->_sThisLoginTemplate;
        }

        return $this->_sThisTemplate;
    }

    /**
     * Template variable getter. Returns an array if there is something in the list
     *
     * @return array
     */
    public function getNoticeProductList()
    {
        if ($this->_aNoticeProductList === null) {
            if ($oUser = $this->getUser()) {
                $this->_aNoticeProductList = $oUser->getBasket('noticelist')->getArticles();
            }
        }

        return $this->_aNoticeProductList;
    }

    /**
     * Template variable getter. Returns the products which are in the noticelist
     *
     * @return array
     */
    public function getSimilarProducts()
    {
        // similar products list
        if ($this->_aSimilarProductList === null && count($this->getNoticeProductList())) {
            // just ensuring that next call will skip this check
            $this->_aSimilarProductList = false;

            // loading similar products
            if ($oSimilarProd = current($this->getNoticeProductList())) {
                $this->_aSimilarProductList = $oSimilarProd->getSimilarProducts();
            }
        }

        return $this->_aSimilarProductList;
    }

    /**
     * Return array of id to form recommend list.
     *
     * @return array
     */
    public function getSimilarRecommListIds()
    {
        if ($this->_aSimilarRecommListIds === null) {
            $this->_aSimilarRecommListIds = false;

            $aNoticeProdList = $this->getNoticeProductList();
            if (is_array($aNoticeProdList) && count($aNoticeProdList)) {
                $this->_aSimilarRecommListIds = array_keys($aNoticeProdList);
            }
        }

        return $this->_aSimilarRecommListIds;
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths = [];
        $aPath = [];

        $oLang = Registry::getLang();
        $sSelfLink = $this->getViewConfig()->getSelfLink();

        $iBaseLanguage = $oLang->getBaseLanguage();
        $aPath['title'] = $oLang->translateString('MY_ACCOUNT', $iBaseLanguage, false);
        $aPath['link'] = Registry::getSeoEncoder()->getStaticUrl($sSelfLink . "cl=account");
        $aPaths[] = $aPath;

        $aPath['title'] = $oLang->translateString('MY_WISH_LIST', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }
}
