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

use Exception;
use OxidEsales\Eshop\Application\Controller\Admin\NavigationTree;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\NamespaceInformationProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopVersion;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use OxidEsales\Facts\Facts;

/**
 * Main Controller class for admin area.
 */
class AdminController extends BaseController
{
    /**
     * Fixed types - enums in database.
     *
     * @var array
     */
    protected $_aSumType = [
        0 => 'abs',
        1 => '%',
        2 => 'itm',
    ];

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = null;

    /**
     * Override this in list class to show other tab from beginning
     * (default 0 - the first tab).
     *
     * @var int
     */
    protected $_iDefEdit = 0;

    /**
     * Navigation tree object
     *
     * @var NavigationTree
     */
    protected static $_oNaviTree = null;

    /**
     * Objects editing language (default 0).
     *
     * @var integer
     */
    protected $_iEditLang = 0;

    /**
     * Active shop title
     *
     * @var string
     */
    protected $_sShopTitle = ' - ';

    /**
     * Shop Version
     *
     * @deprecated since v6.0.0-rc.2 (2017-08-23); Use  OxidEsales\Eshop\Core\ShopVersion::getVersion() instead.
     *
     * @var string
     */
    protected $_sShopVersion = null;

    /**
     * Shop dynamic pages url
     *
     * @deprecated since v5.3 (2016-05-20); Dynpages will be removed.
     *
     * @var string
     */
    protected $_sServiceUrl = null;

    /**
     * Session user rights
     *
     * @var string
     */
    protected static $_sAuthUserRights = null;

    /**
     * Active shop object
     *
     * @return
     */
    protected $_oEditShop = null;

    /**
     * Editable object id
     *
     * @var string
     */
    protected $_sEditObjectId = null;

    /**
     * Optional view id.
     *
     * @var string
     */
    protected $viewId = null;

    /**
     * Creates oxshop object and loads shop data, sets title of shop
     */
    public function __construct()
    {
        $myConfig = Registry::getConfig();
        $myConfig->setConfigParam('blAdmin', true);
        $this->setAdminMode(true);

        if ($oShop = $this->getEditShop($myConfig->getShopId())) {
            // passing shop info
            $this->_sShopTitle = $oShop->oxshops__oxname->getRawValue();
            $this->_sShopVersion = oxNew(ShopVersion::class)->getVersion();
        }
    }

    /**
     * Returns (cached) shop object
     *
     * @param string $sShopId shop id
     *
     * @return Shop
     * @deprecated underscore prefix violates PSR12, will be renamed to "getEditShop" in next major
     */
    protected function _getEditShop($sShopId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getEditShop($sShopId);
    }

    /**
     * Returns (cached) shop object
     *
     * @param string $sShopId shop id
     *
     * @return Shop
     */
    protected function getEditShop($sShopId)
    {
        if (!$this->_oEditShop) {
            $this->_oEditShop = Registry::getConfig()->getActiveShop();
            if ($this->_oEditShop->getId() != $sShopId) {
                $oEditShop = oxNew(Shop::class);
                if ($oEditShop->load($sShopId)) {
                    $this->_oEditShop = $oEditShop;
                }
            }
        }

        return $this->_oEditShop;
    }

    /**
     * Sets some shop configuration parameters (such as language),
     * creates some list object (depends on subclass) and executes
     * parent method parent::Init().
     */
    public function init()
    {
        // authorization check
        if (!$this->authorize()) {
            Registry::getUtils()->redirect('index.php?cl=login', true, 302);
            exit('Authorization error occurred!');
        }

        $oLang = Registry::getLang();

        // language handling
        $this->_iEditLang = $oLang->getEditLanguage();
        $oLang->setBaseLanguage();

        parent::init();

        $this->_aViewData['malladmin'] = Registry::getSession()->getVariable('malladmin');
    }

