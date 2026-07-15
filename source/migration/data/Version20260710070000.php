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

namespace OxidEsales\EshopCommunity\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * EU harmonised guarantee labels (EmpCo Directive (EU) 2024/825, Implementing
 * Regulation (EU) 2025/1960; applies 2026-09-27; see GitHub issue #219).
 *
 * Adds the product-level durability-guarantee columns to `oxarticles` and
 * seeds an inactive operator-notice CMS snippet (the editorial text shown
 * alongside the shop-global legal-guarantee notice), mirroring the
 * §356a BGB electronic revocation migration (issue #99).
 *
 * Schema is documented inline via column COMMENTs so the legal references
 * survive in `SHOW CREATE TABLE` even if this PHP migration file is later
 * removed from the repo.
 */
final class Version20260710070000 extends AbstractMigration
{
    private const TABLE_NAME = 'oxarticles';
    private const NOTICE_LOADID = 'o3_guarantee_notice_info';

    public function getDescription(): string
    {
        return 'EmpCo guarantee labels: add oxarticles guarantee columns and seed operator notice CMS snippet';
    }

    public function up(Schema $schema): void
    {
        $this->addGuaranteeColumns($schema);
        $this->seedOperatorNoticeSnippet();
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        if ($table->hasColumn('o3guaranteedurationmonths')) {
            $this->addSql('ALTER TABLE `oxarticles` DROP COLUMN `O3GUARANTEEDURATIONMONTHS`');
        }
        if ($table->hasColumn('o3guaranteeguarantor')) {
            $this->addSql('ALTER TABLE `oxarticles` DROP COLUMN `O3GUARANTEEGUARANTOR`');
        }
        if ($table->hasColumn('o3guaranteemodel')) {
            $this->addSql('ALTER TABLE `oxarticles` DROP COLUMN `O3GUARANTEEMODEL`');
        }
        if ($table->hasColumn('o3guaranteeconditions')) {
            $this->addSql('ALTER TABLE `oxarticles` DROP COLUMN `O3GUARANTEECONDITIONS`');
        }
        $this->addSql(
            "DELETE FROM oxcontents WHERE OXLOADID = '" . self::NOTICE_LOADID . "'"
        );
    }

    /**
     * Raw `ADD COLUMN` SQL (guarded by `hasColumn()`) so the column-level
     * `COMMENT` directives are preserved verbatim in `SHOW CREATE TABLE` —
     * same rationale as the o3revocation migration (issue #99).
     */
    private function addGuaranteeColumns(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        if (!$table->hasColumn('o3guaranteedurationmonths')) {
            $this->addSql(
                'ALTER TABLE `oxarticles` ADD COLUMN `O3GUARANTEEDURATIONMONTHS` INT NOT NULL DEFAULT 0 '
                . "COMMENT 'EmpCo commercial durability guarantee duration in months, as communicated by the "
                . 'producer. 0 = no durability guarantee communicated (default, not a legal statement). Stored '
                . 'in months (not years) so non-integer-year guarantees can be represented. The harmonised '
                . 'durability-guarantee label (Implementing Regulation (EU) 2025/1960) is only mandatory when '
                . 'this value is strictly greater than 24 (more than two years, Art. 22a Directive (EU) '
                . "2024/825). The trader has no duty to research this value themselves.' "
                . 'AFTER `OXMANUFACTURERID`'
            );
        }

        if (!$table->hasColumn('o3guaranteeguarantor')) {
            $this->addSql(
                'ALTER TABLE `oxarticles` ADD COLUMN `O3GUARANTEEGUARANTOR` VARCHAR(255) NOT NULL DEFAULT \'\' '
                . "COMMENT 'Guarantor/brand name to display on the durability-guarantee label. Empty falls "
                . 'back to the linked oxmanufacturers title at render time; if that is also empty the label '
                . "cannot render.' "
                . 'AFTER `O3GUARANTEEDURATIONMONTHS`'
            );
        }

        if (!$table->hasColumn('o3guaranteemodel')) {
            $this->addSql(
                'ALTER TABLE `oxarticles` ADD COLUMN `O3GUARANTEEMODEL` VARCHAR(255) NOT NULL DEFAULT \'\' '
                . "COMMENT 'Model identifier to display on the durability-guarantee label (a mandatory "
                . 'variable component per Annex II, Implementing Regulation (EU) 2025/1960). Empty falls '
                . "back to OXARTNUM at render time.' "
                . 'AFTER `O3GUARANTEEGUARANTOR`'
            );
        }

        if (!$table->hasColumn('o3guaranteeconditions')) {
            $this->addSql(
                'ALTER TABLE `oxarticles` ADD COLUMN `O3GUARANTEECONDITIONS` TEXT NULL '
                . "COMMENT 'Guarantee conditions text/URL for the pre-existing § 479 BGB guarantee information "
                . 'duties (guarantor address, territorial scope, conditions), triggered once the durability '
                . "label is displayed.' "
                . 'AFTER `O3GUARANTEEMODEL`'
            );
        }
    }

    /**
     * Seed one inactive empty operator-notice snippet (shop 1). Idempotent
     * via `INSERT IGNORE` keyed on the `OXLOADID` UNIQUE index — operator-
     * edited content is never overwritten on a re-run. Mirrors the
     * `o3_revocation_notice` seed from issue #99.
     *
     * Deliberately scoped to shop 1, not looped over every `oxshops` row:
     * `oxcontents.OXLOADID` carries a table-wide UNIQUE index (not composite
     * with `OXSHOPID`), so a second row with the same load id for another
     * shop would violate that constraint. This mirrors the revocation
     * snippet's exact scoping, not an oversight.
     */
    private function seedOperatorNoticeSnippet(): void
    {
        $oxid = md5(self::NOTICE_LOADID);
        $loadid = self::NOTICE_LOADID;

        $this->addSql(
            <<<SQL
INSERT IGNORE INTO `oxcontents`
    (`OXID`, `OXLOADID`, `OXSHOPID`, `OXSNIPPET`, `OXTYPE`,
     `OXACTIVE`,   `OXTITLE`,   `OXCONTENT`,
     `OXACTIVE_1`, `OXTITLE_1`, `OXCONTENT_1`,
     `OXACTIVE_2`, `OXTITLE_2`, `OXCONTENT_2`,
     `OXACTIVE_3`, `OXTITLE_3`, `OXCONTENT_3`,
     `OXFOLDER`)
VALUES
    ('$oxid', '$loadid', 1, 1, 0,
     0, '', '',
     0, '', '',
     0, '', '',
     0, '', '',
     '')
SQL
        );
    }
}
