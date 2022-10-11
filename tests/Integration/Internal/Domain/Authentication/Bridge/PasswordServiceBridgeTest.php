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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Password\Bridge;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 *
 */
class PasswordServiceBridgeTest extends TestCase
{
    use ContainerTrait;

    /**
     * End-to-end test for the PasswordService bridge
     */
    public function testHashWithBcrypt()
    {
        /** @var PasswordServiceBridgeInterface $passwordServiceBridge */
        $passwordServiceBridge = $this->get(PasswordServiceBridgeInterface::class);
        $hash = $passwordServiceBridge->hash('secret');
        $info = password_get_info($hash);

        $this->assertSame(PASSWORD_BCRYPT, $info['algo']);
    }

    /**
     * End-to-end test for the password verification service.
     */
    public function testVerifyPassword()
    {
        /** @var PasswordServiceBridgeInterface $passwordServiceBridge */
        $passwordServiceBridge = $this->get(PasswordServiceBridgeInterface::class);

        $password = 'secret';
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertTrue(
            $passwordServiceBridge->verifyPassword($password, $passwordHash)
        );
    }

    public function testPasswordNeedsRehash()
    {
        /** @var PasswordServiceBridgeInterface $passwordServiceBridge */
        $passwordServiceBridge = $this->get(PasswordServiceBridgeInterface::class);

        $container = $this->getContainer();
        $cost = $container->getParameter('oxid_esales.authentication.service.password_hash.bcrypt.cost');

        $passwordHashWithCostFromConfiguration = password_hash('secret', PASSWORD_BCRYPT, ['cost' => $cost]);
        $passwordHashWithCostChangedCost = password_hash('secret', PASSWORD_BCRYPT, ['cost' => $cost + 1]);

        $this->assertFalse(
            $passwordServiceBridge->passwordNeedsRehash($passwordHashWithCostFromConfiguration)
        );
        $this->assertTrue(
            $passwordServiceBridge->passwordNeedsRehash($passwordHashWithCostChangedCost)
        );
    }

    /**
     * @return ContainerInterface
     */
    private function getContainer() : ContainerInterface
    {
        $factory = ContainerFactory::getInstance();

        return $factory->getContainer();
    }
}
