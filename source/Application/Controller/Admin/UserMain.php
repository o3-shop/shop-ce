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
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use stdClass;
use Exception;

/**
 * Admin article main user manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: User Administration -> Users -> Main.
 */
class UserMain extends AdminDetailsController
{
    private $_sSaveError = null;

    /**
     * Executes parent method parent::render(), creates oxuser, oxshops and oxlist
     * objects, passes data to Smarty engine and returns name of template
     * file "user_main.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        parent::render();

        // malladmin stuff
        $oAuthUser = oxNew(User::class);
        $oAuthUser->loadAdminUser();
        $blisMallAdmin = $oAuthUser->oxuser__oxrights->value == "malladmin";

        // User rights
        $aUserRights = [];
        $oLang = Registry::getLang();
        $iTplLang = $oLang->getTplLanguage();

        $iPos = 0;
        $aUserRights[$iPos] = new stdClass();
        $aUserRights[$iPos]->name = $oLang->translateString("user", $iTplLang);
        $aUserRights[$iPos]->id = "user";

        if ($blisMallAdmin) {
            $iPos = count($aUserRights);
            $aUserRights[$iPos] = new stdClass();
            $aUserRights[$iPos]->id = "malladmin";
            $aUserRights[$iPos]->name = $oLang->translateString("Admin", $iTplLang);
        }

        $aUserRights = $this->calculateAdditionalRights($aUserRights);

        $soxId = $this->_aViewData["oxid"] = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oUser = oxNew(User::class);
            $oUser->load($soxId);
            $this->_aViewData["edit"] = $oUser;

            if (!($oUser->oxuser__oxrights->value == "malladmin" && !$blisMallAdmin)) {
                // generate selected right
                foreach ($aUserRights as $val) {
                    if ($val->id == $oUser->oxuser__oxrights->value) {
                        $val->selected = 1;
                        break;
                    }
                }
            }
        }

        // passing country list
        $oCountryList = oxNew(\OxidEsales\Eshop\Application\Model\CountryList::class);
        $oCountryList->loadActiveCountries($oLang->getObjectTplLanguage());

        $this->_aViewData["countrylist"] = $oCountryList;

        $this->_aViewData["rights"] = $aUserRights;

        if ($this->_sSaveError) {
            $this->_aViewData["sSaveError"] = $this->_sSaveError;
        }

        if (!$this->_allowAdminEdit($soxId)) {
            $this->_aViewData['readonly'] = true;
        }
        if (Registry::getRequest()->getRequestEscapedParameter('aoc')) {
            $oUserMainAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\UserMainAjax::class);
            $this->_aViewData['oxajax'] = $oUserMainAjax->getColumns();

            return "popups/user_main.tpl";
        }

        return "user_main.tpl";
    }

    /**
     * Saves main user parameters.
     *
     * @return void
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function save()
    {
        parent::save();

        //allow admin information edit only for MALL admins
        $soxId = $this->getEditObjectId();
        if ($this->_allowAdminEdit($soxId)) {
            $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');

            // checkbox handling
            if (!isset($aParams['oxuser__oxactive'])) {
                $aParams['oxuser__oxactive'] = 0;
            }

            $oUser = oxNew(User::class);
            if ($soxId != "-1") {
                $oUser->load($soxId);
            } else {
                $aParams['oxuser__oxid'] = null;
            }

            //setting new password
            if (($sNewPass = Registry::getRequest()->getRequestEscapedParameter('newPassword'))) {
                $oUser->setPassword($sNewPass);
            }

            //FS#2167 V checks for already used email

            if (isset($aParams['oxuser__oxusername']) && $oUser->checkIfEmailExists($aParams['oxuser__oxusername'])) {
                $this->_sSaveError = 'EXCEPTION_USER_USEREXISTS';

                return;
            }

            $oUser->assign($aParams);

            // setting shop id for ONLY for new created user
            if ($soxId == "-1") {
                $this->onUserCreation($oUser);
            }

            // A. changing field type to save birthdate correctly
            $oUser->oxuser__oxbirthdate->fldtype = 'char';

            try {
                $oUser->save();

                // set oxid if inserted
                $this->setEditObjectId($oUser->getId());
            } catch (Exception $oExcp) {
                $this->_sSaveError = $oExcp->getMessage();
            }
        }
    }

    /**
     * If we need to add more rights / modify current rights by any conditions.
     *
     * @param array $userRights
     *
     * @return array
     */
    protected function calculateAdditionalRights($userRights)
    {
        return $userRights;
    }

    /**
     * Additional actions on user creation.
     *
     * @param User $user
     *
     * @return User
     */
    protected function onUserCreation($user)
    {
        return $user;
    }
}
