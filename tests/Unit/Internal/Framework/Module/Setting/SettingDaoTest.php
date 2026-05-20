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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\Setting;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Internal\Framework\Config\Utility\ShopSettingEncoderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\TransactionServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Event\SettingChangedEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SettingDaoTest extends TestCase
{
    /**
     * Sequence of fetch() return values. Each query builder created via the
     * factory consumes one value via execute()->fetch().
     *
     * @var array<int, mixed>
     */
    private $fetchQueue = [];

    /**
     * Factory mock that hands out a fresh QueryBuilder mock for each create()
     * call. Each builder's execute() returns a statement whose fetch() pops
     * from $this->fetchQueue.
     */
    private function makeFactory(): QueryBuilderFactoryInterface
    {
        $factory = $this->createMock(QueryBuilderFactoryInterface::class);
        $factory->method('create')->willReturnCallback(function () {
            $qb = $this->getMockBuilder(QueryBuilder::class)
                ->disableOriginalConstructor()
                ->onlyMethods([
                    'insert', 'values', 'select', 'from', 'where', 'andWhere',
                    'delete', 'setParameters', 'execute',
                ])
                ->getMock();
            foreach (['insert', 'values', 'select', 'from', 'where', 'andWhere', 'delete', 'setParameters'] as $method) {
                $qb->method($method)->willReturnSelf();
            }
            $stmt = new class ($this->fetchQueue) {
                private $queue;
                public function __construct(array &$queue)
                {
                    $this->queue = &$queue;
                }
                public function fetch()
                {
                    return $this->queue ? array_shift($this->queue) : false;
                }
            };
            // Bind via reference: stmt sees the live fetchQueue.
            $stmt = (function (array &$queue) {
                return new class ($queue) {
                    /** @var mixed */
                    public $next;
                    public function __construct(array &$queue)
                    {
                        $this->queue = & $queue;
                    }
                    public $queue;
                    public function fetch()
                    {
                        if (empty($this->queue)) {
                            return false;
                        }
                        return array_shift($this->queue);
                    }
                };
            })($this->fetchQueue);
            $qb->method('execute')->willReturn($stmt);
            return $qb;
        });
        return $factory;
    }

    private function makeDao(
        ?QueryBuilderFactoryInterface $factory = null,
        ?TransactionServiceInterface $transaction = null,
        ?EventDispatcherInterface $dispatcher = null,
        ?ShopSettingEncoderInterface $encoder = null,
        ?ShopAdapterInterface $adapter = null
    ): SettingDao {
        return new SettingDao(
            $factory ?: $this->makeFactory(),
            $this->createMock(ContextInterface::class),
            $encoder ?: $this->createMock(ShopSettingEncoderInterface::class),
            $adapter ?: $this->createMock(ShopAdapterInterface::class),
            $transaction ?: $this->createMock(TransactionServiceInterface::class),
            $dispatcher ?: $this->createMock(EventDispatcherInterface::class)
        );
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::__construct
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::save
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::deleteFromOxConfigTable
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::deleteFromOxConfigDisplayTable
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::saveDataToOxConfigTable
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::saveDataToOxConfigDisplayTable
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::dispatchEvent
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::getPrefixedModuleId
     */
    public function testSaveCommitsTransactionAndDispatchesSettingChangedEvent(): void
    {
        $transaction = $this->createMock(TransactionServiceInterface::class);
        $transaction->expects($this->once())->method('begin');
        $transaction->expects($this->once())->method('commit');
        $transaction->expects($this->never())->method('rollback');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                SettingChangedEvent::NAME,
                $this->callback(function (SettingChangedEvent $event) {
                    $this->assertSame('mySetting', $event->getSettingName());
                    $this->assertSame(7, $event->getShopId());
                    $this->assertSame('mymod', $event->getModuleId());
                    return true;
                })
            );

        $encoder = $this->createMock(ShopSettingEncoderInterface::class);
        $encoder->expects($this->once())->method('encode')->willReturn('encoded-value');

        $adapter = $this->createMock(ShopAdapterInterface::class);
        $adapter->method('generateUniqueId')->willReturn('unique-id');

        $setting = new Setting();
        $setting->setName('mySetting')->setType('string')->setValue('rawValue');

        $dao = $this->makeDao(null, $transaction, $dispatcher, $encoder, $adapter);
        $dao->save($setting, 'mymod', 7);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::save
     */
    public function testSaveRollsBackTransactionAndRethrowsOnFailure(): void
    {
        $factory = $this->createMock(QueryBuilderFactoryInterface::class);
        $factory->method('create')->willThrowException(new \RuntimeException('boom'));

        $transaction = $this->createMock(TransactionServiceInterface::class);
        $transaction->expects($this->once())->method('begin');
        $transaction->expects($this->never())->method('commit');
        $transaction->expects($this->once())->method('rollback');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $dao = $this->makeDao($factory, $transaction, $dispatcher);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');
        $dao->save(new Setting(), 'mymod', 7);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::delete
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::deleteFromOxConfigTable
     */
    public function testDeleteOnlyRemovesFromOxConfigTable(): void
    {
        $callCount = 0;
        $factory = $this->createMock(QueryBuilderFactoryInterface::class);
        $factory->method('create')->willReturnCallback(function () use (&$callCount) {
            $callCount++;
            $qb = $this->getMockBuilder(QueryBuilder::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['delete', 'where', 'andWhere', 'setParameters', 'execute'])
                ->getMock();
            foreach (['delete', 'where', 'andWhere', 'setParameters'] as $method) {
                $qb->method($method)->willReturnSelf();
            }
            $qb->method('execute')->willReturn(null);
            return $qb;
        });

        $setting = new Setting();
        $setting->setName('mySetting');

        $dao = $this->makeDao($factory);
        $dao->delete($setting, 'mymod', 7);

        $this->assertSame(1, $callCount, 'delete() should issue exactly one DELETE query');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::get
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::getDataFromOxConfigTable
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::getDataFromOxConfigDisplayTable
     */
    public function testGetReturnsHydratedSettingFromBothTables(): void
    {
        $this->fetchQueue = [
            ['type' => 'string', 'value' => 'encoded', 'name' => 'mySetting'],
            [
                'oxgrouping' => 'general',
                'oxpos' => '3',
                'oxvarconstraint' => 'foo|bar|baz',
            ],
        ];

        $encoder = $this->createMock(ShopSettingEncoderInterface::class);
        $encoder->method('decode')->with('string', 'encoded')->willReturn('decoded value');

        $dao = $this->makeDao(null, null, null, $encoder);
        $setting = $dao->get('mySetting', 'mymod', 5);

        $this->assertSame('mySetting', $setting->getName());
        $this->assertSame('decoded value', $setting->getValue());
        $this->assertSame('string', $setting->getType());
        $this->assertSame(['foo', 'bar', 'baz'], $setting->getConstraints());
        $this->assertSame('general', $setting->getGroupName());
        $this->assertSame(3, $setting->getPositionInGroup());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::get
     */
    public function testGetSkipsConstraintsAndDisplayFieldsWhenAbsent(): void
    {
        $this->fetchQueue = [
            ['type' => 'bool', 'value' => '1', 'name' => 'mySetting'],
            // empty result for oxconfigdisplay → result is `false`, mapped to []
            false,
        ];

        $encoder = $this->createMock(ShopSettingEncoderInterface::class);
        $encoder->method('decode')->willReturn(true);

        $dao = $this->makeDao(null, null, null, $encoder);
        $setting = $dao->get('mySetting', 'mymod', 5);

        $this->assertSame('mySetting', $setting->getName());
        $this->assertSame('bool', $setting->getType());
        $this->assertTrue($setting->getValue());
        $this->assertSame([], $setting->getConstraints());
        $this->assertSame('', $setting->getGroupName());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::getDataFromOxConfigTable
     */
    public function testGetThrowsWhenSettingMissingInOxConfig(): void
    {
        $this->fetchQueue = [
            false, // first query returns nothing → exception
        ];

        $dao = $this->makeDao();

        $this->expectException(EntryDoesNotExistDaoException::class);
        $this->expectExceptionMessage('Setting mySetting');
        $dao->get('mySetting', 'mymod', 5);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao::get
     */
    public function testGetIgnoresEmptyConstraintsString(): void
    {
        $this->fetchQueue = [
            ['type' => 'string', 'value' => 'enc', 'name' => 'name'],
            ['oxgrouping' => 'g', 'oxpos' => '0', 'oxvarconstraint' => ''],
        ];

        $encoder = $this->createMock(ShopSettingEncoderInterface::class);
        $encoder->method('decode')->willReturn('val');

        $dao = $this->makeDao(null, null, null, $encoder);
        $setting = $dao->get('name', 'mymod', 5);
        $this->assertSame([], $setting->getConstraints());
    }
}
