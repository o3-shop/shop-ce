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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core\Guarantee;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Schema-level assertions for the EU guarantee-labels feature (#219).
 * The migration Version20260715090000 must have been applied by the test
 * environment's install/migrate step; these tests prove its effects and
 * its idempotency, not the Doctrine runner itself.
 */
class GuaranteeMigrationTest extends UnitTestCase
{
    public function testGuaranteeColumnsExistWithExpectedTypes(): void
    {
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $columns = [];
        foreach ($db->getAll("SHOW COLUMNS FROM oxarticles LIKE 'O3GUARANTEE%'") as $row) {
            $columns[strtoupper($row['Field'])] = strtolower($row['Type']);
        }

        $this->assertArrayHasKey('O3GUARANTEEYEARS', $columns);
        $this->assertStringContainsString('int', $columns['O3GUARANTEEYEARS']);
        $this->assertArrayHasKey('O3GUARANTEEGUARANTOR', $columns);
        $this->assertStringContainsString('varchar(255)', $columns['O3GUARANTEEGUARANTOR']);
        $this->assertArrayHasKey('O3GUARANTEEMODEL', $columns);
        $this->assertStringContainsString('varchar(255)', $columns['O3GUARANTEEMODEL']);
        $this->assertArrayHasKey('O3GUARANTEECONDITIONS', $columns);
        $this->assertStringContainsString('text', $columns['O3GUARANTEECONDITIONS']);
    }

    public function testYearsColumnCarriesLegalCommentAndDefaultZero(): void
    {
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $row = $db->getRow("SHOW FULL COLUMNS FROM oxarticles LIKE 'O3GUARANTEEYEARS'");

        $this->assertSame('0', $row['Default']);
        $this->assertStringContainsString('2025/1960', $row['Comment']);
    }

    public function testCmsSnippetSeededInactiveAndEmpty(): void
    {
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $row = $db->getRow(
            "SELECT OXACTIVE, OXCONTENT, OXSNIPPET FROM oxcontents WHERE OXLOADID = 'o3_guarantee_notice_info'"
        );

        $this->assertNotEmpty($row, 'CMS snippet o3_guarantee_notice_info must be seeded.');
        $this->assertSame('0', (string) $row['OXACTIVE']);
        $this->assertSame('', (string) $row['OXCONTENT']);
        $this->assertSame('1', (string) $row['OXSNIPPET']);
    }

    public function testSnippetSeedIsIdempotent(): void
    {
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        // Re-run the seed statement verbatim; INSERT IGNORE keyed on the
        // OXLOADID unique index must not duplicate or overwrite.
        $oxid = md5('o3_guarantee_notice_info');
        $db->execute(
            "INSERT IGNORE INTO oxcontents
                (OXID, OXLOADID, OXSHOPID, OXSNIPPET, OXTYPE,
                 OXACTIVE, OXTITLE, OXCONTENT,
                 OXACTIVE_1, OXTITLE_1, OXCONTENT_1,
                 OXACTIVE_2, OXTITLE_2, OXCONTENT_2,
                 OXACTIVE_3, OXTITLE_3, OXCONTENT_3,
                 OXFOLDER)
             VALUES ('$oxid', 'o3_guarantee_notice_info', 1, 1, 0,
                 0, '', '', 0, '', '', 0, '', '', 0, '', '', '')"
        );

        $count = $db->getOne(
            "SELECT COUNT(*) FROM oxcontents WHERE OXLOADID = 'o3_guarantee_notice_info'"
        );
        $this->assertSame('1', (string) $count);
    }
}
