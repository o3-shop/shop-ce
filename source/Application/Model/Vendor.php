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

use OxidEsales\Eshop\Core\Contract\IUrl;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Vendor manager
 *
 */
class Vendor extends MultiLanguageModel implements IUrl
{
    protected static $_aRootVendor = [];

    /**
     * @var string Name of current class
     */
    protected $_sClassName = 'oxvendor';

    /**
     * Marker to load vendor article count info
     *
     * @var bool
     */
    protected $_blShowArticleCnt = false;

    /**
     * Vendor article count (default is -1, which means not calculated)
     *
     * @var int
     */
    protected $_iNrOfArticles = -1;

    /**
     * Marks that current object is managed by SEO
     *
     * @var bool
     */
    protected $_blIsSeoObject = true;

    /**
     * Visibility of a vendor
     *
     * @var bool
     */
    protected $_blIsVisible;

    /**
     * has visible vendors state of a category
     *
     * @var bool
     */
    protected $_blHasVisibleSubCats;

    /**
     * Seo article urls for languages
     *
     * @var array
     */
    protected $_aSeoUrls = [];

    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        $this->setShowArticleCnt(Registry::getConfig()->getConfigParam('bl_perfShowActionCatArticleCnt'));
        parent::__construct();
        $this->init('oxvendor');
    }

    /**
     * Marker to load vendor article count info setter
     *
     * @param bool $blShowArticleCount Marker to load vendor article count
     */
    public function setShowArticleCnt($blShowArticleCount = false)
    {
        $this->_blShowArticleCnt = $blShowArticleCount;
    }

    /**
     * Assigns to $this object some base parameters/values.
     *
     * @param array $dbRecord parameters/values
     */
    public function assign($dbRecord)
    {
        parent::assign($dbRecord);

        // vendor article count is stored in cache
        if ($this->_blShowArticleCnt && !$this->isAdmin()) {
            $this->_iNrOfArticles = Registry::getUtilsCount()->getVendorArticleCount($this->getId());
        }

        $this->oxvendor__oxnrofarticles = new Field($this->_iNrOfArticles, Field::T_RAW);
    }

    /**
     * Loads object data from DB (object data ID is passed to method). Returns
     * true on success.
     *
     * @param string $sOxid object id
     *
     * @return bool
     */
    public function load($sOxid)
    {
        if ($sOxid == 'root') {
            return $this->_setRootObjectData();
        }

        return parent::load($sOxid);
    }

    /**
     * Sets root vendor data. Returns true
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "setRootObjectData" in next major
     */
    protected function _setRootObjectData() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setId('root');
        $this->oxvendor__oxicon = new Field('', Field::T_RAW);
        $this->oxvendor__oxtitle = new Field(
            Registry::getLang()->translateString('BY_VENDOR', $this->getLanguage(), false), Field::T_RAW);
        $this->oxvendor__oxshortdesc = new Field('', Field::T_RAW);

        return true;
    }

    /**
     * Returns raw content seo url
     *
     * @param int $iLang language id
     * @param int $iPage page number [optional]
     *
     * @return string
     */
    public function getBaseSeoLink($iLang, $iPage = 0)
    {
        $oEncoder = Registry::get(SeoEncoderVendor::class);
        if (!$iPage) {
            return $oEncoder->getVendorUrl($this, $iLang);
        }

        return $oEncoder->getVendorPageUrl($this, $iPage, $iLang);
    }

    /**
     * Returns vendor link Url
     *
     * @param int $iLang language id [optional]
     *
     * @return string
     */
    public function getLink($iLang = null)
    {
        if (!Registry::getUtils()->seoIsActive()) {
            return $this->getStdLink($iLang);
        }

        if ($iLang === null) {
            $iLang = $this->getLanguage();
        }

        if (!isset($this->_aSeoUrls[$iLang])) {
            $this->_aSeoUrls[$iLang] = $this->getBaseSeoLink($iLang);
        }

        return $this->_aSeoUrls[$iLang];
    }

    /**
     * Returns base dynamic url: shopurl/index.php?cl=details
     *
     * @param int  $iLang   language id
     * @param bool $blAddId add current object id to url or not
     * @param bool $blFull  return full including domain name [optional]
     *
     * @return string
     */
    public function getBaseStdLink($iLang, $blAddId = true, $blFull = true)
    {
        $sUrl = '';
        if ($blFull) {
            //always returns shop url, not admin
            $sUrl = Registry::getConfig()->getShopUrl($iLang, false);
        }

        return $sUrl . "index.php?cl=vendorlist" . ($blAddId ? "&amp;cnid=v_" . $this->getId() : "");
    }

    /**
     * Returns standard URL to vendor
     *
     * @param int   $iLang   language
     * @param array $aParams additional params to use [optional]
     *
     * @return string
     */
    public function getStdLink($iLang = null, $aParams = [])
    {
        if ($iLang === null) {
            $iLang = $this->getLanguage();
        }

        return Registry::getUtilsUrl()->processUrl($this->getBaseStdLink($iLang), true, $aParams, $iLang);
    }

    /**
     * returns number or articles of this vendor
     *
     * @return integer
     */
    public function getNrOfArticles()
    {
        if (!$this->_blShowArticleCnt || $this->isAdmin()) {
            return -1;
        }

        return $this->_iNrOfArticles;
    }

    /**
     * returns the sub category array
     */
    public function getSubCats()
    {
    }

    /**
     * returns the visibility of a vendor
     *
     * @return bool
     */
    public function getIsVisible()
    {
        return $this->_blIsVisible;
    }

    /**
     * sets the visibility of a category
     *
     * @param bool $blVisible vendors visibility status setter
     */
    public function setIsVisible($blVisible)
    {
        $this->_blIsVisible = $blVisible;
    }

    /**
     * returns if a vendor has visible sub categories
     *
     * @return bool
     */
    public function getHasVisibleSubCats()
    {
        if (!isset($this->_blHasVisibleSubCats)) {
            $this->_blHasVisibleSubCats = false;
        }

        return $this->_blHasVisibleSubCats;
    }

    /**
     * sets the state of has visible sub vendors
     *
     * @param bool $blHasVisibleSubcats marker if vendor has visible subcategories
     */
    public function setHasVisibleSubCats($blHasVisibleSubcats)
    {
        $this->_blHasVisibleSubCats = $blHasVisibleSubcats;
    }

    /**
     * Empty method, called in templates when vendor is used in same code like category
     */
    public function getContentCats()
    {
    }

    /**
     * Delete this object from the database, returns true on success.
     *
     * @param null $oxid Object ID(default null)
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function delete($oxid = null)
    {
        if ($oxid) {
            $this->load($oxid);
        } else {
            $oxid = $this->getId();
        }

        if (parent::delete($oxid)) {
            Registry::get(SeoEncoderVendor::class)->onDeleteVendor($this);

            return true;
        }

        return false;
    }


    /**
     * Returns article picture
     *
     * @return string|void
     */
    public function getIconUrl()
    {
        if (($sIcon = $this->oxvendor__oxicon->value)) {
            $oConfig = Registry::getConfig();
            $sSize = $oConfig->getConfigParam('sManufacturerIconsize');
            if (!isset($sSize)) {
                $sSize = $oConfig->getConfigParam('sIconsize');
            }

            return Registry::getPictureHandler()->getPicUrl("vendor/icon/", $sIcon, $sSize);
        }
    }

    /**
     * Returns category thumbnail picture url if exists, false - if not
     *
     * @return false
     */
    public function getThumbUrl()
    {
        return false;
    }

    /**
     * Returns vendor title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->oxvendor__oxtitle->value;
    }

    /**
     * Returns short description
     *
     * @return string
     */
    public function getShortDescription()
    {
        return $this->oxvendor__oxshortdesc->value;
    }
}
