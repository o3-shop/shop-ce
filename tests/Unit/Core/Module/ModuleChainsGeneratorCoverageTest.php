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

use OxidEsales\Eshop\Core\Module\ModuleVariablesLocator;
use OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator;

/**
 * Covers the ModuleChainsGenerator branches the existing test
 * (testGetActiveModuleChain, testOnModuleExtensionCreationError) doesn't:
 * the alias/class chain merge, the happy-path createClassChain, the
 * deprecated cleanModuleFromClassChain helper, and handleSpecialCases.
 */
class ModuleChainsGeneratorCoverageTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    private function makeLocator(array $valueMap): ModuleVariablesLocator
    {
        $locator = $this->getMockBuilder(ModuleVariablesLocator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getModuleVariable'])
            ->getMock();
        $locator->method('getModuleVariable')
            ->willReturnCallback(function ($key) use ($valueMap) {
                return $valueMap[$key] ?? null;
            });
        return $locator;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::getActiveChain
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::getFullChain
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::getModuleVariablesLocator
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::getClassExtensionChain
     */
    public function testGetActiveChainReturnsExtensionsForExtendedClass(): void
    {
        $locator = $this->makeLocator([
            'aModules' => [
                'oxidesales\eshop\application\model\article' => 'foo/Ext1&foo/Ext2',
            ],
        ]);

        $generator = new ModuleChainsGenerator($locator);

        $chain = $generator->getActiveChain('OxidEsales\Eshop\Application\Model\Article');
        $this->assertSame(['foo/Ext1', 'foo/Ext2'], $chain);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::getFullChain
     */
    public function testGetFullChainReturnsEmptyArrayForUnextendedClass(): void
    {
        $locator = $this->makeLocator([
            'aModules' => [
                'someotherclass' => 'foo/Ext1',
            ],
        ]);

        $generator = new ModuleChainsGenerator($locator);
        $this->assertSame([], $generator->getFullChain('OxidEsales\Eshop\Application\Model\Article', 'oxArticle'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::getFullChain
     */
    public function testGetFullChainMergesClassNameAndAliasInAModulesOrder(): void
    {
        // Two registrations for the same class — once by full namespace and
        // once by the legacy alias. getFullChain must merge them in the order
        // they appear in aModules.
        $locator = $this->makeLocator([
            'aModules' => [
                'oxarticle' => 'foo/AliasExt',
                'oxidesales\eshop\application\model\article' => 'foo/NamespacedExt',
            ],
        ]);

        $generator = new ModuleChainsGenerator($locator);
        $chain = $generator->getFullChain(
            'OxidEsales\Eshop\Application\Model\Article',
            'oxarticle'
        );

        // alias appears before namespaced in aModules → alias chain first.
        $this->assertSame(['foo/AliasExt', 'foo/NamespacedExt'], $chain);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::createClassChain
     */
    public function testCreateClassChainReturnsOriginalClassWhenNoExtensions(): void
    {
        $locator = $this->makeLocator(['aModules' => []]);
        $generator = new ModuleChainsGenerator($locator);

        $this->assertSame(
            'OxidEsales\Eshop\Application\Model\Article',
            $generator->createClassChain('OxidEsales\Eshop\Application\Model\Article')
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::cleanModuleFromClassChain
     */
    public function testCleanModuleFromClassChainRemovesMatchingEntries(): void
    {
        $locator = $this->makeLocator([
            'aModuleExtensions' => [
                'mymod' => ['mymod/Ext1', 'mymod/Ext2'],
            ],
        ]);
        $generator = new ModuleChainsGenerator($locator);

        $clean = $generator->cleanModuleFromClassChain(
            'mymod',
            ['mymod/Ext1', 'other/Ext', 'mymod/Ext2']
        );

        $this->assertSame(['other/Ext'], array_values($clean));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::cleanModuleFromClassChain
     */
    public function testCleanModuleFromClassChainKeepsChainWhenModuleHasNoRegisteredExtensions(): void
    {
        $locator = $this->makeLocator(['aModuleExtensions' => []]);
        $generator = new ModuleChainsGenerator($locator);

        $clean = $generator->cleanModuleFromClassChain(
            'mymod',
            ['other/Ext1', 'other/Ext2']
        );

        $this->assertSame(['other/Ext1', 'other/Ext2'], $clean);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::filterInactiveExtensions
     */
    public function testFilterInactiveExtensionsReturnsChainWhenNoModulesDisabled(): void
    {
        // Use a subclass that overrides getDisabledModuleIds() to skip the
        // container hop — that path is exercised by integration tests.
        $generator = new class ($this->makeLocator(['aModuleExtensions' => []])) extends ModuleChainsGenerator {
            public function getDisabledModuleIds()
            {
                return [];
            }
        };

        $chain = ['mymod/Ext1', 'other/Ext2'];
        $this->assertSame($chain, $generator->filterInactiveExtensions($chain));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::handleSpecialCases
     */
    public function testHandleSpecialCasesIsNoopForNonConfigClass(): void
    {
        $locator = $this->makeLocator([]);
        $generator = new ModuleChainsGenerator($locator);

        $reflection = new \ReflectionMethod(ModuleChainsGenerator::class, 'handleSpecialCases');
        $reflection->setAccessible(true);

        // Capture current Config in Registry; calling handleSpecialCases for a
        // non-config class must NOT replace it.
        $before = \OxidEsales\Eshop\Core\Registry::getConfig();
        $reflection->invoke($generator, \stdClass::class);
        $this->assertSame($before, \OxidEsales\Eshop\Core\Registry::getConfig());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::handleSpecialCases
     */
    public function testHandleSpecialCasesReplacesConfigForOxConfigChain(): void
    {
        $locator = $this->makeLocator([]);
        $generator = new ModuleChainsGenerator($locator);

        $reflection = new \ReflectionMethod(ModuleChainsGenerator::class, 'handleSpecialCases');
        $reflection->setAccessible(true);

        $before = \OxidEsales\Eshop\Core\Registry::getConfig();
        $reflection->invoke($generator, \OxidEsales\Eshop\Core\Config::class);
        $after = \OxidEsales\Eshop\Core\Registry::getConfig();

        // A fresh Config instance has been installed in the registry.
        $this->assertNotSame($before, $after);
        $this->assertInstanceOf(\OxidEsales\Eshop\Core\Config::class, $after);

        // Restore for the next test.
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Config::class, $before);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\Module\ModuleChainsGenerator::isUnitTest
     */
    public function testIsUnitTestReturnsTrueWhenOxidPhpUnitConstantIsDefined(): void
    {
        $locator = $this->makeLocator([]);
        $generator = new ModuleChainsGenerator($locator);

        $reflection = new \ReflectionMethod(ModuleChainsGenerator::class, 'isUnitTest');
        $reflection->setAccessible(true);
        // OXID_PHP_UNIT is set by the o3-shop testing-library bootstrap.
        $this->assertSame(defined('OXID_PHP_UNIT'), $reflection->invoke($generator));
    }
}
