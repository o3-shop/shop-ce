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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Authentication\Policy\Service;

use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Exception\PasswordPolicyException;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Policy\PasswordPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Class PasswordVerificationServiceTest
 */
class PasswordPolicyTest extends TestCase
{
    /**
     *
     */
    public function testPasswordPolicyAcceptsUtf8EncodedStrings()
    {
        $passwordUtf8 = 'äääää';

        $passwordPolicy = new PasswordPolicy();
        $passwordPolicy->enforcePasswordPolicy($passwordUtf8);
    }

    /**
     * @dataProvider unsupportedEncodingDataProvider
     *
     * @param string $unsupportedEncoding
     *
     * @throws PasswordPolicyException
     */
    public function testPasswordPolicyRejectsStringNonUtf8Encoding(string $unsupportedEncoding)
    {
        $this->expectException(PasswordPolicyException::class);
        $this->expectExceptionMessage('The password policy requires UTF-8 encoded strings');

        $passwordUtf8 = 'äääää';
        $passwordIso = mb_convert_encoding($passwordUtf8, $unsupportedEncoding);

        $passwordPolicy = new PasswordPolicy();
        $passwordPolicy->enforcePasswordPolicy($passwordIso);
    }

    /**
     * @return array
     */
    public function unsupportedEncodingDataProvider(): array
    {
        return
            [
                ['UTF-32'],
                ['UTF-32BE'],
                ['UTF-32LE'],
                ['UTF-16'],
                ['UTF-16BE'],
                ['UTF-16LE'],
                ['ISO-8859-1'],
                ['ISO-8859-15'],
                ['Windows-1252']
            ];
    }
}
