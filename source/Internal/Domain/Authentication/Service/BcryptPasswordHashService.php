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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Authentication\Service;

use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Exception\PasswordHashException;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Policy\PasswordPolicyInterface;

class BcryptPasswordHashService implements PasswordHashServiceInterface
{
    /**
     * @var PasswordPolicyInterface
     */
    private $passwordPolicy;

    /**
     * @var int $cost
     *
     * The value of the option cost has to be between 4 and 31.
     */
    private $cost;

    /**
     * @param PasswordPolicyInterface $passwordPolicy
     * @param int                     $cost
     *
     * @throws PasswordHashException
     */
    public function __construct(
        PasswordPolicyInterface $passwordPolicy,
        int $cost
    ) {
        $this->passwordPolicy = $passwordPolicy;

        $this->validateCostOption($cost);
        $this->cost = $cost;
    }

    /**
     * Creates a password hash
     *
     * @param string $password
     *
     * @return string
     * @throws PasswordHashException
     *
     */
    public function hash(string $password): string
    {
        $this->passwordPolicy->enforcePasswordPolicy($password);

        $hash = password_hash(
            $password,
            PASSWORD_BCRYPT,
            $this->getOptions()
        );

        if ($hash === false) {
            throw new PasswordHashException(
                'The password could not have been hashed.'
            );
        }

        return $hash;
    }

    /**
     * @param string $passwordHash
     *
     * @return bool
     */
    public function passwordNeedsRehash(string $passwordHash): bool
    {
        return password_needs_rehash(
            $passwordHash,
            PASSWORD_BCRYPT,
            $this->getOptions()
        );
    }

    /**
     * @return array
     */
    private function getOptions(): array
    {
        return ['cost' => $this->cost];
    }


    /**
     * @param int $cost
     *
     * @throws PasswordHashException
     */
    private function validateCostOption(int $cost)
    {
        if ($cost < 4) {
            throw new PasswordHashException('The cost option for bcrypt must not be smaller than 4.');
        }
        if ($cost > 31) {
            throw new PasswordHashException('The cost option for bcrypt must not be bigger than 31.');
        }
    }
}
