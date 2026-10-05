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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Controller\Admin\RevocationList;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;

/**
 * Stub model used by {@see RevocationListTest} so the parent
 * AdminListController::deleteEntry() returns immediately on the
 * isDerived() guard, isolating us from any DB interaction.
 */
class RevocationListTest_StubModel extends BaseModel
{
    public function isDerived()
    {
        return true;
    }
}

/**
 * Unit tests for the §356a admin revocation list controller.
 *
 * Verifies the audit-log NOTICE the override emits before the canonical
 * parent delete runs, and that nothing is logged when the form arrives
 * without a real oxid (initial render or after a previous delete).
 */
class RevocationListTest extends \OxidTestCase
{
    /** @var array<int,array{level:string,message:string}> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->logged = [];
        $sink = &$this->logged;
        Registry::set('logger', new class ($sink) extends AbstractLogger {
            /** @var array<int,array{level:string,message:string}> */
            private array $sink;
            /** @param array<int,array{level:string,message:string}> $sink */
            public function __construct(array &$sink)
            {
                $this->sink = &$sink;
            }
            public function log($level, $message, array $context = []): void
            {
                $this->sink[] = ['level' => (string) $level, 'message' => (string) $message];
            }
        });
    }

    protected function tearDown(): void
    {
        Registry::set('logger', new NullLogger());
        parent::tearDown();
    }

    public function testListClassDefaultsAndSorting(): void
    {
        $controller = $this->getProxyClass(RevocationList::class);
        $this->assertSame(\OxidEsales\Eshop\Application\Model\O3Revocation::class, $controller->getNonPublicVar('_sListClass'));
        $this->assertSame('oxlist', $controller->getNonPublicVar('_sListType'));
        $this->assertSame('revocation_list.tpl', $controller->getNonPublicVar('_sThisTemplate'));
        $this->assertSame('oxsubmitted', $controller->getNonPublicVar('_sDefSortField'));
        $this->assertTrue((bool) $controller->getNonPublicVar('_blDesc'));
    }

    public function testDeleteEntryEmitsAuditNoticeAndDelegatesToParent(): void
    {
        Registry::getSession()->setVariable('auth', 'admin-oxid-42');

        $controller = $this->makeIsolatedController('rev-oxid-007');
        $controller->deleteEntry();

        $notices = $this->noticeMessages();
        $this->assertCount(1, $notices, 'A NOTICE audit line must be written when deleting a real submission.');
        $this->assertStringContainsString('admin-oxid-42', $notices[0]);
        $this->assertStringContainsString('rev-oxid-007', $notices[0]);
        $this->assertStringContainsString('manually deleted revocation submission', $notices[0]);
    }

    public function testDeleteEntryStaysSilentWhenOxidIsEmpty(): void
    {
        Registry::getSession()->setVariable('auth', 'admin-oxid-42');

        $controller = $this->makeIsolatedController('');
        $controller->deleteEntry();

        $this->assertSame([], $this->noticeMessages(), 'No audit line for empty oxid.');
    }

    public function testDeleteEntryStaysSilentForResetSentinelOxid(): void
    {
        Registry::getSession()->setVariable('auth', 'admin-oxid-42');

        $controller = $this->makeIsolatedController('-1');
        $controller->deleteEntry();

        $this->assertSame([], $this->noticeMessages(), 'No audit line for the -1 reset sentinel.');
    }

    public function testDeleteEntryFallsBackToUnknownAdminWhenSessionMissing(): void
    {
        Registry::getSession()->setVariable('auth', null);

        $controller = $this->makeIsolatedController('rev-oxid-007');
        $controller->deleteEntry();

        $notices = $this->noticeMessages();
        $this->assertCount(1, $notices);
        $this->assertStringContainsString("'unknown'", $notices[0]);
    }

    /**
     * Build a RevocationList that:
     *   - returns the given oxid from getEditObjectId()
     *   - has its _sListClass swapped for a stub whose isDerived() === true,
     *     so AdminListController::deleteEntry() exits early and never
     *     touches the DB.
     */
    private function makeIsolatedController(string $oxidToDelete): RevocationList
    {
        $controller = $this->getMock(RevocationList::class, ['getEditObjectId']);
        $controller->expects($this->any())
            ->method('getEditObjectId')
            ->will($this->returnValue($oxidToDelete));

        $reflection = new \ReflectionClass(RevocationList::class);
        $listClassProp = $reflection->getProperty('_sListClass');
        $listClassProp->setAccessible(true);
        $listClassProp->setValue($controller, RevocationListTest_StubModel::class);

        return $controller;
    }

    /** @return string[] */
    private function noticeMessages(): array
    {
        return array_values(array_map(
            static fn ($entry) => $entry['message'],
            array_filter($this->logged, static fn ($e) => $e['level'] === 'notice')
        ));
    }
}
