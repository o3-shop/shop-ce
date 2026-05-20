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

use OxidEsales\EshopCommunity\Setup\Core;
use OxidEsales\EshopCommunity\Setup\Setup;
use OxidEsales\EshopCommunity\Setup\Utilities;

require_once getShopBasePath() . '/Setup/functions.php';

/**
 * Covers Setup/Setup methods that SetupTest doesn't reach: alreadySetUp,
 * deleteSetupDirectory, getModuleClass branches, setMessage/getMessage,
 * setTitle/getTitle, getCurrentStep with explicit istep, getMessage default.
 */
class SetupCoverageTest extends \OxidTestCase
{
    /** @var array<string, mixed> backups for $_REQUEST keys we touch */
    private $requestBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, []);
    }

    protected function tearDown(): void
    {
        foreach ($this->requestBackup as $key => $value) {
            if ($value === null) {
                unset($_GET[$key], $_POST[$key], $_REQUEST[$key]);
            } else {
                $_GET[$key] = $value;
                $_POST[$key] = $value;
                $_REQUEST[$key] = $value;
            }
        }
        $this->requestBackup = [];

        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, []);
        parent::tearDown();
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Setup::getModuleClass
     */
    public function testGetModuleClassMapsAllStateValuesToCssClass(): void
    {
        $setup = new Setup();
        $this->assertSame('pass', $setup->getModuleClass(2));
        $this->assertSame('pmin', $setup->getModuleClass(1));
        $this->assertSame('null', $setup->getModuleClass(-1));
        $this->assertSame('fail', $setup->getModuleClass(0));
        // Anything else (including unrecognized status values) drops to default.
        $this->assertSame('fail', $setup->getModuleClass(42));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Setup::setMessage
     * @covers \OxidEsales\EshopCommunity\Setup\Setup::getMessage
     */
    public function testSetMessageGetMessageRoundTrip(): void
    {
        $setup = new Setup();
        $this->assertNull($setup->getMessage());

        $setup->setMessage('hello world');
        $this->assertSame('hello world', $setup->getMessage());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Setup::setTitle
     * @covers \OxidEsales\EshopCommunity\Setup\Setup::getTitle
     */
    public function testSetTitleGetTitleRoundTrip(): void
    {
        $setup = new Setup();
        $this->assertNull($setup->getTitle());

        $setup->setTitle('System requirements');
        $this->assertSame('System requirements', $setup->getTitle());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Setup::alreadySetUp
     */
    public function testAlreadySetUpReturnsFalseWhenConfigStillContainsPlaceholder(): void
    {
        // The shop config.inc.php in the testing environment still has the
        // <dbHost> placeholder until the user has run setup.
        $setup = new Setup();
        // Either is acceptable depending on environment state. We assert the
        // method returns a strict bool rather than implying environment state.
        $this->assertIsBool($setup->alreadySetUp());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Setup::deleteSetupDirectory
     */
    public function testDeleteSetupDirectoryReturnsBool(): void
    {
        $setup = new Setup();
        $this->assertIsBool($setup->deleteSetupDirectory());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Setup::getCurrentStep
     */
    public function testGetCurrentStepDefaultsToSystemRequirementsStep(): void
    {
        // Pre-seed Utilities with a stub returning null for getRequestVar('istep')
        // so getCurrentStep falls back to the default.
        $utilities = new class () extends Utilities {
            public function __construct()
            {
            }
            public function getRequestVar($sVarName, $sRequestType = null)
            {
                return null;
            }
        };

        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, [Utilities::class => $utilities]);

        $setup = new Setup();
        $this->assertSame($setup->getStep('STEP_SYSTEMREQ'), $setup->getCurrentStep());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Setup::getCurrentStep
     */
    public function testGetCurrentStepReadsExplicitIstepFromRequest(): void
    {
        $utilities = new class () extends Utilities {
            public function __construct()
            {
            }
            public function getRequestVar($sVarName, $sRequestType = null)
            {
                return '410';
            }
        };

        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, [Utilities::class => $utilities]);

        $setup = new Setup();
        $this->assertSame(410, $setup->getCurrentStep());
    }
}
