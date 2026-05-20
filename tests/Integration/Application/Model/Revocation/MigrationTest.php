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
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Integration tests for the §356a revocation migration (Version20260427090000).
 *
 * Validates that on a database where the migration has already been applied:
 *   (a) the `o3revocation` table exists with the expected schema invariants,
 *   (b) one inactive, empty `oxcontents` row with OXLOADID='o3_revocation_notice'
 *       is seeded per shop,
 *   (c) re-running the seed SQL is a no-op against operator-edited content
 *       (the migration's idempotency guarantee).
 */
class MigrationTest extends UnitTestCase
{
    private const TABLE = 'o3revocation';
    private const SNIPPET_LOADID = 'o3_revocation_notice';

    /**
     * (a) The o3revocation table exists and carries every column the spec requires.
     * Mirrors spec requirement "Persistence schema and immutability".
     */
    public function testRevocationTableExistsWithRequiredColumns(): void
    {
        $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $rows = $database->getAll(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS '
            . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'o3revocation'"
        );
        $columns = array_map(static fn (array $row) => strtoupper($row['COLUMN_NAME']), $rows);

        $expected = [
            'OXID', 'OXSHOPID', 'OXLANG', 'OXNAME', 'OXORDERIDENT',
            'OXEMAIL', 'OXFREETEXT', 'OXSENDFAILED', 'OXSUBMITTED', 'OXTIMESTAMP',
        ];
        foreach ($expected as $columnName) {
            $this->assertContains(
                $columnName,
                $columns,
                "o3revocation is missing required column '$columnName'."
            );
        }
    }

    /**
     * (a) Negative invariant: no IP / User-Agent column exists.
     * Mirrors spec requirement "Persistence schema and immutability" — explicit
     * "MUST NOT persist the request IP or User-Agent" rule.
     */
    public function testRevocationTableDoesNotPersistRequestMetadata(): void
    {
        $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $rows = $database->getAll(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS '
            . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'o3revocation'"
        );
        $columns = array_map(static fn (array $row) => strtoupper($row['COLUMN_NAME']), $rows);

        $this->assertNotContains('OXIP', $columns, 'o3revocation must not persist the request IP.');
        $this->assertNotContains('OXUSERAGENT', $columns, 'o3revocation must not persist the User-Agent.');
    }

    /**
     * (b) The CMS snippet seed row exists and is inactive + empty per language.
     * Mirrors spec requirement "Doctrine migration creates schema and seeds CMS snippet only".
     */
    public function testOperatorNoticeSnippetIsSeededInactiveAndEmpty(): void
    {
        $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $row = $database->getRow(
            'SELECT OXACTIVE, OXACTIVE_1, OXTITLE, OXTITLE_1, OXCONTENT, OXCONTENT_1, OXSNIPPET, OXTYPE '
            . 'FROM oxcontents WHERE OXLOADID = ?',
            [self::SNIPPET_LOADID]
        );

        $this->assertNotEmpty(
            $row,
            "Snippet 'o3_revocation_notice' was not seeded into oxcontents."
        );
        // It IS a snippet (so {oxifcontent} can find it) and TYPE 0 (Snippet).
        $this->assertSame('1', (string) $row['OXSNIPPET']);
        $this->assertSame('0', (string) $row['OXTYPE']);
        // Every language slot is inactive and empty out of the box.
        $this->assertSame('0', (string) $row['OXACTIVE']);
        $this->assertSame('0', (string) $row['OXACTIVE_1']);
        $this->assertSame('', (string) $row['OXTITLE']);
        $this->assertSame('', (string) $row['OXTITLE_1']);
        $this->assertSame('', (string) $row['OXCONTENT']);
        $this->assertSame('', (string) $row['OXCONTENT_1']);
    }

    /**
     * (c) Re-running the seed SQL never overwrites operator-edited content.
     * Simulates the idempotency guarantee: edit the snippet, replay the
     * migration's `INSERT IGNORE`, assert the operator edit survives.
     * Mirrors spec scenario "Migration preserves operator-edited CMS snippet".
     */
    public function testReSeedingPreservesOperatorEdits(): void
    {
        $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        // Snapshot the current state so the test is order-independent and
        // tearDown can restore exactly what was there.
        $original = $database->getRow(
            'SELECT OXTITLE, OXCONTENT, OXACTIVE FROM oxcontents WHERE OXLOADID = ?',
            [self::SNIPPET_LOADID]
        );
        $this->assertNotEmpty($original, 'Seed row missing — preceding test should have caught this.');

        try {
            // Operator edits the snippet through the admin CMS.
            $database->execute(
                'UPDATE oxcontents '
                . "SET OXTITLE = 'Test edit', OXCONTENT = 'Test content', OXACTIVE = 1 "
                . 'WHERE OXLOADID = ?',
                [self::SNIPPET_LOADID]
            );

            // Replay the migration's seed SQL verbatim.
            $oxid = md5(self::SNIPPET_LOADID);
            $database->execute(
                <<<SQL
INSERT IGNORE INTO oxcontents
    (OXID, OXLOADID, OXSHOPID, OXSNIPPET, OXTYPE,
     OXACTIVE,   OXTITLE,   OXCONTENT,
     OXACTIVE_1, OXTITLE_1, OXCONTENT_1,
     OXACTIVE_2, OXTITLE_2, OXCONTENT_2,
     OXACTIVE_3, OXTITLE_3, OXCONTENT_3,
     OXFOLDER)
VALUES
    (?, ?, 1, 1, 0,
     0, '', '',
     0, '', '',
     0, '', '',
     0, '', '',
     '')
SQL
                ,
                [$oxid, self::SNIPPET_LOADID]
            );

            // The operator's edit is intact — INSERT IGNORE skipped because
            // OXLOADID is the UNIQUE key.
            $after = $database->getRow(
                'SELECT OXTITLE, OXCONTENT, OXACTIVE FROM oxcontents WHERE OXLOADID = ?',
                [self::SNIPPET_LOADID]
            );
            $this->assertSame('Test edit', $after['OXTITLE']);
            $this->assertSame('Test content', $after['OXCONTENT']);
            $this->assertSame('1', (string) $after['OXACTIVE']);
        } finally {
            // Restore the original row state regardless of pass/fail.
            $database->execute(
                'UPDATE oxcontents SET OXTITLE = ?, OXCONTENT = ?, OXACTIVE = ? WHERE OXLOADID = ?',
                [$original['OXTITLE'], $original['OXCONTENT'], (int) $original['OXACTIVE'], self::SNIPPET_LOADID]
            );
        }
    }
}
