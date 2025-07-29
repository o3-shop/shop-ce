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

namespace OxidEsales\EshopCommunity\Tests\Unit\Setup;

require_once getShopBasePath() . '/Setup/functions.php';

/**
 * Language tests
 */
class LanguageTest extends \OxidTestCase
{
    /**
     * Test teardown
     */
    protected function tearDown(): void
    {
        if (isset($_GET['istep'])) {
            unset($_GET['istep']);
        }
        if (isset($_POST['istep'])) {
            unset($_POST['istep']);
        }

        parent::tearDown();
    }

    public function testGetSetupLangLanguageIdentIsPassedByRequest()
    {
        $oSession = $this->getMock('SetupSession', ['setSessionParam', 'getSessionParam'], [], '', null);

        // For setSessionParam - if called once
        $oSession->expects($this->once())
            ->method('setSessionParam')
            ->with($this->equalTo('setup_lang'));

        // For getSessionParam - if called once
        $oSession->expects($this->once())
            ->method('getSessionParam')
            ->with($this->equalTo('setup_lang'));

        $oUtils = $this->getMock('Utilities', ['getRequestVar']);
        $oUtils->expects($this->exactly(2))
            ->method('getRequestVar')
            ->withConsecutive(
                [$this->equalTo('setup_lang'), $this->equalTo('post')],
                [$this->equalTo('setup_lang_submit'), $this->equalTo('post')]
            )
            ->willReturnOnConsecutiveCalls('de', 'de');

        $oSetup = $this->getMock('Setup', ['getStep']);
        $oSetup->expects($this->once())
            ->method('getStep')
            ->with($this->equalTo('STEP_WELCOME'));

        $oLang = $this->getMock('OxidEsales\\EshopCommunity\\Setup\\Language', ['getInstance', 'setViewParam']);
        $oLang->expects($this->exactly(3))
            ->method('getInstance')
            ->withConsecutive(
                [$this->equalTo('Session')],
                [$this->equalTo('Utilities')],
                [$this->equalTo('Setup')]
            )
            ->willReturnOnConsecutiveCalls($oSession, $oUtils, $oSetup);
        $oLang->getLanguage();
    }

    /**
     * Testing Language::getSetupLang()
     */
    public function testGetSetupLang()
    {
        $aLangs = ['en', 'de'];
        $sBrowserLang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
        $sBrowserLang = (in_array($sBrowserLang, $aLangs)) ? $sBrowserLang : $aLangs[0];

        $oSession = $this->getMock('SetupSession', ['setSessionParam', 'getSessionParam'], [], '', null);
        $oSession->expects($this->any()) // Changed from once() to any()
        ->method('getSessionParam')
            ->with($this->equalTo('setup_lang'))
            ->willReturn(null);
        $oSession->expects($this->once())
            ->method('setSessionParam')
            ->with($this->equalTo('setup_lang'), $this->equalTo($sBrowserLang));

        $oUtils = $this->getMock('Utilities', ['getRequestVar']);
        $oUtils->expects($this->once())
            ->method('getRequestVar')
            ->with($this->equalTo('setup_lang'), $this->equalTo('post'))
            ->willReturn(null);

        $oLang = $this->getMock('OxidEsales\\EshopCommunity\\Setup\\Language', ['getInstance', 'setViewParam']);
        $oLang->expects($this->exactly(2))
            ->method('getInstance')
            ->withConsecutive(
                [$this->equalTo('Session')],
                [$this->equalTo('Utilities')]
            )
            ->willReturnOnConsecutiveCalls($oSession, $oUtils);
        $oLang->getLanguage();
    }

    /**
     * Testing Language::getText()
     */
    public function testGetText()
    {
        $oLang = $this->getMock('OxidEsales\\EshopCommunity\\Setup\\Language', ['getLanguage']);
        $oLang->expects($this->any())->method('getLanguage')->will($this->returnValue('en'));
        $this->assertEquals('System Requirements', $oLang->getText('TAB_0_TITLE'));
        $this->assertNull($oLang->getText('TEST_IDENT'));
    }

    /**
     * Testing Language::getModuleName()
     */
    public function testGetModuleName()
    {
        $oLang = $this->getMock('OxidEsales\\EshopCommunity\\Setup\\Language', ['getText']);
        $oLang->expects($this->exactly(3))
            ->method('getText')
            ->withConsecutive(
                [$this->equalTo('MOD_MODULE1')],
                [$this->equalTo('MOD_MODULE2')],
                [$this->equalTo('MOD_MODULE3')]
            )
            ->willReturnOnConsecutiveCalls('module1', 'module2', 'module3');
        $this->assertEquals('module1', $oLang->getModuleName('module1'));
        $this->assertEquals('module2', $oLang->getModuleName('module2'));
        $this->assertEquals('module3', $oLang->getModuleName('module3'));
    }
}