    /**
     * Sets some global parameters to Smarty engine (such as self link, etc.), returns
     * modified shop object.
     *
     * @param object $oShop Object to modify some parameters
     *
     * @return object
     * @throws Exception
     */
    public function addGlobalParams($oShop = null)
    {
        $myConfig = Registry::getConfig();
        $oLang = Registry::getLang();

        $oShop = parent::addGlobalParams($oShop);

        // override cause of admin dir
        $sURL = $myConfig->getConfigParam('sShopURL') . $myConfig->getConfigParam('sAdminDir') . '/';

        if ($myConfig->getConfigParam('sAdminSSLURL')) {
            $sURL = $myConfig->getConfigParam('sAdminSSLURL');
        }

        $oViewConf = $this->getViewConfig();
        $oViewConf->setViewConfigParam('selflink', Registry::getUtilsUrl()->processUrl($sURL . 'index.php?editlanguage=' . $this->_iEditLang, false));
        $oViewConf->setViewConfigParam('ajaxlink', str_replace('&amp;', '&', Registry::getUtilsUrl()->processUrl($sURL . 'oxajax.php?editlanguage=' . $this->_iEditLang, false)));
        $oViewConf->setViewConfigParam('sServiceUrl', $this->getServiceUrl());

        // set language in admin
        $iDynInterfaceLanguage = $myConfig->getConfigParam('iDynInterfaceLanguage');
        //$this->_aViewData['adminlang'] = isset( $iDynInterfaceLanguage )?$iDynInterfaceLanguage:$myConfig->getConfigParam( 'iAdminLanguage' );
        $this->_aViewData['adminlang'] = isset($iDynInterfaceLanguage) ? $iDynInterfaceLanguage : $oLang->getTplLanguage();
        $this->_aViewData['charset'] = $this->getCharSet();

        //setting active currency object
        $this->_aViewData['oActCur'] = $myConfig->getActShopCurrencyObject();

        return $oShop;
    }

