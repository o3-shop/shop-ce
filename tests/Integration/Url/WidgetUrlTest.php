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
namespace OxidEsales\EshopCommunity\Tests\Integration\Url;

use oxRegistry;

class WidgetUrlTest extends \oxUnitTestCase
{
    private $shopUrl = 'http://www.example.com/';

    public function providerGetWidgetUrlAddParametersIdNeed()
    {
        $basicUrl = $this->shopUrl . 'widget.php';
        $urlWithoutParams = $basicUrl . '?lang=0';

        $urlParameters = array('param1' => 'value1', 'param2' => 'value2');
        $urlWithParams = $basicUrl . '?lang=0&amp;param1=value1&amp;param2=value2';

        $urlLanguageParameters = array('lang' => '1', 'param1' => 'value1', 'param2' => 'value2');
        $urlWithLanguageParams = $basicUrl . '?lang=1&amp;param1=value1&amp;param2=value2';

        $urlLeveledParameters = array('lang' => '1', 'param1' => array('value1', 'value2'));
        $urlWithLeveledParameters = $basicUrl . '?lang=1&amp;param1%5B0%5D=value1&amp;param1%5B1%5D=value2';

        return array(
            array(array(), $urlWithoutParams),
            array($urlParameters, $urlWithParams),
            array($urlLanguageParameters, $urlWithLanguageParams),
            array($urlLeveledParameters, $urlWithLeveledParameters),
        );
    }

    /**
     * Testing getShopHomeUrl for widget getter
     *
     * @param array $urlParameters parameters to add to url.
     * @param string $sUrl to check if form url matches expectation.
     *
     * @dataProvider providerGetWidgetUrlAddParametersIdNeed
     */
    public function testGetWidgetUrlWithParameters($urlParameters, $sUrl)
    {
        oxRegistry::getLang()->setBaseLanguage(0);

        $config = oxNew('oxConfig');
        $config->setConfigParam('sShopURL', $this->shopUrl);
        $config->init();

        $this->assertEquals($sUrl, $config->getWidgetUrl(null, null, $urlParameters));
    }

    public function providerGetWidgetUrlAddCorrectLanguage()
    {
        return array(
            array(1),
            array(2),
        );
    }

    /**
     * Testing getShopHomeUrl for widget getter
     *
     * @param int $iLang Shop basic language.
     *
     * @dataProvider providerGetWidgetUrlAddCorrectLanguage
     */
    public function testGetWidgetUrlAddCorrectLanguage($iLang)
    {
        oxRegistry::getLang()->setBaseLanguage($iLang);

        $config = oxNew('oxConfig');
        $config->setConfigParam('sShopURL', $this->shopUrl);
        $config->init();

        $this->assertEquals($this->shopUrl. 'widget.php?lang='. $iLang, $config->getWidgetUrl());
    }

    public function providerGetWidgetUrlAddCorrectLanguageWithParameter()
    {
        return array(
            array(1),
            array(2),
        );
    }

    /**
     * Testing getShopHomeUrl for widget getter
     *
     * @param int $iLang Shop basic language.
     *
     * @dataProvider providerGetWidgetUrlAddCorrectLanguage
     */
    public function testGetWidgetUrlAddCorrectLanguageWithParameter($iLang)
    {
        oxRegistry::getLang()->setBaseLanguage(1);

        $config = oxNew('oxConfig');
        $config->setConfigParam('sShopURL', $this->shopUrl);
        $config->init();

        $this->assertEquals($this->shopUrl. 'widget.php?lang='. $iLang, $config->getWidgetUrl($iLang));
    }
}
