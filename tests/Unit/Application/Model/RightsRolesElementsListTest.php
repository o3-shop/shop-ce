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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\RightsRolesElement;
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

/**
 * Test stub for the list element. Records assign() / save() so we can verify
 * the data the list passes into each freshly-oxNew()'d element. oxNew creates
 * an instance of whatever class is in $_sObjectsInListName, so pointing the
 * list at this class lets us bypass the real RightsRolesElement/DB.
 */
class RightsRolesElementsListTest_RecorderElement
{
    /** @var array<int, array> shared sink for assign() arguments */
    public static $assignedRows = [];

    public function assign($data)
    {
        self::$assignedRows[] = $data;
    }

    public function save()
    {
        return true;
    }
}

/**
 * Subclass exposing test seams: lets us pass canned QueryBuilder / baseObject /
 * captured SQL / fake list contents so we don't need a populated database.
 */
class RightsRolesElementsListTest_Stub extends RightsRolesElementsList
{
    /** @var QueryBuilder|null */
    public $queryBuilderToReturn;
    /** @var object|null */
    public $baseObjectToReturn;
    /** @var array */
    public $selectStringCalls = [];
    /** @var array */
    public $arrayItems = [];

    public function __construct()
    {
        // bypass ListModel constructor (no oxNew/DB)
    }

    public function getQueryBuilder(): QueryBuilder
    {
        if ($this->queryBuilderToReturn === null) {
            throw new \RuntimeException('queryBuilderToReturn not set on stub');
        }
        return $this->queryBuilderToReturn;
    }

    public function getBaseObject()
    {
        return $this->baseObjectToReturn;
    }

    public function selectString($sql, array $parameters = [])
    {
        $this->selectStringCalls[] = ['sql' => $sql, 'parameters' => $parameters];
    }

    public function getArray()
    {
        return $this->arrayItems;
    }

    public function setObjectsInListName(string $name): void
    {
        $this->_sObjectsInListName = $name;
    }
}

