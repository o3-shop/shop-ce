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
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\NoJsValidator;
use OxidEsales\Eshop\Core\Registry;
use Exception;
use PDOException;

/**
 * Admin article main selectlist manager.
 * Performs collection and updating (on user submit) main item information.
 */
class LanguageMain extends AdminDetailsController
{
    /**
     * Current shop base languages
     *
     * @var array
     */
    protected $_aLangData = null;

    /**
     * Current shop base languages parameters
     *
     * @var array
     */
    protected $_aLangParams = null;

    /**
     * Current shop base languages base urls
     *
     * @var array
     */
    protected $_aLanguagesUrls = null;

    /**
     * Current shop base languages base ssl urls
     *
     * @var array
     */
    protected $_aLanguagesSslUrls = null;

    /** @var NoJsValidator */
    private $noJsValidator;

    /**
     * Executes parent method parent::render(), creates oxCategoryList object,
     * passes its data to Smarty engine and returns name of template file
     * "selectlist_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $sOxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        //loading languages info from config
        $this->_aLangData = $this->getLanguages();

        if (isset($sOxId) && $sOxId != "-1") {
            //checking if translations files exists
            $this->checkLangTranslations($sOxId);
            $this->_aViewData["edit"] = $this->getLanguageInfo($sOxId);
        }

        return "language_main.tpl";
    }

    /**
     * Saves selection list parameters changes.
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function save()
    {
        parent::save();

        $sOxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        if (!isset($aParams['active'])) {
            $aParams['active'] = 0;
        }

        if (!isset($aParams['default'])) {
            $aParams['default'] = false;
        }

        if (empty($aParams['sort'])) {
            $aParams['sort'] = '99999';
        }

        //loading languages info from config
        $this->_aLangData = $this->getLanguages();
        //checking input errors
        if (!$this->validateInput()) {
            return;
        }

        $blViewError = false;

        // if changed language abbreviation, updating it for all arrays related with languages
        if ($sOxId != -1 && $sOxId != $aParams['abbr']) {
            // #0004850 preventing changing abbr for main language with base id = 0
            if ((int) $this->_aLangData['params'][$sOxId]['baseId'] == 0) {
                $oEx = oxNew(ExceptionToDisplay::class);
                $oEx->setMessage('LANGUAGE_ABBRCHANGEMAINLANG_WARNING');
                Registry::getUtilsView()->addErrorToDisplay($oEx);
                $aParams['abbr'] = $sOxId;
            } else {
                $this->updateAbbreviation($sOxId, $aParams['abbr']);
                $sOxId = $aParams['abbr'];
                $this->setEditObjectId($sOxId);

                $blViewError = true;
            }
        }

        // if adding new language, setting lang id to abbreviation
        if ($blNewLanguage = ($sOxId == -1)) {
            $sOxId = $aParams['abbr'];
            $this->_aLangData['params'][$sOxId]['baseId'] = $this->getAvailableLangBaseId();
            $this->setEditObjectId($sOxId);
        }

        //updating language description
        $this->_aLangData['lang'][$sOxId] = $aParams['desc'];

        //updating language parameters
        $this->_aLangData['params'][$sOxId]['active'] = $aParams['active'];
        $this->_aLangData['params'][$sOxId]['default'] = $aParams['default'];
        $this->_aLangData['params'][$sOxId]['sort'] = $aParams['sort'];

        //if setting lang as default
        if ($aParams['default'] == '1') {
            $this->setDefaultLang($sOxId);
        }

        //updating language urls
        $iBaseId = $this->_aLangData['params'][$sOxId]['baseId'];
        $this->_aLangData['urls'][$iBaseId] = $aParams['baseurl'];
        $this->_aLangData['sslUrls'][$iBaseId] = $aParams['basesslurl'];

        //sort parameters, urls and languages arrays by language base id
        $this->sortLangArraysByBaseId();

        $this->_aViewData["updatelist"] = "1";

        if ($this->isValidLanguageData($this->_aLangData)) {
            //saving languages info
            Registry::getConfig()->saveShopConfVar('aarr', 'aLanguageParams', $this->_aLangData['params']);
            Registry::getConfig()->saveShopConfVar('aarr', 'aLanguages', $this->_aLangData['lang']);
            Registry::getConfig()->saveShopConfVar('arr', 'aLanguageURLs', $this->_aLangData['urls']);
            Registry::getConfig()->saveShopConfVar('arr', 'aLanguageSSLURLs', $this->_aLangData['sslUrls']);
            //checking if added language already has created multilang fields
            //with new base ID - if not, creating new fields
            if ($blNewLanguage) {
                if (!$this->checkMultilangFieldsExistsInDb($sOxId)) {
                    $this->addNewMultilangFieldsToDb();
                } else {
                    $blViewError = true;
                }
            }
            // show message for user to generate views
            if ($blViewError) {
                $oEx = oxNew(ExceptionToDisplay::class);
                $oEx->setMessage('LANGUAGE_ERRORGENERATEVIEWS');
                Registry::getUtilsView()->addErrorToDisplay($oEx);
            }
        }
    }

    /**
     * Get selected language info
     *
     * @param string $sOxId language abbreviation
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getLanguageInfo" in next major
     */
    protected function _getLanguageInfo($sOxId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getLanguageInfo($sOxId);
    }

