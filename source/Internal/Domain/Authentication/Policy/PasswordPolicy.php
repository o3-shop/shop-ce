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

namespace OxidEsales\EshopCommunity\Internal\Domain\Authentication\Policy;

use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Exception\PasswordPolicyException;

class PasswordPolicy implements PasswordPolicyInterface
{
    /**
     * Enforces password policy
     *
     * @param string $password
     *
     * @throws PasswordPolicyException
     */
    public function enforcePasswordPolicy(string $password)
    {
        /**
         * A password policy should at least ensure, that the same character encoding is used for hashing and
         * verification. As there is no real way to ensure, that a byte stream is encoded in a certain character
         * set, at least is should ensured that the password is valid UTF-8.
         */
        if (!$this->isValidUtf8($password)) {
            throw new PasswordPolicyException('The password policy requires UTF-8 encoded strings');
        }
    }

    /**
     * @param string $password
     *
     * @return bool
     */
    private function isValidUtf8(string $password): bool
    {
        /**
         * Use the PCRE_UTF8 pattern modifier to test, if the given string this is a valid UTF-8 string.
         * See http://php.net/manual/de/reference.pcre.pattern.modifiers.php
         * preg_match will return false on a invalid subject
         * Not perfect, but good enough.
         */
        return false !== preg_match('//u', $password);
    }
}
