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
use OxidEsales\Eshop\Application\Model\CategoryList;
use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use stdClass;

/**
 * Admin content manager.
 * There is possibility to change content description, enter page text etc.
 * Admin Menu: Customer-Information -> Content.
 */
class ContentMain extends AdminDetailsController
{
    /**
     * Loads contents info, passes it to Smarty engine and
     * returns name of template file "content_main.tpl".
     *
     * @return string
     */
    public function render()
    {
        $myConfig = Registry::getConfig();

        parent::render();

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();

        // category-tree
        $oCatTree = oxNew(CategoryList::class);
        $oCatTree->loadList();

        $oContent = oxNew(Content::class);
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oContent->loadInLang($this->_iEditLang, $soxId);

            $oOtherLang = $oContent->getAvailableInLangs();
            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $oContent->loadInLang(key($oOtherLang), $soxId);
            }

            // remove already created languages
            $aLang = array_diff(Registry::getLang()->getLanguageNames(), $oOtherLang);
            if (count($aLang)) {
                $this->_aViewData["posslang"] = $aLang;
            }
            foreach ($oOtherLang as $id => $language) {
                $oLang = new stdClass();
                $oLang->sLangDesc = $language;
                $oLang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = clone $oLang;
            }
            // mark selected
            if ($oContent->oxcontents__oxcatid->value && isset($oCatTree[$oContent->oxcontents__oxcatid->value])) {
                $oCatTree[$oContent->oxcontents__oxcatid->value]->selected = 1;
            }
        } else {
            // create ident to make life easier
            $sUId = Registry::getUtilsObject()->generateUId();
            $oContent->oxcontents__oxloadid = new Field($sUId);
        }

        $this->_aViewData["edit"] = $oContent;
        $this->_aViewData["link"] = "[{ oxgetseourl ident=&quot;" . $oContent->oxcontents__oxloadid->value . "&quot; type=&quot;oxcontent&quot; }]";
        $this->_aViewData["cattree"] = $oCatTree;

        // generate editor
        $sCSS = "content.tpl.css";
        if ($oContent->oxcontents__oxsnippet->value == '1') {
            $sCSS = null;
        }

        $this->_aViewData["editor"] = $this->generateTextEditor("100%", 300, $oContent, "oxcontents__oxcontent", $sCSS);
        $this->_aViewData["afolder"] = $myConfig->getConfigParam('aCMSfolder');

        return "content_main.tpl";
    }

    /**
     * Saves content contents.
     *
     * @return void
     * @throws DatabaseConnectionException|DatabaseErrorException
     */
    public function save()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        if (isset($aParams['oxcontents__oxloadid'])) {
            $aParams['oxcontents__oxloadid'] = $this->prepareIdent($aParams['oxcontents__oxloadid']);
        }

        // check if loadid is unique
        if ($this->checkIdent($aParams['oxcontents__oxloadid'], $soxId)) {
            // loadid already used, display error message
            $this->_aViewData["blLoadError"] = true;

            $oContent = oxNew(Content::class);
            if ($soxId != '-1') {
                $oContent->load($soxId);
            }
            $oContent->assign($aParams);
            $this->_aViewData["edit"] = $oContent;

            return;
        }

        // checkbox handling
        if (!isset($aParams['oxcontents__oxactive'])) {
            $aParams['oxcontents__oxactive'] = 0;
        }

        // special treatment
        if ($aParams['oxcontents__oxtype'] == 0) {
            $aParams['oxcontents__oxsnippet'] = 1;
        } else {
            $aParams['oxcontents__oxsnippet'] = 0;
        }

        //Updates object folder parameters
        if ($aParams['oxcontents__oxfolder'] == 'CMSFOLDER_NONE') {
            $aParams['oxcontents__oxfolder'] = '';
        }

        $oContent = oxNew(Content::class);

        if ($soxId != "-1") {
            $oContent->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxcontents__oxid'] = null;
        }

        //$aParams = $oContent->ConvertNameArray2Idx( $aParams);

        $oContent->setLanguage(0);
        $oContent->assign($aParams);
        $oContent->setLanguage($this->_iEditLang);
        $oContent->save();

        // set oxid if inserted
        $this->setEditObjectId($oContent->getId());
    }

    /**
     * Saves content data to different language (eg. english).
     */
    public function saveinnlang()
    {
        parent::save();

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

        if (isset($aParams['oxcontents__oxloadid'])) {
            $aParams['oxcontents__oxloadid'] = $this->prepareIdent($aParams['oxcontents__oxloadid']);
        }

        // checkbox handling
        if (!isset($aParams['oxcontents__oxactive'])) {
            $aParams['oxcontents__oxactive'] = 0;
        }

        $oContent = oxNew(Content::class);

        if ($soxId != "-1") {
            $oContent->loadInLang($this->_iEditLang, $soxId);
        } else {
            $aParams['oxcontents__oxid'] = null;
        }

        $oContent->setLanguage(0);
        $oContent->assign($aParams);

        // apply new language
        $oContent->setLanguage(Registry::getRequest()->getRequestEscapedParameter('new_lang'));
        $oContent->save();

        // set oxid if inserted
        $this->setEditObjectId($oContent->getId());
    }

    /**
     * Prepares ident (removes bad chars, leaves only those that fits in a-zA-Z0-9_ range)
     *
     * @param string $sIdent ident to filter
     *
     * @return string|null
     * @deprecated underscore prefix violates PSR12, will be renamed to "prepareIdent" in next major
     */
    protected function _prepareIdent($sIdent) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->prepareIdent($sIdent);
    }

    /**
     * Prepares ident (removes bad chars, leaves only those that fits in a-zA-Z0-9_ range)
     *
     * @param string $sIdent ident to filter
     *
     * @return string|void
     */
    protected function prepareIdent($sIdent)
    {
        if ($sIdent) {
            return Str::getStr()->preg_replace("/[^a-zA-Z0-9_]*/", "", $sIdent);
        }
    }

    /**
     * Check if ident is unique
     *
     * @param string $sIdent ident
     * @param string $sOxId Object id
     *
     * @return null
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "checkIdent" in next major
     */
    protected function _checkIdent($sIdent, $sOxId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->checkIdent($sIdent, $sOxId);
    }

    /**
     * Check if ident is unique
     *
     * @param string $sIdent ident
     * @param string $sOxId Object id
     *
     * @return null
     * @throws DatabaseConnectionException
     */
    protected function checkIdent($sIdent, $sOxId)
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $masterDb = DatabaseProvider::getMaster();

        $blAllow = false;

        // null not allowed
        if (!strlen($sIdent)) {
            $blAllow = true;
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        } elseif (
            $masterDb->getOne("select oxid from oxcontents where oxloadid = :oxloadid and oxid != :oxid and oxshopid = :oxshopid", [
            ':oxloadid' => $sIdent,
            ':oxid' => $sOxId,
            ':oxshopid' => Registry::getConfig()->getShopId()
            ])
        ) {
            $blAllow = true;
        }

        return $blAllow;
    }
}
