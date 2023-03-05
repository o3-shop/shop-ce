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

namespace OxidEsales\EshopCommunity\Core;

/**
 * Seo encoder base
 */
class SeoDecoder extends \OxidEsales\Eshop\Core\Base
{
    /**
     * _parseStdUrl parses given url into array of params
     *
     * @param string $sUrl given url
     *
     * @access protected
     * @return array
     */
    public function parseStdUrl($sUrl)
    {
        $oStr = getStr();
        $aRet = [];
        $sUrl = $oStr->html_entity_decode($sUrl);

        if (($iPos = strpos($sUrl, '?')) !== false) {
            parse_str($oStr->substr($sUrl, $iPos + 1), $aRet);
        }

        return $aRet;
    }

    /**
     * Returns ident (md5 of seo url) to fetch seo data from DB
     *
     * @param string $sSeoUrl  seo url to calculate ident
     * @param bool   $blIgnore if FALSE - blocks from direct access when default language seo url with language ident executed
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getIdent" in next major
     */
    protected function _getIdent($sSeoUrl, $blIgnore = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return md5(strtolower($sSeoUrl));
    }

    /**
     * decodeUrl decodes given url into O3-Shop required parameters which are returned as array
     *
     * @param string $seoUrl SEO url
     *
     * @access        public
     * @return array || false
     */
    public function decodeUrl($seoUrl)
    {
        $stringObject = getStr();
        $baseUrl = $this->getConfig()->getShopURL();
        if ($stringObject->strpos($seoUrl, $baseUrl) === 0) {
            $seoUrl = $stringObject->substr($seoUrl, $stringObject->strlen($baseUrl));
        }
        $seoUrl = rawurldecode($seoUrl);

        //extract page number from seo url
        list($seoUrl, $pageNumber) = $this->extractPageNumberFromSeoUrl($seoUrl);
        $shopId = $this->getConfig()->getShopId();

        $key = $this->_getIdent($seoUrl);
        $urlParameters = false;

        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $resultSet = $database->select("select oxstdurl, oxlang from oxseo where oxident = :oxident and oxshopid = :oxshopid limit 1", [
            ':oxident' => $key,
            ':oxshopid' => $shopId
        ]);
        if (!$resultSet->EOF) {
            // primary seo language changed ?
            $urlParameters = $this->parseStdUrl($resultSet->fields['oxstdurl']);
            $urlParameters['lang'] = $resultSet->fields['oxlang'];
        }
        if (is_array($urlParameters) && !is_null($pageNumber) && ($pageNumber > 0)) {
            $urlParameters['pgNr'] = $pageNumber;
        }

        return $urlParameters;
    }

    /**
     * Checks if url is stored in history table and if it was found - tries
     * to fetch new url from seo table
     *
     * @param string $seoUrl SEO url
     *
     * @access         public
     * @return string || false
     * @deprecated underscore prefix violates PSR12, will be renamed to "decodeOldUrl" in next major
     */
    protected function _decodeOldUrl($seoUrl) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $stringObject = getStr();
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $baseUrl = $this->getConfig()->getShopURL();
        if ($stringObject->strpos($seoUrl, $baseUrl) === 0) {
            $seoUrl = $stringObject->substr($seoUrl, $stringObject->strlen($baseUrl));
        }
        $shopId = $this->getConfig()->getShopId();
        $seoUrl = rawurldecode($seoUrl);

        //extract page number from seo url
        list($seoUrl, $pageNumber) = $this->extractPageNumberFromSeoUrl($seoUrl);

        $key = $this->_getIdent($seoUrl, true);

        $url = false;
        $resultSet = $database->select("select oxobjectid, oxlang from oxseohistory where oxident = :oxident and oxshopid = :oxshopid limit 1", [
            ':oxident' => $key,
            ':oxshopid' => $shopId
        ]);
        if (!$resultSet->EOF) {
            // updating hit info (oxtimestamp field will be updated automatically)
            $database->execute(
                "update oxseohistory set oxhits = oxhits + 1 where oxident = :oxident and oxshopid = :oxshopid limit 1",
                [
                    ':oxident' => $key,
                    ':oxshopid' => $shopId
                ]
            );

            // fetching new url
            $url = $this->_getSeoUrl($resultSet->fields['oxobjectid'], $resultSet->fields['oxlang'], $shopId);

            // appending with $_SERVER["QUERY_STRING"]
            $url = $this->_addQueryString($url);
        }
        if ($url && !is_null($pageNumber)) {
            $url = \OxidEsales\Eshop\Core\Registry::getUtilsUrl()->appendUrl($url, ['pgNr' => $pageNumber]);
        }

