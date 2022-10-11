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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Form;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidEsales\Eshop\Core\Form\FormFields;
use OxidEsales\Eshop\Core\Form\FormFieldsCleaner;

class FormFieldsCleanerTest extends UnitTestCase
{
    public function testGetAllFieldsWhenNothingInWhiteList()
    {
        $fieldsToClean = ['oxid' => 'some value 1', 'user_name' => 'some value 2'];

        $emptyUpdatableFieldsList = oxNew(FormFields::class, []);

        $cleanedFieldsList = $this->getCleanList($emptyUpdatableFieldsList, $fieldsToClean);

        $this->assertSame($fieldsToClean, $cleanedFieldsList);
    }

    public function testGetEmptyArrayWhenNoneInWhiteList()
    {
        $fieldsToClean = ['none_existing_1' => 'some value 1', 'none_existing_2' => 'some value 2'];

        $updatableFieldsList = $this->getUpdatableFields();

        $cleanedFieldsList = $this->getCleanList($updatableFieldsList, $fieldsToClean);

        $this->assertSame([], $cleanedFieldsList);
    }

    public function providerGetSameCaseSensitiveDataAsRequested()
    {
        return [
            [['userName' => 'some value 1', 'none_existing_2' => 'some value 2'], ['userName' => 'some value 1']],
            [['username' => 'some value 1', 'none_existing_2' => 'some value 2'], ['username' => 'some value 1']],
            [['USERNAME' => 'some value 1', 'none_existing_2' => 'some value 2'], ['USERNAME' => 'some value 1']],
            [['oxuser__username' => 'user name', 'oxuser__notexisting' => 'some value'], ['oxuser__username' => 'user name']],
        ];
    }

    /**
     * @param array $fieldsToClean
     * @param array $expectedFields
     * @dataProvider providerGetSameCaseSensitiveDataAsRequested
     */
    public function testGetSameCaseSensitiveDataAsRequested($fieldsToClean, $expectedFields)
    {
        $updatableFieldsList = $this->getUpdatableFields();

        $cleanedFieldsList = $this->getCleanList($updatableFieldsList, $fieldsToClean);

        $this->assertSame($expectedFields, $cleanedFieldsList);
    }

    private function getUpdatableFields()
    {
        $userUpdatableFieldsList = ['oxuser__username', 'oxuser__userpassword', 'username', 'userpassword'];
        $userUpdatableFields = oxNew(FormFields::class, $userUpdatableFieldsList);

        return $userUpdatableFields;
    }

    /**
     * @param array $updatableFields
     * @param array $fields
     * @return array
     */
    private function getCleanList($updatableFields, $fields)
    {
        $cleaner = oxNew(FormFieldsCleaner::class, $updatableFields);
        $cleanedFieldsList = $cleaner->filterByUpdatableFields($fields);
        return $cleanedFieldsList;
    }
}
