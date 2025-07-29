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
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin article main shop manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Main Menu -> Core Settings -> Main.
 */
class ShopMain extends AdminDetailsController
{
    /** Identifies new shop. */
    public const NEW_SHOP_ID = '-1';

    /**
     * Shop field set size, limited to 64bit by MySQL
     *
     * @var int
     */
    public const SHOP_FIELD_SET_SIZE = 64;

    /**
     * Controller render method, which returns the name of the template file.
     *
     * @return string
     */
    public function render()
    {
        $config = Registry::getConfig();
        parent::render();

        $shopId = $this->_aViewData['oxid'] = $this->getEditObjectId();

        $templateName = $this->renderNewShop();

        if ($templateName) {
            return $templateName;
        }

        $user = $this->getUser();
        $shop = null;
        $shopId = $this->updateShopIdByUser($user, $shopId, true);

        if (isset($shopId) && $shopId != self::NEW_SHOP_ID) {
            // load object
            $shop = oxNew(Shop::class);
            $subjLang = Registry::getRequest()->getRequestEscapedParameter('subjlang');
            if (!isset($subjLang)) {
                $subjLang = $this->_iEditLang;
            }

            if ($subjLang && $subjLang > 0) {
                $this->_aViewData['subjlang'] = $subjLang;
            }

            $shop->loadInLang($subjLang, $shopId);

            $this->_aViewData['edit'] = $shop;
            //\OxidEsales\Eshop\Core\Session::setVar( "actshop", $soxId);//echo "<h2>$soxId</h2>";
            Registry::getSession()->setVariable('shp', $shopId);
        }

        $this->checkParent($shop);

        $this->_aViewData['IsOXDemoShop'] = $config->isDemoShop();
        if (!isset($this->_aViewData['updatenav'])) {
            $this->_aViewData['updatenav'] = Registry::getRequest()->getRequestEscapedParameter('updatenav');
        }

        return 'shop_main.tpl';
    }

    /**
     * Saves changed main shop configuration parameters.
     *
     * @return void
     * @throws Exception
     */
    public function save()
    {
        parent::save();

        $config = Registry::getConfig();
        $shopId = $this->getEditObjectId();

        $parameters = Registry::getRequest()->getRequestEscapedParameter('editval');

        $user = $this->getUser();
        $shopId = $this->updateShopIdByUser($user, $shopId, false);

        //  #918 S
        // checkbox handling
        $parameters['oxshops__oxactive'] = (isset($parameters['oxshops__oxactive']) && $parameters['oxshops__oxactive']) ? 1 : 0;
        $parameters['oxshops__oxproductive'] = (isset($parameters['oxshops__oxproductive']) && $parameters['oxshops__oxproductive']) ? 1 : 0;

        $subjLang = Registry::getRequest()->getRequestEscapedParameter('subjlang');
        $shopLanguageId = ($subjLang && $subjLang > 0) ? $subjLang : 0;

        $shop = oxNew(Shop::class);
        if ($shopId != self::NEW_SHOP_ID) {
            $shop->loadInLang($shopLanguageId, $shopId);
        } else {
            $parameters = $this->updateParameters($parameters);
        }

        if ($parameters['oxshops__oxsmtp']) {
            $parameters['oxshops__oxsmtp'] = trim($parameters['oxshops__oxsmtp']);
        }

        $shop->setLanguage(0);
        $shop->assign($parameters);
        $shop->setLanguage($shopLanguageId);

        if (($newSMPTPass = Registry::getRequest()->getRequestEscapedParameter('oxsmtppwd'))) {
            $shop->oxshops__oxsmtppwd->setValue($newSMPTPass == '-' ? '' : $newSMPTPass);
        }

        $canCreateShop = $this->canCreateShop($shopId, $shop);
        if (!$canCreateShop) {
            return;
        }

        try {
            $shop->save();
        } catch (StandardException $e) {
            $this->checkExceptionType($e);
            return;
        }

        $this->_aViewData['updatelist'] = '1';

        $this->updateShopInformation($config, $shop, $shopId);

        Registry::getSession()->setVariable('actshop', $shopId);
    }

