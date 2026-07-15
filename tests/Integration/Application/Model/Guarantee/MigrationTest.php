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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Model\Guarantee;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Integration tests for the EmpCo guarantee-labels migration
 * (Version20260710070000, issue #219).
 *
 * Validates that on a database where the migration has already been applied:
 *   (a) `oxarticles` carries the three new guarantee columns,
 *   (b) one inactive, empty `oxcontents` row with
 *       OXLOADID='o3_guarantee_notice_info' is seeded,
 *   (c) re-running the seed SQL is a no-op against operator-edited content.
 */
class MigrationTest extends UnitTestCase
{
    private const SNIPPET_LOADID = 'o3_guarantee_notice_info';

    public function testOxarticlesHasGuaranteeColumns(): void
    {
        $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $rows = $database->getAll(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS '
            . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oxarticles'"
        );
        $columns = array_map(static fn (array $row) => strtoupper($row['COLUMN_NAME']), $rows);

        foreach (['O3GUARANTEEDURATIONMONTHS', 'O3GUARANTEEGUARANTOR', 'O3GUARANTEEMODEL', 'O3GUARANTEECONDITIONS'] as $columnName) {
            $this->assertContains(
                $columnName,
                $columns,
                "oxarticles is missing required column '$columnName'."
            );
        }
    }

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
            "Snippet 'o3_guarantee_notice_info' was not seeded into oxcontents."
        );
        $this->assertSame('1', (string) $row['OXSNIPPET']);
        $this->assertSame('0', (string) $row['OXTYPE']);
        $this->assertSame('0', (string) $row['OXACTIVE']);
        $this->assertSame('0', (string) $row['OXACTIVE_1']);
        $this->assertSame('', (string) $row['OXTITLE']);
        $this->assertSame('', (string) $row['OXTITLE_1']);
        $this->assertSame('', (string) $row['OXCONTENT']);
        $this->assertSame('', (string) $row['OXCONTENT_1']);
    }

    public function testReSeedingPreservesOperatorEdits(): void
    {
        $database = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

        $original = $database->getRow(
            'SELECT OXTITLE, OXCONTENT, OXACTIVE FROM oxcontents WHERE OXLOADID = ?',
            [self::SNIPPET_LOADID]
        );
        $this->assertNotEmpty($original, 'Seed row missing — preceding test should have caught this.');

        try {
            $database->execute(
                'UPDATE oxcontents '
                . "SET OXTITLE = 'Test edit', OXCONTENT = 'Test content', OXACTIVE = 1 "
                . 'WHERE OXLOADID = ?',
                [self::SNIPPET_LOADID]
            );

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

            $after = $database->getRow(
                'SELECT OXTITLE, OXCONTENT, OXACTIVE FROM oxcontents WHERE OXLOADID = ?',
                [self::SNIPPET_LOADID]
            );
            $this->assertSame('Test edit', $after['OXTITLE']);
            $this->assertSame('Test content', $after['OXCONTENT']);
            $this->assertSame('1', (string) $after['OXACTIVE']);
        } finally {
            $database->execute(
                'UPDATE oxcontents SET OXTITLE = ?, OXCONTENT = ?, OXACTIVE = ? WHERE OXLOADID = ?',
                [$original['OXTITLE'], $original['OXCONTENT'], (int) $original['OXACTIVE'], self::SNIPPET_LOADID]
            );
        }
    }
}