class RightsRolesElementsListTest extends \OxidTestCase
{
    private function makeBaseObjectStub(string $viewName = 'oxv_o3rightsroleselement', string $coreTable = 'o3rightsroleselement'): object
    {
        $base = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getViewName', 'getCoreTableName'])
            ->getMock();
        $base->method('getViewName')->willReturn($viewName);
        $base->method('getCoreTableName')->willReturn($coreTable);
        return $base;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList::filterEmptyButZero
     */
    public function testFilterEmptyButZeroAcceptsZeroAndStringContent(): void
    {
        $list = new RightsRolesElementsListTest_Stub();
        $reflection = new \ReflectionMethod($list, 'filterEmptyButZero');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->invoke($list, 0));
        $this->assertTrue($reflection->invoke($list, '0'));
        $this->assertTrue($reflection->invoke($list, 'value'));

        $this->assertFalse($reflection->invoke($list, null));
        $this->assertFalse($reflection->invoke($list, false));
        $this->assertFalse($reflection->invoke($list, ''));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList::getElementsByObjectId
     */
    public function testGetElementsByObjectIdBuildsSqlAndPassesItToSelectString(): void
    {
        $list = new RightsRolesElementsListTest_Stub();
        $list->baseObjectToReturn = $this->makeBaseObjectStub('oxv_o3rightsroleselement_de');
        $list->queryBuilderToReturn = $this->buildRealQueryBuilder();

        $result = $list->getElementsByObjectId('user-42');

        $this->assertSame($list, $result);
        $this->assertCount(1, $list->selectStringCalls);

        $call = $list->selectStringCalls[0];
        $this->assertStringContainsString('SELECT *', $call['sql']);
        $this->assertStringContainsString('FROM oxv_o3rightsroleselement_de', $call['sql']);
        $this->assertStringContainsString('objectid =', $call['sql']);
        $this->assertContains('user-42', $call['parameters']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList::getElementsIdsByObjectId
     */
    public function testGetElementsIdsByObjectIdMapsArrayItemsToIdAndType(): void
    {
        $list = new RightsRolesElementsListTest_Stub();
        $list->baseObjectToReturn = $this->makeBaseObjectStub();
        $list->queryBuilderToReturn = $this->buildRealQueryBuilder();
        $list->arrayItems = [
            $this->makeRightsRolesElementStub('NAVI_ORDER', 1),
            $this->makeRightsRolesElementStub('NAVI_USER', 2),
            $this->makeRightsRolesElementStub('NAVI_KEEP_ZERO', 0),
        ];

        $result = $list->getElementsIdsByObjectId('user-x');

        $this->assertSame(
            [
                'NAVI_ORDER' => 1,
                'NAVI_USER' => 2,
                'NAVI_KEEP_ZERO' => 0,
            ],
            $result
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList::setNaviSettings
     */
    public function testSetNaviSettingsDeletesAndStoresEachAssignment(): void
    {
        RightsRolesElementsListTest_RecorderElement::$assignedRows = [];

        $list = new RightsRolesElementsListTest_Stub();
        $list->baseObjectToReturn = $this->makeBaseObjectStub('view', 'core_table');
        $list->setObjectsInListName(RightsRolesElementsListTest_RecorderElement::class);

        $expr = $this->createMock(\Doctrine\DBAL\Query\Expression\ExpressionBuilder::class);
        $expr->method('eq')->willReturn('objectid = :p');
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('expr')->willReturn($expr);
        $qb->expects($this->once())->method('delete')->with('core_table')->willReturnSelf();
        $qb->expects($this->once())->method('where')->willReturnSelf();
        $qb->method('createNamedParameter')->willReturn(':obj');
        $qb->expects($this->once())->method('execute');
        $list->queryBuilderToReturn = $qb;

        $list->setNaviSettings([
            'NAVI_ORDER' => 1,
            'NAVI_USER' => 2,
        ], 'user-x');

        $this->assertSame(
            [
                ['elementid' => 'NAVI_ORDER', 'objectid' => 'user-x', 'type' => 1],
                ['elementid' => 'NAVI_USER', 'objectid' => 'user-x', 'type' => 2],
            ],
            RightsRolesElementsListTest_RecorderElement::$assignedRows
        );

        RightsRolesElementsListTest_RecorderElement::$assignedRows = [];
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList::getQueryBuilder
     */
    public function testGetQueryBuilderResolvesFromContainerFactory(): void
    {
        $list = new RightsRolesElementsList();
        $qb = $list->getQueryBuilder();
        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $factory = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class);
        $this->assertInstanceOf(QueryBuilderFactoryInterface::class, $factory);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList::getElementsByUserId
     */
    public function testGetElementsByUserIdReturnsCombinedIdTypeMap(): void
    {
        $rows = [
            ['elementid' => 'NAVI_ORDER', 'type' => '1'],
            ['elementid' => 'NAVI_USER', 'type' => '2'],
        ];
        $list = $this->makeListWithFetchAllResult($rows);

        $result = $list->getElementsByUserId('user-1');

        $this->assertSame(
            [
                'NAVI_ORDER' => 1,
                'NAVI_USER' => 2,
            ],
            $result
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList::getElementsByUserId
     */
    public function testGetElementsByUserIdKeepsZeroTypeButFiltersEmptyId(): void
    {
        $rows = [
            ['elementid' => 'NAVI_X', 'type' => '5'],
            ['elementid' => 'NAVI_NULL', 'type' => null],
        ];
        $list = $this->makeListWithFetchAllResult($rows);

        $this->assertSame(
            [
                'NAVI_X' => 5,
                'NAVI_NULL' => 0,
            ],
            $list->getElementsByUserId('user-2')
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\RightsRolesElementsList::getRestrictedViewElements
     */
    public function testGetRestrictedViewElementsReturnsCombinedIdTypeMap(): void
    {
        $user = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $user->method('getId')->willReturn('user-r');
        $session = $this->getMockBuilder(\OxidEsales\Eshop\Core\Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser'])
            ->getMock();
        $session->method('getUser')->willReturn($user);
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Session::class, $session);

        $rows = [
            ['elementid' => 'NAVI_X', 'type' => '5'],
            ['elementid' => 'NAVI_Y', 'type' => '6'],
        ];
        $list = $this->makeListWithFetchAllResult($rows);

        $this->assertSame(
            ['NAVI_X' => 5, 'NAVI_Y' => 6],
            $list->getRestrictedViewElements()
        );
    }

    private function makeRightsRolesElementStub(string $elementId, int $type): RightsRolesElement
    {
        $element = $this->getMockBuilder(RightsRolesElement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFieldData'])
            ->getMock();
        $element->method('getFieldData')->willReturnCallback(function ($field) use ($elementId, $type) {
            return $field === 'elementid' ? $elementId : $type;
        });
        return $element;
    }

    /**
     * Wires a stub list whose QueryBuilder->execute()->fetchAllAssociative() returns
     * the given rows on every call.
     */
    private function makeListWithFetchAllResult(array $rows): RightsRolesElementsListTest_Stub
    {
        $list = new RightsRolesElementsListTest_Stub();
        $list->baseObjectToReturn = $this->makeBaseObjectStub();

        $stmt = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['fetchAllAssociative'])
            ->getMock();
        $stmt->method('fetchAllAssociative')->willReturn($rows);

        // Real ExpressionBuilder so ->and() returns a CompositeExpression as
        // declared in its return type.
        $expr = new \Doctrine\DBAL\Query\Expression\ExpressionBuilder(
            $this->createMock(\Doctrine\DBAL\Connection::class)
        );

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('expr')->willReturn($expr);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('createNamedParameter')->willReturn(':p');
        $qb->method('execute')->willReturn($stmt);
        $list->queryBuilderToReturn = $qb;

        return $list;
    }

    private function buildRealQueryBuilder(): QueryBuilder
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class)
            ->create();
    }
}
