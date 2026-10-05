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

use OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\OrderArticle;

class OrderArticleTest extends \OxidTestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\OrderArticle::preAssignObject
     */
    public function testPreAssignObjectSerialisesPipeSeparatedPersParam(): void
    {
        $importer = new OrderArticle();
        $shopObject = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);

        $method = new \ReflectionMethod($importer, 'preAssignObject');
        $method->setAccessible(true);
        $result = $method->invoke(
            $importer,
            $shopObject,
            ['OXID' => 'oa-1', 'OXPERSPARAM' => 'red|XL'],
            false
        );

        $this->assertSame(['red', 'XL'], unserialize($result['OXPERSPARAM']));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\OrderArticle::preAssignObject
     */
    public function testPreAssignObjectKeepsAlreadySerialisedPersParam(): void
    {
        $importer = new OrderArticle();
        $shopObject = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);
        $serialised = serialize(['blue', 'M']);

        $method = new \ReflectionMethod($importer, 'preAssignObject');
        $method->setAccessible(true);
        $result = $method->invoke(
            $importer,
            $shopObject,
            ['OXID' => 'oa-2', 'OXPERSPARAM' => $serialised],
            false
        );

        $this->assertSame($serialised, $result['OXPERSPARAM']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\OrderArticle::preAssignObject
     */
    public function testPreAssignObjectRewritesOrderShopId(): void
    {
        $importer = new OrderArticle();
        $shopObject = oxNew(\OxidEsales\Eshop\Core\Model\BaseModel::class);

        $method = new \ReflectionMethod($importer, 'preAssignObject');
        $method->setAccessible(true);
        $result = $method->invoke(
            $importer,
            $shopObject,
            ['OXID' => 'oa-3', 'OXPERSPARAM' => 'a|b', 'OXORDERSHOPID' => '99'],
            false
        );

        $this->assertSame(1, $result['OXORDERSHOPID']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\OrderArticle::getOrderShopId
     */
    public function testGetOrderShopIdAlwaysReturnsOne(): void
    {
        $importer = new OrderArticle();

        $method = new \ReflectionMethod($importer, 'getOrderShopId');
        $method->setAccessible(true);

        $this->assertSame(1, $method->invoke($importer, '5'));
        $this->assertSame(1, $method->invoke($importer, 'whatever'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\OrderArticle::preAssignObject
     */
    public function testGetBaseTableName(): void
    {
        $this->assertSame('oxorderarticles', (new OrderArticle())->getBaseTableName());
    }
}
