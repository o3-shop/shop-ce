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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Controller\Admin;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Controller\Admin\RevocationMain;
use OxidEsales\EshopCommunity\Application\Model\O3Revocation;
use OxidEsales\TestingLibrary\UnitTestCase;
use Psr\Log\NullLogger;

/**
 * Integration tests for the admin actions on a revocation submission.
 *
 * Verifies the spec invariants captured in the "Admin detail view with
 * manual resend" and "No automatic deletion" requirements:
 *
 *   - Resend re-attempts the customer email; on success, OXSENDFAILED
 *     clears and OXSUBMITTED stays untouched (write-once invariant).
 *   - Resend on a failing send keeps OXSENDFAILED set; OXSUBMITTED
 *     still untouched.
 *   - Manual delete removes the row and emits a single audit
 *     NOTICE log line.
 *
 * Uses a real DB (the o3revocation table from phase 1) and a mocked
 * mailer / mocked logger so the tests stay fast and deterministic.
 */
class RevocationMainTest extends UnitTestCase
{
    private const TEST_OXID_PREFIX = '_revadmin_';

    /** @var array<int,array{string,string}> captured (level, message) log calls */
    private array $loggedCalls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggedCalls = [];
        $this->mockLogger();
    }

    protected function tearDown(): void
    {
        DatabaseProvider::getDb()->execute(
            "DELETE FROM o3revocation WHERE OXID LIKE '" . self::TEST_OXID_PREFIX . "%'"
        );
        parent::tearDown();
    }

    public function testResendSuccessClearsTheSendFailedFlagAndPreservesOxsubmitted(): void
    {
        $submission = $this->seedSubmission(self::TEST_OXID_PREFIX . 'resend_ok', /* preFail */ true);
        $originalSubmitted = $this->fetchColumn($submission->getId(), 'OXSUBMITTED');

        $this->mockMailer(/* sendReturns */ true);

        $controller = $this->makeControllerEditing($submission->getId());
        $controller->resend();

        $row = $this->fetchRow($submission->getId());
        $this->assertSame('0', (string) $row['OXSENDFAILED'], 'Successful resend must clear OXSENDFAILED.');
        $this->assertSame(
            $originalSubmitted,
            $row['OXSUBMITTED'],
            'OXSUBMITTED must stay frozen across the resend (write-once invariant).'
        );
    }

    public function testResendFailureRetainsTheSendFailedFlag(): void
    {
        $submission = $this->seedSubmission(self::TEST_OXID_PREFIX . 'resend_fail', /* preFail */ true);
        $originalSubmitted = $this->fetchColumn($submission->getId(), 'OXSUBMITTED');

        $this->mockMailer(/* sendReturns */ false);

        $controller = $this->makeControllerEditing($submission->getId());
        $controller->resend();

        $row = $this->fetchRow($submission->getId());
        $this->assertSame('1', (string) $row['OXSENDFAILED'], 'Failed resend keeps OXSENDFAILED set.');
        $this->assertSame($originalSubmitted, $row['OXSUBMITTED']);
    }

    public function testDeleteRemovesTheRowAndEmitsAuditLog(): void
    {
        $submission = $this->seedSubmission(self::TEST_OXID_PREFIX . 'delete', false);
        $oxid = $submission->getId();

        $controller = $this->makeControllerEditing($oxid);
        $controller->deleteEntry();

        $remaining = (int) DatabaseProvider::getDb()->getOne(
            'SELECT COUNT(*) FROM o3revocation WHERE OXID = ?',
            [$oxid]
        );
        $this->assertSame(0, $remaining, 'Manual delete must remove the row.');

        $auditEntries = array_filter(
            $this->loggedCalls,
            fn (array $call) => $call[0] === 'notice' && strpos($call[1], $oxid) !== false
        );
        $this->assertCount(
            1,
            $auditEntries,
            'Manual delete emits exactly one NOTICE audit log line naming the submission OXID.'
        );
    }

    private function seedSubmission(string $oxid, bool $preFail): O3Revocation
    {
        $submission = oxNew(O3Revocation::class);
        $submission->setId($oxid);
        $submission->assign([
            'oxshopid' => 1,
            'oxlang' => 0,
            'oxname' => 'Maria Schmidt',
            'oxorderident' => 'ORDER-A1',
            'oxemail' => 'maria@example.com',
        ]);
        if ($preFail) {
            $submission->markSendFailed();
        }
        $submission->save();
        return $submission;
    }

    private function makeControllerEditing(string $oxid): RevocationMain
    {
        $controller = (new \ReflectionClass(RevocationMain::class))->newInstanceWithoutConstructor();
        $controller->setEditObjectId($oxid);
        return $controller;
    }

    private function mockMailer(bool $sendReturns): void
    {
        $mailer = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendRevocationEmailToCustomer'])
            ->getMock();
        $mailer->method('sendRevocationEmailToCustomer')->willReturn($sendReturns);
        Registry::set(Email::class, $mailer);
    }

    private function mockLogger(): void
    {
        $logger = new class ($this->loggedCalls) extends NullLogger {
            /** @var array<int,array{string,string}> */
            private array $sink;

            public function __construct(array &$sink)
            {
                $this->sink = &$sink;
            }

            public function notice($message, array $context = []): void
            {
                $this->sink[] = ['notice', (string) $message];
            }

            public function error($message, array $context = []): void
            {
                $this->sink[] = ['error', (string) $message];
            }

            public function warning($message, array $context = []): void
            {
                $this->sink[] = ['warning', (string) $message];
            }
        };
        Registry::set('logger', $logger);
    }

    private function fetchRow(string $oxid): array
    {
        $row = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getRow(
            'SELECT * FROM o3revocation WHERE OXID = ?',
            [$oxid]
        );
        return $row !== false ? $row : [];
    }

    private function fetchColumn(string $oxid, string $column): string
    {
        return (string) DatabaseProvider::getDb()->getOne(
            "SELECT $column FROM o3revocation WHERE OXID = ?",
            [$oxid]
        );
    }
}
