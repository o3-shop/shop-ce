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

use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Test \OxidEsales\Eshop\Core\EditionSelector.
 * Class NamespaceInformationProviderTest
 *
 * @package OxidEsales\EshopCommunity\Tests\Unit\Core
 */
class NamespaceInformationProviderTest extends UnitTestCase
{
    /**
     * Test getter for shop edition namespaces.
     */
    public function testGetShopEditionNamespaces()
    {
        $expected = ['CE' => 'OxidEsales\EshopCommunity\\'];
        $this->assertEquals($expected, \OxidEsales\Eshop\Core\NamespaceInformationProvider::getShopEditionNamespaces());
    }

    /**
     * Test getter for Unified Namespace.
     */
    public function testGetUnifiedNamespace()
    {
        $this->assertEquals('OxidEsales\Eshop\\', \OxidEsales\Eshop\Core\NamespaceInformationProvider::getUnifiedNamespace());
    }

    /**
     * Test method isNamespacedClass.
     */
    public function testIsNamespacedClass()
    {
        $this->assertTrue(\OxidEsales\Eshop\Core\NamespaceInformationProvider::isNamespacedClass(\OxidEsales\Eshop\Application\Model\Article::class));
        $this->assertFalse(\OxidEsales\Eshop\Core\NamespaceInformationProvider::isNamespacedClass('oxArticle'));
    }

    /**
     * Test method classBelongsToShopUnifiedNamespace.
     */
    public function testClassBelongsToShopUnifiedNamespace()
    {
        $this->assertFalse(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopUnifiedNamespace('oxArticle'));
        $this->assertFalse(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopUnifiedNamespace('oxarticle'));
        $this->assertFalse(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopUnifiedNamespace(\OxidEsales\EshopCommunity\Application\Model\Article::class));
        $this->assertFalse(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopUnifiedNamespace('OxidEsales\EshopCommunity\Application\Model\Article'));
        $this->assertTrue(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopUnifiedNamespace('OxidEsales\Eshop\Application\Model\Article'));
        $this->assertTrue(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopUnifiedNamespace(\OxidEsales\Eshop\Application\Model\Article::class));
    }

    /**
     * Test method classBelongsToShopEditionNamespace.
     */
    public function testClassBelongsToShopEditionNamespace()
    {
        $this->assertFalse(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopEditionNamespace('oxArticle'));
        $this->assertFalse(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopEditionNamespace('oxarticle'));
        $this->assertTrue(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopEditionNamespace(\OxidEsales\EshopCommunity\Application\Model\Article::class));
        $this->assertTrue(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopEditionNamespace('OxidEsales\EshopCommunity\Application\Model\Article'));
        $this->assertFalse(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopEditionNamespace('OxidEsales\Eshop\Application\Model\Article'));
        $this->assertFalse(\OxidEsales\Eshop\Core\NamespaceInformationProvider::classBelongsToShopEditionNamespace(\OxidEsales\Eshop\Application\Model\Article::class));
    }
}
