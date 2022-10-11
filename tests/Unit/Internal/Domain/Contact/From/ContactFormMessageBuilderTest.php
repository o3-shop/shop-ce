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

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\Form;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormField;
use OxidEsales\EshopCommunity\Internal\Domain\Contact\Form\ContactFormMessageBuilder;

class ContactFormMessageBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider fieldsProvider
     */
    public function testContentGetter($name, $value)
    {
        $form = $this->getContactForm();
        $form->handleRequest([$name => $value]);

        $shopAdapter = $this->getMockBuilder(ShopAdapterInterface::class)->getMock();
        $shopAdapter
            ->method('translateString')
            ->will(
                $this->returnCallback(function ($arg) {
                    return $arg;
                })
            );
        $contactFormMessageBuilder = new ContactFormMessageBuilder($shopAdapter);

        $this->assertStringContainsString(
            $value,
            $contactFormMessageBuilder->getContent($form)
        );
    }

    public function fieldsProvider()
    {
        return [
            [
                'email',
                'marina.ginesta@bcn.cat'
            ],
            [
                'firstName',
                'Marina'
            ],
            [
                'lastName',
                'Ginestà'
            ],
            [
                'salutation',
                'MRS'
            ],
            [
                'message',
                'I\'m standing on the rooftop'
            ],
        ];
    }

    private function getContactForm()
    {
        $form = new Form();

        $fieldNames = [
            'email',
            'firstName',
            'lastName',
            'salutation',
            'message',
        ];

        foreach ($fieldNames as $fieldName) {
            $field = new FormField();
            $field->setName($fieldName);
            $form->add($field);
        }

        return $form;
    }
}
