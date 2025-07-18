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

use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * VariantHandler encapsulates methods dealing with multidimensional variant and variant names.
 *
 */
class VariantHandler extends Base
{
    /**
     * Variant names
     *
     * @var array
     */
    protected $_oArticles = null;

    /**
     * Multidimensional variant separator
     *
     * @var string
     */
    protected $_sMdSeparator = " | ";

    /**
     * Multidimensional variant tree structure
     *
     * @var MdVariant
     */
    protected $_oMdVariants = null;

    /**
     * Sets internal variant name array from article list.
     *
     * @param array $oArticles Variant list
     */
    public function init($oArticles)
    {
        $this->_oArticles = $oArticles;
    }

    /**
     * Returns multidimensional variant structure
     *
     * @param object $oVariants all article variants
     * @param string $sParentId parent article id
     *
     * @return MdVariant
     */
    public function buildMdVariants($oVariants, $sParentId)
    {
        $oMdVariants = oxNew(MdVariant::class);
        $oMdVariants->setParentId($sParentId);
        $oMdVariants->setName("_parent_product_");
        foreach ($oVariants as $sKey => $oVariant) {
            $aNames = explode(trim($this->_sMdSeparator), $oVariant->oxarticles__oxvarselect->value);
            foreach ($aNames as $sNameKey => $sName) {
                $aNames[$sNameKey] = trim($sName);
            }
            $oMdVariants->addNames(
                $sKey,
                $aNames,
                (Registry::getConfig()->getConfigParam('bl_perfLoadPrice')) ? $oVariant->getPrice()->getPrice() : null,
                $oVariant->getLink()
            );
        }

        return $oMdVariants;
    }

