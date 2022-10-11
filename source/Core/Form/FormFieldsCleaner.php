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

/**
 * Return those fields which could be changed by a customer.
 */
class FormFieldsCleaner
{
    /** @var FormFields */
    private $updatableFields;

    /**
     * @param FormFields $updatableFields White-list.
     */
    public function __construct(\OxidEsales\Eshop\Core\Form\FormFields $updatableFields)
    {
        $this->updatableFields = $updatableFields;
    }

    /**
     * Return only those items which exist in both lists.
     *
     * @param array $listToClean All fields.
     *
     * @return array
     */
    public function filterByUpdatableFields(array $listToClean)
    {
        $allowedFields = $this->updatableFields->getUpdatableFields();

        $cleanedList = $listToClean;
        if ($allowedFields->count() > 0) {
            $cleanedList = $this->filterFieldsByWhiteList($allowedFields, $listToClean);
        }

        return $cleanedList;
    }

    /**
     * Return fields by performing a case-insensitive compare.
     * Does not change original case-sensitivity of fields.
     *
     * @param \ArrayIterator $allowedFields
     * @param array          $listToClean
     *
     * @return array
     */
    private function filterFieldsByWhiteList(\ArrayIterator $allowedFields, array $listToClean)
    {
        $allowedFieldsLowerCase = array_map('strtolower', (array)$allowedFields);
        $cleanedList = array_filter($listToClean, function ($field) use ($allowedFieldsLowerCase) {
            return in_array(strtolower($field), $allowedFieldsLowerCase);
        }, ARRAY_FILTER_USE_KEY);

        return $cleanedList;
    }
}
