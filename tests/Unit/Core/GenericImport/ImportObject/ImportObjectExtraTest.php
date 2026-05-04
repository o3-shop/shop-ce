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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\GenericImport\ImportObject;

use OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject;

/**
 * Covers the ImportObject base methods ImportObjectTest doesn't reach:
 * getFieldList (which builds the field list from a freshly oxNew'd shop
 * object) and getRightFields when no fieldList has been pre-set
 * (recurses into getFieldList).
 */
class ImportObjectExtraTest_Stub extends ImportObject
{
    public function __construct(string $tableName = 'oxarticles')
    {
        $this->tableName = $tableName;
    }

    public function setShopObjectName(?string $name): void
    {
        $this->shopObjectName = $name;
    }
}

class ImportObjectExtraTest extends \OxidTestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getFieldList
     */
    public function testGetFieldListFromBaseModelWhenShopObjectNameNotSet(): void
    {
        $importer = new ImportObjectExtraTest_Stub('oxarticles');
        $importer->setShopObjectName(null);

        $fields = $importer->getFieldList();
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        // The table is oxarticles → its primary key OXID must show up.
        $this->assertContains('OXID', $fields);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getFieldList
     */
    public function testGetFieldListUsesShopObjectWhenNameSet(): void
    {
        $importer = new ImportObjectExtraTest_Stub('oxarticles');
        $importer->setShopObjectName('oxarticle');

        $fields = $importer->getFieldList();
        $this->assertIsArray($fields);
        $this->assertContains('OXID', $fields);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getRightFields
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\ImportObject::getFieldList
     */
    public function testGetRightFieldsBuildsFieldListLazilyWhenNotSet(): void
    {
        $importer = new ImportObjectExtraTest_Stub('oxarticles');
        $importer->setShopObjectName(null);

        $rights = $importer->getRightFields();
        $this->assertIsArray($rights);
        $this->assertNotEmpty($rights);
        // Each entry follows the `<table>__<lowercase field>` convention.
        foreach ($rights as $right) {
            $this->assertStringStartsWith('oxarticles__', $right);
        }
    }
}
