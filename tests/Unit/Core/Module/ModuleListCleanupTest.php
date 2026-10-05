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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Module;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Module\ModuleList;
use OxidEsales\Eshop\Core\Module\ModuleMetadataValidator;
use OxidEsales\Eshop\Core\Module\ModuleValidatorFactory;

/**
 * Drives the ModuleList::cleanup / getDeletedExtensions paths via a subclass
 * that overrides the validator factory, module factory, and module-id source
 * — none of these have public seams elsewhere, but they can all be replaced
 * by subclassing because they're protected/public methods on ModuleList.
 */
class ModuleListCleanupTest extends \OxidTestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::getDeletedExtensions
     */
    public function testGetDeletedExtensionsFlagsModulesWithMissingMetadata(): void
    {
        $validator = $this->createMock(ModuleMetadataValidator::class);
        $validator->method('validate')->willReturn(false);

        $factory = $this->createMock(ModuleValidatorFactory::class);
        $factory->method('getModuleMetadataValidator')->willReturn($validator);

        $module = $this->getMockBuilder(Module::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setModuleData'])
            ->getMock();

        $list = new class ($factory, $module) extends ModuleList {
            /** @var ModuleValidatorFactory */
            private $injectedFactory;
            /** @var Module */
            private $injectedModule;
            public function __construct(ModuleValidatorFactory $factory, Module $module)
            {
                parent::__construct();
                $this->injectedFactory = $factory;
                $this->injectedModule = $module;
            }
            public function getModuleValidatorFactory()
            {
                return $this->injectedFactory;
            }
            public function getModule()
            {
                return $this->injectedModule;
            }
            public function getModuleIds()
            {
                return ['mod_a', 'mod_b'];
            }
        };

        $deleted = $list->getDeletedExtensions();

        $this->assertSame(['files' => ['mod_a/metadata.php']], $deleted['mod_a']);
        $this->assertSame(['files' => ['mod_b/metadata.php']], $deleted['mod_b']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::getDeletedExtensions
     */
    public function testGetDeletedExtensionsReturnsEmptyForValidModulesWithoutInvalidExtensions(): void
    {
        $validator = $this->createMock(ModuleMetadataValidator::class);
        $validator->method('validate')->willReturn(true);

        $factory = $this->createMock(ModuleValidatorFactory::class);
        $factory->method('getModuleMetadataValidator')->willReturn($validator);

        $module = $this->getMockBuilder(Module::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setModuleData'])
            ->getMock();

        $list = new class ($factory, $module) extends ModuleList {
            private $injectedFactory;
            private $injectedModule;
            public function __construct($factory, $module)
            {
                parent::__construct();
                $this->injectedFactory = $factory;
                $this->injectedModule = $module;
            }
            public function getModuleValidatorFactory()
            {
                return $this->injectedFactory;
            }
            public function getModule()
            {
                return $this->injectedModule;
            }
            public function getModuleIds()
            {
                return ['mod_ok'];
            }
            public function getModuleExtensions($moduleId)
            {
                // No extensions registered for the module — _getInvalidExtensions
                // walks an empty map and returns [].
                return [];
            }
        };

        $this->assertSame([], $list->getDeletedExtensions());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleList::cleanup
     */
    public function testCleanupIsNoopWhenThereAreNoDeletedExtensions(): void
    {
        $list = new class () extends ModuleList {
            public function getDeletedExtensions()
            {
                return [];
            }
        };

        // Just runs without throwing — there's nothing to deactivate.
        $list->cleanup();
        $this->assertTrue(true);
    }
}