    /**
     * Generate variants from selection lists
     *
     * @param array $aSels ids of selection list
     * @param object $oArticle parent article
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function genVariantFromSell($aSels, $oArticle)
    {
        $oVariants = $oArticle->getAdminVariants();
        $myConfig = Registry::getConfig();
        $myUtils = Registry::getUtils();
        $myLang = Registry::getLang();
        $aConfLanguages = $myLang->getLanguageIds();

        foreach ($aSels as $sSelId) {
            $oSel = oxNew(MultiLanguageModel::class);
            $oSel->setEnableMultilang(false);
            $oSel->init('oxselectlist');
            $oSel->load($sSelId);
            $sVarNameUpdate = "";
            foreach ($aConfLanguages as $sKey => $sLang) {
                $sPrefix = $myLang->getLanguageTag($sKey);
                $aSelValues = $myUtils->assignValuesFromText($oSel->{"oxselectlist__oxvaldesc" . $sPrefix}->value);
                foreach ($aSelValues as $sI => $oValue) {
                    $aValues[$sI][$sKey] = $oValue;
                }
                $aSelTitle[$sKey] = $oSel->{"oxselectlist__oxtitle" . $sPrefix}->value;
                $sMdSeparator = ($oArticle->oxarticles__oxvarname->value) ? $this->_sMdSeparator : '';
                if ($sVarNameUpdate) {
                    $sVarNameUpdate .= ", ";
                }
                $sVarName = DatabaseProvider::getDb()->quote($sMdSeparator . $aSelTitle[$sKey]);
                $sVarNameUpdate .= "oxvarname" . $sPrefix . " = CONCAT(oxvarname" . $sPrefix . ", " . $sVarName . ")";
            }
            $oMDVariants = $this->_assignValues($aValues, $oVariants, $oArticle, $aConfLanguages);
            if ($myConfig->getConfigParam('blUseMultidimensionVariants')) {
                $oAttribute = oxNew(Attribute::class);
                $oAttribute->assignVarToAttribute($oMDVariants, $aSelTitle);
            }
            $this->_updateArticleVarName($sVarNameUpdate, $oArticle->oxarticles__oxid->value);
        }
    }

    /**
     * Assigns values of selection list to variants
     *
     * @param array $aValues multilang values of selection list
     * @param object $oVariants variant list
     * @param object $oArticle parent article
     * @param array $aConfLanguages array of all active languages
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "assignValues" in next major
     */
    protected function _assignValues($aValues, $oVariants, $oArticle, $aConfLanguages) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();
        $myLang = Registry::getLang();
        $iCounter = 0;
        $aVarselect = []; //multilanguage names of existing variants
        //iterating through all select list values (e.g. $oValue->name = S, M, X, XL)
        for ($i = 0; $i < count($aValues); $i++) {
            $oValue = $aValues[$i][0];
            $dPriceMod = $this->_getValuePrice($oValue, $oArticle->oxarticles__oxprice->value);
            if ($oVariants->count() > 0) {
                //if we have any existing variants then copying each variant with $oValue->name
                foreach ($oVariants as $oSimpleVariant) {
                    if (!$iCounter) {
                        //we just update the first variant
                        $oVariant = oxNew(Article::class);
                        $oVariant->setEnableMultilang(false);
                        $oVariant->load($oSimpleVariant->oxarticles__oxid->value);
                        $oVariant->oxarticles__oxprice->setValue($oVariant->oxarticles__oxprice->value + $dPriceMod);
                        //assign for all languages
                        foreach ($aConfLanguages as $sKey => $sLang) {
                            $oValue = $aValues[$i][$sKey];
                            $sPrefix = $myLang->getLanguageTag($sKey);
                            $aVarselect[$oSimpleVariant->oxarticles__oxid->value][$sKey] = $oVariant->{"oxarticles__oxvarselect" . $sPrefix}->value;
                            $oVariant->{'oxarticles__oxvarselect' . $sPrefix}->setValue($oVariant->{"oxarticles__oxvarselect" . $sPrefix}->value . $this->_sMdSeparator . $oValue->name);
                        }
                        $oVariant->oxarticles__oxsort->setValue($oVariant->oxarticles__oxsort->value * 10);
                        $oVariant->save();
                        $sVarId = $oSimpleVariant->oxarticles__oxid->value;
                    } else {
                        //we create new variants
                        foreach ($aVarselect[$oSimpleVariant->oxarticles__oxid->value] as $sKey => $sVarselect) {
                            $oValue = $aValues[$i][$sKey];
                            $sPrefix = $myLang->getLanguageTag($sKey);
                            $aParams['oxarticles__oxvarselect' . $sPrefix] = $sVarselect . $this->_sMdSeparator . $oValue->name;
                        }
                        $aParams['oxarticles__oxartnum'] = $oSimpleVariant->oxarticles__oxartnum->value . "-" . $iCounter;
                        $aParams['oxarticles__oxprice'] = $oSimpleVariant->oxarticles__oxprice->value + $dPriceMod;
                        $aParams['oxarticles__oxsort'] = $oSimpleVariant->oxarticles__oxsort->value * 10 + 10 * $iCounter;
                        $aParams['oxarticles__oxstock'] = 0;
                        $aParams['oxarticles__oxstockflag'] = $oSimpleVariant->oxarticles__oxstockflag->value;
                        $aParams['oxarticles__oxisconfigurable'] = $oSimpleVariant->oxarticles__oxisconfigurable->value;
                        $sVarId = $this->_createNewVariant($aParams, $oArticle->oxarticles__oxid->value);
                        if ($myConfig->getConfigParam('blUseMultidimensionVariants')) {
                            $oAttrList = oxNew(Attribute::class);
                            $aIds = $oAttrList->getAttributeAssigns($oSimpleVariant->oxarticles__oxid->value);
                            $aMDVariants["mdvar_" . $sVarId] = $aIds;
                        }
                    }
                    if ($myConfig->getConfigParam('blUseMultidimensionVariants')) {
                        $aMDVariants[$sVarId] = $aValues[$i];
                    }
                }
                $iCounter++;
            } else {
                //in case we don't have any variants then we just create variant(s) with $oValue->name
                $iCounter++;
                foreach ($aConfLanguages as $sKey => $sLang) {
                    $oValue = $aValues[$i][$sKey];
                    $sPrefix = $myLang->getLanguageTag($sKey);
                    $aParams['oxarticles__oxvarselect' . $sPrefix] = $oValue->name;
                }
                $aParams['oxarticles__oxartnum'] = $oArticle->oxarticles__oxartnum->value . "-" . $iCounter;
                $aParams['oxarticles__oxprice'] = $oArticle->oxarticles__oxprice->value + $dPriceMod;
                $aParams['oxarticles__oxsort'] = $iCounter * 100; // reduction
                $aParams['oxarticles__oxstock'] = 0;
                $aParams['oxarticles__oxstockflag'] = $oArticle->oxarticles__oxstockflag->value;
                $aParams['oxarticles__oxisconfigurable'] = $oArticle->oxarticles__oxisconfigurable->value;
                $sVarId = $this->_createNewVariant($aParams, $oArticle->oxarticles__oxid->value);
                if ($myConfig->getConfigParam('blUseMultidimensionVariants')) {
                    $aMDVariants[$sVarId] = $aValues[$i];
                }
            }
        }

