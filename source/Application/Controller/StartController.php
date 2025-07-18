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

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\ActionList;
use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Application\Model\RssFeed;
use OxidEsales\Eshop\Core\Registry;

/**
 * Starting shop page.
 * Shop starter, manages starting visible articles, etc.
 */
class StartController extends FrontendController
{
    /**
     * List display type
     *
     * @var string
     */
    protected $_sListDisplayType = null;

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/shop/start.tpl';

    /**
     * Start page meta description CMS ident
     *
     * @var string
     */
    protected $_sMetaDescriptionIdent = 'oxstartmetadescription';

    /**
     * Start page meta keywords CMS ident
     *
     * @var string
     */
    protected $_sMetaKeywordsIdent = 'oxstartmetakeywords';

    /**
     * Are actions on
     *
     * @var bool
     */
    protected $_blLoadActions = null;

    /**
     * Top article list (OXTOPSTART)
     *
     * @var array
     */
    protected $_aTopArticleList = null;

    /**
     * Newest article list
     *
     * @var array
     */
    protected $_aNewArticleList = null;

    /**
     * First article (OXFIRSTSTART)
     *
     * @var object
     */
    protected $_oFirstArticle = null;

    /**
     * Category offer article (OXCATOFFER)
     *
     * @var object
     */
    protected $_oCatOfferArticle = null;

    /**
     * Category offer article list (OXCATOFFER)
     *
     * @var array
     */
    protected $_oCatOfferArtList = null;

    /**
     * Sign if to load and show top5articles action
     *
     * @var bool
     */
    protected $_blTop5Action = true;

    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_blBargainAction = true;

    /**
     * Executes parent::render(), loads action articles
     * (oxarticlelist::loadActionArticles()). Returns name of
     * template file to render.
     *
     * @return  string  current template file name
     */
    public function render()
    {
        if (Registry::getRequest()->getRequestEscapedParameter('showexceptionpage') == '1') {
            return 'message/exception.tpl';
        }

        $myConfig = Registry::getConfig();

        $oRss = oxNew(RssFeed::class);
        if ($myConfig->getConfigParam('iTop5Mode') && $myConfig->getConfigParam('bl_rssTopShop')) {
            $this->addRssFeed($oRss->getTopInShopTitle(), $oRss->getTopInShopUrl(), 'topArticles');
        }
        if ($myConfig->getConfigParam('iNewestArticlesMode') && $myConfig->getConfigParam('bl_rssNewest')) {
            $this->addRssFeed($oRss->getNewestArticlesTitle(), $oRss->getNewestArticlesUrl(), 'newestArticles');
        }
        if ($myConfig->getConfigParam('bl_rssBargain')) {
            $this->addRssFeed($oRss->getBargainTitle(), $oRss->getBargainUrl(), 'bargainArticles');
        }

        parent::render();

        return $this->_sThisTemplate;
    }

    /**
     * Returns current view metadata
     * If $meta parameter comes empty, sets to it article title and description.
     * It happens if current view has no metadata defined in oxcontent table
     *
     * @param string $meta     category path
     * @param int    $length   max length of result, -1 for no truncation
     * @param bool   $removeDuplicatedWords if true - performs additional duplicate cleaning
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaDescription" in next major
     */
    protected function _prepareMetaDescription($meta, $length = 1024, $removeDuplicatedWords = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (
            !$meta &&
            Registry::getConfig()->getConfigParam('bl_perfLoadAktion') &&
            $oArt = $this->getFirstArticle()
        ) {
            $oDescField = $oArt->getLongDescription();
            $meta = $oArt->oxarticles__oxtitle->value . ' - ' . $oDescField->value;
        }

        return parent::_prepareMetaDescription($meta, $length, $removeDuplicatedWords);
    }

    /**
     * Returns current view keywords seperated by comma
     * If $keywords parameter comes empty, sets to it article title and description.
     * It happens if current view has no metadata defined in oxcontent table
     *
     * @param string $keywords               data to use as keywords
     * @param bool   $removeDuplicatedWords remove duplicated words
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaKeyword" in next major
     */
    protected function _prepareMetaKeyword($keywords, $removeDuplicatedWords = true) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (
            !$keywords &&
            Registry::getConfig()->getConfigParam('bl_perfLoadAktion') &&
            $oArt = $this->getFirstArticle()
        ) {
            $oDescField = $oArt->getLongDescription();
            $keywords = $oDescField->value;
        }

