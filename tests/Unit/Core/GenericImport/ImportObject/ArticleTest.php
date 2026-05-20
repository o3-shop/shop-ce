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

use OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Article;

class ArticleTest extends \OxidTestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Article::preAssignObject
     */
    public function testPreAssignObjectSetsDefaultStockFlagWhenIdMissing(): void
    {
        $importer = new Article();
        $shopObject = $this->createMock(\OxidEsales\Eshop\Application\Model\Article::class);
        $shopObject->method('exists')->willReturn(false);

        $method = new \ReflectionMethod($importer, 'preAssignObject');
        $method->setAccessible(true);
        $result = $method->invoke($importer, $shopObject, ['OXID' => ''], false);

        $this->assertSame(1, $result['OXSTOCKFLAG']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Article::preAssignObject
     */
    public function testPreAssignObjectSetsDefaultStockFlagWhenArticleDoesNotYetExist(): void
    {
        $importer = new Article();
        $shopObject = $this->createMock(\OxidEsales\Eshop\Application\Model\Article::class);
        $shopObject->method('exists')->with('art-new')->willReturn(false);

        $method = new \ReflectionMethod($importer, 'preAssignObject');
        $method->setAccessible(true);
        $result = $method->invoke($importer, $shopObject, ['OXID' => 'art-new'], false);

        $this->assertSame(1, $result['OXSTOCKFLAG']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Article::preAssignObject
     */
    public function testPreAssignObjectKeepsExistingStockFlag(): void
    {
        $importer = new Article();
        $shopObject = $this->createMock(\OxidEsales\Eshop\Application\Model\Article::class);

        $method = new \ReflectionMethod($importer, 'preAssignObject');
        $method->setAccessible(true);
        $result = $method->invoke($importer, $shopObject, ['OXID' => 'art-x', 'OXSTOCKFLAG' => 4], false);

        $this->assertSame(4, $result['OXSTOCKFLAG']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Article::preAssignObject
     */
    public function testPreAssignObjectDoesNotSetStockFlagWhenArticleExists(): void
    {
        $importer = new Article();
        $shopObject = $this->createMock(\OxidEsales\Eshop\Application\Model\Article::class);
        $shopObject->method('exists')->with('art-existing')->willReturn(true);

        $method = new \ReflectionMethod($importer, 'preAssignObject');
        $method->setAccessible(true);
        $result = $method->invoke($importer, $shopObject, ['OXID' => 'art-existing'], false);

        $this->assertArrayNotHasKey('OXSTOCKFLAG', $result);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Article::postSaveObject
     */
    public function testPostSaveObjectTriggersOnChangeAndReturnsId(): void
    {
        $importer = new Article();
        $shopObject = $this->createMock(\OxidEsales\Eshop\Application\Model\Article::class);
        $shopObject->method('getId')->willReturn('art-7');
        $shopObject->expects($this->once())
            ->method('onChange')
            ->with(null, 'art-7', 'art-7');

        $method = new \ReflectionMethod($importer, 'postSaveObject');
        $method->setAccessible(true);
        $result = $method->invoke($importer, $shopObject, []);

        $this->assertSame('art-7', $result);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Article::createShopObject
     */
    public function testCreateShopObjectDisablesVariantLoading(): void
    {
        $importer = new Article();

        $method = new \ReflectionMethod($importer, 'createShopObject');
        $method->setAccessible(true);
        $shopObject = $method->invoke($importer);

        $this->assertInstanceOf(\OxidEsales\Eshop\Application\Model\Article::class, $shopObject);

        $loadVariantsProperty = new \ReflectionProperty(\OxidEsales\Eshop\Application\Model\Article::class, '_blLoadVariants');
        $loadVariantsProperty->setAccessible(true);
        $this->assertFalse($loadVariantsProperty->getValue($shopObject));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Article::import
     */
    public function testImportRejectsMissingOxId(): void
    {
        $importer = new Article();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Articlenumber/ID missing');

        $importer->import(['OXID' => '']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Article::import
     */
    public function testImportRejectsTooLongOxId(): void
    {
        $importer = new Article();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('longer then allowed');

        $importer->import(['OXID' => str_repeat('x', 33)]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Core\GenericImport\ImportObject\Article::preAssignObject
     */
    public function testGetBaseTableName(): void
    {
        $this->assertSame('oxarticles', (new Article())->getBaseTableName());
    }
}
