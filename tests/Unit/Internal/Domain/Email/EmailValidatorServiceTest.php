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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Email;

use OxidEsales\EshopCommunity\Internal\Domain\Email\EmailValidatorService;
use PHPUnit\Framework\TestCase;

/**
 * Class EmailValidatorServiceTest
 *
 * @covers \OxidEsales\EshopCommunity\Internal\Domain\Email\EmailValidatorService
 */
class EmailValidatorServiceTest extends TestCase
{
    public function providerEmailsToValidate()
    {
        return [
            ['mathias.krieck@oxid-esales.com', true],
            ['mytest@com.org', true],
            ['my+test@com.org', true],
            ['mytest@oxid-esales.museum', true],
            ['?mathias.krieck@oxid-esales.com', true],
            ['my/test@com.org', true],
            ['mytest@-com.org', false],
            ['@com.org', false],
            ['mytestcom.org', false],
            ['foo.bar@-.-,-,-.oxid-esales.com', false],
            ['mytest@com', false],
            ['info@�vyturys.lt', false],
        ];
    }

    /**
     * @dataProvider providerEmailsToValidate
     */
    public function testValidateEmailWithValidEmail(string $email, bool $validMail): void
    {
        $mailValidator = new EmailValidatorService();
        $result = $mailValidator->isEmailValid($email);
        if ($validMail) {
            $this->assertTrue(
                $result,
                'Mail ' . $email . ' validation failed. This mail is valid so should validate.'
            );
        } else {
            $this->assertFalse(
                $result,
                'Mail ' . $email . ' was valid. Should not be valid.'
            );
        }
    }
}