        return $url;
    }

    /**
     * Appends and returns given url with $_SERVER["QUERY_STRING"] value
     *
     * @param string $sUrl url to append
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "addQueryString" in next major
     */
    protected function _addQueryString($sUrl) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (isset($_SERVER["QUERY_STRING"]) && $_SERVER["QUERY_STRING"]) {
            $sUrl = rtrim($sUrl, "&?");
            $sQ = ltrim($_SERVER["QUERY_STRING"], "&?");

            $sUrl .= (strpos($sUrl, '?') === false) ? "?" : "&";
            $sUrl .= $sQ;
        }

        return $sUrl;
    }

    /**
     * retrieve SEO url by its object id
     * normally used for getting the redirect url from seo history
     *
     * @param string $sObjectId object id
     * @param int    $iLang     language to fetch
     * @param int    $iShopId   shop id
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSeoUrl" in next major
     */
    protected function _getSeoUrl($sObjectId, $iLang, $iShopId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $aInfo = $oDb->getRow("select oxseourl, oxtype from oxseo where oxobjectid = :oxobjectid and oxlang = :oxlang and oxshopid = :oxshopid order by oxparams limit 1", [
            ':oxobjectid' => $sObjectId,
            ':oxlang' => $iLang,
            ':oxshopid' => $iShopId,
        ]);

        if ('oxarticle' == $aInfo['oxtype']) {
            $sMainCatId = $oDb->getOne("select oxcatnid from " . getViewName("oxobject2category") . " where oxobjectid = :oxobjectid order by oxtime", [
                ':oxobjectid' => $sObjectId
            ]);
            if ($sMainCatId) {
                $sUrl = $oDb->getOne("select oxseourl from oxseo where oxobjectid = :oxobjectid and oxlang = :oxlang and oxshopid = :oxshopid  and oxparams = :oxparams order by oxexpired", [
                    ':oxobjectid' => $sObjectId,
                    ':oxlang' => $iLang,
                    ':oxshopid' => $iShopId,
                    ':oxparams' => $sMainCatId,
                ]);
                if ($sUrl) {
                    return $sUrl;
                }
            }
        }

        return $aInfo['oxseourl'];
    }

    /**
     * processSeoCall handles Server information and passes it to decoder
     *
     * @param string $sRequest request
     * @param string $sPath    path
     *
     * @access public
     */
    public function processSeoCall($sRequest = null, $sPath = null)
    {
        // first - collect needed parameters
        if (!$sRequest) {
            if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI']) {
                $sRequest = $_SERVER['REQUEST_URI'];
            } else {
                // try something else
                $sRequest = $_SERVER['SCRIPT_URI'] ?? null;
            }
        }

        $sPath = $sPath ? $sPath : str_replace('oxseo.php', '', $_SERVER['SCRIPT_NAME']);
        if (($sParams = $this->_getParams($sRequest, $sPath))) {
            // in case SEO url is actual
            if (is_array($aGet = $this->decodeUrl($sParams))) {
                $_GET = array_merge($aGet, $_GET);
                \OxidEsales\Eshop\Core\Registry::getLang()->resetBaseLanguage();
            } elseif (($sRedirectUrl = $this->_decodeOldUrl($sParams))) {
                // in case SEO url was changed - redirecting to new location
                \OxidEsales\Eshop\Core\Registry::getUtils()->redirect($this->getConfig()->getShopURL() . $sRedirectUrl, false, 301);
            } elseif (($sRedirectUrl = $this->_decodeSimpleUrl($sParams))) {
                // old type II seo urls
                \OxidEsales\Eshop\Core\Registry::getUtils()->redirect($this->getConfig()->getShopURL() . $sRedirectUrl, false, 301);
            } else {
                \OxidEsales\Eshop\Core\Registry::getSession()->start();
                // unrecognized url
                error_404_handler($sParams);
            }
        }
    }

    /**
     * Tries to fetch SEO url according to type II seo url data. If no
     * specified data is found NULL will be returned
     *
     * @param string $sParams request params (url chunk)
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "decodeSimpleUrl" in next major
     */
    protected function _decodeSimpleUrl($sParams) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sLastParam = trim($sParams, '/');

        // active object id
        $sUrl = null;

        if ($sLastParam) {
            $iLanguage = \OxidEsales\Eshop\Core\Registry::getLang()->getBaseLanguage();

            // article ?
            if (strpos($sLastParam, '.htm') !== false) {
                $sUrl = $this->_getObjectUrl($sLastParam, 'oxarticles', $iLanguage, 'oxarticle');
            } else {
                // category ?
                if (!($sUrl = $this->_getObjectUrl($sLastParam, 'oxcategories', $iLanguage, 'oxcategory'))) {
                    // maybe manufacturer ?
                    if (!($sUrl = $this->_getObjectUrl($sLastParam, 'oxmanufacturers', $iLanguage, 'oxmanufacturer'))) {
                        // then maybe vendor ?
                        $sUrl = $this->_getObjectUrl($sLastParam, 'oxvendor', $iLanguage, 'oxvendor');
                    }
                }
            }
        }

        return $sUrl;
    }

    /**
     * Searches and returns (if available) current objects seo url
     *
     * @param string $sSeoId    ident (or last chunk of url)
     * @param string $sTable    name of table to look for data
     * @param int    $iLanguage current language identifier
     * @param string $sType     type of object to search in seo table
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getObjectUrl" in next major
     */
    protected function _getObjectUrl($sSeoId, $sTable, $iLanguage, $sType) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sTable = getViewName($sTable, $iLanguage);

        // first checking of field exists at all
        if ($oDb->getOne("show columns from {$sTable} where field = 'oxseoid'")) {
            // if field exists - searching for object id
            if (
                $sObjectId = $oDb->getOne("select oxid from {$sTable} where oxseoid = :oxseoid", [
                ':oxseoid' => $sSeoId
                ])
            ) {
                return $oDb->getOne("select oxseourl from oxseo where oxtype = :oxtype and oxobjectid = :oxobjectid and oxlang = :oxlang", [
                    ':oxtype' => $sType,
                    ':oxobjectid' => $sObjectId,
                    ':oxlang' => $iLanguage,
                ]);
            }
        }
    }

    /**
     * Extracts SEO paramteters and returns as array
     *
     * @param string $sRequest request
     * @param string $sPath    path
     *
     * @return array $aParams extracted params
     * @deprecated underscore prefix violates PSR12, will be renamed to "getParams" in next major
     */
    protected function _getParams($sRequest, $sPath) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oStr = getStr();

        $sParams = $oStr->preg_replace('/\?.*/', '', $sRequest);
        $sPath = preg_quote($sPath, '/');
        $sParams = $oStr->preg_replace("/^$sPath/", '', $sParams);

        // this should not happen on most cases, because this redirect is handled by .htaccess
        if ($sParams && !$oStr->preg_match('/\.html$/', $sParams) && !$oStr->preg_match('/\/$/', $sParams)) {
            \OxidEsales\Eshop\Core\Registry::getUtils()->redirect(\OxidEsales\Eshop\Core\Registry::getConfig()->getShopURL() . $sParams . '/', false, 301);
        }

        return $sParams;
    }

    /**
     * Splits seo url into:
     *     - seo url without page number
     *     - page number
     *
     * @param string $seoUrl
     *
     * @return array
     */
    private function extractPageNumberFromSeoUrl($seoUrl)
    {
        $pageNumber = null;
        if (1 === preg_match('/(.*?)\/(\d+)\/(.*)/', $seoUrl, $matches)) {
            $seoUrl = $matches[1] . '/' . $matches[3];
            $pageNumber = $this->convertSeoPageStringToActualPageNumber($matches[2]);
        }
        return [$seoUrl, $pageNumber];
    }

    /**
     * Converts seo url pagination number to actual page number.
     *
     * @param int $seoPageNumber
     *
     * @return int
     */
    private function convertSeoPageStringToActualPageNumber($seoPageNumber)
    {
        if (!is_null($seoPageNumber)) {
            $seoPageNumber = max(0, (int) $seoPageNumber - 1);
        }
        return $seoPageNumber;
    }
}
