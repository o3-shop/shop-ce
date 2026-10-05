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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use oxRegistry;
use oxTestModules;

/**
 * Language component test
 */

class CmpLangTest extends \OxidTestCase
{
    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getConfig();
        $this->getSession();
        oxTestModules::addFunction('oxutils', 'setseoact', '{oxRegistry::getUtils()->_blSeoIsActive = $aA[0];}');
        oxNew('oxutils')->setseoact(false);
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        oxRegistry::getUtils()->seoIsActive(true);
        parent::tearDown();
    }

    // if addVoucher fnc was executed
    public function testInitSetLinkRemoveSomeFnc()
    {
        $this->setConfigParam('bl_perfLoadLanguages', true);
        $oLangView = oxNew('oxcmp_lang');

        $oView = oxNew('oxubase');
        $oView->setClassName('basket');
        $oView->setFncName('addVoucher');
        $oConfig = $this->getConfig();
        // Clear existing active views so our view is the top active view (Pattern D)
        while (count($oConfig->getActiveViewsList())) {
            $oConfig->dropLastActiveView();
        }
        $oConfig->setActiveView($oView);
        $oLangView->setParent($oView);
        $oLangView->init();
        $oLang = $oLangView->render();

        // addVoucher is in the forbidden functions list, so fnc= should be stripped
        $this->assertStringContainsString('cl=basket', $oLang[0]->link);
        $this->assertStringNotContainsString('fnc=', $oLang[0]->link);
        $this->assertStringContainsString('cl=basket', $oLang[1]->link);
        $this->assertStringNotContainsString('fnc=', $oLang[1]->link);
    }

    public function testInitSetLink()
    {
        $this->setConfigParam('bl_perfLoadLanguages', true);
        $oLangView = oxNew('oxcmp_lang');

        $oView = oxNew('oxubase');
        $oView->setClassName('basket');
        $oView->setFncName('changebasket');
        $oConfig = $this->getConfig();
        // Clear existing active views so our view is the top active view (Pattern D)
        while (count($oConfig->getActiveViewsList())) {
            $oConfig->dropLastActiveView();
        }
        $oConfig->setActiveView($oView);
        $oLangView->setParent($oView);
        $oLangView->init();
        $oLang = $oLangView->render();

        // changebasket is NOT in the forbidden functions list, so fnc= should remain
        $this->assertStringContainsString('cl=basket', $oLang[0]->link);
        $this->assertStringContainsString('fnc=changebasket', $oLang[0]->link);
        $this->assertStringContainsString('cl=basket', $oLang[1]->link);
        $this->assertStringContainsString('fnc=changebasket', $oLang[1]->link);
    }
}
