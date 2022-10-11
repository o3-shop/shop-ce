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

namespace OxidEsales\EshopCommunity\Setup;

use OxidEsales\EshopCommunity\Setup\Exception\SetupControllerExitException;

/**
 * Chooses and executes controller action which must be executec to render expected view
 */
class Dispatcher extends Core
{
    /**
     * Executes current controller action
     */
    public function run()
    {
        // choosing which controller action must be executed
        $sAction = $this->_chooseCurrentAction();

        // executing action which returns name of template to render
        /** @var Controller $oController */
        $oController = $this->getInstance("Controller");

        $view = $oController->getView();
        $view->sendHeaders();

        try {
            $oController->$sAction();
        } catch (SetupControllerExitException $exception) {
        } finally {
            $view->display();
        }
    }

    /**
     * Returns name of controller action script to perform
     *
     * @return string|null
     */
    protected function _chooseCurrentAction() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        /** @var Setup $oSetup */
        $oSetup = $this->getInstance("Setup");
        $iCurrStep = $oSetup->getCurrentStep();

        $sName = null;
        foreach ($oSetup->getSteps() as $sStepName => $sStepId) {
            if ($sStepId == $iCurrStep) {
                $sActionName = str_ireplace("step_", "", $sStepName);
                $sName = str_replace("_", "", $sActionName);
                break;
            }
        }

        return $sName;
    }
}
