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
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Exception\UnavailablePasswordHashException;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Policy\PasswordPolicyInterface;

/**
 * Hashes with the ARGON2I algorithm
 */
class Argon2IPasswordHashService implements PasswordHashServiceInterface
{
    /**
     * @var PasswordPolicyInterface
     */
    private $passwordPolicy;

    /** @var int $memoryCost */
    private $memoryCost;

    /** @var int $timeCost */
    private $timeCost;

    /** @var int $threads */
    private $threads;


    /**
     * @param PasswordPolicyInterface $passwordPolicy
     * @param int                     $memoryCost
     * @param int                     $timeCost
     * @param int                     $threads
     *
     * @throws UnavailablePasswordHashException
     */
    public function __construct(
        PasswordPolicyInterface $passwordPolicy,
        int $memoryCost,
        int $timeCost,
        int $threads
    ) {
        if (!defined('PASSWORD_ARGON2I')) {
            throw new UnavailablePasswordHashException(
                'The password hash algorithm "PASSWORD_ARGON2I" is not available on your installation'
            );
        }

        $this->passwordPolicy = $passwordPolicy;

        $this->memoryCost = $memoryCost;
        $this->timeCost = $timeCost;
        $this->threads = $threads;
    }

    /**
     * Creates a password hash
     *
     * @param string $password
     *
     * @throws PasswordHashException
     *
     * @return string
     */
    public function hash(string $password): string
    {
        $this->passwordPolicy->enforcePasswordPolicy($password);

        $hash = password_hash(
            $password,
            PASSWORD_ARGON2I,
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
        return password_needs_rehash($passwordHash, PASSWORD_ARGON2I, $this->getOptions());
    }

    /**
     * @return array
     */
    private function getOptions(): array
    {
        return [
                'memory_cost' => $this->memoryCost,
                'time_cost' => $this->timeCost,
                'threads' => $this->threads
        ];
    }
}
