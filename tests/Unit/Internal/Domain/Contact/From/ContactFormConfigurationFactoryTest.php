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

use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FieldConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormFieldsConfigurationDataProviderInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Contact\Form\ContactFormConfigurationFactory;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

class ContactFormConfigurationFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigurationGetter()
    {
        $context = $this->getMockBuilder(ContextInterface::class)->getMock();

        $formFieldsConfigurationDataProvider = $this->getMockBuilder(
            FormFieldsConfigurationDataProviderInterface::class
        )->getMock();
        $formFieldsConfigurationDataProvider
            ->method('getFormFieldsConfiguration')
            ->willReturn([]);

        $formConfigurationFactory = new ContactFormConfigurationFactory(
            $formFieldsConfigurationDataProvider,
            $context
        );

        $this->assertInstanceOf(
            FormConfigurationInterface::class,
            $formConfigurationFactory->getFormConfiguration()
        );
    }

    public function testFormFieldsConfiguration()
    {
        $context = $this->getMockBuilder(ContextInterface::class)->getMock();
        $context
            ->method('getRequiredContactFormFields')
            ->willReturn([
                'name',
            ]);

        $formFieldsConfigurationDataProvider = $this->getMockBuilder(
            FormFieldsConfigurationDataProviderInterface::class
        )->getMock();
        $formFieldsConfigurationDataProvider
            ->method('getFormFieldsConfiguration')
            ->willReturn([
                [
                    'name'              => 'email',
                    'label'             => 'EMAIL',
                ],
                [
                    'name'              => 'firstName',
                    'label'             => 'FIRST_NAME',
                    'required'          => true,
                ],
            ]);

        $formConfigurationFactory = new ContactFormConfigurationFactory(
            $formFieldsConfigurationDataProvider,
            $context
        );

        $contactFormConfiguration = $formConfigurationFactory->getFormConfiguration();

        $emailConfiguration = new FieldConfiguration();
        $emailConfiguration
            ->setName('email')
            ->setLabel('EMAIL');

        $firstNameConfiguration = new FieldConfiguration();
        $firstNameConfiguration
            ->setName('firstName')
            ->setLabel('FIRST_NAME')
            ->isRequired();

        $this->assertEquals(
            [
                $emailConfiguration,
                $firstNameConfiguration,
            ],
            $contactFormConfiguration->getFieldConfigurations()
        );
    }
}
