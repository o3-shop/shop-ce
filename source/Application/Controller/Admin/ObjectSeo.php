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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use stdClass;

/**
 * Base seo config class.
 */
class ObjectSeo extends AdminDetailsController
{
    /**
     * Executes parent method parent::render(),
     * and returns name of template file
     * "object_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        if ($sType = $this->_getType()) {
            $iLang = null;
            $oObject = oxNew($sType);
            if ($oObject->load($this->getEditObjectId())) {
                $oOtherLang = $oObject->getAvailableInLangs();
                if (!isset($oOtherLang[$iLang])) {
                    $oObject->loadInLang(key($oOtherLang), $this->getEditObjectId());
                }
                $this->_aViewData['edit'] = $oObject;
            }

            if ($oObject->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }
        }

        $iLang = $this->getEditLang();
        $aLangs = Registry::getLang()->getLanguageNames();
        foreach ($aLangs as $sLangId => $sLanguage) {
            $oLang = new stdClass();
            $oLang->sLangDesc = $sLanguage;
            $oLang->selected = ($sLangId == $iLang);
            $this->_aViewData['otherlang'][$sLangId] = clone $oLang;
        }

        return 'object_seo.tpl';
    }

    /**
     * Saves selection list parameters changes.
     */
    public function save()
    {
        // saving/updating seo params
        if (($sOxid = $this->_getSaveObjectId())) {
            $aSeoData = Registry::getRequest()->getRequestEscapedParameter('aSeoData');
            $iShopId = Registry::getConfig()->getShopId();
            $iLang = $this->getEditLang();

            // checkbox handling
            if (!isset($aSeoData['oxfixed'])) {
                $aSeoData['oxfixed'] = 0;
            }

            $sParams = $this->_getAdditionalParams($aSeoData);

            $oEncoder = $this->_getEncoder();
            // marking self and page links as expired
            $oEncoder->markAsExpired($sOxid, $iShopId, 1, $iLang, $sParams);

            // saving
            $oEncoder->addSeoEntry(
                $sOxid,
                $iShopId,
                $iLang,
                $this->_getStdUrl($sOxid),
                $aSeoData['oxseourl'],
                $this->_getSeoEntryType(),
                $aSeoData['oxfixed'],
                trim($aSeoData['oxkeywords']),
                trim($aSeoData['oxdescription']),
                $this->processParam($aSeoData['oxparams']),
                true,
                $this->_getAltSeoEntryId()
            );
        }
    }

    /**
     * Gets additional params from aSeoData['oxparams'] if it is set.
     *
     * @param array $aSeoData Seo data array
     *
     * @return null|string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAdditionalParamsFromSeoData" in next major
     */
    protected function _getAdditionalParams($aSeoData) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sParams = null;
        if (isset($aSeoData['oxparams'])) {
            if (preg_match('/([a-z]*#)?(?<objectseo>[a-z0-9]+)(#[0-9])?/i', $aSeoData['oxparams'], $aMatches)) {
                $sQuotedObjectSeoId = DatabaseProvider::getDb()->quote($aMatches['objectseo']);
                $sParams = "oxparams = {$sQuotedObjectSeoId}";
            }
        }
        return $sParams;
    }

    /**
     * Returns id of object which must be saved
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSaveObjectId" in next major
     */
    protected function _getSaveObjectId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getEditObjectId();
    }

    /**
     * Returns object seo data
     *
     * @param string $sMetaType metadata type (oxkeywords/oxdescription)
     *
     * @return string
     */
    public function getEntryMetaData($sMetaType)
    {
        return $this->_getEncoder()->getMetaData($this->getEditObjectId(), $sMetaType, Registry::getConfig()->getShopId(), $this->getEditLang());
    }

    /**
     * Returns TRUE if current seo entry has fixed state
     *
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function isEntryFixed()
    {
        $iLang = (int) $this->getEditLang();
        $iShopId = Registry::getConfig()->getShopId();

        $sQ = "select oxfixed from oxseo where
                   oxseo.oxobjectid = :oxobjectid and
                   oxseo.oxshopid = :oxshopid and oxseo.oxlang = :oxlang and oxparams = '' ";

        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return (bool) DatabaseProvider::getMaster()->getOne($sQ, [
            ':oxobjectid' => $this->getEditObjectId(),
            ':oxshopid' => $iShopId,
            ':oxlang' => $iLang
        ]);
    }

    /**
     * Returns url type
     * @deprecated underscore prefix violates PSR12, will be renamed to "getType" in next major
     */
    protected function _getType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /**
     * Returns objects std url
     *
     * @param string $sOxid object id
     *
     * @return string|void
     * @deprecated underscore prefix violates PSR12, will be renamed to "getStdUrl" in next major
     */
    protected function _getStdUrl($sOxid) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($sType = $this->_getType()) {
            $oObject = oxNew($sType);
            if ($oObject->load($sOxid)) {
                return $oObject->getBaseStdLink($this->getEditLang(), true, false);
            }
        }
    }

    /**
     * Returns edit language id
     *
     * @return int
     */
    public function getEditLang()
    {
        return $this->_iEditLang;
    }

    /**
     * Returns alternative seo entry id
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAltSeoEntryId" in next major
     */
    protected function _getAltSeoEntryId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /**
     * Returns seo entry type
     *
     * @return string|null
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSeoEntryType" in next major
     */
    protected function _getSeoEntryType() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->_getType();
    }

    /**
     * Processes parameter before writing to db
     *
     * @param string $sParam parameter to process
     *
     * @return string
     */
    public function processParam($sParam)
    {
        return $sParam;
    }

    /**
     * Returns current object type seo encoder object
     * @deprecated underscore prefix violates PSR12, will be renamed to "getEncoder" in next major
     */
    protected function _getEncoder() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /**
     * Returns seo uri
     */
    public function getEntryUri()
    {
    }

    /**
     * Returns true if SEO object id has suffix enabled. Default is FALSE
     *
     * @return bool
     */
    public function isEntrySuffixed()
    {
        return false;
    }

    /**
     * Returns TRUE if seo object supports suffixes. Default is FALSE
     *
     * @return bool
     */
    public function isSuffixSupported()
    {
        return false;
    }

    /**
     * Returns FALSE, as this view does not support category selector
     *
     * @return bool
     */
    public function showCatSelect()
    {
        return false;
    }

    /**
     * Returns FALSE, as this view does not support active selection type
     *
     * @return bool
     */
    public function getActCatType()
    {
        return false;
    }
}
