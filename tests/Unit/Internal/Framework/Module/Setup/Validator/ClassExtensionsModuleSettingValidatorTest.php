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

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Validator\ClassExtensionsValidator;
use PHPUnit\Framework\TestCase;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\InvalidClassExtensionNamespaceException;

/**
 * @internal
 */
class ClassExtensionsModuleSettingValidatorTest extends TestCase
{
    public function testValidClassExtensionsModuleSetting()
    {
        $anyExistentClass = self::class;

        $shopAdapter = $this->getMockBuilder(ShopAdapterInterface::class)->getMock();
        $shopAdapter
            ->method('isNamespace')
            ->willReturn(true);
        $shopAdapter
            ->method('isShopEditionNamespace')
            ->willReturn(false);
        $shopAdapter
            ->method('isShopUnifiedNamespace')
            ->willReturn(true);

        $validator = new ClassExtensionsValidator($shopAdapter);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->addClassExtension(new ClassExtension($anyExistentClass, 'moduleClass'));

        $this->assertNull(
            $validator->validate($moduleConfiguration, 1)
        );
    }

    public function testNamespaceOfPatchedClassMustNotBeShopEditionNamespace()
    {
        $shopAdapter = $this->getMockBuilder(ShopAdapterInterface::class)->getMock();
        $shopAdapter
            ->method('isNamespace')
            ->willReturn(true);
        $shopAdapter
            ->method('isShopEditionNamespace')
            ->willReturn(true);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->addClassExtension(new ClassExtension('shopClass', 'moduleClass'));

        
        $validator = new ClassExtensionsValidator($shopAdapter);
        $this->expectException(InvalidClassExtensionNamespaceException::class);
        $validator->validate($moduleConfiguration, 1);
    }

    public function testNamespaceOfPatchedClassIsShopUnifiedNamespaceButClassDoesNotExist()
    {
        $shopAdapter = $this->getMockBuilder(ShopAdapterInterface::class)->getMock();
        $shopAdapter
            ->method('isNamespace')
            ->willReturn(true);
        $shopAdapter
            ->method('isShopEditionNamespace')
            ->willReturn(false);
        $shopAdapter
            ->method('isShopUnifiedNamespace')
            ->willReturn(true);

        $validator = new ClassExtensionsValidator($shopAdapter);

        $moduleConfiguration = new ModuleConfiguration();
        $moduleConfiguration->addClassExtension(new ClassExtension('nonExistentClass', 'moduleClass'));

        $this->expectException(InvalidClassExtensionNamespaceException::class);
        $validator->validate($moduleConfiguration, 1);
    }
}
