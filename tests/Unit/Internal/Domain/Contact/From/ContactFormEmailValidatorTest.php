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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Domain\Contact\Form;

use OxidEsales\EshopCommunity\Internal\Framework\Form\Form;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormField;
use OxidEsales\EshopCommunity\Internal\Domain\Contact\Form\ContactFormEmailValidator;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapter;

class ContactFormEmailValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidEmailValidation()
    {
        $validator = $this->getContactFormEmailValidator();

        $invalidEmailField = new FormField();
        $invalidEmailField->setName('email');
        $invalidEmailField->setValue('ImSoInvalid');

        $form = new Form();
        $form->add($invalidEmailField);

        $this->assertFalse(
            $validator->isValid($form)
        );

        $this->assertSame(
            ['ERROR_MESSAGE_INPUT_NOVALIDEMAIL'],
            $validator->getErrors()
        );
    }

    public function testValidEmailValidation()
    {
        $validator = $this->getContactFormEmailValidator();

        $validEmailField = new FormField();
        $validEmailField->setName('email');
        $validEmailField->setValue('someemail@validEmailsClub.com');

        $form = new Form();
        $form->add($validEmailField);

        $this->assertTrue(
            $validator->isValid($form)
        );
    }

    public function testEmptyEmailIsNotValidIfEmailIsRequired()
    {
        $validator = $this->getContactFormEmailValidator();

        $emailField = new FormField();
        $emailField
            ->setName('email')
            ->setValue('')
            ->setIsRequired(true);

        $form = new Form();
        $form->add($emailField);

        $this->assertFalse(
            $validator->isValid($form)
        );
    }

    public function testEmptyEmailIsValidIfEmailIsRequired()
    {
        $validator = $this->getContactFormEmailValidator();

        $emailField = new FormField();
        $emailField
            ->setName('email')
            ->setValue('');

        $form = new Form();
        $form->add($emailField);

        $this->assertTrue(
            $validator->isValid($form)
        );
    }

    private function getContactFormEmailValidator()
    {
        return new ContactFormEmailValidator(
            new ShopAdapter()
        );
    }
}