        return $aMDVariants;
    }

    /**
     * Returns article price
     *
     * @param object $oValue       selection list value
     * @param double $dParentPrice parent article price
     *
     * @return double
     * @deprecated underscore prefix violates PSR12, will be renamed to "getValuePrice" in next major
     */
    protected function _getValuePrice($oValue, $dParentPrice) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $myConfig = Registry::getConfig();
        $dPriceMod = 0;
        if ($myConfig->getConfigParam('bl_perfLoadSelectLists') && $myConfig->getConfigParam('bl_perfUseSelectlistPrice')) {
            if ($oValue->priceUnit == 'abs') {
                $dPriceMod = $oValue->price;
            } elseif ($oValue->priceUnit == '%') {
                $dPriceModPercent = abs($oValue->price) * $dParentPrice / 100.0;
                if (($oValue->price) >= 0.0) {
                    $dPriceMod = $dPriceModPercent;
                } else {
                    $dPriceMod = -$dPriceModPercent;
                }
            }
        }

        return $dPriceMod;
    }

    /**
     * Creates new article variant.
     *
     * @param null $aParams assigned parameters
     * @param null $sParentId parent article id
     *
     * @return null
     * @throws \Exception
     * @deprecated underscore prefix violates PSR12, will be renamed to "createNewVariant" in next major
     */
    protected function _createNewVariant($aParams = null, $sParentId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // checkbox handling
        $aParams['oxarticles__oxactive'] = 0;

        // shopid
        $sShopID = Registry::getSession()->getVariable("actshop");
        $aParams['oxarticles__oxshopid'] = $sShopID;

        // variant-handling
        $aParams['oxarticles__oxparentid'] = $sParentId;

        $oArticle = oxNew(Article::class);
        $oArticle->setEnableMultilang(false);
        $oArticle->assign($aParams);
        $oArticle->save();

        return $oArticle->getId();
    }

    /**
     * Inserts article variant name for all languages
     *
     * @param string $sUpdate query for update variant name
     * @param string $sArtId parent article id
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "updateArticleVarName" in next major
     */
    protected function _updateArticleVarName($sUpdate, $sArtId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oDb = DatabaseProvider::getDb();
        $sUpdate = "update oxarticles set " . $sUpdate . " where oxid = :oxid";
        $oDb->execute($sUpdate, [':oxid' => $sArtId]);
    }

    /**
     * Check if variant is multidimensional
     *
     * @param Article $oArticle Article object
     *
     * @return bool
     */
    public function isMdVariant($oArticle)
    {
        if (Registry::getConfig()->getConfigParam('blUseMultidimensionVariants')) {
            if (strpos($oArticle->oxarticles__oxvarselect->value, trim($this->_sMdSeparator)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Creates array/matrix with variant selections
     *
     * @param ArticleList $oVariantList  variant list
     * @param int                                             $iVarSelCnt    possible variant selection count
     * @param array                                           $aFilter       active filter array
     * @param string                                          $sActVariantId active variant id
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "fillVariantSelections" in next major
     */
    protected function _fillVariantSelections($oVariantList, $iVarSelCnt, &$aFilter, $sActVariantId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aSelections = [];

        // filling selections
        foreach ($oVariantList as $oVariant) {
            $aNames = $this->_getSelections($oVariant->oxarticles__oxvarselect->getRawValue());
            $blActive = ($sActVariantId === $oVariant->getId()) ? true : false;
            for ($i = 0; $i < $iVarSelCnt; $i++) {
                $sName = isset($aNames[$i]) ? trim($aNames[$i]) : false;
                if ($sName !== '' && $sName !== false) {
                    $sHash = md5($sName);

                    // filling up filter
                    if ($blActive) {
                        $aFilter[$i] = $sHash;
                    }

                    $aSelections[$oVariant->getId()][$i] = ['name' => $sName, 'disabled' => null, 'active' => false, 'hash' => $sHash];
                }
            }
        }

        return $aSelections;
    }

    /**
     * Cleans up user given filter. If filter was empty - returns false
     *
     * @param array $aFilter user given filter
     *
     * @return array|bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "cleanFilter" in next major
     */
    protected function _cleanFilter($aFilter) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $aCleanFilter = false;
        if (is_array($aFilter) && count($aFilter)) {
            foreach ($aFilter as $iKey => $sFilter) {
                if ($sFilter) {
                    $aCleanFilter[$iKey] = $sFilter;
                }
            }
        }

        return $aCleanFilter;
    }

    /**
     * Applies filter on variant selection array
     *
     * @param array $aSelections selections
     * @param array $aFilter     filter
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "applyVariantSelectionsFilter" in next major
     */
    protected function _applyVariantSelectionsFilter($aSelections, $aFilter) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $iMaxActiveCount = 0;
        $sMostSuitableVariantId = null;
        $blPerfectFit = false;
        // applying filters, disabling/activating items
        if (($aFilter = $this->_cleanFilter($aFilter))) {
            $aFilterKeys = array_keys($aFilter);
            $iFilterKeysCount = count($aFilter);
            foreach ($aSelections as $sVariantId => &$aLineSelections) {
                $iActive = 0;
                foreach ($aFilter as $iKey => $sVal) {
                    if (strcmp($aLineSelections[$iKey]['hash'], $sVal) === 0) {
                        $aLineSelections[$iKey]['active'] = true;
                        $iActive++;
                    } else {
                        foreach ($aLineSelections as $iOtherKey => &$aLineOtherVariant) {
                            if ($iKey != $iOtherKey) {
                                $aLineOtherVariant['disabled'] = true;
                            }
                        }
                    }
                }
                foreach ($aLineSelections as $iOtherKey => &$aLineOtherVariant) {
                    if (!in_array($iOtherKey, $aFilterKeys)) {
                        $aLineOtherVariant['disabled'] = !($iFilterKeysCount == $iActive);
                    }
                }

                $blFitsAll = $iActive && (count($aLineSelections) == $iActive) && ($iFilterKeysCount == $iActive);
                if (($iActive > $iMaxActiveCount) || (!$blPerfectFit && $blFitsAll)) {
                    $blPerfectFit = $blFitsAll;
                    $sMostSuitableVariantId = $sVariantId;
                    $iMaxActiveCount = $iActive;
                }

                unset($aLineSelections);
            }
        }

        return [$aSelections, $sMostSuitableVariantId, $blPerfectFit];
    }

    /**
     * Builds variant selections list - array containing oxVariantSelectList
     *
     * @param array $aVarSelects variant selection titles
     * @param array $aSelections variant selections
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "buildVariantSelectionsList" in next major
     */
    protected function _buildVariantSelectionsList($aVarSelects, $aSelections) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // creating selection lists
        foreach ($aVarSelects as $iKey => $sLabel) {
            $aVariantSelections[$iKey] = oxNew(VariantSelectList::class, $sLabel, $iKey);
        }

        // building variant selections
        foreach ($aSelections as $aLineSelections) {
            foreach ($aLineSelections as $oPos => $aLine) {
                $aVariantSelections[$oPos]->addVariant($aLine['name'], $aLine['hash'], $aLine['disabled'], $aLine['active']);
            }
        }

        return $aVariantSelections;
    }

    /**
     * In case multidimensional variants ON explodes title by _sMdSeparator
     * and returns array, else - returns array containing title
     *
     * @param string $sTitle title to process
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSelections" in next major
     */
    protected function _getSelections($sTitle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (Registry::getConfig()->getConfigParam('blUseMultidimensionVariants')) {
            $aSelections = explode($this->_sMdSeparator, $sTitle);
        } else {
            $aSelections = [$sTitle];
        }

        return $aSelections;
    }

    /**
     * Builds variant selection list
     *
     * @param string                                          $sVarName      product (parent product) oxvarname value
     * @param ArticleList $oVariantList  variant list
     * @param array                                           $aFilter       variant filter
     * @param string                                          $sActVariantId active variant id
     * @param int                                             $iLimit        limit variant lists count (if non-zero, return limited number of multidimensional variant selections)
     *
     * @return false | array
     */
    public function buildVariantSelections($sVarName, $oVariantList, $aFilter, $sActVariantId, $iLimit = 0)
    {
        // assigning variants
        $aVarSelects = $this->_getSelections($sVarName);

        if ($iLimit) {
            $aVarSelects = array_slice($aVarSelects, 0, $iLimit);
        }
        if (($iVarSelCnt = count($aVarSelects))) {
            // filling selections
            $aRawVariantSelections = $this->_fillVariantSelections($oVariantList, $iVarSelCnt, $aFilter, $sActVariantId);

            // applying filters, disabling/activating items
            list($aRawVariantSelections, $sActVariantId, $blPerfectFit) = $this->_applyVariantSelectionsFilter($aRawVariantSelections, $aFilter);
            // creating selection lists
            $aVariantSelections = $this->_buildVariantSelectionsList($aVarSelects, $aRawVariantSelections);

            $oCurrentVariant = null;
            if ($sActVariantId) {
                $oCurrentVariant = $oVariantList[$sActVariantId];
            }

            return [
                'selections'     => $aVariantSelections,
                'rawselections'  => $aRawVariantSelections,
                'oActiveVariant' => $oCurrentVariant,
                'blPerfectFit'   => $blPerfectFit
            ];
        }

        return false;
    }
}
