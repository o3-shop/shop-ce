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

use Language_Main;
use oxRegistry;
use oxTestModules;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for Language_Main class
 */
class LanguageMainTest extends \OxidTestCase
{
    /**
     * Language_Main::Render() test case
     */
    public function testRender()
    {
        $oView = oxNew('Language_Main');
        $sTplName = $oView->render();

        $this->assertEquals('language_main.tpl', $sTplName);
    }

    /**
     * Language_Main::Save() test case, testing upadating existing language
     *
     * @return null
     */
    public function testSave_update()
    {
        $aNewParams['abbr'] = 'en';
        $aNewParams['active'] = 1;
        $aNewParams['default'] = false;
        $aNewParams['sort'] = 10;
        $aNewParams['desc'] = 'testEnglish';
        $aNewParams['baseurl'] = 'testBaseUrl';
        $aNewParams['basesslurl'] = 'testBaseSslUrl';

        $aDefaultLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aDefaultLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 2];
        $aDefaultLangData['lang'] = ['de' => 'Deutsch', 'en' => 'English'];
        $aDefaultLangData['urls'] = [0 => '', 1 => 'testBaseUrl'];
        $aDefaultLangData['sslUrls'] = [0 => '', 1 => 'testBaseSslUrl'];

        $this->setRequestParameter('oxid', 'en');
        $this->setRequestParameter('editval', $aNewParams);

        $this->getConfig()->setConfigParam('blAllowSharedEdit', true);

