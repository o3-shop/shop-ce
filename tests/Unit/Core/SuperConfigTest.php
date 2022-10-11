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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use oxField;
use oxRegistry;

class SuperConfigTest extends \OxidTestCase
{
    public function testSetGetConfig()
    {
        $oOxSuperCfg = oxNew('oxSuperCfg');
        $oOxSuperCfg->setConfig(null);
        $oConfig = $this->getConfig();
        $this->assertEquals($oConfig, $oOxSuperCfg->getConfig());

        $config = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array('getConfigParam'));
        $config->expects($this->once())->method('getConfigParam')->will($this->returnValue(true));
        $oOxSuperCfg->setConfig($config);
        $this->assertTrue($oOxSuperCfg->getConfig()->getConfigParam('xxx'));
    }

    public function testSetGetSession()
    {
        $oOxSuperCfg = oxNew('oxSuperCfg');
        $oOxSuperCfg->setSession(null);
        $oSession = oxRegistry::getSession();
        $this->assertEquals($oSession, $oOxSuperCfg->getSession());

        $oSession = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array('getId'));
        $oSession->expects($this->once())->method('getId')->will($this->returnValue('xxx'));
        $oOxSuperCfg->setSession($oSession);
        $this->assertEquals('xxx', $oOxSuperCfg->getSession()->getId());
    }

    public function testSetGetUser()
    {
        $oOxSuperCfg = oxNew('oxSuperCfg');
        $oOxSuperCfg->setUser(null);
        $this->getSession()->setVariable('usr', 'oxdefaultadmin');
        $oActUser = oxNew('oxuser');
        $oActUser->loadActiveUser();
        $this->assertEquals(oxADMIN_LOGIN, $oOxSuperCfg->getUser()->oxuser__oxusername->value);
        $this->getSession()->setVariable('usr', null);
        $oActUser = oxNew('oxuser');
        $oActUser->oxuser__oxusername = new oxField('testUser', oxField::T_RAW);
        $oOxSuperCfg->setUser($oActUser);
        $this->assertEquals('testUser', $oOxSuperCfg->getUser()->oxuser__oxusername->value);
    }

    public function testSetGetAdminMode()
    {
        $oOxSuperCfg = oxNew('oxSuperCfg');
        $this->assertFalse($oOxSuperCfg->isAdmin());

        $oOxSuperCfg->setAdminMode(true);
        $this->assertTrue($oOxSuperCfg->isAdmin());
    }
}
