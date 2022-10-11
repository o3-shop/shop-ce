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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core;

use OxidEsales\Eshop\Core\InputValidator;
use OxidEsales\TestingLibrary\UnitTestCase;

class InputValidatorTest extends UnitTestCase
{
    private $oxidDebitNote = 'oxiddebitnote';

    public function testValidatePaymentInputDataWithSpaceCharacterForBankCode()
    {
        $testValues = [
            'lsbankname'   => 'Bank name',
            'lsblz'        => ' ',
            'lsktonr'      => '123456',
            'lsktoinhaber' => 'Hans Mustermann'
        ];

        $validator = oxNew(InputValidator::class);
        $result = $validator->validatePaymentInputData($this->oxidDebitNote, $testValues);
        $this->assertEquals(InputValidator::INVALID_BANK_CODE, $result, 'Should validate as invalid bank code error.');
    }

    public function testValidatePaymentInputDataWithCorrectBankCode()
    {
        $testValues = [
            'lsbankname'   => 'Bank name',
            'lsblz'        => '12345678',
            'lsktonr'      => '123456',
            'lsktoinhaber' => 'Hans Mustermann'
        ];

        $validator = oxNew(InputValidator::class);
        $result = $validator->validatePaymentInputData($this->oxidDebitNote, $testValues);
        $this->assertTrue($result, 'Should validate as True');
    }

    public function testValidatePaymentInputDataWithBlankBankCode()
    {
        $testValues = [
            'lsbankname'   => 'Bank name',
            'lsblz'        => '',
            'lsktonr'      => '123456',
            'lsktoinhaber' => 'Hans Mustermann'
        ];

        $validator = oxNew(InputValidator::class);
        $validationResult = $validator->validatePaymentInputData($this->oxidDebitNote, $testValues);

        $this->assertEquals(InputValidator::INVALID_BANK_CODE, $validationResult, 'Should validate as bank code error.');
    }
}
