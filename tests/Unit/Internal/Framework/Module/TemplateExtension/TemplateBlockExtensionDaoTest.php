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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\TemplateExtension;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use PHPUnit\Framework\TestCase;

class TemplateBlockExtensionDaoTest extends TestCase
{
    private function makeQueryBuilder(): QueryBuilder
    {
        // Use a real ExpressionBuilder so ->and() returns a CompositeExpression
        // (its declared return type), not a string.
        $expr = new ExpressionBuilder($this->createMock(\Doctrine\DBAL\Connection::class));

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'insert', 'values', 'select', 'from', 'where', 'andWhere', 'delete',
                'setParameters', 'execute', 'expr',
            ])
            ->getMock();
        $qb->method('insert')->willReturnSelf();
        $qb->method('values')->willReturnSelf();
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('delete')->willReturnSelf();
        $qb->method('setParameters')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        return $qb;
    }

    private function makeStatement($fetchOneReturn = false, array $fetchAllReturn = []): object
    {
        return new class ($fetchOneReturn, $fetchAllReturn) {
            /** @var mixed */
            public $fetchOneReturn;
            /** @var array */
            public $fetchAllReturn;
            public function __construct($fetchOneReturn, array $fetchAllReturn)
            {
                $this->fetchOneReturn = $fetchOneReturn;
                $this->fetchAllReturn = $fetchAllReturn;
            }
            public function fetchOne()
            {
                return $this->fetchOneReturn;
            }
            public function fetchAll(): array
            {
                return $this->fetchAllReturn;
            }
        };
    }

    private function makeExtension(array $overrides = []): TemplateBlockExtension
    {
        $defaults = [
            'shopId' => 1,
            'moduleId' => 'mymod',
            'themeId' => 'o3-theme',
            'name' => 'product_main',
            'filePath' => 'mymod/views/blocks/product_main.tpl',
            'templatePath' => 'page/details/inc/productmain.tpl',
            'position' => 1,
        ];
        $values = array_merge($defaults, $overrides);

        $extension = new TemplateBlockExtension();
        $extension->setShopId($values['shopId'])
            ->setModuleId($values['moduleId'])
            ->setThemeId($values['themeId'])
            ->setName($values['name'])
            ->setFilePath($values['filePath'])
            ->setExtendedBlockTemplatePath($values['templatePath'])
            ->setPosition($values['position']);

        return $extension;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao::__construct
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao::add
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao::exists
     */
    public function testAddSkipsInsertWhenExtensionAlreadyExists(): void
    {
        $existsQb = $this->makeQueryBuilder();
        $existsQb->method('execute')->willReturn($this->makeStatement(true));
        // We expect NO insert call on this QB because the existence check short-circuits.
        $existsQb->expects($this->never())->method('insert');

        $factory = $this->createMock(QueryBuilderFactoryInterface::class);
        $factory->expects($this->once())->method('create')->willReturn($existsQb);

        $shopAdapter = $this->createMock(ShopAdapterInterface::class);
        $shopAdapter->expects($this->never())->method('generateUniqueId');

        $dao = new TemplateBlockExtensionDao($factory, $shopAdapter);
        $dao->add($this->makeExtension());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao::add
     */
    public function testAddInsertsWithGeneratedUniqueIdAndExtensionFieldsWhenNotPresent(): void
    {
        $existsQb = $this->makeQueryBuilder();
        $existsQb->method('execute')->willReturn($this->makeStatement(false));

        $insertQb = $this->makeQueryBuilder();
        $capturedParameters = [];
        $insertQb->method('setParameters')->willReturnCallback(function ($parameters) use (&$capturedParameters, $insertQb) {
            $capturedParameters = $parameters;
            return $insertQb;
        });
        $insertQb->method('execute')->willReturn(1);

        $factory = $this->createMock(QueryBuilderFactoryInterface::class);
        $factory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($existsQb, $insertQb);

        $shopAdapter = $this->createMock(ShopAdapterInterface::class);
        $shopAdapter->expects($this->once())->method('generateUniqueId')->willReturn('generated-uid-1');

        $dao = new TemplateBlockExtensionDao($factory, $shopAdapter);
        $dao->add($this->makeExtension(['shopId' => 5, 'moduleId' => 'mymod', 'name' => 'header_block']));

        $this->assertSame('generated-uid-1', $capturedParameters['id']);
        $this->assertSame(5, $capturedParameters['shopId']);
        $this->assertSame('mymod', $capturedParameters['moduleId']);
        $this->assertSame('header_block', $capturedParameters['name']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao::exists
     */
    public function testExistsReturnsTrueWhenFetchOneReturnsTruthy(): void
    {
        $qb = $this->makeQueryBuilder();
        $qb->method('execute')->willReturn($this->makeStatement('1'));

        $factory = $this->createMock(QueryBuilderFactoryInterface::class);
        $factory->method('create')->willReturn($qb);

        $dao = new TemplateBlockExtensionDao($factory, $this->createMock(ShopAdapterInterface::class));
        $this->assertTrue($dao->exists($this->makeExtension()));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao::exists
     */
    public function testExistsReturnsFalseWhenFetchOneReturnsFalsy(): void
    {
        $qb = $this->makeQueryBuilder();
        $qb->method('execute')->willReturn($this->makeStatement(false));

        $factory = $this->createMock(QueryBuilderFactoryInterface::class);
        $factory->method('create')->willReturn($qb);

        $dao = new TemplateBlockExtensionDao($factory, $this->createMock(ShopAdapterInterface::class));
        $this->assertFalse($dao->exists($this->makeExtension()));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao::getExtensions
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao::mapDataToObjects
     */
    public function testGetExtensionsMapsRowsToTemplateBlockExtensionObjects(): void
    {
        $rows = [
            [
                'OXSHOPID' => '5',
                'OXMODULE' => 'mymod',
                'OXTHEME' => 'o3-theme',
                'OXBLOCKNAME' => 'product_main',
                'OXFILE' => 'mymod/views/blocks/product_main.tpl',
                'OXTEMPLATE' => 'page/details/inc/productmain.tpl',
                'OXPOS' => '3',
            ],
            [
                'OXSHOPID' => '5',
                'OXMODULE' => 'othermod',
                'OXTHEME' => 'wave',
                'OXBLOCKNAME' => 'product_main',
                'OXFILE' => 'othermod/views/blocks/product_main.tpl',
                'OXTEMPLATE' => 'page/details/inc/productmain.tpl',
                'OXPOS' => '7',
            ],
        ];

        $qb = $this->makeQueryBuilder();
        $qb->method('execute')->willReturn($this->makeStatement(false, $rows));

        $factory = $this->createMock(QueryBuilderFactoryInterface::class);
        $factory->method('create')->willReturn($qb);

        $dao = new TemplateBlockExtensionDao($factory, $this->createMock(ShopAdapterInterface::class));
        $result = $dao->getExtensions('product_main', 5);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(TemplateBlockExtension::class, $result);

        $this->assertSame(5, $result[0]->getShopId());
        $this->assertSame('mymod', $result[0]->getModuleId());
        $this->assertSame('o3-theme', $result[0]->getThemeId());
        $this->assertSame('product_main', $result[0]->getName());
        $this->assertSame('mymod/views/blocks/product_main.tpl', $result[0]->getFilePath());
        $this->assertSame('page/details/inc/productmain.tpl', $result[0]->getExtendedBlockTemplatePath());
        $this->assertSame(3, $result[0]->getPosition());

        $this->assertSame(7, $result[1]->getPosition());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao::getExtensions
     */
    public function testGetExtensionsReturnsEmptyArrayForNoRows(): void
    {
        $qb = $this->makeQueryBuilder();
        $qb->method('execute')->willReturn($this->makeStatement(false, []));

        $factory = $this->createMock(QueryBuilderFactoryInterface::class);
        $factory->method('create')->willReturn($qb);

        $dao = new TemplateBlockExtensionDao($factory, $this->createMock(ShopAdapterInterface::class));
        $this->assertSame([], $dao->getExtensions('product_main', 5));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension\TemplateBlockExtensionDao::deleteExtensions
     */
    public function testDeleteExtensionsExecutesDeleteWithModuleAndShopParameters(): void
    {
        $qb = $this->makeQueryBuilder();
        $captured = [];
        $qb->method('setParameters')->willReturnCallback(function ($params) use (&$captured, $qb) {
            $captured = $params;
            return $qb;
        });
        $qb->expects($this->once())->method('delete')->with('oxtplblocks');
        $qb->expects($this->once())->method('execute');

        $factory = $this->createMock(QueryBuilderFactoryInterface::class);
        $factory->method('create')->willReturn($qb);

        $dao = new TemplateBlockExtensionDao($factory, $this->createMock(ShopAdapterInterface::class));
        $dao->deleteExtensions('mymod', 5);

        $this->assertSame(['shopId' => 5, 'moduleId' => 'mymod'], $captured);
    }
}
