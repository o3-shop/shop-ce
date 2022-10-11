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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Password\Service;

use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Exception\PasswordPolicyException;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Policy\PasswordPolicyInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Service\Argon2IPasswordHashService;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Service\BcryptPasswordHashService;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Service\PasswordHashServiceInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;

class PasswordHashServiceTest extends TestCase
{
    use ContainerTrait;

    /**
     */
    public function testPasswordNeedsRehashReturnsTrueOnChangedAlgorithm()
    {
        if (!defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped(
                'The password hashing algorithms PASSWORD_ARGON2I and/or PASSWORD_ARGON2I are not available'
            );
        }

        $argon2iPasswordHashService = $this->getArgon2IPasswordHashService();
        $bcryptPasswordHashService = $this->getBcryptPasswordHashService();

        $bcryptHash = $bcryptPasswordHashService->hash('secret');
        $argon2iHash = $argon2iPasswordHashService->hash('secret');

        $this->assertFalse($bcryptPasswordHashService->passwordNeedsRehash($bcryptHash));
        $this->assertFalse($argon2iPasswordHashService->passwordNeedsRehash($argon2iHash));
        $this->assertTrue($bcryptPasswordHashService->passwordNeedsRehash($argon2iHash));
        $this->assertTrue($argon2iPasswordHashService->passwordNeedsRehash($bcryptHash));
    }

    /**
     * End-to-end test to ensure, that the password policy checking is called during password hashing
     */
    public function testPasswordHashServiceEnforcesPasswordPolicy()
    {
        $this->expectException(PasswordPolicyException::class);

        $passwordUtf8 = 'äääääää';
        $passwordIso = mb_convert_encoding($passwordUtf8, 'ISO-8859-15');

        $passwordHashService = $this->get(PasswordHashServiceInterface::class);
        $passwordHashService->hash($passwordIso);
    }

    /**
     * @return PasswordHashServiceInterface
     */
    private function getBcryptPasswordHashService(): PasswordHashServiceInterface
    {
        $passwordPolicy = $this->getPasswordPolicyMock();

        $passwordHashService = new BcryptPasswordHashService(
            $passwordPolicy,
            4
        );

        return $passwordHashService;
    }

    /**
     * @return PasswordHashServiceInterface
     */
    private function getArgon2IPasswordHashService(): PasswordHashServiceInterface
    {
        $passwordPolicyMock = $this->getPasswordPolicyMock();

        $passwordHashService = new Argon2IPasswordHashService(
            $passwordPolicyMock,
            PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            PASSWORD_ARGON2_DEFAULT_TIME_COST,
            PASSWORD_ARGON2_DEFAULT_THREADS
        );

        return $passwordHashService;
    }


    /**
     * @return PasswordPolicyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getPasswordPolicyMock(): PasswordPolicyInterface
    {
        $passwordPolicyMock = $this
            ->getMockBuilder(PasswordPolicyInterface::class)
            ->setMethods(['enforcePasswordPolicy'])
            ->getMock();

        return $passwordPolicyMock;
    }
}
