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

use oxRegistry;

/**
 * Shop news window.
 * Arranges news texts. O3-Shop -> (click on News box on left side).
 * @deprecated 6.5.6 "News" feature will be removed completely
 */
class NewsController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Newslist
     *
     * @var object
     */
    protected $_oNewsList = null;
    /**
     * Current class login template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/info/news.tpl';

    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_blBargainAction = true;


    /**
     * Page navigation
     *
     * @var object
     */
    protected $_oPageNavigation = null;

    /**
     * Number of possible pages.
     *
     * @var integer
     */
    protected $_iCntPages = null;

    /**
     * Template variable getter. Returns newslist
     *
     * @return object
     */
    public function getNews()
    {
        if ($this->_oNewsList === null) {
            $this->_oNewsList = false;

            $iPerPage = (int) $this->getConfig()->getConfigParam('iNrofCatArticles');
            $iPerPage = $iPerPage ? $iPerPage : 10;

            $oActNews = oxNew(\OxidEsales\Eshop\Application\Model\NewsList::class);

            if ($iCnt = $oActNews->getCount()) {
                $this->_iCntPages = ceil($iCnt / $iPerPage);
                $oActNews->loadNews($this->getActPage() * $iPerPage, $iPerPage);
                $this->_oNewsList = $oActNews;
            }
        }

        return $this->_oNewsList;
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

        $oLang = \OxidEsales\Eshop\Core\Registry::getLang();
        $iBaseLanguage = $oLang->getBaseLanguage();
        $sTranslatedString = $oLang->translateString('LATEST_NEWS_AND_UPDATES_AT', $iBaseLanguage, false);

        $aPath['title'] = $sTranslatedString . ' ' . $this->getConfig()->getActiveShop()->oxshops__oxname->value;
        $aPath['link'] = $this->getLink();

        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * Template variable getter. Returns page navigation
     *
     * @return object
     */
    public function getPageNavigation()
    {
        if ($this->_oPageNavigation === null) {
            $this->_oPageNavigation = false;
            $this->_oPageNavigation = $this->generatePageNavigation();
        }

        return $this->_oPageNavigation;
    }

    /**
     * Page title
     *
     * @return string
     */
    public function getTitle()
    {
        $oLang = \OxidEsales\Eshop\Core\Registry::getLang();
        $iBaseLanguage = $oLang->getBaseLanguage();
        $sTranslatedString = $oLang->translateString('LATEST_NEWS_AND_UPDATES_AT', $iBaseLanguage, false);

        return $sTranslatedString . ' ' . $this->getConfig()->getActiveShop()->oxshops__oxname->value;
    }
}
