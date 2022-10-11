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

use OxidEsales\EshopCommunity\Internal\Framework\Form\FormFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfigurationInterface;

class ContactFormBridge implements ContactFormBridgeInterface
{
    /**
     * @var FormFactoryInterface
     */
    private $contactFormFactory;

    /**
     * @var ContactFormMessageBuilderInterface
     */
    private $contactFormMessageBuilder;

    /**
     * @var FormConfigurationInterface
     */
    private $contactFormConfiguration;

    /**
     * ContactFormBridge constructor.
     * @param FormFactoryInterface               $contactFormFactory
     * @param ContactFormMessageBuilderInterface $contactFormMessageBuilder
     * @param FormConfigurationInterface         $contactFormConfiguration
     */
    public function __construct(
        FormFactoryInterface $contactFormFactory,
        ContactFormMessageBuilderInterface $contactFormMessageBuilder,
        FormConfigurationInterface $contactFormConfiguration
    ) {
        $this->contactFormFactory = $contactFormFactory;
        $this->contactFormMessageBuilder = $contactFormMessageBuilder;
        $this->contactFormConfiguration = $contactFormConfiguration;
    }

    /**
     * @return FormInterface
     */
    public function getContactForm()
    {
        return $this->contactFormFactory->getForm();
    }

    /**
     * @param FormInterface $form
     * @return string
     */
    public function getContactFormMessage(FormInterface $form)
    {
        return $this->contactFormMessageBuilder->getContent($form);
    }

    /**
     * @return FormConfigurationInterface
     */
    public function getContactFormConfiguration()
    {
        return $this->contactFormConfiguration;
    }
}
