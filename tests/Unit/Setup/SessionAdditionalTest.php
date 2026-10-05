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
use OxidEsales\EshopCommunity\Setup\Session;
use OxidEsales\EshopCommunity\Setup\Utilities;

require_once getShopBasePath() . '/Setup/functions.php';

/**
 * Covers the Session methods that SessionTest leaves untouched:
 * _initSessionData() request-param branches, setIsNewSession/getIsNewSession,
 * setSessionParam writes, and _getSessionData reference returning $_SESSION.
 */
class SessionAdditionalTest extends \OxidTestCase
{
    /** @var array<string,mixed> snapshot of $_POST keys we will overwrite */
    private $postBackup = [];

    protected function setUp(): void
    {
        session_cache_limiter('no-cache');
        parent::setUp();
        $this->resetCoreInstanceCache();
    }

    protected function tearDown(): void
    {
        foreach ($this->postBackup as $key => $value) {
            if ($value === null) {
                unset($_POST[$key]);
            } else {
                $_POST[$key] = $value;
            }
        }
        $this->postBackup = [];
        $this->resetCoreInstanceCache();
        parent::tearDown();
    }

    private function setPost(string $key, $value): void
    {
        if (!array_key_exists($key, $this->postBackup)) {
            $this->postBackup[$key] = $_POST[$key] ?? null;
        }
        $_POST[$key] = $value;
    }

    private function resetCoreInstanceCache(): void
    {
        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, []);
    }

    /**
     * Returns a Session that skipped its constructor (no real session_start).
     */
    private function makeRawSession(): Session
    {
        $reflection = new \ReflectionClass(Session::class);
        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Session::setIsNewSession
     * @covers \OxidEsales\EshopCommunity\Setup\Session::getIsNewSession
     */
    public function testIsNewSessionRoundTrip(): void
    {
        $session = $this->makeRawSession();
        $this->assertFalse($session->getIsNewSession());

        $session->setIsNewSession(true);
        $this->assertTrue($session->getIsNewSession());

        $session->setIsNewSession(false);
        $this->assertFalse($session->getIsNewSession());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Session::setSessionParam
     * @covers \OxidEsales\EshopCommunity\Setup\Session::getSessionParam
     */
    public function testSessionParamWriteIsReflectedByGetter(): void
    {
        $session = $this->makeRawSession();
        $session->setSessionParam('test_key', 'test_value');
        $this->assertSame('test_value', $session->getSessionParam('test_key'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Session::setSessionParam
     */
    public function testSessionParamOverwritesExistingValue(): void
    {
        $session = $this->makeRawSession();
        $session->setSessionParam('shared', 'first');
        $session->setSessionParam('shared', 'second');
        $this->assertSame('second', $session->getSessionParam('shared'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Session::_getSessionData
     */
    public function testGetSessionDataReturnsReferenceToGlobalSession(): void
    {
        // setSessionParam goes through _getSessionData() and the assignment
        // must propagate to $_SESSION because the method returns by reference.
        unset($_SESSION['__test_marker__']);
        $session = $this->makeRawSession();
        $session->setSessionParam('__test_marker__', 'visible-in-superglobal');

        $this->assertArrayHasKey('__test_marker__', $_SESSION);
        $this->assertSame('visible-in-superglobal', $_SESSION['__test_marker__']);

        unset($_SESSION['__test_marker__']);
    }

    /**
     * Stubs Utilities so getRequestVar() returns deterministic values
     * for the four params _initSessionData reads.
     */
    private function injectUtilities(array $requestParams): void
    {
        $utilities = new class ($requestParams) extends Utilities {
            /** @var array */
            private $params;
            public function __construct(array $params)
            {
                $this->params = $params;
            }
            public function getRequestVar($sVarName, $sRequestType = null)
            {
                return $this->params[$sVarName] ?? null;
            }
        };

        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, [Utilities::class => $utilities]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Session::_initSessionData
     */
    public function testInitSessionDataStoresAllSubmittedFields(): void
    {
        $this->injectUtilities([
            'country_lang' => 'en',
            'sShopLang' => '1',
            'check_for_updates' => '1',
            'iEula' => '1',
        ]);

        // Wipe any stale values in $_SESSION that earlier tests may have written.
        foreach (['country_lang', 'sShopLang', 'check_for_updates', 'eula'] as $key) {
            unset($_SESSION[$key]);
        }

        $session = $this->makeRawSession();
        $method = new \ReflectionMethod(Session::class, '_initSessionData');
        $method->setAccessible(true);
        $method->invoke($session);

        $this->assertSame('en', $session->getSessionParam('country_lang'));
        $this->assertSame('1', $session->getSessionParam('sShopLang'));
        $this->assertSame('1', $session->getSessionParam('check_for_updates'));
        $this->assertSame('1', $session->getSessionParam('eula'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Session::_initSessionData
     */
    public function testInitSessionDataSkipsParamsThatWereNotSubmitted(): void
    {
        // Only one of the four — the others go down the isset-false path.
        $this->injectUtilities(['country_lang' => 'de']);

        foreach (['country_lang', 'sShopLang', 'check_for_updates', 'eula'] as $key) {
            unset($_SESSION[$key]);
        }

        $session = $this->makeRawSession();
        $method = new \ReflectionMethod(Session::class, '_initSessionData');
        $method->setAccessible(true);
        $method->invoke($session);

        $this->assertSame('de', $session->getSessionParam('country_lang'));
        $this->assertNull($session->getSessionParam('sShopLang'));
        $this->assertNull($session->getSessionParam('check_for_updates'));
        $this->assertNull($session->getSessionParam('eula'));
    }
}
