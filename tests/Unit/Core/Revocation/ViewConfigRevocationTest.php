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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Revocation;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsObject;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Tests for `ViewConfig::getRevocationLinkVisible()` — the helper that
 * encodes the §356a BGB visibility matrix from the spec / design D5.
 *
 * Five-row truth table; one test per row plus a state-transition row.
 */
class ViewConfigRevocationTest extends UnitTestCase
{
    /** @var array<string, mixed> oxconfig values returned by the mocked Config */
    private array $configValues = [];

    /** @var bool whether `User::loadActiveUser()` returns true */
    private bool $userLoggedIn = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configValues = [
            'blShowRevocationForm' => false,
            'blRevocationRequireLogin' => false,
        ];
        $this->userLoggedIn = false;
        $this->mockRegistry();
    }

    public function testFeatureOffHidesLinkRegardlessOfLoginConfig(): void
    {
        $this->configValues['blShowRevocationForm'] = false;
        // login config and user state are irrelevant when the feature is off
        foreach ([
            ['blRevocationRequireLogin' => false, 'logged' => false],
            ['blRevocationRequireLogin' => false, 'logged' => true],
            ['blRevocationRequireLogin' => true, 'logged' => false],
            ['blRevocationRequireLogin' => true, 'logged' => true],
        ] as $combo) {
            $this->configValues['blRevocationRequireLogin'] = $combo['blRevocationRequireLogin'];
            $this->userLoggedIn = $combo['logged'];
            $this->assertFalse(
                (new ViewConfig())->getRevocationLinkVisible(),
                'When blShowRevocationForm=0 the link is never rendered.'
            );
        }
    }

    public function testFeatureOnLoginNotRequiredAnonymousVisitorSeesLink(): void
    {
        $this->configValues = ['blShowRevocationForm' => true, 'blRevocationRequireLogin' => false];
        $this->userLoggedIn = false;
        $this->assertTrue((new ViewConfig())->getRevocationLinkVisible());
    }

    public function testFeatureOnLoginNotRequiredAuthenticatedVisitorSeesLink(): void
    {
        $this->configValues = ['blShowRevocationForm' => true, 'blRevocationRequireLogin' => false];
        $this->userLoggedIn = true;
        $this->assertTrue((new ViewConfig())->getRevocationLinkVisible());
    }

    public function testFeatureOnLoginRequiredAnonymousVisitorDoesNotSeeLink(): void
    {
        $this->configValues = ['blShowRevocationForm' => true, 'blRevocationRequireLogin' => true];
        $this->userLoggedIn = false;
        $this->assertFalse(
            (new ViewConfig())->getRevocationLinkVisible(),
            'Login-required + anonymous = no link (would mislead users to a flow they cannot use).'
        );
    }

    public function testFeatureOnLoginRequiredAuthenticatedVisitorSeesLink(): void
    {
        $this->configValues = ['blShowRevocationForm' => true, 'blRevocationRequireLogin' => true];
        $this->userLoggedIn = true;
        $this->assertTrue((new ViewConfig())->getRevocationLinkVisible());
    }

    public function testReturnsBoolType(): void
    {
        $this->configValues = ['blShowRevocationForm' => true, 'blRevocationRequireLogin' => false];
        $result = (new ViewConfig())->getRevocationLinkVisible();
        $this->assertIsBool($result, 'getRevocationLinkVisible must return a strict boolean (declared in signature).');
    }

    protected function tearDown(): void
    {
        UtilsObject::resetClassInstances();
        parent::tearDown();
    }

    private function mockRegistry(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getConfigParam')->willReturnCallback(
            fn ($name, $default = null) => $this->configValues[$name] ?? $default
        );
        Registry::set(Config::class, $config);

        // ViewConfig instantiates a fresh User via `oxNew(User::class)`. The
        // OXID idiom for overriding oxNew in tests is `UtilsObject::setClassInstance()`.
        $user = $this->createMock(User::class);
        $user->method('loadActiveUser')->willReturnCallback(fn () => $this->userLoggedIn);
        UtilsObject::setClassInstance(User::class, $user);
    }
}
