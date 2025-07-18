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
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Content manager.
 * Base object for content pages
 *
 */
class Content extends MultiLanguageModel implements IUrl
{
    /**
     * Current class name.
     *
     * @var string
     */
    protected $_sClassName = 'oxcontent';

    /**
     * Seo article urls for languages.
     *
     * @var array
     */
    protected $_aSeoUrls = [];

    /**
     * Content parent category id
     *
     * @var string
     */
    protected $_sParentCatId = null;

    /**
     * Expanded state of a content category.
     *
     * @var bool
     */
    protected $_blExpanded = null;

    /**
     * Marks that current object is managed by SEO.
     *
     * @var bool
     */
    protected $_blIsSeoObject = true;

    /**
     * Category id.
     *
     * @var string
     */
    protected $_sCategoryId;

    /**
     * Extra getter to guarantee compatibility with templates.
     *
     * @param string $sName parameter name
     *
     * @return mixed
     */
    public function __get($sName)
    {
        if ($sName == 'expanded') {
            return $this->getExpanded();
        }
        return parent::__get($sName);
    }

    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxcontents');
    }

    /**
     * Returns the expanded state of the content category.
     *
     * @return bool
     */
    public function getExpanded()
    {
        if (!isset($this->_blExpanded)) {
            $this->_blExpanded = ($this->getId() == Registry::getRequest()->getRequestEscapedParameter('oxcid'));
        }

        return $this->_blExpanded;
    }

    /**
     * Sets category id.
     *
     * @param string $sCategoryId
     */
    public function setCategoryId($sCategoryId)
    {
        $this->oxcontents__oxcatid = new Field($sCategoryId);
    }

    /**
     * Returns category id.
     *
     * @return string
     */
    public function getCategoryId()
    {
        return $this->oxcontents__oxcatid->value;
    }

    /**
     * Get data from db.
     *
     * @param string $sLoadId id
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadFromDb" in next major
     */
    protected function _loadFromDb($sLoadId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sTable = $this->getViewName();
        $sShopId = $this->getShopId();
        $aParams = [$sTable . '.oxloadid' => $sLoadId, $sTable . '.oxshopid' => $sShopId];

        $sSelect = $this->buildSelectString($aParams);

        //Loads "credits" content object and its text (first available)
        if ($sLoadId == 'oxcredits') {
            // fetching column names
            $sColQ = "SHOW COLUMNS FROM oxcontents WHERE field LIKE  'oxcontent%'";
            $aCols = DatabaseProvider::getDb()->getAll($sColQ);

            // building sub-query
            $sPattern = "IF ( %s != '', %s, %s ) ";
            $iCount = count($aCols) - 1;

            $sContQ = "SELECT {$sPattern}";
            foreach ($aCols as $iKey => $aCol) {
                $sContQ = sprintf($sContQ, $aCol[0], $aCol[0], $iCount != $iKey ? $sPattern : "''");
            }
            $sContQ .= " FROM oxcontents WHERE oxloadid = '{$sLoadId}' AND oxshopid = '{$sShopId}'";

            $sSelect = $this->buildSelectString($aParams);
            $sSelect = str_replace("`{$sTable}`.`oxcontent`", "( $sContQ ) as oxcontent", $sSelect);
        }

        $aData = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getRow($sSelect);

        return $aData;
    }

    /**
     * Loads Content by using field oxloadid instead of oxid.
     *
     * @param string $loadId content load ID
     * @param bool $onlyActive selection state - active/inactive
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function loadByIdent($loadId, $onlyActive = false)
    {
        return $this->assignContentData($this->_loadFromDb($loadId), $onlyActive);
    }

    /**
     * Assign content data, filter inactive if needed.
     *
     * @param array $fetchedContent Item data to assign
     * @param bool  $onlyActive     Only assign if item is active
     *
     * @return bool
     */
    protected function assignContentData($fetchedContent, $onlyActive = false)
    {
        $filteredContent = $this->filterInactive($fetchedContent, $onlyActive);

        if (!is_null($filteredContent)) {
            $this->assign($filteredContent);
            return true;
        }

        return false;
    }

    /**
     * Decide if content item can be loaded by checking item activity if needed
     *
     * @param array $data
     * @param bool  $checkIfActive
     *
     * @return array | null
     */
    protected function filterInactive($data, $checkIfActive = false)
    {
        return $data && (!$checkIfActive || ($checkIfActive && $data['OXACTIVE']) == '1') ? $data : null;
    }

    /**
     * Returns unique object id.
     *
     * @return string
     */
    public function getLoadId()
    {
        return $this->oxcontents__oxloadid->value;
    }

    /**
     * Returns unique object id.
     *
     * @return string
     */
    public function isActive()
    {
        return $this->oxcontents__oxactive->value;
    }

    /**
     * Replace the "&amp;" into "&" and call base class.
     *
     * @param array $dbRecord database record
     */
    public function assign($dbRecord)
    {
        parent::assign($dbRecord);
        // workaround for firefox showing &lang= as &9001;= entity, mantis#0001272

        if ($this->oxcontents__oxcontent) {
            $this->oxcontents__oxcontent->setValue(str_replace('&lang=', '&amp;lang=', $this->oxcontents__oxcontent->value), Field::T_RAW);
        }
    }

    /**
     * Returns raw content seo url
     *
     * @param int $iLang language id
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getBaseSeoLink($iLang)
    {
        return Registry::get(SeoEncoderContent::class)->getContentUrl($this, $iLang);
    }

    /**
     * getLink returns link for this content in the frontend.
     *
     * @param null $iLang language id [optional]
     *
     * @return string
     * @throws DatabaseConnectionException
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
     * @param int $iLang language id
     * @param bool $blAddId add current object id to url or not
     * @param bool $blFull return full including domain name [optional]
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getBaseStdLink($iLang, $blAddId = true, $blFull = true)
    {
        $sUrl = '';
        if ($blFull) {
            //always returns shop url, not admin
            $sUrl = Registry::getConfig()->getShopUrl($iLang, false);
        }

        if ($this->oxcontents__oxloadid->value === 'oxcredits') {
            $sUrl .= "index.php?cl=credits";
        } else {
            $sUrl .= "index.php?cl=content";
        }
        $sUrl .= '&amp;oxloadid=' . $this->getLoadId();

        if ($blAddId) {
            $sUrl .= "&amp;oxcid=" . $this->getId();
            // adding parent category if available
            if ($this->_sParentCatId !== false && $this->oxcontents__oxcatid->value && $this->oxcontents__oxcatid->value != 'oxrootid') {
                if ($this->_sParentCatId === null) {
                    $this->_sParentCatId = false;
                    $oDb = DatabaseProvider::getDb();
                    $sParentId = $oDb->getOne("select oxparentid from oxcategories where oxid = :oxid", [
                        ':oxid' => $this->oxcontents__oxcatid->value
                    ]);
                    if ($sParentId && 'oxrootid' != $sParentId) {
                        $this->_sParentCatId = $sParentId;
                    }
                }

                if ($this->_sParentCatId) {
                    $sUrl .= "&amp;cnid=" . $this->_sParentCatId;
                }
            }
        }

        //always returns shop url, not admin
        return $sUrl;
    }

    /**
     * Returns standard URL to product.
     *
     * @param null $iLang language
     * @param array $aParams additional params to use [optional]
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getStdLink($iLang = null, $aParams = [])
    {
        if ($iLang === null) {
            $iLang = $this->getLanguage();
        }

        return Registry::getUtilsUrl()->processUrl($this->getBaseStdLink($iLang), true, $aParams, $iLang);
    }

    /**
     * Sets data field value.
     *
     * @param string $sFieldName index OR name (e.g. 'oxarticles__oxtitle') of a data field to set
     * @param string $sValue     value of data field
     * @param int    $iDataType  field type
     *
     * @return null
     * @deprecated underscore prefix violates PSR12, will be renamed to "setFieldData" in next major
     */
    protected function _setFieldData($sFieldName, $sValue, $iDataType = Field::T_TEXT) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sLoweredFieldName = strtolower($sFieldName);
        if ('oxcontent' === $sLoweredFieldName || 'oxcontents__oxcontent' === $sLoweredFieldName) {
            $iDataType = Field::T_RAW;
        }

        return parent::_setFieldData($sFieldName, $sValue, $iDataType);
    }

    /**
     * Get field data
     *
     * @param string $sFieldName name of the field which value to get
     *
     * @return mixed
     * @deprecated underscore prefix violates PSR12, use "getFieldData" instead
     */
    protected function _getFieldData($sFieldName) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->{$sFieldName}->value;
    }

    /**
     * Delete this object from the database, returns true on success.
     *
     * @param null $sOXID Object ID(default null)
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function delete($sOXID = null)
    {
        if (!$sOXID) {
            $sOXID = $this->getId();
        }

        if (parent::delete($sOXID)) {
            Registry::get(SeoEncoderContent::class)->onDeleteContent($sOXID);

            return true;
        }

        return false;
    }

    /**
     * Save this Object to database, insert or update as needed.
     *
     * @return bool|string|null
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function save()
    {
        $blSaved = parent::save();
        if ($blSaved && $this->oxcontents__oxloadid->value === 'oxagb') {
            $sShopId = Registry::getConfig()->getShopId();
            $sVersion = $this->oxcontents__oxtermversion->value;

            $oDb = DatabaseProvider::getDb();
            // dropping expired...
            $oDb->execute("delete from oxacceptedterms where oxshopid = :oxshopid and oxtermversion != :notoxtermversion", [
                ':oxshopid' => $sShopId,
                ':notoxtermversion' => $sVersion
            ]);
        }

        return $blSaved;
    }

    /**
     * Returns latest terms version id.
     *
     * @return string|void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getTermsVersion()
    {
        if ($this->loadByIdent('oxagb')) {
            return $this->oxcontents__oxtermversion->value;
        }
    }

    /**
     * Set type of content.
     *
     * @param string $sValue type value
     */
    public function setType($sValue)
    {
        $this->_setFieldData('oxcontents__oxtype', $sValue);
    }

    /**
     * Return type of content
     *
     * @return integer
     */
    public function getType()
    {
        return (int) $this->_getFieldData('oxcontents__oxtype');
    }

    /**
     * Set title of content
     *
     * @param string $sValue title value
     */
    public function setTitle($sValue)
    {
        $this->_setFieldData('oxcontents__oxtitle', $sValue);
    }

    /**
     * Return title of content
     *
     * @return string
     */
    public function getTitle()
    {
        return (string) $this->_getFieldData('oxcontents__oxtitle');
    }

    /**
     * @return bool
     */
    public function isPlain(): bool
    {
        return (bool) $this->getFieldData('oxisplain');
    }
}
