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

use oxRegistry;
use oxDb;

/**
 * Admin shop system setting manager.
 * Collects shop system settings, updates it on user submit, etc.
 * Admin Menu: Main Menu -> Core Settings -> System.
 */
class ShopSeo extends \OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration
{
    /**
     * Active seo url id
     */
    protected $_sActSeoObject = null;

    /**
     * Executes parent method parent::render() and returns name of template
     * file "shop_system.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $this->_aViewData['subjlang'] = $this->_iEditLang;

        // loading shop
        $oShop = oxNew(\OxidEsales\Eshop\Application\Model\Shop::class);
        $oShop->loadInLang($this->_iEditLang, $this->_aViewData['edit']->getId());
        $this->_aViewData['edit'] = $oShop;

        // loading static seo urls
        $sQ = "select oxstdurl, oxobjectid from oxseo where oxtype='static' and oxshopid = :oxshopid group by oxobjectid order by oxstdurl";

        $oList = oxNew(\OxidEsales\Eshop\Core\Model\ListModel::class);
        $oList->init('oxbase', 'oxseo');
        $oList->selectString($sQ, [
            ':oxshopid' => $oShop->getId()
        ]);

        $this->_aViewData['aStaticUrls'] = $oList;

        // loading active url info
        $this->_loadActiveUrl($oShop->getId());

        return "shop_seo.tpl";
    }

    /**
     * Loads and sets active url info to view
     *
     * @param int $iShopId active shop id
     * @deprecated underscore prefix violates PSR12, will be renamed to "loadActiveUrl" in next major
     */
    protected function _loadActiveUrl($iShopId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sActObject = null;
        if ($this->_sActSeoObject) {
            $sActObject = $this->_sActSeoObject;
        } elseif (is_array($aStatUrl = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('aStaticUrl'))) {
            $sActObject = $aStatUrl['oxseo__oxobjectid'];
        }

        if ($sActObject && $sActObject != '-1') {
            $this->_aViewData['sActSeoObject'] = $sActObject;

            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
            $sQ = "select oxseourl, oxlang from oxseo where oxobjectid = :oxobjectid and oxshopid = :oxshopid";
            $oRs = $oDb->select($sQ, [
                ':oxobjectid' => $sActObject,
                ':oxshopid' => $iShopId
            ]);
            if ($oRs != false && $oRs->count() > 0) {
                while (!$oRs->EOF) {
                    $aSeoUrls[$oRs->fields['oxlang']] = [$sActObject, $oRs->fields['oxseourl']];
                    $oRs->fetchRow();
                }
                $this->_aViewData['aSeoUrls'] = $aSeoUrls;
            }
        }
    }

    /**
     * Saves changed shop configuration parameters.
     */
    public function save()
    {
        // saving config params
        $this->saveConfVars();

        $oShop = oxNew(\OxidEsales\Eshop\Application\Model\Shop::class);
        if ($oShop->loadInLang($this->_iEditLang, $this->getEditObjectId())) {
            //assigning values
            $oShop->setLanguage(0);
            $oShop->assign(\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('editval'));
            $oShop->setLanguage($this->_iEditLang);
            $oShop->save();

            // saving static url changes
            if (is_array($aStaticUrl = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('aStaticUrl'))) {
                $this->_sActSeoObject = \OxidEsales\Eshop\Core\Registry::getSeoEncoder()->encodeStaticUrls($this->_processUrls($aStaticUrl), $oShop->getId(), $this->_iEditLang);
            }
        }
    }

    /**
     * Goes through urls array and prepares them for saving to db
     *
     * @param array $aUrls urls to process
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "processUrls" in next major
     */
    protected function _processUrls($aUrls) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (isset($aUrls['oxseo__oxstdurl']) && $aUrls['oxseo__oxstdurl']) {
            $aUrls['oxseo__oxstdurl'] = $this->_cleanupUrl($aUrls['oxseo__oxstdurl']);
        }

        if (isset($aUrls['oxseo__oxseourl']) && is_array($aUrls['oxseo__oxseourl'])) {
            foreach ($aUrls['oxseo__oxseourl'] as $iPos => $sUrl) {
                $aUrls['oxseo__oxseourl'][$iPos] = $this->_cleanupUrl($sUrl);
            }
        }

        return $aUrls;
    }

    /**
     * processes urls by fixing "&amp;", "&"
     *
     * @param string $sUrl processable url
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "cleanupUrl" in next major
     */
    protected function _cleanupUrl($sUrl) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // replacing &amp; to & or removing double &&
        while ((stripos($sUrl, '&amp;') !== false) || (stripos($sUrl, '&&') !== false)) {
            $sUrl = str_replace('&amp;', '&', $sUrl);
            $sUrl = str_replace('&&', '&', $sUrl);
        }

        // converting & to &amp;
        return str_replace('&', '&amp;', $sUrl);
    }

    /**
     * Resetting SEO ids
     */
    public function dropSeoIds()
    {
        $this->resetSeoData($this->getConfig()->getShopId());
    }

    /**
     * Deletes static url.
     */
    public function deleteStaticUrl()
    {
        $aStaticUrl = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('aStaticUrl');
        if (is_array($aStaticUrl)) {
            $sObjectid = $aStaticUrl['oxseo__oxobjectid'];
            if ($sObjectid && $sObjectid != '-1') {
                $this->deleteStaticUrlFromDb($sObjectid);
            }
        }
    }

    /**
     * Deletes static url from DB.
     *
     * @param string $staticUrlId
     */
    protected function deleteStaticUrlFromDb($staticUrlId)
    {
        // active shop id
        $shopId = $this->getEditObjectId();
        $db = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $db->execute("delete from oxseo where oxtype='static' and oxobjectid = :oxobjectid and oxshopid = :oxshopid", [
            ':oxobjectid' => $staticUrlId,
            ':oxshopid' => $shopId
        ]);
    }
}
