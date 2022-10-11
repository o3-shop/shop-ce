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

use Exception;

/**
 * Contains system event handler methods
 *
 * @internal Do not make a module extension for this class.
 */
class SystemEventHandler
{
    /**
     * onAdminLogin() is called on every successful login to the backend
     */
    public function onAdminLogin()
    {
    }

    /**
     * Perform shop startup related actions, like license check.
     */
    public function onShopStart()
    {
        $this->validateOffline();
    }

    /**
     * Perform shop finishing up related actions, like updating app server data.
     */
    public function onShopEnd()
    {
        $this->validateOnline();
    }

    /**
     * Check if shop is valid online.
     */
    protected function validateOnline()
    {
        try {
            $appServerService = $this->getAppServerService();
            if ($this->getConfig()->isAdmin()) {
                $appServerService->updateAppServerInformationInAdmin();
            } else {
                $appServerService->updateAppServerInformationInFrontend();
            }
        } catch (Exception $exception) {
            \OxidEsales\Eshop\Core\Registry::getLogger()->error($exception->getMessage(), [$exception]);
        }
    }

    /**
     * Check if shop valid and do related actions.
     */
    protected function validateOffline()
    {
    }

    /**
     * Return Config from registry.
     *
     * @return \OxidEsales\Eshop\Core\Config
     */
    protected function getConfig()
    {
        return \OxidEsales\Eshop\Core\Registry::getConfig();
    }

    /**
     * Gets application server service.
     *
     * @return \OxidEsales\Eshop\Core\Service\ApplicationServerServiceInterface
     */
    protected function getAppServerService()
    {
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $appServerDao = oxNew(\OxidEsales\Eshop\Core\Dao\ApplicationServerDao::class, $database, $config);
        $utilsServer = oxNew(\OxidEsales\Eshop\Core\UtilsServer::class);

        $appServerService = oxNew(
            \OxidEsales\Eshop\Core\Service\ApplicationServerService::class,
            $appServerDao,
            $utilsServer,
            \OxidEsales\Eshop\Core\Registry::get("oxUtilsDate")->getTime()
        );

        return $appServerService;
    }
}
