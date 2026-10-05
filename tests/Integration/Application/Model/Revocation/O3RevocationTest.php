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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Model\Revocation;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Application\Model\O3Revocation;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Integration tests for the {@see O3Revocation} model.
 *
 * Validates the two persistence invariants from spec D3 / spec requirement
 * "Persistence schema and immutability":
 *   - `OXSUBMITTED` is write-once (set on first save, never updated).
 *   - `OXTIMESTAMP` is auto-maintained by the DB on UPDATE.
 *
 * Also covers the typed getters and the send-failure flag.
 */
class O3RevocationTest extends UnitTestCase
{
    private const TEST_OXID_PREFIX = '_revtest_';

    protected function tearDown(): void
    {
        // Remove any rows the tests inserted under the test-OXID prefix.
        DatabaseProvider::getDb()->execute(
            "DELETE FROM o3revocation WHERE OXID LIKE '" . self::TEST_OXID_PREFIX . "%'"
        );
        parent::tearDown();
    }

    public function testSaveAssignsOxsubmittedWhenCallerLeavesItUnset(): void
    {
        $submission = $this->makeSubmission(self::TEST_OXID_PREFIX . 'auto_ts');
        $submission->save();

        $row = $this->fetchRowById($submission->getId());
        $this->assertNotEmpty($row);
        $this->assertNotEmpty($row['OXSUBMITTED']);
        $this->assertNotSame('0000-00-00 00:00:00', $row['OXSUBMITTED']);
    }

    public function testTypedGettersReturnExpectedScalarTypes(): void
    {
        $submission = $this->makeSubmission(self::TEST_OXID_PREFIX . 'getters');
        $submission->save();

        $reloaded = oxNew(O3Revocation::class);
        $reloaded->load($submission->getId());

        $this->assertSame($submission->getId(), $reloaded->getId());
        $this->assertSame(1, $reloaded->getShopId());
        $this->assertSame(0, $reloaded->getLang());
        $this->assertSame('Maria Schmidt', $reloaded->getName());
        $this->assertSame('ORDER-12345', $reloaded->getOrderIdent());
        $this->assertSame('maria@example.com', $reloaded->getEmail());
        $this->assertNull($reloaded->getFreeText());
        $this->assertFalse($reloaded->hasSendFailed());
    }

    public function testFreeTextRoundTripsAsStringWhenPopulated(): void
    {
        $submission = $this->makeSubmission(self::TEST_OXID_PREFIX . 'freetext');
        $submission->oxrevocation__oxfreetext = new Field('Partial revocation only for product XYZ.', Field::T_RAW);
        $submission->o3revocation__oxfreetext = new Field('Partial revocation only for product XYZ.', Field::T_RAW);
        $submission->save();

        $reloaded = oxNew(O3Revocation::class);
        $reloaded->load($submission->getId());
        $this->assertSame('Partial revocation only for product XYZ.', $reloaded->getFreeText());
    }

    /**
     * Spec invariant: OXSUBMITTED is set exactly once and never updated.
     * Spec scenario: "OXSUBMITTED preserved across an update".
     *
     * OXTIMESTAMP advances on UPDATE. We assert the *advance*, not a
     * raw `>` comparison against OXSUBMITTED — the PHP container runs in
     * Europe/Berlin while the DB container's CURRENT_TIMESTAMP is UTC, so
     * the two columns are written against different timezone references
     * and direct comparison would be misleading.
     */
    public function testOxsubmittedIsImmutableAcrossUpdates(): void
    {
        $submission = $this->makeSubmission(self::TEST_OXID_PREFIX . 'immut');
        $submission->save();

        $rowBefore = $this->fetchRowById($submission->getId());
        $originalSubmitted = $rowBefore['OXSUBMITTED'];
        $originalTimestamp = $rowBefore['OXTIMESTAMP'];

        // Wait at least one second so the DB-engine UPDATE-trigger on
        // OXTIMESTAMP advances detectably.
        sleep(1);

        // Caller attempts to overwrite OXSUBMITTED on update — model must
        // refuse via the _aSkipSaveFields mechanism.
        $submission->o3revocation__oxsubmitted = new Field('1999-01-01 00:00:00', Field::T_RAW);
        $submission->markSendFailed();
        $submission->save();

        $rowAfter = $this->fetchRowById($submission->getId());

        $this->assertSame(
            $originalSubmitted,
            $rowAfter['OXSUBMITTED'],
            'OXSUBMITTED must be write-once; the model rejected an explicit overwrite attempt.'
        );
        $this->assertGreaterThan(
            $originalTimestamp,
            $rowAfter['OXTIMESTAMP'],
            'OXTIMESTAMP must advance between INSERT and UPDATE (DB-engine ON UPDATE CURRENT_TIMESTAMP).'
        );
        // The other field WAS updated.
        $this->assertSame('1', (string) $rowAfter['OXSENDFAILED']);
    }

    public function testMarkSendSucceededClearsTheSendFailedFlag(): void
    {
        $submission = $this->makeSubmission(self::TEST_OXID_PREFIX . 'resend');
        $submission->markSendFailed();
        $submission->save();

        $reloaded = oxNew(O3Revocation::class);
        $this->assertTrue($reloaded->load($submission->getId()));
        $this->assertTrue($reloaded->hasSendFailed());

        $reloaded->markSendSucceeded();
        $reloaded->save();

        $reloadedAgain = oxNew(O3Revocation::class);
        $this->assertTrue($reloadedAgain->load($submission->getId()));
        $this->assertFalse($reloadedAgain->hasSendFailed());
    }

    private function makeSubmission(string $oxid): O3Revocation
    {
        $submission = oxNew(O3Revocation::class);
        $submission->setId($oxid);
        $submission->assign([
            'oxshopid'     => 1,
            'oxlang'       => 0,
            'oxname'       => 'Maria Schmidt',
            'oxorderident' => 'ORDER-12345',
            'oxemail'      => 'maria@example.com',
        ]);
        return $submission;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRowById(string $oxid): array
    {
        $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $row = $database->getRow(
            'SELECT OXID, OXSHOPID, OXLANG, OXNAME, OXORDERIDENT, OXEMAIL, '
            . 'OXFREETEXT, OXSENDFAILED, OXSUBMITTED, OXTIMESTAMP '
            . 'FROM o3revocation WHERE OXID = ?',
            [$oxid]
        );
        return $row !== false ? $row : [];
    }
}
