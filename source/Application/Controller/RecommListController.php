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

use Exception;
use OxidEsales\Eshop\Application\Controller\ArticleListController;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\Rating;
use OxidEsales\Eshop\Application\Model\RecommendationList;
use OxidEsales\Eshop\Application\Model\Review;
use OxidEsales\Eshop\Application\Model\RssFeed;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Article suggestion page.
 * Collects some article base information, sets default recomendation text,
 * sends suggestion mail to user.
 *
 * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
 */
class RecommListController extends ArticleListController
{
    /**
     * List type
     *
     * @var string
     */
    protected $_sListType = 'recommlist';

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/recommendations/recommlist.tpl';

    /**
     * Other recommendations list
     *
     * @var RecommendationList
     */
    protected $_oOtherRecommList = null;

    /**
     * Recommlist reviews
     *
     * @var array
     */
    protected $_aReviews = null;

    /**
     * Can user rate
     *
     * @var bool
     */
    protected $_blRate = null;

    /**
     * Rating value
     *
     * @var double
     */
    protected $_dRatingValue = null;

    /**
     * Rating count
     *
     * @var integer
     */
    protected $_iRatingCnt = null;

    /**
     * Searched recommendations list
     *
     * @var object
     */
    protected $_oSearchRecommLists = null;

    /**
     * Search string
     *
     * @var string
     */
    protected $_sSearch = null;

    /**
     * Template location
     *
     * @var string
     */
    protected $_sTplLocation = null;

    /**
     * Page navigation
     *
     * @var object
     */
    protected $_oPageNavigation = null;

    /**
     * Collects current view data, return current template file name
     *
     * @return string
     */
    public function render()
    {
        FrontendController::render();
        $myConfig = Registry::getConfig();

        $this->_iAllArtCnt = 0;

        if ($oActiveRecommList = $this->getActiveRecommList()) {
            if (($oList = $this->getArticleList()) && $oList->count()) {
                $this->_iAllArtCnt = $oActiveRecommList->getArtCount();
            }

            if ($myConfig->getConfigParam('bl_rssRecommListArts')) {
                /** @var RssFeed $oRss */
                $oRss = oxNew(RssFeed::class);
                $this->addRssFeed(
                    $oRss->getRecommListArticlesTitle($oActiveRecommList),
                    $oRss->getRecommListArticlesUrl($this->_oActiveRecommList),
                    'recommlistarts'
                );
            }
        } else {
            if (($oList = $this->getRecommLists()) && $oList->count()) {
                $oRecommList = oxNew(RecommendationList::class);
                $this->_iAllArtCnt = $oRecommList->getSearchRecommListCount($this->getRecommSearch());
            }
        }

        if (!($oList = $this->getArticleList())) {
            $oList = $this->getRecommLists();
        }

        if ($oList && $oList->count()) {
            $iNrofCatArticles = (int) Registry::getConfig()->getConfigParam('iNrofCatArticles');
            $iNrofCatArticles = $iNrofCatArticles ? $iNrofCatArticles : 10;
            $this->_iCntPages = ceil($this->_iAllArtCnt / $iNrofCatArticles);
        }
        // processing list articles
        $this->_processListArticles();

        return $this->_sThisTemplate;
    }

