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

namespace OxidEsales\EshopCommunity\Internal\Framework\Form;

class Form implements FormInterface
{
    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $validators = [];

    /**
     * @param FormFieldInterface $field
     */
    public function add(FormFieldInterface $field)
    {
        $this->fields[$field->getName()] = $field;
    }

    /**
     * @param string $name
     * @return FormField
     */
    public function __get($name)
    {
        return $this->fields[$name];
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $request
     */
    public function handleRequest($request)
    {
        foreach ($request as $fieldName => $value) {
            $this->$fieldName->setValue($value);
        }
    }

    /**
     * @param FormValidatorInterface $validator
     */
    public function addValidator(FormValidatorInterface $validator)
    {
        $this->validators[] = $validator;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $isValid = true;

        foreach ($this->validators as $validator) {
            if ($validator->isValid($this) !== true) {
                $isValid = false;

                $this->errors = array_merge(
                    $this->errors,
                    $validator->getErrors()
                );
            }
        }

        return $isValid;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
