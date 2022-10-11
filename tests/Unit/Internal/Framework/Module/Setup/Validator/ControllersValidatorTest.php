<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setup\Validator;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ControllersDuplicationModuleConfigurationException;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Validator\ControllersValidator;
use PHPUnit\Framework\TestCase;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\Controller;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class ControllersValidatorTest extends TestCase
{
    public function testValidationWithCorrectSetting(): void
    {
        $shopAdapter = $this->getMockBuilder(ShopAdapterInterface::class)->getMock();
        $shopAdapter
            ->method('getShopControllerClassMap')
            ->willReturn([
                'shopControllerName' => 'shopControllerNamespace',
            ]);

        $shopConfigurationSetting = new ShopConfigurationSetting();
        $shopConfigurationSetting->setValue(
            [
                'moduleId' => [
                    'alreadyActiveModuleControllerName' => 'alreadyActiveModuleControllerNamepace'
                ],
            ]
        );

        $shopConfigurationSettingDao = $this->getMockBuilder(ShopConfigurationSettingDaoInterface::class)->getMock();
        $shopConfigurationSettingDao
            ->method('get')
            ->willReturn($shopConfigurationSetting);

        $validator = new ControllersValidator(
            $shopAdapter,
            $shopConfigurationSettingDao,
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->addController(
            new Controller(
                'newModuleControllerName',
                'newModuleControllerNamepace'
            )
        );

        $validator->validate($moduleConfiguration, 1);
    }

    public function testValidationWithDuplicatedControllerNamespace(): void
    {
        $this->expectException(ControllersDuplicationModuleConfigurationException::class);

        $shopAdapter = $this->getMockBuilder(ShopAdapterInterface::class)->getMock();
        $shopAdapter
            ->method('getShopControllerClassMap')
            ->willReturn([
                'anotherModuleControllerId' => 'duplicatedNamespace',
            ]);

        $validator = new ControllersValidator(
            $shopAdapter,
            $this->getMockBuilder(ShopConfigurationSettingDaoInterface::class)->getMock(),
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->addController(
            new Controller('someId', 'duplicatedNamespace')
        );

        $validator->validate($moduleConfiguration, 1);
    }

    public function testValidationWithDuplicatedControllerId(): void
    {
        $this->expectException(ControllersDuplicationModuleConfigurationException::class);

        $shopAdapter = $this->getMockBuilder(ShopAdapterInterface::class)->getMock();
        $shopAdapter
            ->method('getShopControllerClassMap')
            ->willReturn([
                'duplicatedid' => 'anotherModuleNamespace',
            ]);

        $validator = new ControllersValidator(
            $shopAdapter,
            $this->getMockBuilder(ShopConfigurationSettingDaoInterface::class)->getMock(),
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->addController(
            new Controller('duplicatedId', 'controllerNamespace')
        );

        $validator->validate($moduleConfiguration, 1);
    }

    public function testValidatorLogsErrorIfModuleControllerAlreadyExistsInControllersMap(): void
    {
        $shopAdapter = $this->getMockBuilder(ShopAdapterInterface::class)->getMock();
        $shopAdapter
            ->method('getShopControllerClassMap')
            ->willReturn([
                'sameid' => 'sameNamespace',
            ]);

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())->method('error');

        $validator = new ControllersValidator(
            $shopAdapter,
            $this->getMockBuilder(ShopConfigurationSettingDaoInterface::class)->getMock(),
            $logger
        );

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->setId('testModule');
        $moduleConfiguration->addController(
            new Controller('sameId', 'sameNamespace')
        );

        $validator->validate($moduleConfiguration, 1);
    }
}
