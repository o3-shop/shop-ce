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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

/**
 * oxSepaValidator test class
 *
 * Can validate:
 *  - IBAN (International Business Account Number)
 *  - IBAN Registry (all IBAN lengths by country)
 *  - BIC (Bank International Code)
 */
class SepaValidatorTest extends \OxidTestCase
{

    /**
     * Test case to check getting IBAN registry records
     */
    public function testGetIBANRegistry()
    {
        $oSepaValidator = oxNew('oxSepaValidator');

        $aIBANRegistry = $oSepaValidator->getIBANCodeLengths();

        $this->assertNotNull($aIBANRegistry['DE'], "IBAN length for SEPA country (DE) must be not null");
    }

    /**
     * Test case to check IBAN validation
     *
     */
    public function testIsValidIBAN_validIBAN_true()
    {
        $oSepaValidator = oxNew('oxSepaValidator');

        $this->assertTrue($oSepaValidator->isValidIBAN("MT84MALT011000012345MTLCAST001S"), "IBAN must be valid");
    }

    /**
     * Test case to check IBAN validation
     *
     */
    public function testIsValidIBAN_invalidIBAN_false()
    {
        $oSepaValidator = oxNew('oxSepaValidator');

        $this->assertFalse($oSepaValidator->isValidIBAN("NX9386011117947"), "IBAN must be not valid");
    }

    /**
     * Test case to check BIC validation
     *
     */
    public function testIsValidBIC_validBIC_true()
    {
        $oSepaValidator = oxNew('oxSepaValidator');

        $this->assertTrue($oSepaValidator->isValidBIC("ASPKAT2L"), "BIC must be valid");
    }

    /**
     * Test case to check BIC validation
     *
     */
    public function testIsValidBIC_invalidBIC_false()
    {
        $oSepaValidator = oxNew('oxSepaValidator');

        $this->assertFalse($oSepaValidator->isValidBIC("AAAACCX"), "BIC must be not valid");
    }
}