        $oMainLang = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LanguageMain::class, ['validateInput', 'getLanguages'], [], '', false);
        $oMainLang->expects($this->once())->method('getLanguages')->will($this->returnValue($aDefaultLangData));
        $oMainLang->expects($this->once())->method('validateInput')->will($this->returnValue(true));

        $oMainLang->save();
    }

    /**
     * Language_Main::Save() test case, saveing new language
     *
     * @return null
     */
    public function testSave_addingNewMultilangFieldsToDb()
    {
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];
        $aLangData['lang'] = ['de' => 'Deutsch', 'en' => 'English'];
        $aLangData['urls'] = [0 => '', 1 => 'testBaseUrl'];
        $aLangData['sslUrls'] = [0 => '', 1 => 'testBaseSslUrl'];

        $aNewParams['baseurl'] = 'testUrl';
        $aNewParams['basesslurl'] = 'testUrl';
        $aNewParams['abbr'] = 'fr';
        $aNewParams['active'] = 1;
        $aNewParams['default'] = false;
        $aNewParams['sort'] = 10;
        $aNewParams['desc'] = 'testFr';

        $this->setRequestParameter('oxid', -1);
        $this->setRequestParameter('editval', $aNewParams);

        $this->getConfig()->setConfigParam('blAllowSharedEdit', true);

        $oMainLang = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LanguageMain::class, ['validateInput', 'checkMultilangFieldsExistsInDb', 'addNewMultilangFieldsToDb', 'getLanguages'], [], '', false);
        $oMainLang->expects($this->once())->method('getLanguages')->will($this->returnValue($aLangData));
        $oMainLang->expects($this->once())->method('validateInput')->will($this->returnValue(true));
        $oMainLang->expects($this->once())->method('checkMultilangFieldsExistsInDb')->with($this->equalTo('fr'))->will($this->returnValue(false));
        $oMainLang->expects($this->once())->method('addNewMultilangFieldsToDb');

        $oMainLang->save();
    }

    /**
     * Language_Main::GetLanguageInfo() test case
     *
     * @return null
     */
    public function testGetLanguageInfo()
    {
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];
        $aLangData['lang'] = ['de' => 'Deutsch', 'en' => 'testEnglish'];
        $aLangData['urls'] = [0 => '', 1 => 'testBaseUrl'];
        $aLangData['sslUrls'] = [0 => '', 1 => 'testBaseSslUrl'];

        $aRes['baseId'] = 1;
        $aRes['active'] = 1;
        $aRes['default'] = false;
        $aRes['sort'] = 10;
        $aRes['abbr'] = 'en';
        $aRes['desc'] = 'testEnglish';
        $aRes['baseurl'] = 'testBaseUrl';
        $aRes['basesslurl'] = 'testBaseSslUrl';

        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);

        $this->assertEquals($aRes, $oView->UNITgetLanguageInfo('en'));
    }

    /**
     * Language_Main::GetLanguages() test case
     *
     * @return null
     */
    public function testGetLanguages()
    {
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 2];
        $aLangData['lang'] = ['de' => 'Deutsch', 'en' => 'English'];
        $aLangData['urls'] = [0 => '', 1 => ''];
        $aLangData['sslUrls'] = [0 => '', 1 => ''];

        $oView = $this->getProxyClass('Language_Main');

        $this->assertEquals($aLangData, $oView->UNITgetLanguages());
    }

    /**
     * Language_Main::UpdateAbbervation() test case
     *
     * @return null
     */
    public function testUpdateAbbervation()
    {
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];
        $aLangData['lang'] = ['de' => 'Deutsch', 'en' => 'testEnglish'];

        $aRes['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aRes['params']['fr'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];
        $aRes['lang'] = ['de' => 'Deutsch', 'fr' => 'testEnglish'];

        // defining parameters
        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);
        $oView->UNITupdateAbbervation('en', 'fr');

        $this->assertEquals($aRes, $oView->getNonPublicVar('_aLangData'));
    }

    /**
     * Language_Main::SortLangArraysByBaseId() test case
     *
     * @return null
     */
    public function testSortLangArraysByBaseId()
    {
        $aLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['lang'] = ['en' => 'testEnglish', 'de' => 'Deutsch'];
        $aLangData['urls'] = [1 => 'testBaseUrl', 0 => ''];
        $aLangData['sslUrls'] = [1 => 'testBaseSslUrl', 0 => ''];

        $aRes['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aRes['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];
        $aRes['lang'] = ['de' => 'Deutsch', 'en' => 'testEnglish'];
        $aRes['urls'] = [0 => '', 1 => 'testBaseUrl'];
        $aRes['sslUrls'] = [0 => '', 1 => 'testBaseSslUrl'];

        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);
        $oView->UNITsortLangArraysByBaseId('en', 'fr');

        $this->assertEquals($aRes, $oView->getNonPublicVar('_aLangData'));
    }

    /**
     * Language_Main::AssignDefaultLangParams() test case
     *
     * @return null
     */
    public function testAssignDefaultLangParams()
    {
        $aLangData = ['de' => 'Deutsch', 'en' => 'testEnglish'];

        $aRes['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aRes['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 2];

        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);

        $this->assertEquals($aRes, $oView->UNITassignDefaultLangParams($aLangData));
    }

    /**
     * Language_Main::SetDefaultLang() test case
     *
     * @return null
     */
    public function testSetDefaultLang()
    {
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];

        /** @var MockObject|Language_Main $oView */
        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);

        $oView->UNITsetDefaultLang('en');

        $this->assertEquals(1, $this->getConfig()->getConfigParam('sDefaultLang'));
    }

    /**
     * Language_Main::GetAvailableLangBaseId() test case
     *
     * @return null
     */
    public function testGetAvailableLangBaseId()
    {
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];

        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);

        $this->assertEquals(2, $oView->UNITgetAvailableLangBaseId());
    }

    /**
     * Language_Main::CheckLangTranslations() test case
     *
     * @return null
     */
    public function testCheckLangTranslations()
    {
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];

        /** @var MockObject|Language_Main $oView */
        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);

        // 'de' is a language that has translation files in the default shop
        $oView->UNITcheckLangTranslations('de');

        //no errors should be added to session
        $aEx = oxRegistry::getSession()->getVariable('Errors');
        $this->assertNull($aEx);
    }

    /**
     * Language_Main::CheckLangTranslations() test case
     *
     * @return null
     */
    public function testCheckLangTranslations_withError()
    {
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['params']['xx'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];

        /** @var MockObject|Language_Main $oView */
        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);

        // 'xx' is a non-existent language, so getTranslationsDir will return empty
        $oView->UNITcheckLangTranslations('xx');

        $aEx = oxRegistry::getSession()->getVariable('Errors');
        $oEx = unserialize($aEx['default'][0]);
        $sErrMsg = oxRegistry::getLang()->translateString('LANGUAGE_NOTRANSLATIONS_WARNING');

        $this->assertEquals($sErrMsg, $oEx->getOxMessage());
    }

    /**
     * Language_Main::CheckMultilangFieldsExistsInDb() test case
     *
     * @return null
     */
    public function testCheckMultilangFieldsExistsInDb()
    {
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];
        $aLangData['params']['fr'] = ['baseId' => 9, 'active' => 1, 'sort' => 20, 'default' => false];

        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);

        $this->assertTrue($oView->UNITcheckMultilangFieldsExistsInDb('de'));
        $this->assertTrue($oView->UNITcheckMultilangFieldsExistsInDb('en'));
        $this->assertFalse($oView->UNITcheckMultilangFieldsExistsInDb('fr'));
    }

    /**
     * Language_Main::AddNewMultilangFieldsToDb() test case
     *
     * @return null
     */
    public function testAddNewMultilangFieldsToDb()
    {
        oxTestModules::addFunction('oxDbMetaDataHandler', 'addNewLangToDb', '{return true;}');

        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', null);

        $oView->UNITaddNewMultilangFieldsToDb();

        //no errors should be added to session
        $aEx = oxRegistry::getSession()->getVariable('Errors');
        $this->assertNull($aEx);
    }

    /**
     * Language_Main::AddNewMultilangFieldsToDb() test case
     *
     * @return null
     */
    public function testAddNewMultilangFieldsToDb_withError()
    {
        oxTestModules::addFunction('oxDbMetaDataHandler', 'addNewLangToDb', '{Throw new Exception();}');

        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', null);

        $oView->UNITaddNewMultilangFieldsToDb();

        $aEx = oxRegistry::getSession()->getVariable('Errors');
        $oEx = unserialize($aEx['default'][0]);
        $sErrMsg = oxRegistry::getLang()->translateString('LANGUAGE_ERROR_ADDING_MULTILANG_FIELDS');

        $this->assertEquals($sErrMsg, $oEx->getOxMessage());
    }

    /**
     * Language_Main::CheckLangExists() test case
     *
     * @return null
     */
    public function testCheckLangExists()
    {
        $aLangData['lang'] = ['de' => 'Deutsch', 'en' => 'English'];

        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);

        $this->assertTrue($oView->UNITcheckLangExists('de'));
        $this->assertTrue($oView->UNITcheckLangExists('en'));
        $this->assertFalse($oView->UNITcheckLangExists('fr'));
    }

    /**
     * Language_Main::SortLangParamsByBaseIdCallback() test case
     *
     * @return null
     */
    public function testSortLangParamsByBaseIdCallback()
    {
        $aLangData['params']['de'] = ['baseId' => 0, 'active' => 1, 'sort' => 1];
        $aLangData['params']['en'] = ['baseId' => 1, 'active' => 1, 'sort' => 10, 'default' => false];

        $oView = $this->getProxyClass('Language_Main');
        $oView->setNonPublicVar('_aLangData', $aLangData);

        $this->assertEquals(1, $oView->UNITsortLangParamsByBaseIdCallback($aLangData['params']['en'], $aLangData['params']['de']));
        $this->assertEquals(-1, $oView->UNITsortLangParamsByBaseIdCallback($aLangData['params']['de'], $aLangData['params']['en']));
    }

    /**
     * Testing validation errors - language already exist
     *
     * @return null
     */
    public function testValidateInput_langExists()
    {
        $this->setRequestParameter('oxid', '-1');
        $this->setRequestParameter('editval', ['abbr' => 'en']);

        $oMainLang = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LanguageMain::class, ['checkLangExists']);
        $oMainLang->expects($this->once())->method('checkLangExists')->with($this->equalTo('en'))->will($this->returnValue(true));

        $this->assertFalse($oMainLang->UNITvalidateInput());

        $aEx = oxRegistry::getSession()->getVariable('Errors');
        $oEx = unserialize($aEx['default'][0]);
        $sErrMsg = oxRegistry::getLang()->translateString('LANGUAGE_ALREADYEXISTS_ERROR');

        $this->assertEquals($sErrMsg, $oEx->getOxMessage());
    }

    /**
     * Testing validation errors - empty language name
     *
     * @return null
     */
    public function testValidateInput_emptyLangName()
    {
        $this->setRequestParameter('oxid', '1');
        $this->setRequestParameter('editval', ['abbr' => 'en', 'desc' => '']);

        $oMainLang = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LanguageMain::class, ['_checkLangExists']);
        $oMainLang->expects($this->never())->method('_checkLangExists');

        $this->assertFalse($oMainLang->UNITvalidateInput());

        $aEx = oxRegistry::getSession()->getVariable('Errors');
        $oEx = unserialize($aEx['default'][0]);
        $sErrMsg = oxRegistry::getLang()->translateString('LANGUAGE_EMPTYLANGUAGENAME_ERROR');

        $this->assertEquals($sErrMsg, $oEx->getOxMessage());
    }

    /**
     * Testing validation errors - all values valid
     *
     * @return null
     */
    public function testValidateInput_validInput()
    {
        $this->setRequestParameter('oxid', '1');
        $this->setRequestParameter('editval', ['abbr' => 'en', 'desc' => 'English']);

        $oMainLang = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LanguageMain::class, ['_checkLangExists']);
        $oMainLang->expects($this->never())->method('_checkLangExists');

        $this->assertTrue($oMainLang->UNITvalidateInput());

        $aEx = oxRegistry::getSession()->getVariable('Errors');
        $this->assertNull($aEx);
    }

    /**
     * Testing validation errors - abbreviation contains forbidden characters
     */
    public function testValidateInputInvalidAbbreviation()
    {
        $this->setRequestParameter('oxid', '-1');
        $this->setRequestParameter('editval', ['abbr' => 'ch-xx']);

        $mainLanguage = $this->getMock(\OxidEsales\Eshop\Application\Controller\Admin\LanguageMain::class, ['checkLangExists']);
        $mainLanguage->expects($this->once())->method('checkLangExists')->with($this->equalTo('ch-xx'))->will($this->returnValue(false));

        $this->assertFalse($mainLanguage->UNITvalidateInput());

        $exceptions = oxRegistry::getSession()->getVariable('Errors');
        $exception = unserialize($exceptions['default'][0]);
        $errorMessage = oxRegistry::getLang()->translateString('LANGUAGE_ABBREVIATION_INVALID_ERROR');

        $this->assertEquals($errorMessage, $exception->getOxMessage());
    }
}
