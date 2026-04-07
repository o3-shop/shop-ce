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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\EshopCommunity\Core\BackwardsCompatibleClassNameProvider;
use OxidEsales\TestingLibrary\UnitTestCase;

class ClassNameProviderTest extends UnitTestCase
{
    public function providerReturnsClassNameFromClassAlias()
    {
        return [
            'known alias resolves to class' => ['oxarticle', 'OxidEsales\\Eshop\\Application\\Model\\Article'],
            'unknown alias returns itself'  => ['nonExistentAlias', 'nonExistentAlias'],
        ];
    }

    /**
     * @param string $classAlias
     * @param string $className
     *
     * @dataProvider providerReturnsClassNameFromClassAlias
     */
    public function testReturnsClassNameFromClassAlias($classAlias, $className)
    {
        $map = [
            'oxarticle' => 'OxidEsales\\Eshop\\Application\\Model\\Article',
            'oxorder'   => 'OxidEsales\\Eshop\\Application\\Model\\Order',
        ];

        $utilsObject = new BackwardsCompatibleClassNameProvider($map);

        $this->assertSame($className, $utilsObject->getClassName($classAlias));
    }

    public function providerReturnsClassNameAliasFromClassName()
    {
        return [
            'known class resolves to alias'          => ['OxidEsales\\Eshop\\Application\\Model\\Article', 'oxarticle'],
            'leading backslash is stripped'           => ['\\OxidEsales\\Eshop\\Application\\Model\\Article', 'oxarticle'],
            'unknown class returns null'              => ['NonExistent\\Class\\Name', null],
        ];
    }

    /**
     * @param string $className
     * @param string|null $classAliasName
     *
     * @dataProvider providerReturnsClassNameAliasFromClassName
     */
    public function testReturnsClassNameAliasFromClassName($className, $classAliasName)
    {
        $map = [
            'oxarticle' => 'OxidEsales\\Eshop\\Application\\Model\\Article',
            'oxorder'   => 'OxidEsales\\Eshop\\Application\\Model\\Order',
        ];

        $utilsObject = new BackwardsCompatibleClassNameProvider($map);

        $this->assertSame($classAliasName, $utilsObject->getClassAliasName($className));
    }
}