    /**
     * Get selected language info
     *
     * @param string $sOxId language abbreviation
     *
     * @return array
     */
    protected function getLanguageInfo($sOxId)
    {
        $sDefaultLang = Registry::getConfig()->getConfigParam('sDefaultLang');

        $aLangData = $this->_aLangData['params'][$sOxId];
        $aLangData['abbr'] = $sOxId;
        $aLangData['desc'] = $this->_aLangData['lang'][$sOxId];
        $aLangData['baseurl'] = $this->_aLangData['urls'][$aLangData['baseId']];
        $aLangData['basesslurl'] = $this->_aLangData['sslUrls'][$aLangData['baseId']];
        $aLangData['default'] = (bool)($this->_aLangData['params'][$sOxId]["baseId"] == $sDefaultLang);

        return $aLangData;
    }

    /**
     * Languages array setter
     *
     * @param array $aLangData languages parameters array
     * @deprecated underscore prefix violates PSR12, will be renamed to "setLanguages" in next major
     */
    protected function _setLanguages($aLangData) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setLanguages($aLangData);
    }

    /**
     * Languages array setter
     *
     * @param array $aLangData languages parameters array
     */
    protected function setLanguages($aLangData)
    {
        $this->_aLangData = $aLangData;
    }

    /**
     * Loads from config all data related with languages.
     * If no languages parameters array exists, sets default parameters values.
     * Returns collected languages parameters array.
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getLanguages" in next major
     */
    protected function _getLanguages() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getLanguages();
    }

    /**
     * Loads from config all data related with languages.
     * If no languages parameters array exists, sets default parameters values.
     * Returns collected languages parameters array.
     *
     * @return array
     */
    protected function getLanguages()
    {
        $aLangData['params'] = Registry::getConfig()->getConfigParam('aLanguageParams');
        $aLangData['lang'] = Registry::getConfig()->getConfigParam('aLanguages');
        $aLangData['urls'] = Registry::getConfig()->getConfigParam('aLanguageURLs');
        $aLangData['sslUrls'] = Registry::getConfig()->getConfigParam('aLanguageSSLURLs');

        // empty languages parameters array - creating new one with default values
        if (!is_array($aLangData['params'])) {
            $aLangData['params'] = $this->assignDefaultLangParams($aLangData['lang']);
        }

        return $aLangData;
    }

    /**
     * Replaces languages arrays keys by new value.
     *
     * @param string $sOldId old ID
     * @param string $sNewId new ID
     * @deprecated underscore prefix violates PSR12, will be renamed to "updateAbbreviation" in next major
     */
    protected function _updateAbbervation($sOldId, $sNewId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->updateAbbreviation($sOldId, $sNewId);
    }

    /**
     * Replaces languages arrays keys by new value.
     *
     * @param string $sOldId old ID
     * @param string $sNewId new ID
     */
    protected function updateAbbreviation($sOldId, $sNewId)
    {
        foreach (array_keys($this->_aLangData) as $sTypeKey) {
            if (is_array($this->_aLangData[$sTypeKey]) && count($this->_aLangData[$sTypeKey]) > 0) {
                if ($sTypeKey == 'urls' || $sTypeKey == 'sslUrls') {
                    continue;
                }

                $aKeys = array_keys($this->_aLangData[$sTypeKey]);
                $aValues = array_values($this->_aLangData[$sTypeKey]);
                //find and replace key
                $iReplaceId = array_search($sOldId, $aKeys);
                $aKeys[$iReplaceId] = $sNewId;

                $this->_aLangData[$sTypeKey] = array_combine($aKeys, $aValues);
            }
        }
    }
    
    /**
     * Sort languages, languages parameters, urls, ssl urls arrays according
     * base land ID
     * @deprecated underscore prefix violates PSR12, will be renamed to "sortLangArraysByBaseId" in next major
     */
    protected function _sortLangArraysByBaseId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->sortLangArraysByBaseId();
    }

    /**
     * Sort languages, languages parameters, urls, ssl urls arrays according
     * base land ID
     */
    protected function sortLangArraysByBaseId()
    {
        $aUrls = [];
        $aSslUrls = [];
        $aLanguages = [];

        uasort($this->_aLangData['params'], [$this, '_sortLangParamsByBaseIdCallback']);

        foreach ($this->_aLangData['params'] as $sAbbr => $aParams) {
            $iId = (int) $aParams['baseId'];
            $aUrls[$iId] = $this->_aLangData['urls'][$iId];
            $aSslUrls[$iId] = $this->_aLangData['sslUrls'][$iId];
            $aLanguages[$sAbbr] = $this->_aLangData['lang'][$sAbbr];
        }

        $this->_aLangData['lang'] = $aLanguages;
        $this->_aLangData['urls'] = $aUrls;
        $this->_aLangData['sslUrls'] = $aSslUrls;
    }

    /**
     * Assign default values for each language
     *
     * @param array $aLanguages language array
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignDefaultLangParams" in next major
     */
    protected function _assignDefaultLangParams($aLanguages) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->assignDefaultLangParams($aLanguages);
    }

    /**
     * Assign default values for each language
     *
     * @param array $aLanguages language array
     *
     * @return array
     */
    protected function assignDefaultLangParams($aLanguages)
    {
        $aParams = [];
        $iBaseId = 0;

        foreach (array_keys($aLanguages) as $sOxId) {
            $aParams[$sOxId]['baseId'] = $iBaseId;
            $aParams[$sOxId]['active'] = 1;
            $aParams[$sOxId]['sort'] = $iBaseId + 1;

            $iBaseId++;
        }

        return $aParams;
    }

    /**
     * Sets default language base ID to config var 'sDefaultLang'
     *
     * @param string $sOxId language abbreviation
     * @deprecated underscore prefix violates PSR12, will be renamed to "setDefaultLang" in next major
     */
    protected function _setDefaultLang($sOxId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setDefaultLang($sOxId);
    }

    /**
     * Sets default language base ID to config var 'sDefaultLang'
     *
     * @param string $sOxId language abbreviation
     */
    protected function setDefaultLang($sOxId)
    {
        $sDefaultId = $this->_aLangData['params'][$sOxId]['baseId'];
        Registry::getConfig()->saveShopConfVar('str', 'sDefaultLang', $sDefaultId);
    }

    /**
     * Get available language base ID
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "getAvailableLangBaseId" in next major
     */
    protected function _getAvailableLangBaseId() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getAvailableLangBaseId();
    }

    /**
     * Get available language base ID
     *
     * @return int
     */
    protected function getAvailableLangBaseId()
    {
        $aBaseId = [];
        foreach ($this->_aLangData['params'] as $aLang) {
            $aBaseId[] = $aLang['baseId'];
        }

        $iNewId = 0;
        sort($aBaseId);
        $iTotal = count($aBaseId);

        //getting first available id
        while ($iNewId <= $iTotal - 1) {
            if ($iNewId !== $aBaseId[$iNewId]) {
                break;
            }
            $iNewId++;
        }

        return $iNewId;
    }

    /**
     * Check selected language has translation file lang.php
     * If not - displays warning
     *
     * @param string $sOxId language abbreviation
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkLangTranslations" in next major
     */
    protected function _checkLangTranslations($sOxId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->checkLangTranslations($sOxId);
    }

    /**
     * Check selected language has translation file lang.php
     * If not - displays warning
     *
     * @param string $sOxId language abbreviation
     */
    protected function checkLangTranslations($sOxId)
    {
        $myConfig = Registry::getConfig();

        $sDir = dirname($myConfig->getTranslationsDir('lang.php', $sOxId));

        if (empty($sDir)) {
            $oEx = oxNew(ExceptionToDisplay::class);
            $oEx->setMessage('LANGUAGE_NOTRANSLATIONS_WARNING');
            Registry::getUtilsView()->addErrorToDisplay($oEx);
        }
    }

    /**
     * Check if selected language already has multilanguage fields in DB
     *
     * @param string $sOxId language abbreviation
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkMultilangFieldsExistsInDb" in next major
     */
    protected function _checkMultilangFieldsExistsInDb($sOxId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->checkMultilangFieldsExistsInDb($sOxId);
    }

    /**
     * Check if selected language already has multilanguage fields in DB
     *
     * @param string $sOxId language abbreviation
     *
     * @return bool
     */
    protected function checkMultilangFieldsExistsInDb($sOxId)
    {
        $iBaseId = $this->_aLangData['params'][$sOxId]['baseId'];
        $sTable = getLangTableName('oxarticles', $iBaseId);
        $sColumn = 'oxtitle' . Registry::getLang()->getLanguageTag($iBaseId);

        $oDbMetadata = oxNew(DbMetaDataHandler::class);

        return $oDbMetadata->tableExists($sTable) && $oDbMetadata->fieldExists($sColumn, $sTable);
    }

    /**
     * Adding new language to DB - creating new multilanguage fields with new
     * language ID (e.g. oxtitle_4)
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "addNewMultilangFieldsToDb" in next major
     */
    protected function _addNewMultilangFieldsToDb() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->addNewMultilangFieldsToDb();
    }

    /**
     * Adding new language to DB - creating new multilanguage fields with new
     * language ID (e.g. oxtitle_4)
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function addNewMultilangFieldsToDb()
    {
        //creating new multilingual fields with new id over whole DB
        $oDbMeta = oxNew(DbMetaDataHandler::class);

        $db = DatabaseProvider::getDb();
        $db->startTransaction();
        try {
            $oDbMeta->addNewLangToDb();
            $db->commitTransaction();
        } catch (Exception $oEx) {
            if (!$oEx instanceof PDOException) {
                $db->rollbackTransaction();
            }
            //show warning
            $oEx = oxNew(ExceptionToDisplay::class);
            $oEx->setMessage('LANGUAGE_ERROR_ADDING_MULTILANG_FIELDS');
            Registry::getUtilsView()->addErrorToDisplay($oEx);
        }
    }

    /**
     * Check if language already exists
     *
     * @param string $sAbbr language abbreviation
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkLangExists" in next major
     */
    protected function _checkLangExists($sAbbr) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->checkLangExists($sAbbr);
    }

    /**
     * Check if language already exists
     *
     * @param string $sAbbr language abbreviation
     *
     * @return bool
     */
    protected function checkLangExists($sAbbr)
    {
        $aAbbrs = array_keys($this->_aLangData['lang']);

        return in_array($sAbbr, $aAbbrs);
    }

    /**
     * Callback function for sorting languages already. Sorts array according
     * 'baseId' parameter
     *
     * @param object $oLang1 language array
     * @param object $oLang2 language array
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "sortLangParamsByBaseIdCallback" in next major
     */
    protected function _sortLangParamsByBaseIdCallback($oLang1, $oLang2) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->sortLangParamsByBaseIdCallback($oLang1, $oLang2);
    }

    /**
     * Callback function for sorting languages already. Sorts array according
     * 'baseId' parameter
     *
     * @param object $oLang1 language array
     * @param object $oLang2 language array
     *
     * @return int
     */
    protected function sortLangParamsByBaseIdCallback($oLang1, $oLang2)
    {
        return ($oLang1['baseId'] < $oLang2['baseId']) ? -1 : 1;
    }

    /**
     * Check language input errors
     *
     * @return bool
     * @throws Exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "validateInput" in next major
     */
    protected function _validateInput() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->validateInput();
    }

    /**
     * Check language input errors
     *
     * @return bool
     * @throws Exception
     */
    protected function validateInput()
    {
        $result = true;

        $oxid = $this->getEditObjectId();
        $parameters = Registry::getRequest()->getRequestEscapedParameter('editval');

        // if creating new language, checking if language already exists with
        // entered language abbreviation
        if (($oxid == -1) && $this->checkLangExists($parameters['abbr'])) {
            $this->addDisplayException('LANGUAGE_ALREADYEXISTS_ERROR');
            $result = false;
        }

        // As the abbreviation is used in database view creation, check for allowed characters
        if (!$this->checkAbbreviationAllowedCharacters($parameters['abbr'])) {
            $this->addDisplayException('LANGUAGE_ABBREVIATION_INVALID_ERROR');
            $result = false;
        }

        // checking if language name is not empty
        if (empty($parameters['desc'])) {
            $this->addDisplayException('LANGUAGE_EMPTYLANGUAGENAME_ERROR');
            $result = false;
        }

        return $result;
    }

    /**
     * Check if language abbreviation contains only allowed characters.
     * Abbreviation is used for view creation, so to be on the safe side with MySQL,
     * only allow characters [0-9,a-z,A-Z_] (basic Latin letters, digits 0-9, underscore).
     * Allowing other characters means table names would have to be escaped with backticks in all queries.
     *
     * @param string $abbreviation language abbreviation
     *
     * @throws Exception if pattern does not match
     *
     * @return bool
     */
    protected function checkAbbreviationAllowedCharacters($abbreviation)
    {
        $pattern = '/^[a-zA-Z0-9_]*$/';
        $result = preg_match($pattern, $abbreviation);
        if ($result === false) {
            throw new Exception(preg_last_error(), $pattern, $abbreviation);
        }

        return (bool) $result;
    }

    /**
     * Add exception to be displayed in frontend.
     *
     * @param string $message Language constant
     */
    protected function addDisplayException($message)
    {
        $exception = oxNew(ExceptionToDisplay::class);
        $exception->setMessage($message);
        Registry::getUtilsView()->addErrorToDisplay($exception);
    }

    /**
     * Validates provided language data and sets error to view in case it is not valid.
     *
     * @param array $aLanguageData
     *
     * @return bool
     */
    protected function isValidLanguageData($aLanguageData)
    {
        $blValid = true;
        $configValidator = $this->getNoJsValidator();
        foreach ($aLanguageData as $mLanguageDataParameters) {
            if (is_array($mLanguageDataParameters)) {
                // Recursion till we are going to have a string.
                $blDeepResult = $this->isValidLanguageData($mLanguageDataParameters);
                $blValid = $blDeepResult === false ? false : $blValid;
            } elseif (!$configValidator->isValid($mLanguageDataParameters)) {
                $blValid = false;
                $error = oxNew(DisplayError::class);
                $error->setFormatParameters([htmlspecialchars($mLanguageDataParameters)]);
                $error->setMessage("SHOP_CONFIG_ERROR_INVALID_VALUE");
                Registry::getUtilsView()->addErrorToDisplay($error);
            }
        }

        return $blValid;
    }

    /**
     * @return NoJsValidator
     */
    protected function getNoJsValidator()
    {
        if (is_null($this->noJsValidator)) {
            $this->noJsValidator = oxNew(NoJsValidator::class);
        }

        return $this->noJsValidator;
    }
}
