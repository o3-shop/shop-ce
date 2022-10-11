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

use OxidEsales\EshopCommunity\Internal\Framework\Form\FormFieldInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormValidatorInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;

class ContactFormEmailValidator implements FormValidatorInterface
{
    /**
     * @var array
     */
    private $errors;

    /**
     * @var ShopAdapterInterface
     */
    private $shopAdapter;

    /**
     * ContactFormEmailValidator constructor.
     * @param ShopAdapterInterface $shopAdapter
     */
    public function __construct(ShopAdapterInterface $shopAdapter)
    {
        $this->shopAdapter = $shopAdapter;
    }

    /**
     * @param FormInterface $form
     * @return bool
     */
    public function isValid(FormInterface $form)
    {
        $isValid = true;
        $email = $form->email;

        if ($this->isValidationNeeded($email)) {
            $isValid = $this
                ->shopAdapter
                ->isValidEmail($email->getValue());

            if ($isValid !== true) {
                $this->errors[] = 'ERROR_MESSAGE_INPUT_NOVALIDEMAIL';
            }
        }

        return $isValid;
    }

    /**
     * @param FormFieldInterface $email
     * @return bool
     */
    private function isValidationNeeded(FormFieldInterface $email)
    {
        return $this->isNotEmptyEmail($email) || $email->isRequired();
    }

    /**
     * @param FormFieldInterface $email
     * @return bool
     */
    private function isNotEmptyEmail(FormFieldInterface $email)
    {
        return $email->getValue() !== '';
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
