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

use OxidEsales\Eshop\Core\Registry;

/**
 * CMS - loads pages and displays it
 */
class ContentController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Content id.
     *
     * @var string
     */
    protected $_sContentId = null;

    /**
     * Content object
     *
     * @var object
     */
    protected $_oContent = null;

    /**
     * Current view template
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/info/content.tpl';

    /**
     * Current view plain template
     *
     * @var string
     */
    protected $_sThisPlainTemplate = 'page/info/content_plain.tpl';

    /**
     * Current view content category (if available)
     *
     * @var oxcontent
     */
    protected $_oContentCat = null;

    /**
     * Ids of contents which can be accessed without any restrictions when private sales is ON
     *
     * @var array
     */
    protected $_aPsAllowedContents = ["oxagb", "oxrightofwithdrawal", "oximpressum"];

    /**
     * Current view content title
     *
     * @var sting
     */
    protected $_sContentTitle = null;

    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_blBargainAction = true;

    /**
     * Business entity data template
     *
     * @var string
     */
    protected $_sBusinessTemplate = 'rdfa/content/inc/business_entity.tpl';

    /**
     * Delivery charge data template
     *
     * @var string
     */
    protected $_sDeliveryTemplate = 'rdfa/content/inc/delivery_charge.tpl';

    /**
     * Payment charge data template
     *
     * @var string
     */
    protected $_sPaymentTemplate = 'rdfa/content/inc/payment_charge.tpl';

    /**
     * An array including all ShopConfVars which are used to extend business
     * entity data
     *
     * @var array
     */
    protected $_aBusinessEntityExtends = ["sRDFaLogoUrl",
                                               "sRDFaLongitude",
                                               "sRDFaLatitude",
                                               "sRDFaGLN",
                                               "sRDFaNAICS",
                                               "sRDFaISIC",
                                               "sRDFaDUNS"];

    /**
     * Returns prefix ID used by template engine.
     *
     * @return string    $this->_sViewId
     */
    public function getViewId()
    {
        if (!isset($this->_sViewId)) {
            $this->_sViewId = parent::getViewId() . '|' . Registry::getConfig()->getRequestParameter('oxcid');
        }

        return $this->_sViewId;
    }

    /**
     * Executes parent::render(), passes template variables to
     * template engine and generates content. Returns the name
     * of template to render content::_sThisTemplate
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        parent::render();

        $oContent = $this->getContent();
        if ($oContent && !$this->_canShowContent($oContent->oxcontents__oxloadid->value)) {
            Registry::getUtils()->redirect($this->getConfig()->getShopHomeUrl() . 'cl=account');
        }

        $sTpl = false;
        if ($sTplName = $this->_getTplName()) {
            $this->_sThisTemplate = $sTpl = $sTplName;
        } elseif ($oContent) {
            $sTpl = $oContent->getId();
        }

        if (!$sTpl) {
            error_404_handler();
        }

        // sometimes you need to display plain templates (e.g. when showing popups)
        if ($this->showPlainTemplate()) {
            $this->_sThisTemplate = $this->_sThisPlainTemplate;
        }

        if ($oContent) {
            $this->getViewConfig()->setViewConfigParam('oxloadid', $oContent->getLoadId());
        }

        return $this->_sThisTemplate;
    }

    /**
     * Checks if content can be shown
     *
     * @param string $sContentIdent ident of content to display
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "canShowContent" in next major
     */
    protected function _canShowContent($sContentIdent) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return !(
            $this->isEnabledPrivateSales() &&
            !$this->getUser() && !in_array($sContentIdent, $this->_aPsAllowedContents)
        );
    }

    /**
     * Returns current view meta data
     * If $sMeta parameter comes empty, sets to it current content title
     *
     * @param string $sMeta     category path
     * @param int    $iLength   max length of result, -1 for no truncation
     * @param bool   $blDescTag if true - performs additional duplicate cleaning
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaDescription" in next major
     */
    protected function _prepareMetaDescription($sMeta, $iLength = 200, $blDescTag = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$sMeta) {
            $sMeta = $this->getContent()->oxcontents__oxtitle->value;
        }

        return parent::_prepareMetaDescription($sMeta, $iLength, $blDescTag);
    }

    /**
     * Returns current view keywords seperated by comma
     * If $sKeywords parameter comes empty, sets to it current content title
     *
     * @param string $sKeywords               data to use as keywords
     * @param bool   $blRemoveDuplicatedWords remove duplicated words
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareMetaKeyword" in next major
     */
    protected function _prepareMetaKeyword($sKeywords, $blRemoveDuplicatedWords = true) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$sKeywords) {
            $sKeywords = $this->getContent()->oxcontents__oxtitle->value;
        }

        return parent::_prepareMetaKeyword($sKeywords, $blRemoveDuplicatedWords);
    }

    /**
     * If current content is assigned to category returns its object
     *
     * @return oxcontent
     */
    public function getContentCategory()
    {
        if ($this->_oContentCat === null) {
            // setting default status ..
            $this->_oContentCat = false;
            if (($oContent = $this->getContent()) && $oContent->oxcontents__oxtype->value == 2) {
                $this->_oContentCat = $oContent;
            }
        }

        return $this->_oContentCat;
    }

    /**
     * Returns true if user forces to display plain template or
     * if private sales switched ON and user is not logged in
     *
     * @return bool
     */
    public function showPlainTemplate()
    {
        $blPlain = (bool) Registry::getConfig()->getRequestParameter('plain');
        if ($blPlain === false) {
            $oUser = $this->getUser();
            if (
                $this->isEnabledPrivateSales() &&
                (!$oUser || ($oUser && !$oUser->isTermsAccepted()))
            ) {
                $blPlain = true;
            }
        }

        return (bool) $blPlain;
    }

    /**
     * Returns active content id to load its seo meta info
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSeoObjectId" in next major
     */
    protected function _getSeoObjectId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return Registry::getConfig()->getRequestParameter('oxcid');
    }

    /**
     * Template variable getter. Returns active content id.
     * If no content id specified, uses "impressum" content id
     *
     * @return object
     */
    public function getContentId()
    {
        if ($this->_sContentId === null) {
            $sContentId = Registry::getConfig()->getRequestParameter('oxcid');
            $sLoadId = Registry::getConfig()->getRequestParameter('oxloadid');

            $this->_sContentId = false;
            $oContent = oxNew(\OxidEsales\Eshop\Application\Model\Content::class);

            if ($sLoadId) {
                $blRes = $oContent->loadByIdent($sLoadId);
            } elseif ($sContentId) {
                $blRes = $oContent->load($sContentId);
            } else {
                //get default content (impressum)
                $blRes = $oContent->loadByIdent('oximpressum');
            }

            if ($blRes && $oContent->oxcontents__oxactive->value) {
                $this->_sContentId = $oContent->oxcontents__oxid->value;
                $this->_oContent = $oContent;
            }
        }

        return $this->_sContentId;
    }

    /**
     * Template variable getter. Returns active content
     *
     * @return object
     */
    public function getContent()
    {
        if ($this->_oContent === null) {
            $this->_oContent = false;
            if ($this->getContentId()) {
                return $this->_oContent;
            }
        }

        return $this->_oContent;
    }

    /**
     * returns object, assosiated with current view.
     * (the object that is shown in frontend)
     *
     * @param int $iLang language id
     *
     * @return object
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSubject" in next major
     */
    protected function _getSubject($iLang) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getContent();
    }

    /**
     * Returns name of template
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getTplName" in next major
     */
    protected function _getTplName() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // assign template name
        $sTplName = Registry::getConfig()->getRequestParameter('tpl');

        if ($sTplName) {
            // security fix so that you cant access files from outside template dir
            $sTplName = basename($sTplName);

            //checking if it is template name, not content id
            if (!getStr()->preg_match("/\.tpl$/", $sTplName)) {
                $sTplName = null;
            } else {
                $sTplName = 'message/' . $sTplName;
            }
        }

        return $sTplName;
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $oContent = $this->getContent();

        $aPaths = [];
        $aPath = [];

        $aPath['title'] = $oContent->oxcontents__oxtitle->value;
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * Template variable getter. Returns tag title
     *
     * @return string
     */
    public function getTitle()
    {
        if ($this->_sContentTitle === null) {
            $oContent = $this->getContent();
            $this->_sContentTitle = $oContent->oxcontents__oxtitle->value;
        }

        return $this->_sContentTitle;
    }

    /**
     * Returns if page has rdfa
     *
     * @return bool
     */
    public function showRdfa()
    {
        return $this->getConfig()->getConfigParam('blRDFaEmbedding');
    }

    /**
     * Returns template name wich content page to specify:
     * business entity data, payment charge specifications or delivery charge
     *
     * @return array
     */
    public function getContentPageTpl()
    {
        $aTemplate = [];
        $sContentId = $this->getContent()->oxcontents__oxloadid->value;
        $myConfig = $this->getConfig();
        if ($sContentId == $myConfig->getConfigParam('sRDFaBusinessEntityLoc')) {
            $aTemplate[] = $this->_sBusinessTemplate;
        }
        if ($sContentId == $myConfig->getConfigParam('sRDFaDeliveryChargeSpecLoc')) {
            $aTemplate[] = $this->_sDeliveryTemplate;
        }
        if ($sContentId == $myConfig->getConfigParam('sRDFaPaymentChargeSpecLoc')) {
            $aTemplate[] = $this->_sPaymentTemplate;
        }

        return $aTemplate;
    }

    /**
     * Gets extended business entity data
     *
     * @return object
     */
    public function getBusinessEntityExtends()
    {
        $myConfig = $this->getConfig();
        $aExtends = [];

        foreach ($this->_aBusinessEntityExtends as $sExtend) {
            $aExtends[$sExtend] = $myConfig->getConfigParam($sExtend);
        }

        return $aExtends;
    }

    /**
     * Returns an object including all payments which are not mapped to a
     * predefined GoodRelations payment method. This object is used for
     * defining new instances of gr:PaymentMethods at content pages.
     *
     * @return object
     */
    public function getNotMappedToRDFaPayments()
    {
        $oPayments = oxNew(\OxidEsales\Eshop\Application\Model\PaymentList::class);
        $oPayments->loadNonRDFaPaymentList();

        return $oPayments;
    }

    /**
     * Returns an object including all delivery sets which are not mapped to a
     * predefined GoodRelations delivery method. This object is used for
     * defining new instances of gr:DeliveryMethods at content pages.
     *
     * @return object
     */
    public function getNotMappedToRDFaDeliverySets()
    {
        $oDelSets = oxNew(\OxidEsales\Eshop\Application\Model\DeliverySetList::class);
        $oDelSets->loadNonRDFaDeliverySetList();

        return $oDelSets;
    }

    /**
     * Returns delivery methods with assigned deliverysets.
     *
     * @return object
     */
    public function getDeliveryChargeSpecs()
    {
        $aDeliveryChargeSpecs = [];
        $oDeliveryChargeSpecs = $this->getDeliveryList();
        foreach ($oDeliveryChargeSpecs as $oDeliveryChargeSpec) {
            if ($oDeliveryChargeSpec->oxdelivery__oxaddsumtype->value == "abs") {
                $oDelSets = oxNew(\OxidEsales\Eshop\Application\Model\DeliverySetList::class);
                $oDelSets->loadRDFaDeliverySetList($oDeliveryChargeSpec->getId());
                $oDeliveryChargeSpec->deliverysetmethods = $oDelSets;
                $aDeliveryChargeSpecs[] = $oDeliveryChargeSpec;
            }
        }

        return $aDeliveryChargeSpecs;
    }

    /**
     * Template variable getter. Returns delivery list
     *
     * @return object
     */
    public function getDeliveryList()
    {
        if ($this->_oDelList === null) {
            $this->_oDelList = oxNew(\OxidEsales\Eshop\Application\Model\DeliveryList::class);
            $this->_oDelList->getList();
        }

        return $this->_oDelList;
    }

    /**
     * Returns rdfa VAT
     *
     * @return bool
     */
    public function getRdfaVAT()
    {
        return $this->getConfig()->getConfigParam('iRDFaVAT');
    }

    /**
     * Returns rdfa VAT
     *
     * @return bool
     */
    public function getRdfaPriceValidity()
    {
        $iDays = $this->getConfig()->getConfigParam('iRDFaPriceValidity');
        $iFrom = Registry::getUtilsDate()->getTime();
        $iThrough = $iFrom + ($iDays * 24 * 60 * 60);
        $oPriceValidity = [];
        $oPriceValidity['validfrom'] = date('Y-m-d\TH:i:s', $iFrom) . "Z";
        $oPriceValidity['validthrough'] = date('Y-m-d\TH:i:s', $iThrough) . "Z";

        return $oPriceValidity;
    }

    /**
     * Returns content parsed through smarty
     *
     * @return string
     */
    public function getParsedContent()
    {
        /** @var \OxidEsales\Eshop\Core\UtilsView $oUtilsView */
        $oUtilsView = Registry::getUtilsView();
        return $oUtilsView->parseThroughSmarty(
            $this->getContent()->oxcontents__oxcontent->value,
            $this->getContent()->getId(),
            null,
            true
        );
    }

    /**
     * Returns view canonical url
     *
     * @return string
     */
    public function getCanonicalUrl()
    {
        $url = '';
        if ($content = $this->getContent()) {
            $utils = Registry::getUtilsUrl();
            if (Registry::getUtils()->seoIsActive()) {
                $url = $utils->prepareCanonicalUrl($content->getBaseSeoLink($content->getLanguage()));
            } else {
                $url = $utils->prepareCanonicalUrl($content->getBaseStdLink($content->getLanguage()));
            }
        }

        return $url;
    }
}
