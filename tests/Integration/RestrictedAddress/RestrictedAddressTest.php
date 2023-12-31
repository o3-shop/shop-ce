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
namespace OxidEsales\EshopCommunity\Tests\Integration\RestrictedAddress;

/**
 * Class Integration_RestrictedAddress_RestrictedAddressTest
 */
class RestrictedAddressTest extends \OxidTestCase
{
    /**
     * DataProvider returns shop URL list to call.
     *
     * @return array
     */
    public function providerRequestFunctionThatResultsInNoValidNewActionGetsRedirectedToStart()
    {
        $shopUrl = $this->getConfig()->getShopMainUrl();

        return array(
            array($shopUrl . '?fnc=getShopVersion'),
            array($shopUrl . '?fnc=getShopEdition'),
            array($shopUrl . '?fnc=getShopVersion&n2=v2'),
            array($shopUrl . '?fnc=getShopEdition&n2=v2'),
            array($shopUrl . '?name=value&fnc=getShopVersion'),
            array($shopUrl . '?name=value&fnc=getShopEdition'),
            array($shopUrl . '?name=value&fnc=getShopVersion&n2=v2'),
            array($shopUrl . '?name=value&fnc=getShopEdition&n2=v2'),
            array($shopUrl . '?fnc=%67etshopversion'),
            array($shopUrl . '?fnc=getCharSet'),
            array($shopUrl . '?fnc=getShopFullEdition'),
            array($shopUrl . '?fnc=isMall'),
            array($shopUrl . '?fnc=getCacheLifeTime'),
            array($shopUrl . '?fnc=addGlobalParams')
        );
    }

    /**
     * Test case that a function's return value is no callable new action, directly redirect
     * to startpage without trying to call a not extisting view class.
     *
     * @dataProvider providerRequestFunctionThatResultsInNoValidNewActionGetsRedirectedToStart
     */
    public function _testRequestFunctionThatResultsInNoValidNewActionGetsRedirectedToStart($sForbiddenUrl)
    {
        $shopUrl = $this->getConfig()->getShopMainUrl();

        $result = $this->callPage($sForbiddenUrl);

        $location = "Location: " .  $shopUrl . 'index.php?force_sid=' . $this->extractSessionId($result) .
                     "&cl=start&redirected=1\r\n";
        $this->assertStringContainsString($location, $result, 'User should be redirected to shop front page.');
    }

    /**
     * Fix for bug 0005565: Accessing config.inc.php directly results in Fatal error
     */
    public function test_configCalled_notAccessed()
    {
        $sShopUrl = $this->getConfig()->getShopMainUrl();
        $sResult = $this->_getPageResult('/config.inc.php');
        $sLocation = "Location: " . $sShopUrl . "index.php\r\n";
        $this->assertStringContainsString($sLocation, $sResult, 'User should be redirected to same URL without forbidden parameter.');
    }

    public function providerForbiddenFilesAccessibility()
    {
        return array(
            array('/log/EXCEPTION_LOG.txt'),
            array('/log/anything'),
            array('/Application/views/azure/tpl/widget/rss.tpl'),
            array('/pkg.info'),
            array('/op.ini'),
            array('/.htaccess'),
            array('/.ht'),
        );
    }

    /**
     * @param string $sFilePath Path to forbidden file.
     *
     * @dataProvider providerForbiddenFilesAccessibility
     */
    public function testCheckForbiddenFilesAccessibility($sFilePath)
    {
        $sResultPage = $this->_getPageResult($sFilePath);

        $this->assertStringContainsString('Forbidden', $sResultPage, 'User should see forbidden page message.');
    }

    public function providerCheckAllowedFilesAccessibility()
    {
        return array(
            array('/op.ini.php'),
            array('/Application/views/azure/tpl/widget/rss.tpl.whatever'),
        );
    }

    /**
     * @param string $sFilePath Path to allowable file.
     *
     * @dataProvider providerCheckAllowedFilesAccessibility
     */
    public function testCheckAllowedFilesAccessibility($sFilePath)
    {
        $sResultPage = $this->_getPageResult($sFilePath);

        $this->assertStringNotContainsString('Forbidden', $sResultPage, "User shouldn't see forbidden page message.");
    }

    /**
     * @param string $sShopUrl shop url to call.
     *
     * @return string
     */
    private function callPage($sShopUrl)
    {
        $oCurl = oxNew('oxCurl');
        $oCurl->setOption('CURLOPT_HEADER', true);
        $oCurl->setUrl($sShopUrl);

        return $oCurl->execute();
    }

    /**
     * @param $sFilePath
     *
     * @return string
     */
    private function _getPageResult($sFilePath)
    {
        $sShopUrl = $this->getConfig()->getShopMainUrl();
        $sResultPage = $this->callPage($sShopUrl . $sFilePath);

        return $sResultPage;
    }

    /**
     * Test helper to extract session id form curl response
     *
     * @param $text
     *
     * @return string
     */
    private function extractSessionId($text)
    {
        $parts = explode('Set-Cookie: sid=', $text);
        $parts = explode(';', $parts[1]);
        return trim($parts[0]);
    }
}
