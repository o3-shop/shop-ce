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

namespace OxidEsales\EshopCommunity\Internal\Domain\Contact\Form;

use OxidEsales\EshopCommunity\Internal\Framework\Form\Form;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormField;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormFieldInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormValidatorInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FieldConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfigurationInterface;

class ContactFormFactory implements FormFactoryInterface
{
    /**
     * @var FormConfigurationInterface
     */
    private $contactFormConfiguration;

    /**
     * @var FormValidatorInterface
     */
    private $contactFormEmailValidator;

    /**
     * @var FormValidatorInterface
     */
    private $requiredFieldsValidator;

    /**
     * ContactFormFactory constructor.
     * @param FormConfigurationInterface $contactFormConfiguration
     * @param FormValidatorInterface     $contactFormEmailValidator
     * @param FormValidatorInterface     $requiredFieldsValidator
     */
    public function __construct(
        FormConfigurationInterface $contactFormConfiguration,
        FormValidatorInterface $contactFormEmailValidator,
        FormValidatorInterface $requiredFieldsValidator
    ) {
        $this->contactFormConfiguration = $contactFormConfiguration;
        $this->contactFormEmailValidator = $contactFormEmailValidator;
        $this->requiredFieldsValidator = $requiredFieldsValidator;
    }


    /**
     * @return FormInterface
     */
    public function getForm()
    {
        $form = new Form();

        foreach ($this->contactFormConfiguration->getFieldConfigurations() as $fieldConfiguration) {
            $field = $this->getFormField($fieldConfiguration);
            $form->add($field);
        }

        $form->addValidator($this->requiredFieldsValidator);
        $form->addValidator($this->contactFormEmailValidator);

        return $form;
    }

    /**
     * @param FieldConfigurationInterface $fieldConfiguration
     * @return FormFieldInterface
     */
    private function getFormField(FieldConfigurationInterface $fieldConfiguration)
    {
        $field = new FormField();
        $field
            ->setName($fieldConfiguration->getName())
            ->setLabel($fieldConfiguration->getLabel())
            ->setIsRequired($fieldConfiguration->isRequired());

        return $field;
    }
}
