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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Form;

use OxidEsales\EshopCommunity\Internal\Framework\Form\Form;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormField;
use OxidEsales\EshopCommunity\Internal\Framework\Form\RequiredFieldsValidator;

class RequiredFieldsValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidFormValidation()
    {
        $form = new Form();

        $field = new FormField();
        $field->setName('requiredField');
        $field->setIsRequired(true);

        $form->add($field);

        $requiredFieldsValidator = new RequiredFieldsValidator();

        $this->assertFalse($requiredFieldsValidator->isValid($form));
    }

    public function testValidFormValidation()
    {
        $form = new Form();

        $field = new FormField();
        $field->setName('requiredField');
        $field->setIsRequired(true);
        $field->setValue('123');

        $form->add($field);

        $requiredFieldsValidator = new RequiredFieldsValidator();

        $this->assertTrue($requiredFieldsValidator->isValid($form));
    }

    public function testInvalidFormValidationErrors()
    {
        $form = new Form();

        $field = new FormField();
        $field->setName('requiredField');
        $field->setIsRequired(true);

        $form->add($field);

        $requiredFieldsValidator = new RequiredFieldsValidator();
        $requiredFieldsValidator->isValid($form);

        $this->assertSame(
            ['ERROR_MESSAGE_INPUT_NOTALLFIELDS'],
            $requiredFieldsValidator->getErrors()
        );
    }
}
