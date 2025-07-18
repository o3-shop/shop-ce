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

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Registry;
use Exception;

/**
 * Admin selectlist list manager.
 */
class LanguageList extends AdminListController
{
    /**
     * Default sorting parameter.
     *
     * @var string
     */
    protected $_sDefSortField = 'sort';

    /**
     * Default sorting order.
     *
     * @var string
     */
    protected $_sDefSortOrder = 'asc';

    /**
     * Checks for Malladmin rights
     *
     * @return void
     */
    public function deleteEntry()
    {
        $myConfig = Registry::getConfig();
        $sOxId = $this->getEditObjectId();

        $aLangData['params'] = $myConfig->getConfigParam('aLanguageParams');
        $aLangData['lang'] = $myConfig->getConfigParam('aLanguages');
        $aLangData['urls'] = $myConfig->getConfigParam('aLanguageURLs');
        $aLangData['sslUrls'] = $myConfig->getConfigParam('aLanguageSSLURLs');

        $iBaseId = (int) $aLangData['params'][$sOxId]['baseId'];

        // preventing deleting main language with base id = 0
        if ($iBaseId == 0) {
            $oEx = oxNew(ExceptionToDisplay::class);
            $oEx->setMessage('LANGUAGE_DELETINGMAINLANG_WARNING');
            Registry::getUtilsView()->addErrorToDisplay($oEx);

            return;
        }

        // unsetting selected lang from languages arrays
        unset($aLangData['params'][$sOxId]);
        unset($aLangData['lang'][$sOxId]);
        unset($aLangData['urls'][$iBaseId]);
        unset($aLangData['sslUrls'][$iBaseId]);

        //saving languages info back to DB
        $myConfig->saveShopConfVar('aarr', 'aLanguageParams', $aLangData['params']);
        $myConfig->saveShopConfVar('aarr', 'aLanguages', $aLangData['lang']);
        $myConfig->saveShopConfVar('arr', 'aLanguageURLs', $aLangData['urls']);
        $myConfig->saveShopConfVar('arr', 'aLanguageSSLURLs', $aLangData['sslUrls']);

        //if deleted language was default, setting default lang to 0
        if ($iBaseId == $myConfig->getConfigParam('sDefaultLang')) {
            $myConfig->saveShopConfVar('str', 'sDefaultLang', 0);
        }
    }

    /**
     * Executes parent method parent::render() and returns name of template
     * file "selectlist_list.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();
        $this->_aViewData['mylist'] = $this->getLanguagesList();

        return "language_list.tpl";
    }

    /**
     * Collects shop languages list.
     *
     * @return array
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getLanguagesList" in next major
     */
    protected function _getLanguagesList() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getLanguagesList();
    }

    /**
     * Collects shop languages list.
     *
     * @return array
     * @throws DatabaseConnectionException
     */
    protected function getLanguagesList()
    {
        $aLangParams = Registry::getConfig()->getConfigParam('aLanguageParams');
        $aLanguages = Registry::getLang()->getLanguageArray();
        $sDefaultLang = Registry::getConfig()->getConfigParam('sDefaultLang');

        foreach ($aLanguages as $sKey => $sValue) {
            $sOxId = $sValue->oxid;
            $aLanguages[$sKey]->active = (!isset($aLangParams[$sOxId]["active"])) ? 1 : $aLangParams[$sOxId]["active"];
            $aLanguages[$sKey]->default = (bool)($aLangParams[$sOxId]["baseId"] == $sDefaultLang);
            $aLanguages[$sKey]->sort = $aLangParams[$sOxId]["sort"];
        }

        if (is_array($aLangParams)) {
            $aSorting = $this->getListSorting();

            if (is_array($aSorting)) {
                foreach ($aSorting as $aFieldSorting) {
                    foreach ($aFieldSorting as $sField => $sDir) {
                        $this->_sDefSortField = $sField;
                        $this->_sDefSortOrder = $sDir;

                        if ($sField == 'active') {
                            //reverting sort order for field 'active'
                            $this->_sDefSortOrder = 'desc';
                        }
                        break 2;
                    }
                }
            }

            uasort($aLanguages, [$this, '_sortLanguagesCallback']);
        }

        return $aLanguages;
    }

    /**
     * Callback function for sorting languages objects. Sorts array according
     * 'sort' parameter
     *
     * @param object $oLang1 language object
     * @param object $oLang2 language object
     *
     * @return int
     * @deprecated underscore prefix violates PSR12, will be renamed to "sortLanguagesCallback" in next major
     */
    protected function _sortLanguagesCallback($oLang1, $oLang2) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->sortLanguagesCallback($oLang1, $oLang2);
    }

    /**
     * Callback function for sorting languages objects. Sorts array according
     * 'sort' parameter
     *
     * @param object $oLang1 language object
     * @param object $oLang2 language object
     *
     * @return int
     */
    protected function sortLanguagesCallback($oLang1, $oLang2)
    {
        $sSortParam = $this->_sDefSortField;
        $sVal1 = is_string($oLang1->$sSortParam) ? strtolower($oLang1->$sSortParam) : $oLang1->$sSortParam;
        $sVal2 = is_string($oLang2->$sSortParam) ? strtolower($oLang2->$sSortParam) : $oLang2->$sSortParam;

        if ($this->_sDefSortOrder == 'asc') {
            return ($sVal1 < $sVal2) ? -1 : 1;
        } else {
            return ($sVal1 > $sVal2) ? -1 : 1;
        }
    }

    /**
     * Resets all multilanguage fields with specific language id
     * to default value in all tables.
     *
     * @param string $iLangId language ID
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "resetMultiLangDbFields" in next major
     */
    protected function _resetMultiLangDbFields($iLangId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->resetMultiLangDbFields($iLangId);
    }

    /**
     * Resets all multilanguage fields with specific language id
     * to default value in all tables.
     *
     * @param string $iLangId language ID
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function resetMultiLangDbFields($iLangId)
    {
        $iLangId = (int) $iLangId;

        //skipping reseting language with id = 0
        if ($iLangId) {
            DatabaseProvider::getDb()->startTransaction();

            try {
                $oDbMeta = oxNew(DbMetaDataHandler::class);
                $oDbMeta->resetLanguage($iLangId);

                DatabaseProvider::getDb()->commitTransaction();
            } catch (Exception $oEx) {
                // if exception, rollBack everything
                DatabaseProvider::getDb()->rollbackTransaction();

                //show warning
                $oEx = oxNew(ExceptionToDisplay::class);
                $oEx->setMessage('LANGUAGE_ERROR_RESETING_MULTILANG_FIELDS');
                Registry::getUtilsView()->addErrorToDisplay($oEx);
            }
        }
    }    
}
