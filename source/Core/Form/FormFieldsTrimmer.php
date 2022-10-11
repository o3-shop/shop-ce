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

namespace OxidEsales\EshopCommunity\Core\Form;

use OxidEsales\Eshop\Core\Form\FormFieldsTrimmerInterface as EshopFormFieldsTrimmerInterface;
use OxidEsales\Eshop\Core\Form\FormFields as EshopFormFields;

/**
 * Trim FormFields.
 */
class FormFieldsTrimmer implements EshopFormFieldsTrimmerInterface
{
    /**
     * Returns trimmed fields.
     *
     * @param EshopFormFields $fields to trim.
     *
     * @return ArrayIterator
     */
    public function trim(EshopFormFields $fields)
    {
        $updatableFields = $fields->getUpdatableFields()->getArrayCopy();

        array_walk_recursive($updatableFields, function (&$value) {
            $value = $this->isTrimmableField($value) ? $this->trimField($value) : $value;
        });

        return new \ArrayIterator($updatableFields);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function isTrimmableField($value)
    {
        return is_string($value);
    }

    /**
     * Returns trimmed field value.
     *
     * @param   string $field
     *
     * @return  string
     */
    private function trimField($field)
    {
        return trim($field);
    }
}
