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

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererInterface;

/**
 * Admin systeminfo manager.
 * Returns template "systeminfo.tpl" and phphinfo() result to frame.
 */
class SystemInfoController extends AdminController
{
    /**
     * Executes parent method parent::render(), prints shop and
     * PHP configuration information.
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        $myConfig = Registry::getConfig();

        parent::render();

        $oAuthUser = oxNew(User::class);
        $oAuthUser->loadAdminUser();
        $blisMallAdmin = $oAuthUser->oxuser__oxrights->value == "malladmin";

        if ($blisMallAdmin && !$myConfig->isDemoShop()) {
            $aClassVars = get_object_vars($myConfig);
            $aSystemInfo = [];
            $aSystemInfo['pkg.info'] = $myConfig->getPackageInfo();
            foreach ($aClassVars as $name => $value) {
                if (gettype($value) == "object") {
                    continue;
                }

                if (!$this->isClassVariableVisible($name)) {
                    continue;
                }

                $value = var_export($value, true);
                $value = str_replace("\n", "<br>", $value);
                $aSystemInfo[$name] = $value;
                //echo( "$name = $value <br>");
            }
            $context = [
                "oViewConf" => $this->_aViewData["oViewConf"],
                "oView" => $this->_aViewData["oView"],
                "shop" => $this->_aViewData["shop"],
                "isdemo" => $myConfig->isDemoShop(),
                "aSystemInfo" => $aSystemInfo
            ];

            ob_start();
            echo $this->getRenderer()->renderTemplate("systeminfo.tpl", $context);
            echo("<br><br>");

            phpinfo();
            $sMessage = ob_get_clean();

            Registry::getUtils()->showMessageAndExit($sMessage);
        } else {
            return Registry::getUtils()->showMessageAndExit("Access denied !");
        }
    }

    /**
     * @internal
     *
     * @return TemplateRendererInterface
     */
    private function getRenderer()
    {
        return $this->getContainer()
            ->get(TemplateRendererBridgeInterface::class)
            ->getTemplateRenderer();
    }

    /**
     * Checks if class var can be shown in systeminfo.
     *
     * @param string $varName
     * @return bool
     */
    protected function isClassVariableVisible($varName)
    {
        return !in_array($varName, [
            'oDB',
            'dbUser',
            'dbPwd',
            'aSerials',
            'sSerialNr'
        ]);
    }
}
