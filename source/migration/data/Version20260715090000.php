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
use OxidEsales\EshopCommunity\Core\ConfigFile;
use OxidEsales\EshopCommunity\Core\Utils;
use OxidEsales\Facts\Facts;

/**
 * EU guarantee labels (issue #219; Directive (EU) 2024/825, Implementing
 * Regulation (EU) 2025/1960, applicable from 2026-09-27).
 *
 * Adds the per-article producer-durability-guarantee fields and seeds the
 * operator-editable supplementary CMS snippet shown below the shop-global
 * legal-guarantee notice. Column COMMENTs carry the legal semantics so they
 * survive in `SHOW CREATE TABLE` independently of this file.
 */
final class Version20260715090000 extends AbstractMigration
{
    private const SNIPPET_LOADID = 'o3_guarantee_notice_info';

    private const COLUMNS = [
        'O3GUARANTEEYEARS' => "INT NULL DEFAULT NULL COMMENT 'Producer commercial guarantee of durability in WHOLE YEARS; NULL/0 = none/not communicated (nullable so an unset field reads empty, not a misleading 0). Label-eligible only when > 2 (Art. 6(1)(la) CRD as amended by Directive (EU) 2024/825; label design per Reg. (EU) 2025/1960 renders whole years only). Producer guarantees only - seller guarantees never qualify.'",
        'O3GUARANTEEGUARANTOR' => "VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Producer/brand name exactly as it must appear on the EU durability-guarantee label. Empty = fall back to the linked oxmanufacturers title; if that is also empty the label cannot render.'",
        'O3GUARANTEEMODEL' => "VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Model identifier exactly as it must appear on the EU durability-guarantee label (mandatory label component per Reg. (EU) 2025/1960 Annex II). Empty = fall back to OXARTNUM, then to the article title.'",
        'O3GUARANTEECONDITIONS' => "TEXT NULL COMMENT 'Guarantee conditions the trader must provide when advertising with the guarantee (sec. 479 BGB: guarantor name/address, scope, conditions). Shown as an expandable section under the label. Single-language field by design.'",
    ];

    public function getDescription(): string
    {
        return '#219 EU guarantee labels: oxarticles guarantee columns + supplementary-notice CMS snippet'
            . ' (clears the permanent oxarticles field-name cache in both directions)';
    }

    public function up(Schema $schema): void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $table = $schema->getTable('oxarticles');
        $after = 'OXPRICE';
        foreach (self::COLUMNS as $name => $definition) {
            if (!$table->hasColumn($name)) {
                $this->addSql("ALTER TABLE `oxarticles` ADD COLUMN `$name` $definition AFTER `$after`");
            }
            $after = $name;
        }
        $this->seedSupplementarySnippet();
    }

    /**
     * Adding a column is not enough for the model to expose it: the
     * `oxarticles` field-name cache under `source/tmp/` is PERMANENT — a normal
     * cache clear skips it on purpose (`Utils::$_sPermanentCachePattern`). On a
     * shop upgraded with a warm tmp dir the columns would exist in MySQL while
     * `$oArticle->oxarticles__o3guaranteeyears` stayed unset, so
     * `getGuaranteeYears()` returned 0, `isDurabilityGuaranteeEligible()` was
     * permanently false and the whole feature was silently inert — with no
     * error anywhere. That is not acceptable for a legally mandatory label
     * (Reg. (EU) 2025/1960, applicable from 2026-09-27), hence clearing it here
     * rather than asking operators to read a release note.
     *
     * A migration runs WITHOUT the shop bootstrap — `oxNew()` is not defined,
     * so `Registry::getUtils()` is unusable here. The compile dir is therefore
     * resolved straight from `config.inc.php` via `ConfigFile` (+ `Facts` for
     * the source path, both dependency-free) and handed to the static
     * `Utils::clearTableFieldCacheIn()`.
     *
     * Never throws: the schema change has already been applied at this point,
     * so a cache-clearing problem must not abort the migration.
     */
    public function postUp(Schema $schema): void
    {
        $this->clearArticleFieldCache('the new guarantee columns become visible on the article model');
    }

    /**
     * The mirror image of postUp(), and just as necessary: after down() drops
     * the columns the cache still lists them, so the model would keep exposing
     * four fields that no longer exist in MySQL — and that field list is what
     * `_getUpdateFields()` builds its SQL from, so article writes would
     * reference dropped columns until someone purged `source/tmp/` by hand.
     */
    public function postDown(Schema $schema): void
    {
        $this->clearArticleFieldCache('the dropped guarantee columns disappear from the article model');
    }

    /**
     * @param string $outcome what clearing the cache achieves, for the CLI line
     */
    private function clearArticleFieldCache(string $outcome): void
    {
        try {
            $removed = Utils::clearTableFieldCacheIn($this->resolveCompileDir(), 'oxarticles');
            $this->write(
                "    -> cleared $removed permanent 'oxarticles' field-name cache file(s) so $outcome."
            );
        } catch (\Throwable $e) {
            $this->write(
                '    -> WARNING: could not clear the permanent oxarticles field-name cache'
                . " ('{$e->getMessage()}'). Delete 'source/tmp/*' manually, otherwise the article"
                . ' model and the oxarticles table stay out of sync.'
            );
        }
    }

    /**
     * @return string|false absolute compile/cache dir, or false when unresolvable
     */
    private function resolveCompileDir()
    {
        $configFile = new ConfigFile((new Facts())->getSourcePath() . '/config.inc.php');
        $compileDir = $configFile->getVar('sCompileDir');

        return is_string($compileDir) && $compileDir !== '' ? $compileDir : false;
    }

    public function down(Schema $schema): void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $table = $schema->getTable('oxarticles');
        foreach (array_keys(self::COLUMNS) as $name) {
            if ($table->hasColumn($name)) {
                $this->addSql("ALTER TABLE `oxarticles` DROP COLUMN `$name`");
            }
        }
        $this->addSql("DELETE FROM oxcontents WHERE OXLOADID = '" . self::SNIPPET_LOADID . "'");
    }

    /**
     * One inactive, empty snippet per shop; INSERT IGNORE on the OXLOADID
     * unique index makes re-runs no-ops and never overwrites operator edits.
     * The snippet is strictly SUPPLEMENTARY text below the notice - the
     * notice artwork itself is fixed EU design and never operator-editable.
     */
    private function seedSupplementarySnippet(): void
    {
        $oxid = md5(self::SNIPPET_LOADID);
        $loadid = self::SNIPPET_LOADID;
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
