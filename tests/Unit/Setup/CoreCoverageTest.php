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

namespace OxidEsales\EshopCommunity\Tests\Unit\Setup;

use OxidEsales\Eshop\Core\Exception\SystemComponentException;
use OxidEsales\EshopCommunity\Setup\Core;
use OxidEsales\EshopCommunity\Setup\Database;
use OxidEsales\EshopCommunity\Setup\Language;
use OxidEsales\EshopCommunity\Setup\Session;
use OxidEsales\EshopCommunity\Setup\Setup;
use OxidEsales\EshopCommunity\Setup\Utilities;

require_once getShopBasePath() . '/Setup/functions.php';

/**
 * Covers Setup/Core's instance-cache + getClass/__call/userDecided* helpers
 * which the existing CoreTest barely touches (only one test of getInstance).
 */
class CoreCoverageTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCoreInstanceCache();
    }

    protected function tearDown(): void
    {
        $this->resetCoreInstanceCache();
        parent::tearDown();
    }

    private function resetCoreInstanceCache(): void
    {
        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, []);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::getInstance
     * @covers \OxidEsales\EshopCommunity\Setup\Core::getClass
     */
    public function testGetInstanceResolvesShortNameToFullyQualifiedClass(): void
    {
        $core = new Core();
        $instance = $core->getInstance('Language');
        $this->assertInstanceOf(Language::class, $instance);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::getInstance
     */
    public function testGetInstanceCachesByClassNameAcrossCalls(): void
    {
        $core = new Core();
        $a = $core->getInstance('Setup');
        $b = $core->getInstance('Setup');
        $this->assertSame($a, $b);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::getInstance
     */
    public function testGetInstanceAcceptsFullyQualifiedClassNames(): void
    {
        $core = new Core();
        $a = $core->getInstance(Setup::class);
        $b = $core->getInstance('Setup');
        $this->assertSame($a, $b);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::__call
     */
    public function testCallExposesProtectedMethodsViaUnitPrefix(): void
    {
        $session = new class () extends Setup {
            protected function _testHelper()
            {
                return 'reached';
            }
        };
        $this->assertSame('reached', $session->UNITtestHelper());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::__call
     */
    public function testCallThrowsSystemComponentExceptionForUnknownMethod(): void
    {
        $core = new Core();
        $this->expectException(SystemComponentException::class);
        $this->expectExceptionMessage("Function 'doesNotExist'");
        $core->doesNotExist();
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::getSetupInstance
     */
    public function testGetSetupInstanceReturnsSetup(): void
    {
        $reflection = new \ReflectionMethod(Core::class, 'getSetupInstance');
        $reflection->setAccessible(true);
        $this->assertInstanceOf(Setup::class, $reflection->invoke(new Core()));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::getLanguageInstance
     */
    public function testGetLanguageInstanceReturnsLanguage(): void
    {
        $reflection = new \ReflectionMethod(Core::class, 'getLanguageInstance');
        $reflection->setAccessible(true);
        $this->assertInstanceOf(Language::class, $reflection->invoke(new Core()));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::getUtilitiesInstance
     */
    public function testGetUtilitiesInstanceReturnsUtilities(): void
    {
        $reflection = new \ReflectionMethod(Core::class, 'getUtilitiesInstance');
        $reflection->setAccessible(true);
        $this->assertInstanceOf(Utilities::class, $reflection->invoke(new Core()));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::getSessionInstance
     */
    public function testGetSessionInstanceReturnsSession(): void
    {
        $reflection = new \ReflectionMethod(Core::class, 'getSessionInstance');
        $reflection->setAccessible(true);
        $this->assertInstanceOf(Session::class, $reflection->invoke(new Core()));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::getDatabaseInstance
     */
    public function testGetDatabaseInstanceReturnsDatabase(): void
    {
        $reflection = new \ReflectionMethod(Core::class, 'getDatabaseInstance');
        $reflection->setAccessible(true);
        $this->assertInstanceOf(Database::class, $reflection->invoke(new Core()));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::userDecidedOverwriteDB
     */
    public function testUserDecidedOverwriteDBReturnsFalseWhenNothingFlagged(): void
    {
        $core = new Core();
        $reflection = new \ReflectionMethod(Core::class, 'userDecidedOverwriteDB');
        $reflection->setAccessible(true);
        $this->assertFalse($reflection->invoke($core));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::userDecidedOverwriteDB
     */
    public function testUserDecidedOverwriteDBHonoursOwGetParam(): void
    {
        $previousGet = $_GET['ow'] ?? null;
        $_GET['ow'] = '1';

        try {
            $core = new Core();
            $reflection = new \ReflectionMethod(Core::class, 'userDecidedOverwriteDB');
            $reflection->setAccessible(true);
            $this->assertTrue($reflection->invoke($core));
        } finally {
            if ($previousGet === null) {
                unset($_GET['ow']);
            } else {
                $_GET['ow'] = $previousGet;
            }
        }
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::userDecidedIgnoreDBWarning
     */
    public function testUserDecidedIgnoreDBWarningReturnsFalseWhenNothingFlagged(): void
    {
        $core = new Core();
        $reflection = new \ReflectionMethod(Core::class, 'userDecidedIgnoreDBWarning');
        $reflection->setAccessible(true);
        $this->assertFalse($reflection->invoke($core));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::userDecidedIgnoreDBWarning
     */
    public function testUserDecidedIgnoreDBWarningHonoursOwrecGetParam(): void
    {
        $previousGet = $_GET['owrec'] ?? null;
        $_GET['owrec'] = '1';

        try {
            $core = new Core();
            $reflection = new \ReflectionMethod(Core::class, 'userDecidedIgnoreDBWarning');
            $reflection->setAccessible(true);
            $this->assertTrue($reflection->invoke($core));
        } finally {
            if ($previousGet === null) {
                unset($_GET['owrec']);
            } else {
                $_GET['owrec'] = $previousGet;
            }
        }
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Core::__call
     */
    public function testCallDispatchesUNITPrefixToProtectedHelperWithMatchingArguments(): void
    {
        $stub = new class () extends Core {
            protected function _addOne($x)
            {
                return $x + 1;
            }
        };
        $this->assertSame(7, $stub->UNITaddOne(6));
    }
}
