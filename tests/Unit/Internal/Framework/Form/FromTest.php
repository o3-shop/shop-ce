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
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormValidatorInterface;

class FromTest extends \PHPUnit\Framework\TestCase
{
    public function testAddField()
    {
        $form = new Form();

        $field = new FormField();
        $field->setName('testField');

        $form->add($field);

        $this->assertSame($field, $form->testField);
    }

    public function testValidation()
    {
        $validator = $this->getMockBuilder(FormValidatorInterface::class)->getMock();
        $validator
            ->method('isValid')
            ->willReturn(false);

        $validator
            ->method('getErrors')
            ->willReturn([]);

        $form = new Form();
        $form->addValidator($validator);

        $this->assertFalse($form->isValid());
    }

    public function testValidationErrors()
    {
        $validator = $this->getMockBuilder(FormValidatorInterface::class)->getMock();
        $validator
            ->method('isValid')
            ->willReturn(false);

        $validator
            ->method('getErrors')
            ->willReturn([
                'something is wrong',
                'alles ist kaput',
            ]);

        $anotherValidator = $this->getMockBuilder(FormValidatorInterface::class)->getMock();
        $anotherValidator
            ->method('isValid')
            ->willReturn(false);

        $anotherValidator
            ->method('getErrors')
            ->willReturn([
                'everything is wrong',
                'etwas ist kaput',
            ]);

        $form = new Form();
        $form->addValidator($validator);
        $form->addValidator($anotherValidator);

        $form->isValid();

        $this->assertSame(
            [
                'something is wrong',
                'alles ist kaput',
                'everything is wrong',
                'etwas ist kaput',
            ],
            $form->getErrors()
        );
    }

    public function testFieldsGetter()
    {
        $form = new Form();

        $field = new FormField();
        $field->setName('testField');

        $anotherField = new FormField();
        $anotherField->setName('anotherTestField');

        $form->add($field);
        $form->add($anotherField);

        $this->assertEquals(
            $form->getFields(),
            [
                'testField'         => $field,
                'anotherTestField'  => $anotherField,
            ]
        );
    }

    public function testRequestHandling()
    {
        $form = new Form();

        $field = new FormField();
        $field->setName('testField');

        $form->add($field);
        $form->handleRequest([
            'testField' => 'testValue',
        ]);

        $this->assertSame(
            'testValue',
            $form->testField->getValue()
        );
    }
}
