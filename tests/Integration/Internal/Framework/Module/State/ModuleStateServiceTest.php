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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Module\State;

use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ContextStub;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ModuleStateServiceTest extends TestCase
{
    use ContainerTrait;

    private $moduleStateService;

    public function setup(): void
    {
        $this->moduleStateService = $this->get(ModuleStateServiceInterface::class);

        /** @var ContextStub $contextStub */
        $contextStub = $this->get(ContextInterface::class);
        $contextStub->setAllShopIds([1,2]);

        if ($this->moduleStateService->isActive('testModuleId', 1)) {
            $this->moduleStateService->setDeactivated('testModuleId', 1);
        }

        if ($this->moduleStateService->isActive('testModuleId', 2)) {
            $this->moduleStateService->setDeactivated('testModuleId', 2);
        }

        parent::setUp();
    }

    public function testSetActive()
    {
        $this->assertFalse(
            $this->moduleStateService->isActive('testModuleId', 1)
        );
        $this->assertFalse(
            $this->moduleStateService->isActive('testModuleId', 2)
        );

        $this->moduleStateService->setActive('testModuleId', 1);
        $this->moduleStateService->setActive('testModuleId', 2);

        $this->assertTrue(
            $this->moduleStateService->isActive('testModuleId', 1)
        );
        $this->assertTrue(
            $this->moduleStateService->isActive('testModuleId', 2)
        );
    }

    public function testSetActiveIfActiveStateIsAlreadySet()
    {
        $this->expectException(\OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateIsAlreadySetException::class);
        $this->moduleStateService->setActive('testModuleId', 1);
        $this->moduleStateService->setActive('testModuleId', 1);
    }

    public function testSetDeactivated()
    {
        $this->moduleStateService->setActive('testModuleId', 1);

        $this->moduleStateService->setDeactivated('testModuleId', 1);

        $this->assertFalse(
            $this->moduleStateService->isActive('testModuleId', 1)
        );
    }

    public function testSetDeactivatedIfActiveStateIsNotSet()
    {
        $this->expectException(
            \OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateIsAlreadySetException::class
        );
        $this->moduleStateService = $this->get(ModuleStateServiceInterface::class);

        $this->moduleStateService->setDeactivated('testModuleId', 1);
    }

}
