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
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\RecommendationList;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\MailValidator;
use OxidEsales\Eshop\Core\Registry;

/**
 * Article suggestion page.
 * Collects some article base information, sets default recommendation text,
 * sends suggestion mail to user.
 * @deprecated since v6.5.4 (2020-04-06); Suggest feature will be removed completely
 */
class SuggestController extends FrontendController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/info/suggest.tpl';

    /**
     * Required fields to fill before sending suggest email
     *
     * @var array
     */
    protected $_aReqFields = ['rec_name', 'rec_email', 'send_name', 'send_email', 'send_message', 'send_subject'];

    /**
     * CrossSelling articlelist
     *
     * @var object
     */
    protected $_oCrossSelling = null;

    /**
     * Similar products articlelist
     *
     * @var object
     */
    protected $_oSimilarProducts = null;

    /**
     * Recommlist
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_oRecommList = null;

    /**
     * Recommlist
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var array
     */
    protected $_aSuggestData = null;

    /**
     * Assures, that controller would not be accessed if functionality disabled.
     */
    public function init()
    {
        $this->redirectToHomeIfDisabled();
        parent::init();
    }

    /**
     * Sends product suggestion mail and returns a URL according to
     * URL formatting rules.
     *
     * Template variables:
     * <b>editval</b>, <b>error</b>
     *
     * @return string|void
     */
    public function send()
    {
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval', true);
        if (!is_array($aParams)) {
            return;
        }

        // storing used written values
        $oParams = (object) $aParams;
        $this->setSuggestData((object) Registry::getRequest()->getRequestEscapedParameter('editval'));

        $oUtilsView = Registry::getUtilsView();

        // filled not all fields ?
        foreach ($this->_aReqFields as $sFieldName) {
            if (!isset($aParams[$sFieldName]) || !$aParams[$sFieldName]) {
                $oUtilsView->addErrorToDisplay('SUGGEST_COMLETECORRECTLYFIELDS');

                return;
            }
        }

        if (
            !oxNew(MailValidator::class)->isValidEmail($aParams["rec_email"])
            || !oxNew(MailValidator::class)->isValidEmail($aParams["send_email"])
        ) {
            $oUtilsView->addErrorToDisplay('SUGGEST_INVALIDMAIL');

            return;
        }

        $sReturn = "";
        // #1834M - specialchar search
        $sSearchParamForLink = rawurlencode(Registry::getRequest()->getRequestEscapedParameter('searchparam', true));
        if ($sSearchParamForLink) {
            $sReturn .= "&searchparam=$sSearchParamForLink";
        }

        $sSearchCatId = Registry::getRequest()->getRequestEscapedParameter('searchcnid');
        if ($sSearchCatId) {
            $sReturn .= "&searchcnid=$sSearchCatId";
        }

        $sSearchVendor = Registry::getRequest()->getRequestEscapedParameter('searchvendor');
        if ($sSearchVendor) {
            $sReturn .= "&searchvendor=$sSearchVendor";
        }

        if (($sSearchManufacturer = Registry::getRequest()->getRequestEscapedParameter('searchmanufacturer'))) {
            $sReturn .= "&searchmanufacturer=$sSearchManufacturer";
        }

        $sListType = Registry::getRequest()->getRequestEscapedParameter('listtype');
        if ($sListType) {
            $sReturn .= "&listtype=$sListType";
        }

        // sending suggest email
        $oEmail = oxNew(Email::class);
        $oProduct = $this->getProduct();
        if ($oProduct && $oEmail->sendSuggestMail($oParams, $oProduct)) {
            return 'details?anid=' . $oProduct->getId() . $sReturn;
        } else {
            $oUtilsView->addErrorToDisplay('SUGGEST_INVALIDMAIL');
        }
    }

    /**
     * Template variable getter. Returns search product
     *
     * @return object
     */
    public function getProduct()
    {
        if ($this->_oProduct === null) {
            $this->_oProduct = false;

            if ($sProductId = Registry::getRequest()->getRequestEscapedParameter('anid')) {
                $oProduct = oxNew(Article::class);
                $oProduct->load($sProductId);
                $this->_oProduct = $oProduct;
            }
        }

        return $this->_oProduct;
    }

    /**
     * Template variable getter. Returns recommlists reviews
     *
     * @return array
     */
    public function getCrossSelling()
    {
        if ($this->_oCrossSelling === null) {
            $this->_oCrossSelling = false;
            if ($oProduct = $this->getProduct()) {
                $this->_oCrossSelling = $oProduct->getCrossSelling();
            }
        }

        return $this->_oCrossSelling;
    }

    /**
     * Template variable getter. Returns recommlists reviews
     *
     * @return array
     */
    public function getSimilarProducts()
    {
        if ($this->_oSimilarProducts === null) {
            $this->_oSimilarProducts = false;
            if ($oProduct = $this->getProduct()) {
                $this->_oSimilarProducts = $oProduct->getSimilarProducts();
            }
        }

        return $this->_oSimilarProducts;
    }

    /**
     * Template variable getter. Returns recommlists reviews
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return array|bool
     */
    public function getRecommList()
    {
        if (!$this->getViewConfig()->getShowListmania()) {
            return false;
        }

        if ($this->_oRecommList === null) {
            $this->_oRecommList = false;
            if ($oProduct = $this->getProduct()) {
                $oRecommList = oxNew(RecommendationList::class);
                $this->_oRecommList = $oRecommList->getRecommListsByIds([$oProduct->getId()]);
            }
        }

        return $this->_oRecommList;
    }

    /**
     * Suggest data setter
     *
     * @param object $oData suggest data object
     */
    public function setSuggestData($oData)
    {
        $this->_aSuggestData = $oData;
    }

    /**
     * Template variable getter. Returns active object's reviews
     *
     * @return array
     */
    public function getSuggestData()
    {
        return $this->_aSuggestData;
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
        $sLink = parent::getLink($languageId);

        // active category
        if ($sVal = Registry::getRequest()->getRequestEscapedParameter('cnid')) {
            $sLink .= ((strpos($sLink, '?') === false) ? '?' : '&amp;') . "cnid={$sVal}";
        }

        // active article
        if ($sVal = Registry::getRequest()->getRequestEscapedParameter('anid')) {
            $sLink .= ((strpos($sLink, '?') === false) ? '?' : '&amp;') . "anid={$sVal}";
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
        $aPath['title'] = Registry::getLang()->translateString('RECOMMEND_PRODUCT', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();

        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * In case functionality disabled, redirects to home page.
     */
    private function redirectToHomeIfDisabled()
    {
        if (Registry::getConfig()->getConfigParam('blAllowSuggestArticle') !== true) {
            Registry::getUtils()->redirect(Registry::getConfig()->getShopHomeUrl(), true, 301);
        }
    }
}
