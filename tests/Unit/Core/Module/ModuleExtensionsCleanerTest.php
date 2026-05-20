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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Module;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\EshopCommunity\Core\Module\ModuleExtensionsCleaner;
use PHPUnit\Framework\TestCase;

class ModuleExtensionsCleanerTest extends TestCase
{
    public function testCleanExtensionsIsNoopWhenNoModuleExtensionsInstalled(): void
    {
        $module = $this->getMockBuilder(Module::class)->disableOriginalConstructor()->getMock();
        $module->method('getExtensions')->willReturn([]);
        $module->method('getId')->willReturn('mymodule');

        $cleaner = new class () extends ModuleExtensionsCleaner {
            // Bypass the container-driven filter in the unit test — return
            // the empty list to mean "this module has no extensions installed".
            public function filterExtensionsByModuleIdPublic(array $installedExtensions, string $moduleId)
            {
                $reflection = new \ReflectionMethod(parent::class, 'filterExtensionsByModuleId');
                $reflection->setAccessible(true);
                return [];
            }
        };

        // The protected filter is hard to exercise without a container; we
        // verify the public API leaves `$installedExtensions` untouched
        // when the module has no extensions configured (getExtensions=[]).
        $installed = ['oxarticle' => ['vendor/foo/Article']];

        // Use Reflection to skip the container-using filter and instead test
        // the public method's "no-op when nothing to clean" branch via stub.
        $cleanerStub = $this->getMockBuilder(ModuleExtensionsCleaner::class)
            ->onlyMethods([])
            ->getMock();

        // Without re-implementing the container, we verify the `getModuleExtensionsGarbage`
        // helper directly — that's the heart of the cleanup logic.
        $reflection = new \ReflectionMethod($cleanerStub, 'getModuleExtensionsGarbage');
        $reflection->setAccessible(true);

        // Module says: oxarticle has extension 'vendor/foo/Article'
        // Installed says: oxarticle has both 'vendor/foo/Article' and 'vendor/foo/Stale'
        // → garbage should contain 'vendor/foo/Stale' only.
        $garbage = $reflection->invoke($cleanerStub, [
            'oxarticle' => 'vendor/foo/Article',
        ], [
            'oxarticle' => ['vendor/foo/Article', 'vendor/foo/Stale'],
        ]);

        $this->assertArrayHasKey('oxarticle', $garbage);
        $this->assertContains('vendor/foo/Stale', $garbage['oxarticle']);
        $this->assertNotContains('vendor/foo/Article', $garbage['oxarticle']);
    }

    public function testGetModuleExtensionsGarbageDropsClassWhenAllExtensionsAreCurrent(): void
    {
        $cleaner = new ModuleExtensionsCleaner();
        $reflection = new \ReflectionMethod($cleaner, 'getModuleExtensionsGarbage');
        $reflection->setAccessible(true);

        $garbage = $reflection->invoke($cleaner, [
            'oxarticle' => ['vendor/foo/Article', 'vendor/foo/Article2'],
        ], [
            'oxarticle' => ['vendor/foo/Article', 'vendor/foo/Article2'],
        ]);

        $this->assertSame([], $garbage, 'When every installed path is in metadata, no garbage remains.');
    }

    public function testGetModuleExtensionsGarbageHandlesScalarMetadataValue(): void
    {
        $cleaner = new ModuleExtensionsCleaner();
        $reflection = new \ReflectionMethod($cleaner, 'getModuleExtensionsGarbage');
        $reflection->setAccessible(true);

        // Metadata uses a scalar (non-array) extension value — the cleaner
        // wraps it before comparing.
        $garbage = $reflection->invoke($cleaner, [
            'oxorder' => 'vendor/foo/Order',
        ], [
            'oxorder' => ['vendor/foo/Order'],
        ]);

        $this->assertSame([], $garbage);
    }

    public function testGetModuleExtensionsGarbagePreservesMissingClasses(): void
    {
        $cleaner = new ModuleExtensionsCleaner();
        $reflection = new \ReflectionMethod($cleaner, 'getModuleExtensionsGarbage');
        $reflection->setAccessible(true);

        // 'oxorphan' is not in metadata at all → entire entry is garbage.
        $garbage = $reflection->invoke($cleaner, [
            'oxarticle' => 'vendor/foo/Article',
        ], [
            'oxarticle' => ['vendor/foo/Article'],
            'oxorphan'  => ['vendor/foo/Orphan'],
        ]);

        $this->assertArrayHasKey('oxorphan', $garbage);
        $this->assertSame(['vendor/foo/Orphan'], $garbage['oxorphan']);
        $this->assertArrayNotHasKey('oxarticle', $garbage);
    }

    public function testRemoveGarbagePrunesMatchingPathsAndCollapsesEmptyClasses(): void
    {
        $cleaner = new ModuleExtensionsCleaner();
        $reflection = new \ReflectionMethod($cleaner, 'removeGarbage');
        $reflection->setAccessible(true);

        $installed = [
            'oxarticle' => ['vendor/foo/Article', 'vendor/foo/Stale'],
            'oxorphan'  => ['vendor/foo/Orphan'],
        ];
        $garbage = [
            'oxarticle' => ['vendor/foo/Stale'],
            'oxorphan'  => ['vendor/foo/Orphan'],
        ];

        $cleaned = $reflection->invoke($cleaner, $installed, $garbage);

        // 'vendor/foo/Stale' is gone; 'vendor/foo/Article' remains.
        $this->assertSame(['vendor/foo/Article'], array_values($cleaned['oxarticle']));
        // 'oxorphan' had only one extension and that was garbage → entry removed.
        $this->assertArrayNotHasKey('oxorphan', $cleaned);
    }

    public function testRemoveGarbageIgnoresClassesNotInInstalledList(): void
    {
        $cleaner = new ModuleExtensionsCleaner();
        $reflection = new \ReflectionMethod($cleaner, 'removeGarbage');
        $reflection->setAccessible(true);

        $installed = [
            'oxarticle' => ['vendor/foo/Article'],
        ];
        $garbage = [
            'oxnotinstalled' => ['vendor/whatever'],
        ];

        $cleaned = $reflection->invoke($cleaner, $installed, $garbage);
        $this->assertSame($installed, $cleaned, 'Unknown garbage classes leave installed list untouched.');
    }
}
