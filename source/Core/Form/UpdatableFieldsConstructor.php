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

use OxidEsales\Eshop\Core\Model\FieldNameHelper;
use OxidEsales\Eshop\Core\Contract\AbstractUpdatableFields;

/**
 * Provides creators for cleaners of fields which could be updated by a customer.
 */
class UpdatableFieldsConstructor
{
    /**
     * Get cleaner for field list which are allowed to be submitted in a form.
     *
     * @param AbstractUpdatableFields $updatableFields
     *
     * @return FormFieldsCleaner
     */
    public function getAllowedFieldsCleaner(AbstractUpdatableFields $updatableFields)
    {
        $helper = oxNew(FieldNameHelper::class);
        $allowedFields = $helper->getFullFieldNames($updatableFields->getTableName(), $updatableFields->getUpdatableFields());

        $updatableFields = oxNew(\OxidEsales\Eshop\Core\Form\FormFields::class, $allowedFields);

        return oxNew(\OxidEsales\Eshop\Core\Form\FormFieldsCleaner::class, $updatableFields);
    }
}