    /**
     * Returns service url protocol: "https" is admin works in ssl mode, "http" if no ssl
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getServiceProtocol" in next major
     */
    protected function _getServiceProtocol() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getServiceProtocol();
    }

    /**
     * Returns service url protocol: "https" is admin works in ssl mode, "http" if no ssl
     *
     * @return string
     */
    protected function getServiceProtocol()
    {
        return Registry::getConfig()->isSsl() ? 'https' : 'http';
    }

    /**
     * Returns service URL
     *
     * @param string $sLangAbbr language abbr.
     *
     * @return string
     * @throws Exception
     * @deprecated since v5.3 (2016-05-20); Dynpages will be removed.
     *
     */
    public function getServiceUrl($sLangAbbr = null)
    {
        if ($this->_sServiceUrl === null) {
            $sProtocol = $this->getServiceProtocol();

            $oFacts = new Facts();
            $sUrl = $sProtocol . '://admin.oxid-esales.com/' . $oFacts->getEdition() . '/';

            $sCountry = 'international';

            if (!$sLangAbbr) {
                $oLang = Registry::getLang();
                $sLangAbbr = $oLang->getLanguageAbbr($oLang->getTplLanguage());
            }

            if ($sLangAbbr != 'de') {
                $sLangAbbr = 'en';
            }

            $this->_sServiceUrl = $sUrl . ShopVersion::getVersion() . "/{$sCountry}/{$sLangAbbr}/";
        }

        return $this->_sServiceUrl;
    }

    /**
     * Returns shop version
     *
     * @deprecated since v6.0.0-rc.2 (2017-08-23); Use  OxidEsales\Eshop\Core\ShopVersion::getVersion() instead.
     *
     * @return string
     */
    protected function _getShopVersionNr() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return oxNew(ShopVersion::class)->getVersion();
    }

    /**
     * Sets-up navigation parameters
     *
     * @param string $sNode active view id
     * @deprecated underscore prefix violates PSR12, will be renamed to "setupNavigation" in next major
     */
    protected function _setupNavigation($sNode) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->setupNavigation($sNode);
    }

    /**
     * Sets-up navigation parameters
     *
     * @param string $sNode active view id
     */
    protected function setupNavigation($sNode)
    {
        // navigation according to class
        if ($sNode) {
            $myAdminNavig = $this->getNavigation();

            // active tab
            $iActTab = Registry::getRequest()->getRequestEscapedParameter('actedit');
            $iActTab = $iActTab ? $iActTab : $this->_iDefEdit;

            $sActTab = $iActTab ? "&actedit=$iActTab" : '';

            // list url
            $this->_aViewData['listurl'] = $myAdminNavig->getListUrl($sNode) . $sActTab;

            // edit url
            $this->_aViewData['editurl'] = $myAdminNavig->getEditUrl($sNode, $iActTab) . $sActTab;
        }
    }

    /**
     * Executes parent method parent::render(), passes configuration data to
     * Smarty engine.
     *
     * @return string
     */
    public function render()
    {
        $sReturn = parent::render();

        $myConfig = Registry::getConfig();
        $oLang = Registry::getLang();

        // sets up navigation data
        $this->setupNavigation(Registry::getConfig()->getRequestControllerId());

        // active object id
        $sOxId = $this->getEditObjectId();
        $this->_aViewData['oxid'] = (!$sOxId) ? -1 : $sOxId;
        // add Sumtype to all templates
        $this->_aViewData['sumtype'] = $this->_aSumType;

        // active shop title
        $this->_aViewData['actshop'] = $this->_sShopTitle;
        $this->_aViewData['shopid'] = $myConfig->getShopId();

        // loading active shop
        if ($sActShopId = Registry::getSession()->getVariable('actshop')) {
            // load object
            $this->_aViewData['actshopobj'] = $this->getEditShop($sActShopId);
        }

        // add language data to all templates
        $this->_aViewData['actlang'] = $iLanguage = $oLang->getBaseLanguage();
        $this->_aViewData['editlanguage'] = $this->_iEditLang;
        $this->_aViewData['languages'] = $oLang->getLanguageArray($iLanguage);

        // setting maximum upload size
        list($this->_aViewData['iMaxUploadFileSize'], $this->_aViewData['sMaxFormattedFileSize']) = $this->getMaxUploadFileInfo(@ini_get('upload_max_filesize'));

        // "save-on-tab"
        if (!isset($this->_aViewData['updatelist'])) {
            $this->_aViewData['updatelist'] = Registry::getRequest()->getRequestEscapedParameter('updatelist');
        }

        return $sReturn;
    }

    /**
     * Returns maximum allowed size of upload file and formatted size equivalent
     *
     * @param int  $maxFileSize recommended maximum size of file (normal value is taken from php ini, otherwise sets 2MB)
     * @param bool $isFormatted  Return formatted
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getMaxUploadFileInfo" in next major
     */
    protected function _getMaxUploadFileInfo($maxFileSize, $isFormatted = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getMaxUploadFileInfo($maxFileSize, $isFormatted);
    }

    /**
     * Returns maximum allowed size of upload file and formatted size equivalent
     *
     * @param int  $maxFileSize recommended maximum size of file (normal value is taken from php ini, otherwise sets 2MB)
     * @param bool $isFormatted  Return formatted
     *
     * @return array
     */
    protected function getMaxUploadFileInfo($maxFileSize, $isFormatted = false)
    {
        $maxFileSize = $maxFileSize ? trim($maxFileSize) : '2M';

        // processing config
        $intMaxFileSize = (int)$maxFileSize;
        $sParam = strtolower($maxFileSize[strlen($maxFileSize) - 1]);
        switch ($sParam) {
            case 'g':
                $intMaxFileSize *= 1024;
                // no break
            case 'm':
                $intMaxFileSize *= 1024;
                // no break
            case 'k':
                $intMaxFileSize *= 1024;
        }

        // formatting
        $markers = ['KB', 'MB', 'GB'];
        $sFormattedMaxSize = '';

        $size = floor($intMaxFileSize / 1024);
        while ($size && current($markers)) {
            $sFormattedMaxSize = $size . ' ' . current($markers);
            $size = floor($size / 1024);
            next($markers);
        }

        return [$intMaxFileSize, $sFormattedMaxSize];
    }

    /**
     * Clears cache
     */
    public function save()
    {
        $this->resetContentCache();
    }

    /**
     * Reset output cache
     *
     * @param bool $blForceReset if true, forces reset
     */
    public function resetContentCache($blForceReset = null)
    {
        $blDeleteCacheOnLogout = Registry::getConfig()->getConfigParam('blClearCacheOnLogout');
        if (!$blDeleteCacheOnLogout || $blForceReset) {
            Registry::getUtils()->oxResetFileCache();
        }
    }

    /**
     * Resets counters values from cache. Resets price category articles, category articles,
     * vendor articles, manufacturer articles count.
     *
     * @param string $sCounterType counter type
     * @param string $sValue       reset value
     */
    public function resetCounter($sCounterType, $sValue = null)
    {
        $blDeleteCacheOnLogout = Registry::getConfig()->getConfigParam('blClearCacheOnLogout');
        $myUtilsCount = Registry::getUtilsCount();

        if (!$blDeleteCacheOnLogout) {
            switch ($sCounterType) {
                case 'priceCatArticle':
                    $myUtilsCount->resetPriceCatArticleCount($sValue);
                    break;
                case 'catArticle':
                    $myUtilsCount->resetCatArticleCount($sValue);
                    break;
                case 'vendorArticle':
                    $myUtilsCount->resetVendorArticleCount($sValue);
                    break;
                case 'manufacturerArticle':
                    $myUtilsCount->resetManufacturerArticleCount($sValue);
                    break;
            }
            $this->resetContentCache();
        }
    }

    /**
     * Resets cache.
     * @deprecated underscore prefix violates PSR12, will be renamed to "resetContentCacheAfterResetCounter" in next major
     */
    protected function _resetContentCache() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /**
     * Checks if current $sUserId user is not an admin and checks if user can be edited by logged-in user.
     * This method does not perform full rights check.
     *
     * @param string $sUserId user id
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "allowAdminEdit" in next major
     */
    protected function _allowAdminEdit($sUserId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->allowAdminEdit($sUserId);
    }

    /**
     * Checks if current $sUserId user is not an admin and checks if user can be edited by logged-in user.
     * This method does not perform full rights check.
     *
     * @param string $sUserId user id
     *
     * @return bool
     */
    protected function allowAdminEdit($sUserId)
    {
        return true;
    }

    /**
     * Get english country name by country iso alpha 2 code
     *
     * @param string $sCountryCode Country code
     *
     * @return string
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getCountryByCode" in next major
     */
    protected function _getCountryByCode($sCountryCode) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getCountryByCode($sCountryCode);
    }

    /**
     * Get english country name by country iso alpha 2 code
     *
     * @param string $sCountryCode Country code
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function getCountryByCode($sCountryCode)
    {
        //default country
        $sCountry = 'international';

        if (!empty($sCountryCode)) {
            $aLangIds = Registry::getLang()->getLanguageIds();
            $iEnglishId = array_search('en', $aLangIds);
            if (false !== $iEnglishId) {
                $sViewName = Registry::get(TableViewNameGenerator::class)->getViewName('oxcountry', $iEnglishId);
                $sQ = "select oxtitle from {$sViewName} where oxisoalpha2 = :oxisoalpha2";
                // Value does not change that often, reading from slave is ok here (see ESDEV-3804 and ESDEV-3822).
                $sCountryName = DatabaseProvider::getDb()->getOne($sQ, [
                    ':oxisoalpha2' => $sCountryCode,
                ]);
                if ($sCountryName) {
                    $sCountry = $sCountryName;
                }
            } else {
                // handling when english language is deleted
                switch ($sCountryCode) {
                    case 'de':
                        return 'germany';
                    default:
                        return 'international';
                }
            }
        }

        return strtolower($sCountry);
    }

    /**
     * performs authorization of admin user
     *
     * @return boolean
     * @deprecated underscore prefix violates PSR12, will be renamed to "authorize" in next major
     */
    protected function _authorize() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->authorize();
    }

    /**
     * performs authorization of admin user
     *
     * @return boolean
     */
    protected function authorize()
    {
        return (
            Registry::getSession()->checkSessionChallenge()
            && count(Registry::getUtilsServer()->getOxCookie())
            && Registry::getUtils()->checkAccessRights()
        );
    }

    /**
     * Returns navigation object
     *
     * @return NavigationTree
     */
    public function getNavigation()
    {
        if (self::$_oNaviTree == null) {
            self::$_oNaviTree = oxNew(NavigationTree::class);
        }

        return self::$_oNaviTree;
    }

    /**
     * Current view ID getter helps to identify navigation position
     *
     * @return string
     */
    public function getViewId()
    {
        $viewId = is_null($this->viewId) ? strtolower($this->getControllerKey()) : $this->viewId;
        return $this->getNavigation()->getClassId($viewId);
    }

    /**
     * Changing active shop
     */
    public function chshp()
    {
        $sActShop = Registry::getRequest()->getRequestEscapedParameter('shp');
        Registry::getSession()->setVariable('shp', $sActShop);
        Registry::getSession()->setVariable('currentadminshop', $sActShop);
    }

    /**
     * Marks seo entries as expired.
     *
     * @param string $sShopId Shop id
     */
    public function resetSeoData($sShopId)
    {
        $aTypes = ['oxarticle', 'oxcategory', 'oxvendor', 'oxcontent', 'dynamic', 'oxmanufacturer'];
        $oEncoder = Registry::getSeoEncoder();
        foreach ($aTypes as $sType) {
            $oEncoder->markAsExpired(null, $sShopId, 1, null, "oxtype = '{$sType}'");
        }
    }

    /**
     * Returns id which is used for product preview in shop during administration
     *
     * @return string
     */
    public function getPreviewId()
    {
        return Registry::getUtils()->getPreviewId();
    }

    /**
     * Returns active/editable object id
     *
     * @return string
     */
    public function getEditObjectId()
    {
        if (null === ($sId = $this->_sEditObjectId)) {
            if (null === ($sId = Registry::getRequest()->getRequestEscapedParameter('oxid'))) {
                $sId = Registry::getSession()->getVariable('saved_oxid');
            }
        }

        return $sId;
    }

    /**
     * Sets editable object id
     *
     * @param string $sId object id
     */
    public function setEditObjectId($sId)
    {
        $this->_sEditObjectId = $sId;
        $this->_aViewData['updatelist'] = 1;
    }

    /**
     * Returns true if editable object is new.
     *
     * @return bool
     */
    protected function isNewEditObject()
    {
        return '-1' === (string) $this->getEditObjectId();
    }

    /**
     * Get controller key also for chain extended class.
     *
     * @return null|string
     */
    protected function getControllerKey()
    {
        $actualClass = get_class($this);
        $controllerKey = Registry::getControllerClassNameResolver()->getIdByClassName($actualClass);
        if (is_null($controllerKey)) {
            //we might not have found a class key because class is a module chain extended class
            $controllerKey = Registry::getControllerClassNameResolver()->getIdByClassName($this->getShopParentClass());
        }
        return $controllerKey;
    }

    /**
     * Method to figure out \OxidEsales\Eshop class.
     *
     * @return string
     */
    protected function getShopParentClass()
    {
        $className = get_class($this); //actual class, might be shop class chain extended by module
        while ($className && !NamespaceInformationProvider::classBelongsToShopUnifiedNamespace($className)) {
            $className = get_parent_class($className);
        }
        return $className;
    }
}
