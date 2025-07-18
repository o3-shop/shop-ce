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

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Application\Controller\UserController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * User registration window.
 * Collects and arranges user object data (information, like shipping address, etc.).
 */
class RegisterController extends UserController
{
    /**
     * Current class template.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/account/register.tpl';

    /**
     * Successful registration confirmation template
     *
     * @var string
     */
    protected $_sSuccessTemplate = 'page/account/register_success.tpl';

    /**
     * Successful Confirmation state template name
     *
     * @var string
     */
    protected $_sConfirmTemplate = 'page/account/register_confirm.tpl';

    /**
     * Order step marker
     *
     * @var bool
     */
    protected $_blIsOrderStep = false;

    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_iViewIndexState = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;

    /**
     * Executes parent::render(), passes error code to template engine,
     * returns name of template to render register::_sThisTemplate.
     *
     * @return string   current template file name
     */
    public function render()
    {
        parent::render();

        // checking registration status
        if ($this->isEnabledPrivateSales() && $this->isConfirmed()) {
            $sTemplate = $this->_sConfirmTemplate;
        } elseif ($this->getRegistrationStatus()) {
            $sTemplate = $this->_sSuccessTemplate;
        } else {
            $sTemplate = $this->_sThisTemplate;
        }

        return $sTemplate;
    }

    /**
     * Returns registration error code (if it was set)
     *
     * @return int | null
     */
    public function getRegistrationError()
    {
        return Registry::getRequest()->getRequestEscapedParameter('newslettererror');
    }

    /**
     * Return registration status (if it was set)
     *
     * @return int | null
     */
    public function getRegistrationStatus()
    {
        return Registry::getRequest()->getRequestEscapedParameter('success');
    }

    /**
     * Check if field is required.
     *
     * @param string $field required field to check
     *
     * @return bool
     */
    public function isFieldRequired($field)
    {
        return isset($this->getMustFillFields()[$field]);
    }

    /**
     * Registration confirmation functionality. If registration
     * succeeded - redirects to success page, if not - returns
     * exception informing about expired confirmation link
     *
     * @return string
     */
    public function confirmRegistration()
    {
        $oUser = oxNew(User::class);
        if ($oUser->loadUserByUpdateId($this->getUpdateId())) {
            // resetting update key parameter
            $oUser->setUpdateKey(true);

            // saving ..
            $oUser->oxuser__oxactive = new Field(1);
            $oUser->save();

            // forcing user login
            Registry::getSession()->setVariable('usr', $oUser->getId());

            // redirecting to confirmation page
            return 'register?confirmstate=1';
        } else {
            // confirmation failed
            Registry::getUtilsView()->addErrorToDisplay('REGISTER_ERRLINKEXPIRED', false, true);

            // redirecting to confirmation page
            return 'account';
        }
    }

    /**
     * Returns special id used for password update functionality
     *
     * @return string
     */
    public function getUpdateId()
    {
        return Registry::getRequest()->getRequestEscapedParameter('uid');
    }

    /**
     * Returns confirmation state: "1" - success, "-1" - error
     *
     * @return bool
     */
    public function isConfirmed()
    {
        return (bool) Registry::getRequest()->getRequestEscapedParameter('confirmstate');
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths = [];
        $aPath = [];

        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        $aPath['title'] = Registry::getLang()->translateString('REGISTER', $iBaseLanguage, false);
        $aPath['link']  = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }
}
