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

use DateTime;

/**
 * @covers \OxidEsales\Eshop\Core\SystemEventHandler
 */
class SystemEventHandlerTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    /**
     * @return null|void
     */
    public function setup(): void
    {
        parent::setUp();
        $this->getConfig()->saveShopConfVar('str', 'sOnlineLicenseNextCheckTime', null);
        $this->getConfig()->saveShopConfVar('str', 'sOnlineLicenseCheckTime', null);
        $this->getConfig()->setConfigParam('blSendTechnicalInformationToOxid', true);
    }

    public function testOnAdminLoginSendModuleInformation()
    {
        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);

        $this->assertModuleVersionNotifierCalled($systemEventHandler, 'once');

        $systemEventHandler->onAdminLogin(1);
    }

    public function testOnAdminLoginDoNotSendModuleInformationWhenNotConfigured()
    {
        if ($this->getTestConfig()->getShopEdition() !== 'CE') {
            $this->markTestSkipped('This test is for Community edition only');
        }

        $this->getConfig()->setConfigParam('blSendTechnicalInformationToOxid', false);

        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);

        $this->assertModuleVersionNotifierCalled($systemEventHandler, 'never');

        $systemEventHandler->onAdminLogin(1);
    }

    public function testOnShopEndSendShopInformationForFirstTime()
    {
        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);

        $onlineLicenseCheckMock = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Test that shop online validation was performed.
        $onlineLicenseCheckMock->expects($this->once())->method("validateShopSerials");

        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheck $onlineLicenseCheck */
        $onlineLicenseCheck = $onlineLicenseCheckMock;
        $systemEventHandler->setOnlineLicenseCheck($onlineLicenseCheck);

        $systemEventHandler->onShopEnd();
    }

    public function testOnShopNedSendShopInformationNotFirstTime()
    {
        $onlineLicenseCheckValidityTime = 24 * 60 * 60;
        $onlineLicenseInvalidTime = time() - $onlineLicenseCheckValidityTime;
        $this->setConfigParam('sOnlineLicenseNextCheckTime', $onlineLicenseInvalidTime);
        $this->setConfigParam('sOnlineLicenseCheckTime', date('H:i:s'));

        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);

        $onlineLicenseCheckMock = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Test that shop online validation was performed.
        $onlineLicenseCheckMock->expects($this->once())->method("validateShopSerials");

        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheck $onlineLicenseCheck */
        $onlineLicenseCheck = $onlineLicenseCheckMock;
        $systemEventHandler->setOnlineLicenseCheck($onlineLicenseCheck);

        $systemEventHandler->onShopEnd();
    }

    public function testOnShopEndDoNotSendShopInformationTimeNotExpired()
    {
        $this->setConfigParam('sOnlineLicenseNextCheckTime', time() + (24 * 60 * 60));
        $this->setConfigParam('sOnlineLicenseCheckTime', date('H:i:s'));

        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);

        $onlineLicenseCheckMock = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Test that shop online validation was not performed.
        $onlineLicenseCheckMock->expects($this->never())->method("validateShopSerials");

        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheck $onlineLicenseCheck */
        $onlineLicenseCheck = $onlineLicenseCheckMock;
        $systemEventHandler->setOnlineLicenseCheck($onlineLicenseCheck);

        $systemEventHandler->onShopEnd();
    }

    public function testOnEndStartDoNotSendShopInformationIfSearchEngine()
    {
        /** @var \OxidEsales\Eshop\Core\Utils $utils */
        $utils = \OxidEsales\Eshop\Core\Registry::getUtils();
        $utils->setSearchEngine(true);

        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);

        $onlineLicenseCheckMock = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Test that shop online validation was not performed.
        $onlineLicenseCheckMock->expects($this->never())->method("validateShopSerials");

        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheck $onlineLicenseCheck */
        $onlineLicenseCheck = $onlineLicenseCheckMock;
        $systemEventHandler->setOnlineLicenseCheck($onlineLicenseCheck);

        $systemEventHandler->onShopEnd();
    }

    public function testOnShopEndSetWhenToSendInformationForFirstTimeCorrectFormat()
    {
        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);

        $onlineLicenseCheck = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheck $onlineLicenseCheck */
        $systemEventHandler->setOnlineLicenseCheck($onlineLicenseCheck);
        $systemEventHandler->onShopEnd();

        $checkTime = $this->getConfigParam('sOnlineLicenseCheckTime');
        $this->assertNotNull($checkTime);
        $this->assertRegExp('/\d{1,2}:\d{1,2}:\d{1,2}/', $checkTime);

        return $checkTime;
    }

    /**
     * @param string $checkTime
     *
     * @depends testOnShopEndSetWhenToSendInformationForFirstTimeCorrectFormat
     */
    public function testInformationSendTimeIsBetweenCorrectHours($checkTime)
    {
        $hourToCheck = explode(':', $checkTime);
        $hour = $hourToCheck[0];
        $this->assertTrue($hour < 24, 'Get hour: '. $hour);
        $this->assertTrue($hour > 7, 'Get hour: '. $hour);
    }

    public function testOnShopEndDoNotChangeWhenToSendInformation()
    {
        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);

        $onlineLicenseCheck = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheck $onlineLicenseCheck */
        $systemEventHandler->setOnlineLicenseCheck($onlineLicenseCheck);

        $systemEventHandler->onShopEnd();
        $checkTime1 = $this->getConfigParam('sOnlineLicenseCheckTime');

        $systemEventHandler->onShopEnd();
        $checkTime2 = $this->getConfigParam('sOnlineLicenseCheckTime');

        $this->assertSame($checkTime1, $checkTime2);
    }

    public function testOnShopEndLicenseCheckNextSendTimeUpdated()
    {
        // 2014-05-13 19:53:20
        $currentTime = 1400000000;
        $checkHours = 17;
        $checkMinutes = 10;
        $checkSeconds = 15;
        $checkTime = $checkHours . ':' . $checkMinutes . ':' . $checkSeconds;
        $this->prepareCurrentTime($currentTime);
        $this->getConfig()->saveShopConfVar('str', 'sOnlineLicenseNextCheckTime', $currentTime - (24 * 60 * 60));
        $this->getConfig()->saveShopConfVar('str', 'sOnlineLicenseCheckTime', $checkTime);

        $nextCheckTime = new DateTime('tomorrow');
        $nextCheckTime->setTime($checkHours, $checkMinutes, $checkSeconds);
        $expectedNextCheckTime = $nextCheckTime->getTimestamp();

        $onlineLicenseCheckMock = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class)
            ->disableOriginalConstructor()
            ->getMock();
        $onlineLicenseCheckMock->expects($this->any())->method("validateShopSerials");

        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheck $onlineLicenseCheck */
        $onlineLicenseCheck = $onlineLicenseCheckMock;

        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);
        $systemEventHandler->setOnlineLicenseCheck($onlineLicenseCheck);

        $systemEventHandler->onShopEnd();

        $nextCheckTime = $this->getConfigParam('sOnlineLicenseNextCheckTime');
        $this->assertSame($expectedNextCheckTime, $nextCheckTime);
    }

    public function testFormationOfOnlineLicenseCheckObjectWhenNotSet()
    {
        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);
        $this->assertInstanceOf('\OxidEsales\Eshop\Core\OnlineLicenseCheck', $systemEventHandler->getOnlineLicenseCheck());
    }

    public function testOnShopStartSaveServerInformation()
    {
        $onlineLicenseCheck = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class)
            ->disableOriginalConstructor()
            ->getMock();

        $appServer = $this->getMockBuilder('\OxidEsales\Eshop\Core\Service\ApplicationServerServiceInterface')->getMock();
        $appServer->expects($this->any())->method('loadActiveAppServerList');

        $systemEventHandler = $this->getMockBuilder(\OxidEsales\Eshop\Core\SystemEventHandler::class)
            ->setMethods(['getAppServerService', 'pageStart'])
            ->getMock();
        $systemEventHandler->expects($this->any())->method('getAppServerService')->will($this->returnValue($appServer));
        $systemEventHandler->setOnlineLicenseCheck($onlineLicenseCheck);

        $systemEventHandler->onShopStart();
    }

    public function testShopInformationSendingWhenSendingIsAllowed()
    {
        $this->prepareCurrentTime(1400000000);
        $this->getConfig()->setConfigParam('blSendTechnicalInformationToOxid', true);

        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);

        $onlineLicenseCheck = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class)
            ->disableOriginalConstructor()
            ->getMock();
        $onlineLicenseCheck->expects($this->once())->method("validateShopSerials");
        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheck $onlineLicenseCheck */

        $systemEventHandler->setOnlineLicenseCheck($onlineLicenseCheck);

        $systemEventHandler->onShopEnd();
    }

    public function testShopInformationSendingWhenSendingIsNotAllowedInCommunityEdition()
    {
        if ($this->getTestConfig()->getShopEdition() != 'CE') {
            $this->markTestSkipped('This test is for Community edition only.');
        }
        $this->prepareCurrentTime(1400000000);
        $this->getConfig()->setConfigParam('blSendTechnicalInformationToOxid', false);

        $systemEventHandler = oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);

        $onlineLicenseCheck = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineLicenseCheck::class)
            ->disableOriginalConstructor()
            ->getMock();
        $onlineLicenseCheck->expects($this->never())->method("validateShopSerials");
        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheck $onlineLicenseCheck */

        $systemEventHandler->setOnlineLicenseCheck($onlineLicenseCheck);

        $systemEventHandler->onShopStart();
    }

    /**
     * @param int $currentTime
     */
    private function prepareCurrentTime($currentTime)
    {
        $this->setTime($currentTime);
    }

    /**
     * Inject mock to check if version notifier triggered correct number of times.
     * Etc. once or never.
     *
     * @param SystemEventHandler $systemEventHandler object in which mock is injected.
     * @param string $timesCalled PHPUnit method name of how many times method should be called.
     */
    private function assertModuleVersionNotifierCalled($systemEventHandler, $timesCalled)
    {
        $moduleNotifierMock = $this->getMockBuilder(\OxidEsales\Eshop\Core\OnlineModuleVersionNotifier::class)
            ->disableOriginalConstructor()
            ->getMock();
        $moduleNotifierMock->expects($this->$timesCalled())->method("versionNotify");

        /** @var \OxidEsales\Eshop\Core\OnlineModuleVersionNotifier $moduleNotifier */
        $moduleNotifier = $moduleNotifierMock;
        $systemEventHandler->setOnlineModuleVersionNotifier($moduleNotifier);
    }
}
