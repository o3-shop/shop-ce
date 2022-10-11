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

use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FieldConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FieldConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfigurationFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormFieldsConfigurationDataProviderInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

class ContactFormConfigurationFactory implements FormConfigurationFactoryInterface
{
    /**
     * @var FormFieldsConfigurationDataProviderInterface
     */
    private $contactFormConfigurationDataProvider;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * ContactFormConfigurationFactory constructor.
     * @param FormFieldsConfigurationDataProviderInterface $contactFormConfigurationDataProvider
     * @param ContextInterface                             $context
     */
    public function __construct(
        FormFieldsConfigurationDataProviderInterface $contactFormConfigurationDataProvider,
        ContextInterface $context
    ) {
        $this->contactFormConfigurationDataProvider = $contactFormConfigurationDataProvider;
        $this->context = $context;
    }


    /**
     * @return FormConfigurationInterface
     */
    public function getFormConfiguration()
    {
        $formConfiguration = new FormConfiguration();

        $fieldsConfigurationData = $this
            ->contactFormConfigurationDataProvider
            ->getFormFieldsConfiguration();

        foreach ($fieldsConfigurationData as $fieldConfigurationData) {
            $fieldConfiguration = $this->getFieldConfiguration($fieldConfigurationData);
            $formConfiguration->addFieldConfiguration($fieldConfiguration);
        }

        return $formConfiguration;
    }

    /**
     * @param array $fieldConfigurationData
     * @return FieldConfiguration
     */
    private function getFieldConfiguration($fieldConfigurationData)
    {
        $fieldConfiguration = new FieldConfiguration();
        $fieldConfiguration->setName($fieldConfigurationData['name']);
        $fieldConfiguration->setLabel($fieldConfigurationData['label']);

        if ($this->isFieldRequired($fieldConfiguration)) {
            $fieldConfiguration->setIsRequired(true);
        }

        return $fieldConfiguration;
    }

    /**
     * @param FieldConfigurationInterface $fieldConfiguration
     * @return bool
     */
    private function isFieldRequired(FieldConfigurationInterface $fieldConfiguration)
    {
        return in_array(
            $fieldConfiguration->getName(),
            $this->context->getRequiredContactFormFields()
        );
    }
}