    /**
     * Returns product link type (OXARTICLE_LINKTYPE_RECOMM)
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "getProductLinkType" in next major
     */
    protected function _getProductLinkType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return OXARTICLE_LINKTYPE_RECOMM;
    }

    /**
     * Returns additional URL parameters which must be added to list products dynamic urls
     *
     * @return string
     */
    public function getAddUrlParams()
    {
        $sAddParams = parent::getAddUrlParams();
        $sAddParams .= ($sAddParams ? '&amp;' : '') . "listtype={$this->_sListType}";

        if ($oRecommList = $this->getActiveRecommList()) {
            $sAddParams .= "&amp;recommid=" . $oRecommList->getId();
        }

        return $sAddParams;
    }

    /**
     * Returns additional URL parameters which must be added to list products seo urls
     *
     * @return string
     */
    public function getAddSeoUrlParams()
    {
        $sAddParams = parent::getAddSeoUrlParams();
        if ($sParam = Registry::getRequest()->getRequestEscapedParameter('searchrecomm', true)) {
            $sAddParams .= "&amp;searchrecomm=" . rawurlencode($sParam);
        }

        return $sAddParams;
    }

    /**
     * Saves user ratings and review text (oxreview object)
     *
     * @return void
     * @throws Exception
     */
    public function saveReview()
    {
        if (!Registry::getSession()->checkSessionChallenge()) {
            return;
        }

        if (
            $this->canAcceptFormData() &&
            ($oRecommList = $this->getActiveRecommList()) && ($oUser = $this->getUser())
        ) {
            //save rating
            $dRating = Registry::getRequest()->getRequestEscapedParameter('recommlistrating');
            if ($dRating !== null) {
                $dRating = (int) $dRating;
            }

            if ($dRating !== null && $dRating >= 1 && $dRating <= 5) {
                $oRating = oxNew(Rating::class);
                if ($oRating->allowRating($oUser->getId(), 'oxrecommlist', $oRecommList->getId())) {
                    $oRating->oxratings__oxuserid = new Field($oUser->getId());
                    $oRating->oxratings__oxtype = new Field('oxrecommlist');
                    $oRating->oxratings__oxobjectid = new Field($oRecommList->getId());
                    $oRating->oxratings__oxrating = new Field($dRating);
                    $oRating->save();
                    $oRecommList->addToRatingAverage($dRating);
                }
            }

            if (($sReviewText = trim((string) Registry::getRequest()->getRequestEscapedParameter('rvw_txt', true)))) {
                $oReview = oxNew(Review::class);
                $oReview->oxreviews__oxobjectid = new Field($oRecommList->getId());
                $oReview->oxreviews__oxtype = new Field('oxrecommlist');
                $oReview->oxreviews__oxtext = new Field($sReviewText, Field::T_RAW);
                $oReview->oxreviews__oxlang = new Field(Registry::getLang()->getBaseLanguage());
                $oReview->oxreviews__oxuserid = new Field($oUser->getId());
                $oReview->oxreviews__oxrating = new Field(($dRating !== null) ? $dRating : null);
                $oReview->save();
            }
        }
    }

    /**
     * Returns array of params => values which are used in hidden forms and as additional url params
     *
     * @return array
     */
    public function getNavigationParams()
    {
        $aParams = FrontendController::getNavigationParams();
        $aParams['recommid'] = Registry::getRequest()->getRequestEscapedParameter('recommid');

        return $aParams;
    }

    /**
     * Template variable getter. Returns category's article list
     *
     * @return array
     */
    public function getArticleList()
    {
        if ($this->_aArticleList === null) {
            $this->_aArticleList = false;
            if ($oActiveRecommList = $this->getActiveRecommList()) {
                // sets active page
                $iActPage = (int) Registry::getRequest()->getRequestEscapedParameter('pgNr');
                $iActPage = ($iActPage < 0) ? 0 : $iActPage;

                // load only lists which we show on screen
                $iNrofCatArticles = Registry::getConfig()->getConfigParam('iNrofCatArticles');
                $iNrofCatArticles = $iNrofCatArticles ? $iNrofCatArticles : 10;

                $this->_aArticleList = $oActiveRecommList->getArticles(
                    $iNrofCatArticles * $iActPage,
                    $iNrofCatArticles
                );

                if ($this->_aArticleList && $this->_aArticleList->count()) {
                    foreach ($this->_aArticleList as $oItem) {
                        $oItem->text = $oActiveRecommList->getArtDescription($oItem->getId());
                    }
                }
            }
        }

        return $this->_aArticleList;
    }

    /**
     * Template variable getter. Returns other recommlists
     *
     * @return object
     */
    public function getSimilarRecommLists()
    {
        if ($this->_oOtherRecommList === null) {
            $this->_oOtherRecommList = false;
            if (($oActiveRecommList = $this->getActiveRecommList()) && ($oList = $this->getArticleList())) {
                $oRecommLists = $oActiveRecommList->getRecommListsByIds($oList->arrayKeys());
                //do not show the same list
                unset($oRecommLists[$oActiveRecommList->getId()]);
                $this->_oOtherRecommList = $oRecommLists;
            }
        }

        return $this->_oOtherRecommList;
    }

    /**
     * Template variable getter. Returns recommlists reviews
     *
     * @return array
     */
    public function getReviews()
    {
        if ($this->_aReviews === null) {
            $this->_aReviews = false;
            if ($this->isReviewActive() && ($oActiveRecommList = $this->getActiveRecommList())) {
                $this->_aReviews = $oActiveRecommList->getReviews();
            }
        }

        return $this->_aReviews;
    }

    /**
     * Template variable getter. Returns if review module is on
     *
     * @return bool
     */
    public function isReviewActive()
    {
        return Registry::getConfig()->getConfigParam('bl_perfLoadReviews');
    }

    /**
     * Template variable getter. Returns if user can rate
     *
     * @return bool
     */
    public function canRate()
    {
        if ($this->_blRate === null) {
            $this->_blRate = false;
            if ($this->isReviewActive() && ($oActiveRecommList = $this->getActiveRecommList())) {
                $oRating = oxNew(Rating::class);
                $sUserVariable = Registry::getSession()->getVariable('usr');
                $this->_blRate = $oRating->allowRating($sUserVariable, 'oxrecommlist', $oActiveRecommList->getId());
            }
        }

        return $this->_blRate;
    }

    /**
     * Template variable getter. Returns rating value
     *
     * @return double
     */
    public function getRatingValue()
    {
        if ($this->_dRatingValue === null) {
            $this->_dRatingValue = (double) 0;
            if ($this->isReviewActive() && ($oActiveRecommList = $this->getActiveRecommList())) {
                $this->_dRatingValue = round($oActiveRecommList->oxrecommlists__oxrating->value, 1);
            }
        }

        return (double) $this->_dRatingValue;
    }

    /**
     * Template variable getter. Returns rating count
     *
     * @return integer
     */
    public function getRatingCount()
    {
        if ($this->_iRatingCnt === null) {
            $this->_iRatingCnt = false;
            if ($this->isReviewActive() && ($oActiveRecommList = $this->getActiveRecommList())) {
                $this->_iRatingCnt = $oActiveRecommList->oxrecommlists__oxratingcnt->value;
            }
        }

        return $this->_iRatingCnt;
    }

    /**
     * Template variable getter. Returns searched recommlist
     *
     * @return object
     */
    public function getRecommLists()
    {
        if ($this->_oSearchRecommLists === null) {
            $this->_oSearchRecommLists = [];
            if (!$this->getActiveRecommList()) {
                // list of found oxrecommlists
                $oRecommList = oxNew(RecommendationList::class);
                $oList = $oRecommList->getSearchRecommLists($this->getRecommSearch());
                if ($oList && $oList->count()) {
                    $this->_oSearchRecommLists = $oList;
                }
            }
        }

        return $this->_oSearchRecommLists;
    }

    /**
     * Template variable getter. Returns search string
     *
     * @return string
     */
    public function getRecommSearch()
    {
        if ($this->_sSearch === null) {
            $this->_sSearch = false;
            if ($sSearch = Registry::getRequest()->getRequestEscapedParameter('searchrecomm', false)) {
                $this->_sSearch = $sSearch;
            }
        }

        return $this->_sSearch;
    }

    /**
     * Template variable getter. Returns category path array
     *
     * @return array
     */
    public function getTreePath()
    {
        $oLang = Registry::getLang();

        $aPath[0] = oxNew(Category::class);
        $aPath[0]->setLink(false);
        $aPath[0]->oxcategories__oxtitle = new Field($oLang->translateString('RECOMMLIST'));

        if ($sSearchParam = $this->getRecommSearch()) {
            $shopHomeURL = Registry::getConfig()->getShopHomeUrl();
            $sUrl = $shopHomeURL . "cl=recommlist&amp;searchrecomm=" . rawurlencode($sSearchParam);
            $sTitle = $oLang->translateString('RECOMMLIST_SEARCH') . ' "' . $sSearchParam . '"';

            $aPath[1] = oxNew(Category::class);
            $aPath[1]->setLink($sUrl);
            $aPath[1]->oxcategories__oxtitle = new Field($sTitle);
        }

        return $aPath;
    }

    /**
     * Template variable getter. Returns search string
     *
     * @return string
     */
    public function getSearchForHtml()
    {
        // #M1450 if active recommlist is loaded return it's title
        if ($oActiveRecommList = $this->getActiveRecommList()) {
            return $oActiveRecommList->oxrecommlists__oxtitle->value;
        }

        return Registry::getRequest()->getRequestEscapedParameter('searchrecomm');
    }

    /**
     * Generates Url for page navigation
     *
     * @return string
     */
    public function generatePageNavigationUrl()
    {
        if ((Registry::getUtils()->seoIsActive() && ($oRecomm = $this->getActiveRecommList()))) {
            return $oRecomm->getLink();
        }

        return FrontendController::generatePageNavigationUrl();
    }

    /**
     * Adds page number parameter to current Url and returns formatted url
     *
     * @param string $url  url to append page numbers
     * @param int    $currentPage current page number
     * @param int    $languageId requested language
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "addPageNrParam" in next major
     */
    protected function _addPageNrParam($url, $currentPage, $languageId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (Registry::getUtils()->seoIsActive() && ($oRecomm = $this->getActiveRecommList())) {
            if ($currentPage) {
                // only if page number > 0
                $url = $oRecomm->getBaseSeoLink($languageId, $currentPage);
            }
        } else {
            $url = FrontendController::_addPageNrParam($url, $currentPage, $languageId);
        }

        return $url;
    }

    /**
     * Template variable getter. Returns additional params for url
     *
     * @return string
     */
    public function getAdditionalParams()
    {
        $sAddParams = FrontendController::getAdditionalParams();

        if ($oRecomm = $this->getActiveRecommList()) {
            $sAddParams .= "&amp;recommid=" . $oRecomm->getId();
        }

        if ($sSearch = $this->getRecommSearch()) {
            $sAddParams .= "&amp;searchrecomm=" . rawurlencode($sSearch);
        }

        return $sAddParams;
    }

    /**
     * get link of current view
     *
     * @param int $languageId requested language
     *
     * @return string
     */
    public function getLink($languageId = null)
    {
        if ($oRecomm = $this->getActiveRecommList()) {
            $sLink = $oRecomm->getLink($languageId);
        } else {
            $sLink = FrontendController::getLink($languageId);
        }
        $sSearch = Registry::getRequest()->getRequestEscapedParameter('searchrecomm');
        if ($sSearch) {
            $sLink .= ((strpos($sLink, '?') === false) ? '?' : '&amp;') . "searchrecomm={$sSearch}";
        }

        return $sLink;
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

        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        $aPath['title'] = Registry::getLang()->translateString('LISTMANIA', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * Page title
     *
     * @return string
     */
    public function getTitle()
    {
        $oLang = Registry::getLang();
        if ($aActiveList = $this->getActiveRecommList()) {
            $sTranslatedString = $oLang->translateString('LIST_BY', $oLang->getBaseLanguage(), false);
            $sTitleField = 'oxrecommlists__oxtitle';
            $sAuthorField = 'oxrecommlists__oxauthor';

            return $aActiveList->$sTitleField->value . ' (' . $sTranslatedString . ' ' .
                      $aActiveList->$sAuthorField->value . ')';
        }
        $sTranslatedString = $oLang->translateString('HITS_FOR', $oLang->getBaseLanguage(), false);

        return $this->getArticleCount() . ' ' . $sTranslatedString . ' "' . $this->getSearchForHtml() . '"';
    }
}