    /**
     * Returns array of config variables which cannot be copied
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "getNonCopyConfigVars" in next major
     */
    protected function _getNonCopyConfigVars() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $nonCopyVars = [
            'aSerials',
            'IMS',
            'IMD',
            'IMA',
            'sBackTag',
            'sUtilModule',
            'aModulePaths',
            'aModuleFiles',
            'aModuleEvents',
            'aModuleVersions',
            'aModuleTemplates',
            'aModules',
            'aDisabledModules',
            'aModuleExtensions',
            'aModuleControllers',
            'moduleSmartyPluginDirectories',
            'activeModules',
        ];
        //adding non-copyable multishop field options
        $multiShopTables = Registry::getConfig()->getConfigParam('aMultiShopTables');
        foreach ($multiShopTables as $multishopTable) {
            $nonCopyVars[] = 'blMallInherit_' . strtolower($multishopTable);
        }

        return $nonCopyVars;
    }

    /**
     * Copies base shop config variables to current
     *
     * @param Shop $shop new shop object
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @deprecated underscore prefix violates PSR12, will be renamed to "copyConfigVars" in next major
     */
    protected function _copyConfigVars($shop) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $config = Registry::getConfig();
        $utilsObject = Registry::getUtilsObject();
        $db = DatabaseProvider::getDb();

        $nonCopyVars = $this->_getNonCopyConfigVars();

        $selectShopConfigurationQuery =
            "select oxvarname, oxvartype,
            oxvarvalue as oxvarvalue, oxmodule
            from oxconfig where oxshopid = '1'";
        $shopConfiguration = $db->select($selectShopConfigurationQuery);
        if ($shopConfiguration && $shopConfiguration->count() > 0) {
            while (!$shopConfiguration->EOF) {
                $configName = $shopConfiguration->fields[0];
                if (!in_array($configName, $nonCopyVars)) {
                    $newId = $utilsObject->generateUID();
                    $insertNewConfigQuery =
                        'insert into oxconfig (oxid, oxshopid, oxvarname, oxvartype, oxvarvalue, oxmodule)
                         values (:oxid, :oxshopid, :oxvarname, :oxvartype, :value, :oxmodule)';
                    $db->execute($insertNewConfigQuery, [
                        ':oxid' => $newId,
                        ':oxshopid' => $shop->getId(),
                        ':oxvarname' => $shopConfiguration->fields[0],
                        ':oxvartype' => $shopConfiguration->fields[1],
                        ':value' => $shopConfiguration->fields[2],
                        ':oxmodule' => $shopConfiguration->fields[3],
                    ]);
                }
                $shopConfiguration->fetchRow();
            }
        }

        $inheritAll = $shop->oxshops__oxisinherited->value ? 'true' : 'false';
        $multiShopTables = $config->getConfigParam('aMultiShopTables');
        foreach ($multiShopTables as $multishopTable) {
            $config->saveShopConfVar('bool', 'blMallInherit_' . strtolower($multishopTable), $inheritAll, $shop->oxshops__oxid->value);
        }
    }

    /**
     * Return template name for new shop if it is different from standard.
     *
     * @return string
     */
    protected function renderNewShop()
    {
        return '';
    }

    /**
     * Check user rights and change userId if needed.
     *
     * @param User $user
     * @param string                                   $shopId
     * @param bool                                     $updateViewData If needing to update view data when shop ID changes.
     *
     * @return string
     */
    protected function updateShopIdByUser($user, $shopId, $updateViewData = false)
    {
        return $shopId;
    }

    /**
     * Load Shop parent and set result to _aViewData.
     *
     * @param Shop $shop
     */
    protected function checkParent($shop)
    {
    }

    /**
     * Unset not used Shop parameters.
     *
     * @param array $parameters
     *
     * @return array
     */
    protected function updateParameters($parameters)
    {
        $parameters['oxshops__oxid'] = null;

        return $parameters;
    }

    /**
     * Check for exception type and set it to _aViewData.
     *
     * @param StandardException $exception
     */
    protected function checkExceptionType($exception)
    {
    }

    /**
     * Check if Shop can be created.
     *
     * @param string                                   $shopId
     * @param Shop $shop
     *
     * @return bool
     */
    protected function canCreateShop($shopId, $shop)
    {
        return true;
    }

    /**
     * Update shop information in DB and oxConfig.
     *
     * @param Config            $config
     * @param Shop $shop
     * @param string                                   $shopId
     */
    protected function updateShopInformation($config, $shop, $shopId)
    {
    }
}
