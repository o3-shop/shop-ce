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

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\AllCookiesRemovedEvent;

/**
 * CMS - loads pages and displays it
 */
class ClearCookiesController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Current view template
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/info/clearcookies.tpl';

    /**
     * Executes parent::render(), passes template variables to
     * template engine and generates content. Returns the name
     * of template to render content::_sThisTemplate
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        parent::render();

        $this->_removeCookies();

        return $this->_sThisTemplate;
    }

    /**
     * Clears all cookies
     * @deprecated underscore prefix violates PSR12, will be renamed to "removeCookies" in next major
     */
    protected function _removeCookies() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oUtilsServer = Registry::getUtilsServer();
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $aCookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach ($aCookies as $sCookie) {
                $sRawCookie = explode('=', $sCookie);
                $oUtilsServer->setOxCookie(trim($sRawCookie[0]), '', time() - 10000, '/');
            }
        }
        $oUtilsServer->setOxCookie('language', '', time() - 10000, '/');
        $oUtilsServer->setOxCookie('displayedCookiesNotification', '', time() - 10000, '/');

        $this->dispatchEvent(new AllCookiesRemovedEvent());
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
        $aPath['title'] = Registry::getLang()->translateString('INFO_ABOUT_COOKIES', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }
}
