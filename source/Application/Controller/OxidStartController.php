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

use OxidEsales\Eshop\Core\SystemEventHandler;

/**
 * Encapsulates methods for application initialization.
 */
class OxidStartController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Initializes globals and environment vars
     *
     * @return null
     */
    public function appInit()
    {
        $this->pageStart();

        if ('oxstart' == \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestControllerId() || $this->isAdmin()) {
            return;
        }

        $oSystemEventHandler = $this->_getSystemEventHandler();
        $oSystemEventHandler->onShopStart();
    }

    /**
     * Renders error screen
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $errorNumber = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('execerror');
        $templates = $this->getErrorTemplates();

        if (array_key_exists($errorNumber, $templates)) {
            return $templates[$errorNumber];
        } else {
            return 'message/err_unknown.tpl';
        }
    }

    /**
     * Creates and starts session object, sets default currency.
     */
    public function pageStart()
    {
        $config = $this->getConfig();

        $config->setConfigParam('iMaxMandates', $config->getConfigParam('IMS'));
        $config->setConfigParam('iMaxArticles', $config->getConfigParam('IMA'));
    }

    /**
     * Finalizes the script.
     */
    public function pageClose()
    {
        $systemEventHandler = $this->_getSystemEventHandler();
        $systemEventHandler->onShopEnd();

        $mySession = $this->getSession();

        if (isset($mySession)) {
            $mySession->freeze();
        }

        //commit file cache
        \OxidEsales\Eshop\Core\Registry::getUtils()->commitFileCache();
    }

    /**
     * Return error number
     *
     * @return integer
     */
    public function getErrorNumber()
    {
        return \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('errornr');
    }

    /**
     * Returns which template should be used for specific error.
     *
     * @return array
     */
    protected function getErrorTemplates()
    {
        return [
            'unknown' => 'message/err_unknown.tpl',
        ];
    }

    /**
     * Gets system event handler.
     *
     * @return SystemEventHandler
     * @deprecated underscore prefix violates PSR12, will be renamed to "getSystemEventHandler" in next major
     */
    protected function _getSystemEventHandler() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);
    }
}