        return parent::_prepareMetaKeyword($keywords, $removeDuplicatedWords);
    }

    /**
     * Template variable getter. Returns if actions are ON
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "getLoadActionsParam" in next major
     */
    protected function _getLoadActionsParam() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->_blLoadActions === null) {
            $this->_blLoadActions = false;
            if (Registry::getConfig()->getConfigParam('bl_perfLoadAktion')) {
                $this->_blLoadActions = true;
            }
        }

        return $this->_blLoadActions;
    }

    /**
     * Template variable getter. Returns start page articles (OXSTART)
     *
     * @deprecated since v6.3.7 (2019-11-06)
     * @return array
     */
    public function getArticleList()
    {
        if ($this->_aArticleList === null) {
            $this->_aArticleList = [];
            if ($this->_getLoadActionsParam()) {
                // start list
                $oArtList = oxNew(ArticleList::class);
                $oArtList->loadActionArticles('OXSTART');
                if ($oArtList->count()) {
                    $this->_aArticleList = $oArtList;
                }
            }
        }

        return $this->_aArticleList;
    }

    /**
     * Template variable getter. Returns Top article list (OXTOPSTART)
     *
     * @return array
     */
    public function getTopArticleList()
    {
        if ($this->_aTopArticleList === null) {
            $this->_aTopArticleList = false;
            if ($this->_getLoadActionsParam()) {
                // start list
                $oArtList = oxNew(ArticleList::class);
                $oArtList->loadActionArticles('OXTOPSTART');
                if ($oArtList->count()) {
                    $this->_aTopArticleList = $oArtList;
                }
            }
        }

        return $this->_aTopArticleList;
    }


    /**
     * Template variable getter. Returns newest article list
     *
     * @return array
     */
    public function getNewestArticles()
    {
        if ($this->_aNewArticleList === null) {
            $this->_aNewArticleList = [];
            if ($this->_getLoadActionsParam()) {
                // newest articles
                $oArtList = oxNew(ArticleList::class);
                $oArtList->loadNewestArticles();
                if ($oArtList->count()) {
                    $this->_aNewArticleList = $oArtList;
                }
            }
        }

        return $this->_aNewArticleList;
    }

    /**
     * Template variable getter. Returns first article
     *
     * @return object
     */
    public function getFirstArticle()
    {
        if ($this->_oFirstArticle === null) {
            $this->_oFirstArticle = false;
            if ($this->_getLoadActionsParam()) {
                // top articles ( big one )
                $oArtList = oxNew(ArticleList::class);
                $oArtList->loadActionArticles('OXFIRSTSTART');
                if ($oArtList->count()) {
                    $this->_oFirstArticle = $oArtList->current();
                }
            }
        }

        return $this->_oFirstArticle;
    }

    /**
     * Template variable getter. Returns category offer article (OXCATOFFER)
     *
     * @return object
     */
    public function getCatOfferArticle()
    {
        if ($this->_oCatOfferArticle === null) {
            $this->_oCatOfferArticle = false;
            if ($oArtList = $this->getCatOfferArticleList()) {
                $this->_oCatOfferArticle = $oArtList->current();
            }
        }

        return $this->_oCatOfferArticle;
    }

    /**
     * Template variable getter. Returns category offer article list (OXCATOFFER)
     *
     * @return array
     */
    public function getCatOfferArticleList()
    {
        if ($this->_oCatOfferArtList === null) {
            $this->_oCatOfferArtList = [];
            if ($this->_getLoadActionsParam()) {
                // "category offer" articles
                $oArtList = oxNew(ArticleList::class);
                $oArtList->loadActionArticles('OXCATOFFER');
                if ($oArtList->count()) {
                    $this->_oCatOfferArtList = $oArtList;
                }
            }
        }

        return $this->_oCatOfferArtList;
    }

    /**
     * Returns SEO suffix for page title
     *
     * @return string
     */
    public function getTitleSuffix()
    {
        return Registry::getConfig()->getActiveShop()->oxshops__oxstarttitle->value;
    }

    /**
     * Returns view canonical url
     *
     * @return string|void
     */
    public function getCanonicalUrl()
    {
        if (Registry::getUtils()->seoIsActive() && ($oViewConf = $this->getViewConfig())) {
            return Registry::getUtilsUrl()->prepareCanonicalUrl($oViewConf->getHomeLink());
        }
    }


    /**
     * Returns active banner list
     *
     * @return ActionList
     */
    public function getBanners()
    {
        $oBannerList = null;

        if (Registry::getConfig()->getConfigParam('bl_perfLoadAktion')) {
            $oBannerList = oxNew(ActionList::class);
            $oBannerList->loadBanners();
        }

        return $oBannerList;
    }

    /**
     * Returns manufacturer list for manufacturer slider
     *
     * @return array
     */
    public function getManufacturerForSlider()
    {
        $oList = null;

        if (Registry::getConfig()->getConfigParam('bl_perfLoadManufacturerTree')) {
            $oList = $this->getManufacturerlist();
        }

        return $oList;
    }
}
