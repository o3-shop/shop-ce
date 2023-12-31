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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use \stdClass;
use \Exception;
use \oxTestModules;

/**
 * Tests for Language_List class
 */
class LanguageListTest extends \OxidTestCase
{

    /**
     * Language_List::DeleteEntry() test case
     */
    public function testDeleteEntry()
    {
        $this->getConfig()->setConfigParam("blAllowSharedEdit", true);
        $this->setRequestParameter('oxid', 1);

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array("getConfigParam", "saveShopConfVar"));

        $map = array(
            array('blAllowSharedEdit',  null, "1"),
            array('aLanguageParams', null, array(1 => array('baseId' => 1))),
            array('aLanguages', null, array(1 => 1)),
            array('aLanguageURLs', null, array(1 => 1)),
            array('aLanguageSSLURLs', null, array(1 => 1)),
            array('sDefaultLang', null, 1),
        );
        $oConfig->expects($this->any())->method('getConfigParam')->will($this->returnValueMap($map));

        $map = array(
            array('aarr', "aLanguageParams", array(), null),
            array('aarr', "aLanguages", array(), null),
            array('arr', "aLanguageURLs", array(), null),
            array('arr', "aLanguageSSLURLs", array(), null),
            array('str', "sDefaultLang", 0, null),
        );
        $oConfig->expects($this->exactly(5))->method('saveShopConfVar')->will($this->returnValueMap($map));

        $aTasks = array("getConfig");

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LanguageList::class, $aTasks, array(), '', false);
        $oView->expects($this->any())->method('getConfig')->will($this->returnValue($oConfig));

        $oView->deleteEntry();
    }

    /**
     * Language_List::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = oxNew('Language_List');
        $this->assertEquals('language_list.tpl', $oView->render());
    }

    /**
     * Language_List::GetLanguagesList() test case
     *
     * @return null
     */
    public function testGetLanguagesList()
    {
        $oLang1 = new stdClass();
        $oLang1->id = 0;
        $oLang1->oxid = 'de';
        $oLang1->abbr = 'de';
        $oLang1->name = 'Deutsch';
        $oLang1->active = 1;
        $oLang1->sort = null;
        $oLang1->selected = 1;
        $oLang1->default = false;

        $oLang2 = new stdClass();
        $oLang2->id = 1;
        $oLang2->oxid = 'en';
        $oLang2->abbr = 'en';
        $oLang2->name = 'English';
        $oLang2->active = 1;
        $oLang2->sort = null;
        $oLang2->selected = 0;
        $oLang2->default = false;

        $oView = oxNew('Language_List');
        $this->assertEquals(array($oLang1, $oLang2), $oView->UNITgetLanguagesList());
    }

    /**
     * Language_List::SortLanguagesCallback() test case
     *
     * @return null
     */
    public function testSortLanguagesCallback()
    {
        $oView = $this->getProxyClass("Language_List");

        $oLang1 = new stdClass();
        $oLang1->sort = 'EN';
        $oLang2 = new stdClass();
        $oLang2->sort = 'DE';
        $this->assertEquals(1, $oView->UNITsortLanguagesCallback($oLang1, $oLang2));

        $oLang1 = new stdClass();
        $oLang1->sort = 'DE';
        $oLang2 = new stdClass();
        $oLang2->sort = 'EN';
        $this->assertEquals(-1, $oView->UNITsortLanguagesCallback($oLang1, $oLang2));

        $oLang1 = new stdClass();
        $oLang1->sort = 1;
        $oLang2 = new stdClass();
        $oLang2->sort = 2;
        $oView->setNonPublicVar("_sDefSortOrder", "desc");
        $this->assertEquals(1, $oView->UNITsortLanguagesCallback($oLang1, $oLang2));
    }

    /**
     * Language_List::ResetMultiLangDbFields() test case
     *
     * @return null
     */
    public function testResetMultiLangDbFieldsExceptionThrownWhileResetting()
    {
        oxTestModules::addFunction('oxDbMetaDataHandler', 'resetLanguage', '{ throw new Exception( "resetLanguage" ); }');
        oxTestModules::addFunction('oxUtilsView', 'addErrorToDisplay', '{ throw new Exception( "addErrorToDisplay" ); }');

        try {
            $oView = oxNew('Language_List');
            $oView->UNITresetMultiLangDbFields(3);
        } catch (Exception $oExcp) {
            $this->assertEquals("addErrorToDisplay", $oExcp->getMessage(), "Error in Language_List::UNITresetMultiLangDbFields()");

            return;
        }
        $this->fail("Error in Language_List::UNITresetMultiLangDbFields()");
    }

    /**
     * Language_List::ResetMultiLangDbFields() test case
     *
     * @return null
     */
    public function testResetMultiLangDbFields()
    {
        oxTestModules::addFunction('oxDbMetaDataHandler', 'resetLanguage', '{}');

        $oView = oxNew('Language_List');
        $this->assertNull($oView->UNITresetMultiLangDbFields(3));
    }
}
