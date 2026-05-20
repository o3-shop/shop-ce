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
 * §356a BGB electronic revocation feature.
 *
 * Creates the `o3revocation` table that stores consumer revocation declarations
 * (effective 2026-06-19, see GitHub issue #99) and seeds an inactive operator-
 * notice CMS snippet so the operator can find and edit it from day one.
 *
 * Schema is documented inline via column COMMENTs so the legal references and
 * immutability invariants survive in `SHOW CREATE TABLE` even if this PHP
 * migration file is later removed from the repo.
 */
final class Version20260427090000 extends AbstractMigration
{
    private const TABLE_NAME = 'o3revocation';
    private const NOTICE_LOADID = 'o3_revocation_notice';

    public function getDescription(): string
    {
        return '§356a BGB: create o3revocation table and seed operator notice CMS snippet';
    }

    public function up(Schema $schema): void
    {
        $this->createRevocationTable($schema);
        $this->seedOperatorNoticeSnippet();
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(self::TABLE_NAME)) {
            $schema->dropTable(self::TABLE_NAME);
        }
        $this->addSql(
            "DELETE FROM oxcontents WHERE OXLOADID = '" . self::NOTICE_LOADID . "'"
        );
    }

    /**
     * Doctrine's Schema API is the standard idempotent path. We intentionally
     * fall back to a raw `CREATE TABLE IF NOT EXISTS` SQL statement here so the
     * column-level `COMMENT` directives are preserved verbatim — the Schema
     * API's `setComment()` works but the resulting `CREATE TABLE` rendering
     * varies by DBAL version, and we want the legal-evidence invariants
     * (OXSUBMITTED immutability, no IP/User-Agent column) visible in
     * `SHOW CREATE TABLE` exactly as written.
     */
    private function createRevocationTable(Schema $schema): void
    {
        if ($schema->hasTable(self::TABLE_NAME)) {
            return;
        }

        $this->addSql(
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `o3revocation` (
  `OXID`         CHAR(32)     CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL
                              COMMENT 'O3 convention: 32-char primary key, generated with UtilsObject::generateUID(). latin1_general_ci collation matches every other OXID column in the schema (verified by SystemRequirements::checkCollation).',
  `OXSHOPID`     INT          NOT NULL DEFAULT 1
                              COMMENT 'Owning shop ID (multi-shop installations).',
  `OXLANG`       INT          NOT NULL
                              COMMENT 'Consumer language ID at submission time. Drives the language of the confirmation email.',
  `OXNAME`       VARCHAR(255) NOT NULL
                              COMMENT 'Consumer full name as typed in the form. Mandatory per § 356a Abs. 2 BGB.',
  `OXORDERIDENT` VARCHAR(255) NOT NULL
                              COMMENT 'Order/contract identifier as typed by the consumer. Mandatory per § 356a Abs. 2 BGB. NOT a foreign key to oxorder.OXORDERNR — the law forbids rejecting submissions that do not match shop records. Accept verbatim.',
  `OXEMAIL`      VARCHAR(255) NOT NULL
                              COMMENT 'Consumer electronic communication channel for the confirmation receipt. Mandatory per § 356a Abs. 2 BGB. Do NOT validate or match against oxorder.OXBILLEMAIL or oxuser.OXUSERNAME — the law forbids such gatekeeping.',
  `OXFREETEXT`   TEXT         NULL
                              COMMENT 'Optional free-text from the consumer (e.g. partial revocation scope). Not legally required; never made mandatory.',
  `OXSENDFAILED` TINYINT(1)   NOT NULL DEFAULT 0
                              COMMENT 'Set to 1 when the synchronous customer-email send returned an error. Cleared by a successful resend. Does NOT track downstream delivery (bounces/spam) — we cannot detect those.',
  `OXSUBMITTED`  DATETIME     NOT NULL
                              COMMENT 'Legal time of receipt per § 356a Abs. 4 BGB. Written ONCE on persist; MUST NEVER be updated. Goes into the confirmation email.',
  `OXTIMESTAMP`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                              COMMENT 'Standard O3 housekeeping column (row last touched). Auto-maintained by the DB engine; do not write from application code.',
  PRIMARY KEY (`OXID`),
  KEY `IDX_O3REVOCATION_SHOPLANG` (`OXSHOPID`, `OXLANG`),
  KEY `IDX_O3REVOCATION_SUBMITTED` (`OXSUBMITTED`)
) ENGINE=InnoDB
  CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Electronic revocation declarations per § 356a BGB (effective 2026-06-19). One row per consumer submission. Insert-only in normal operation; updates are rare (e.g. flagging a confirmation-email resend).'
SQL
        );
    }

    /**
     * Seed one inactive empty operator-notice snippet per shop. Idempotent via
     * `INSERT IGNORE` keyed on the `OXLOADID` UNIQUE index — operator-edited
     * content is never overwritten on a re-run.
     *
     * `oxcontents` uses suffixed columns for multi-language (`OXTITLE_1`,
     * `OXCONTENT_1`, …), so one row covers every language slot. The seed
     * leaves all slots inactive and empty; the operator activates and fills
     * them per language via the standard CMS module.
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
