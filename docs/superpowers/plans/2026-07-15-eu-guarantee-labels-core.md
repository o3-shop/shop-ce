# EU Guarantee Labels (#219) — Core (shop-ce) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Execution model (mandated by Nick, 2026-07-15):** The orchestrator (Fable 5) dispatches one **Opus 4.8 subagent per task** (`model: opus`) and writes no production code itself. Implementer agents: if anything in your task is ambiguous, contradictory, or fails unexpectedly twice, STOP and report back to the orchestrator with your question instead of improvising. You see only your own task — the **Interfaces** block tells you what neighbors provide/expect; trust it verbatim.

**Goal:** Make shop-ce render the two EU-mandated guarantee artifacts (Reg. (EU) 2025/1960): the shop-global legal-guarantee notice and the per-product producer-durability-guarantee label, compliant by 2026-09-27.

**Architecture:** Four new `oxarticles` columns + one qualification predicate on `Article`; a `GuaranteeLabelGenerator` core service that composites the official label artwork with three text fields via GD/FreeType into a content-hash-cached PNG under `out/pictures/generated/guarantee/`; `ViewConfig` getters expose the per-language notice artwork; an admin config page (2 bool switches) and guarantee fields on the article "Extended" tab. Theme-repo work (templates/CSS for o3-Theme + wave-theme) is a **separate plan** — this plan delivers every core interface those templates will call.

**Tech Stack:** PHP 7.4+ (shop floor), GD + FreeType (`imagettftext`), Doctrine Migrations, Smarty 2.6 admin templates, PHPUnit 9 via `./docker.sh`.

**Companion spec:** `docs/superpowers/specs/2026-07-15-eu-guarantee-labels-design.md` (approved). Legal facts cited there are settled — do not re-research them.

## Global Constraints

- Work happens on branch `garantie-label-2` in `/Users/nick/o3/shop-ce`. Never push; never open PRs (Nick authorizes that separately).
- Commits in Nick's name only — **NO** `Co-Authored-By`, **NO** "Generated with Claude Code" footers, no session links.
- Docker env must be up before any test: `./docker.sh start`. Single test file: `./docker.sh test --fast <path>`. If the env is broken, report to the orchestrator — do NOT composer-install inside testing-library or otherwise "fix" the harness.
- PSR-12 via `./docker.sh cs-fixer` before every commit.
- DB access in code: Doctrine DBAL QueryBuilder or `addSql` in migrations — never string-concatenated SQL with user input.
- Logging: `Registry::getLogger()-><level>(__METHOD__ . " - <Full sentence ending with period.>")`, variables in single quotes, IDs labeled by meaning, English only.
- All new user-facing strings use the `O3_GUARANTEE_` translation-key prefix; strings inside the fixed EU artwork are artwork, never translation keys.
- Config switches: `blShowLegalGuaranteeNotice`, `blShowDurabilityGuaranteeLabel`. Fresh installs default ON (`initial_data.sql`); code reads `getConfigParam($name, false)` so upgrades default OFF.
- Never blocking saves on validation problems — warnings/infos only (house graceful-degradation rule).
- License header: every new PHP file starts with the standard O3-Shop GPL-3 header block — copy it verbatim from `source/Application/Controller/Admin/RevocationConfigController.php` lines 1–21 (including `declare(strict_types=1);`).

---

### Task 1: Database migration, schema, and fresh-install seeds

**Files:**
- Create: `source/migration/data/Version20260715090000.php`
- Modify: `source/Setup/Sql/database_schema.sql` (the `CREATE TABLE oxarticles` statement)
- Modify: `source/Setup/Sql/initial_data.sql` (append to the `oxconfig` INSERT block near lines 1494–1497 where the `blShowRevocationForm` rows live)
- Test: `tests/Integration/Core/Guarantee/GuaranteeMigrationTest.php`

**Interfaces:**
- Consumes: nothing (first task).
- Produces: `oxarticles` columns `O3GUARANTEEYEARS` (INT NOT NULL DEFAULT 0), `O3GUARANTEEGUARANTOR` (VARCHAR(255) NOT NULL DEFAULT ''), `O3GUARANTEEMODEL` (VARCHAR(255) NOT NULL DEFAULT ''), `O3GUARANTEECONDITIONS` (TEXT NULL); `oxcontents` row with `OXLOADID='o3_guarantee_notice_info'`; `oxconfig` rows `blShowLegalGuaranteeNotice`/`blShowDurabilityGuaranteeLabel` (fresh installs, bool `'1'`).

- [ ] **Step 1: Write the failing integration test**

```php
<?php

// ... standard O3-Shop GPL-3 header (copy verbatim per Global Constraints) ...

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
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./docker.sh test --fast tests/Integration/Core/Guarantee/GuaranteeMigrationTest.php`
Expected: FAIL — `O3GUARANTEEYEARS` column missing (migration not yet written/applied).

- [ ] **Step 3: Write the migration**

`source/migration/data/Version20260715090000.php` (GPL header + `declare(strict_types=1);` first, as always):

```php
namespace OxidEsales\EshopCommunity\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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
        'O3GUARANTEEYEARS' => "INT NOT NULL DEFAULT 0 COMMENT 'Producer commercial guarantee of durability in WHOLE YEARS; 0 = none/not communicated. Label-eligible only when > 2 (Art. 6(1)(la) CRD as amended by Directive (EU) 2024/825; label design per Reg. (EU) 2025/1960 renders whole years only). Producer guarantees only - seller guarantees never qualify.'",
        'O3GUARANTEEGUARANTOR' => "VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Producer/brand name exactly as it must appear on the EU durability-guarantee label. Empty = fall back to the linked oxmanufacturers title; if that is also empty the label cannot render.'",
        'O3GUARANTEEMODEL' => "VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Model identifier exactly as it must appear on the EU durability-guarantee label (mandatory label component per Reg. (EU) 2025/1960 Annex II). Empty = fall back to OXARTNUM, then to the article title.'",
        'O3GUARANTEECONDITIONS' => "TEXT NULL COMMENT 'Guarantee conditions the trader must provide when advertising with the guarantee (sec. 479 BGB: guarantor name/address, scope, conditions). Shown as an expandable section under the label. Single-language field by design.'",
    ];

    public function getDescription(): string
    {
        return '#219 EU guarantee labels: oxarticles guarantee columns + supplementary-notice CMS snippet';
    }

    public function up(Schema $schema): void
    {
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

    public function down(Schema $schema): void
    {
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
```

- [ ] **Step 4: Update `database_schema.sql` (fresh installs)**

Locate the `CREATE TABLE \`oxarticles\`` statement in `source/Setup/Sql/database_schema.sql`; add the same four column definitions (identical types/defaults/comments as the migration) directly after the `OXPRICE` column line, matching the file's existing formatting.

- [ ] **Step 5: Update `initial_data.sql` (fresh-install config defaults)**

In `source/Setup/Sql/initial_data.sql`, find the oxconfig INSERT block containing `blShowRevocationForm` (~line 1494). That block's final row ends with `;` — change that `;` to `,` and append (OXIDs are `md5(<varname>)`, precomputed):

```sql
    ('3bab9ff7dfb761e35b833dcf56c9593d', 1, '', 'blShowLegalGuaranteeNotice',      'bool', '1'),
    ('465bf751756bc97271fc580071ee5fc8', 1, '', 'blShowDurabilityGuaranteeLabel',  'bool', '1');
```

Add a one-line SQL comment above the two rows: `-- #219 EU guarantee labels: fresh installs are compliant out of the box; upgrades default OFF in code.`

- [ ] **Step 6: Apply the migration and run the test**

Run: `./docker.sh start` (if not up), then inside the project container run the migration runner: `docker exec o3shop-shop-ce-1 vendor/bin/oe-eshop-db_migrate migrations:migrate` (if the container name differs, discover it with `docker ps --format '{{.Names}}' | grep o3shop`).
Then: `./docker.sh test --fast tests/Integration/Core/Guarantee/GuaranteeMigrationTest.php`
Expected: PASS (4 tests).

- [ ] **Step 7: cs-fixer + commit**

```bash
./docker.sh cs-fixer
git add source/migration/data/Version20260715090000.php source/Setup/Sql/database_schema.sql source/Setup/Sql/initial_data.sql tests/Integration/Core/Guarantee/GuaranteeMigrationTest.php
git commit -m "feat(#219): oxarticles guarantee columns, CMS snippet seed, fresh-install config defaults"
```

---

### Task 2: Article model — eligibility predicate, fallback chains, variant inheritance

**Files:**
- Modify: `source/Application/Model/Article.php` (new getters near `getManufacturer()` ~line 2003; `_isFieldEmpty()` zero-value list ~line 4575)
- Test: `tests/Unit/Application/Model/ArticleGuaranteeTest.php`

**Interfaces:**
- Consumes: Task 1 columns.
- Produces (later tasks call these exact signatures):
  - `Article::getGuaranteeYears(): int` — raw years, 0 = none.
  - `Article::isDurabilityGuaranteeEligible(): bool` — `getGuaranteeYears() > 2`. THE single qualification predicate; nothing else re-implements the threshold.
  - `Article::getGuaranteeGuarantor(): string` — own field, else active linked manufacturer title, else `''`.
  - `Article::getGuaranteeModel(): string` — own field, else `OXARTNUM`, else article title.
  - `Article::getGuaranteeConditions(): string` — raw text, `''` when unset.

- [ ] **Step 1: Write the failing tests**

```php
<?php

// ... standard O3-Shop GPL-3 header ...

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Qualification + fallback-chain logic for the EU durability-guarantee
 * label (#219). The legal trigger (producer guarantee > 2 whole years,
 * Art. 6(1)(la) CRD) lives in exactly one predicate:
 * Article::isDurabilityGuaranteeEligible().
 */
class ArticleGuaranteeTest extends UnitTestCase
{
    private function makeArticle(array $fields = []): Article
    {
        $article = oxNew(Article::class);
        $article->setId('_guaranteetest');
        foreach ($fields as $field => $value) {
            $article->$field = new \OxidEsales\Eshop\Core\Field($value);
        }
        return $article;
    }

    /** @dataProvider yearsEligibilityProvider */
    public function testEligibilityThresholdIsMoreThanTwoYears(int $years, bool $expected): void
    {
        $article = $this->makeArticle(['oxarticles__o3guaranteeyears' => $years]);
        $this->assertSame($expected, $article->isDurabilityGuaranteeEligible());
        $this->assertSame($years, $article->getGuaranteeYears());
    }

    public function yearsEligibilityProvider(): array
    {
        return [
            'none' => [0, false],
            'one year' => [1, false],
            'exactly two years - legal minimum, not eligible' => [2, false],
            'three years' => [3, true],
            'ten years' => [10, true],
        ];
    }

    public function testGuarantorPrefersOwnField(): void
    {
        $article = $this->makeArticle(['oxarticles__o3guaranteeguarantor' => 'ACME GmbH']);
        $this->assertSame('ACME GmbH', $article->getGuaranteeGuarantor());
    }

    public function testGuarantorFallsBackToManufacturerTitle(): void
    {
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $manufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
        $manufacturer->oxmanufacturers__oxtitle = new \OxidEsales\Eshop\Core\Field('Fallback Brand');
        $article->method('getManufacturer')->willReturn($manufacturer);
        $article->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('');

        $this->assertSame('Fallback Brand', $article->getGuaranteeGuarantor());
    }

    public function testGuarantorEmptyWhenNoFieldAndNoManufacturer(): void
    {
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $article->method('getManufacturer')->willReturn(null);
        $article->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('');

        $this->assertSame('', $article->getGuaranteeGuarantor());
    }

    public function testModelFallbackChainFieldThenArtnumThenTitle(): void
    {
        $article = $this->makeArticle([
            'oxarticles__o3guaranteemodel' => 'X-2000',
            'oxarticles__oxartnum' => 'ART-1',
            'oxarticles__oxtitle' => 'Widget',
        ]);
        $this->assertSame('X-2000', $article->getGuaranteeModel());

        $article = $this->makeArticle([
            'oxarticles__o3guaranteemodel' => '',
            'oxarticles__oxartnum' => 'ART-1',
            'oxarticles__oxtitle' => 'Widget',
        ]);
        $this->assertSame('ART-1', $article->getGuaranteeModel());

        $article = $this->makeArticle([
            'oxarticles__o3guaranteemodel' => '',
            'oxarticles__oxartnum' => '',
            'oxarticles__oxtitle' => 'Widget',
        ]);
        $this->assertSame('Widget', $article->getGuaranteeModel());
    }

    public function testConditionsReturnEmptyStringWhenUnset(): void
    {
        $article = $this->makeArticle([]);
        $this->assertSame('', $article->getGuaranteeConditions());

        $article = $this->makeArticle(['oxarticles__o3guaranteeconditions' => 'Full terms here.']);
        $this->assertSame('Full terms here.', $article->getGuaranteeConditions());
    }

    /**
     * Variant inheritance: a child with years = 0 must inherit the parent's
     * value. oxarticles ints are only treated as "empty" (= inheritable) when
     * whitelisted in Article::_isFieldEmpty()'s zero-value list — this test
     * pins that whitelisting.
     */
    public function testVariantChildWithZeroYearsInheritsParentValue(): void
    {
        $parent = oxNew(Article::class);
        $parent->setId('_guaranteeparent');
        $parent->oxarticles__oxartnum = new \OxidEsales\Eshop\Core\Field('PARENT-1');
        $parent->oxarticles__o3guaranteeyears = new \OxidEsales\Eshop\Core\Field(5);
        $parent->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('ACME GmbH');
        $parent->save();

        $child = oxNew(Article::class);
        $child->setId('_guaranteechild');
        $child->oxarticles__oxparentid = new \OxidEsales\Eshop\Core\Field('_guaranteeparent');
        $child->oxarticles__o3guaranteeyears = new \OxidEsales\Eshop\Core\Field(0);
        $child->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('');
        $child->save();

        $loaded = oxNew(Article::class);
        $loaded->load('_guaranteechild');

        $this->assertSame(5, $loaded->getGuaranteeYears());
        $this->assertSame('ACME GmbH', $loaded->getGuaranteeGuarantor());
        $this->assertTrue($loaded->isDurabilityGuaranteeEligible());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./docker.sh test --fast tests/Unit/Application/Model/ArticleGuaranteeTest.php`
Expected: FAIL — `isDurabilityGuaranteeEligible` undefined.

- [ ] **Step 3: Implement the Article methods**

In `source/Application/Model/Article.php`, directly after `getManufacturer()` (~line 2025), add:

```php
    /**
     * Producer commercial guarantee of durability in whole years (#219).
     * 0 = none / not communicated to the trader.
     *
     * @return int
     */
    public function getGuaranteeYears(): int
    {
        return (int) $this->oxarticles__o3guaranteeyears->value;
    }

    /**
     * THE single legal-qualification predicate for the EU durability-guarantee
     * label: producer guarantee of MORE THAN two years (Art. 6(1)(la) CRD as
     * amended by Directive (EU) 2024/825). Producer-only / no-extra-cost /
     * whole-good are operator-side conditions documented in admin help; the
     * code threshold is the duration rule. Do not re-implement elsewhere.
     *
     * @return bool
     */
    public function isDurabilityGuaranteeEligible(): bool
    {
        return $this->getGuaranteeYears() > 2;
    }

    /**
     * Guarantor/brand name as it must appear on the label.
     * Fallback chain: own field -> active linked manufacturer title -> ''.
     * '' means the label CANNOT render (mandatory label component).
     *
     * @return string
     */
    public function getGuaranteeGuarantor(): string
    {
        $own = trim((string) $this->oxarticles__o3guaranteeguarantor->value);
        if ($own !== '') {
            return $own;
        }
        $manufacturer = $this->getManufacturer();
        if ($manufacturer !== null) {
            return trim((string) $manufacturer->oxmanufacturers__oxtitle->value);
        }
        return '';
    }

    /**
     * Model identifier as it must appear on the label (mandatory component,
     * Reg. (EU) 2025/1960 Annex II). Fallback chain: own field -> OXARTNUM
     * -> article title.
     *
     * @return string
     */
    public function getGuaranteeModel(): string
    {
        $own = trim((string) $this->oxarticles__o3guaranteemodel->value);
        if ($own !== '') {
            return $own;
        }
        $artnum = trim((string) $this->oxarticles__oxartnum->value);
        if ($artnum !== '') {
            return $artnum;
        }
        return trim((string) $this->oxarticles__oxtitle->value);
    }

    /**
     * Guarantee conditions text (sec. 479 BGB information duty).
     *
     * @return string
     */
    public function getGuaranteeConditions(): string
    {
        return trim((string) $this->oxarticles__o3guaranteeconditions->value);
    }
```

In `_isFieldEmpty()` (~line 4575), extend the zero-value whitelist so variant children with `0` years inherit the parent value:

```php
        $aZeroValueFields = ['oxarticles__oxprice', 'oxarticles__oxvat', 'oxarticles__oxunitquantity', 'oxarticles__o3guaranteeyears'];
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `./docker.sh test --fast tests/Unit/Application/Model/ArticleGuaranteeTest.php`
Expected: PASS (all tests). If the variant test fails on inheritance, verify the zero-value list edit and that the test DB has the Task-1 columns.

- [ ] **Step 5: cs-fixer + commit**

```bash
./docker.sh cs-fixer
git add source/Application/Model/Article.php tests/Unit/Application/Model/ArticleGuaranteeTest.php
git commit -m "feat(#219): article guarantee getters, eligibility predicate, variant zero-inheritance"
```

---

### Task 3: Assets — official EU artwork + Inter fonts

**Files:**
- Create: `source/Core/GuaranteeLabel/assets/label-template.png` (language-neutral label, variable areas blanked)
- Create: `source/Core/GuaranteeLabel/assets/Inter-Regular.ttf`, `Inter-SemiBold.ttf`, `Inter-ExtraBold.ttf`, `Inter-LICENSE.txt`
- Create: `source/out/pictures/guarantee/notice-de.png`, `source/out/pictures/guarantee/notice-en.png`
- Create: `source/Core/GuaranteeLabel/assets/README.md` (provenance record)

**Interfaces:**
- Consumes: nothing.
- Produces: the exact file paths above; Task 4's generator hard-codes `source/Core/GuaranteeLabel/assets/` and Task 6's ViewConfig hard-codes `out/pictures/guarantee/notice-<abbr>.png`.

This task is acquisition + verification, not TDD. Every artifact's origin goes into `README.md` (URL, retrieval date, transformation commands).

- [ ] **Step 1: Download the official artwork**

The Commission publishes ready-made files at
`https://commission.europa.eu/publications/harmonised-notice-legal-guarantee-conformity-and-harmonised-label-commercial-guarantee-durability_en`.
Fetch that page, identify the download links for: the harmonised **notice** in **German** and **English**, and the harmonised **label** (language-neutral, one file). Download into the session scratchpad. If the page structure defeats you or files are missing, STOP and ask the orchestrator — do not substitute third-party recreations.

- [ ] **Step 2: Convert to web PNGs**

Files may be PDF/EPS/PNG. Target: RGB colour PNG (colour is legally mandatory online), notice ≥ 1200 px on the long edge, label template ≥ 1000 px wide, QR modules crisp (no smoothing artifacts). On the macOS host, `sips -s format png --resampleHeight 1675 in.pdf --out out.png` works for single-page PDFs; ImageMagick (`magick -density 300 in.pdf out.png`) is fine too if available. Verify each PNG by opening it with the Read tool: correct language, correct colours (EU blue #003399 / yellow #FFED00), QR visually intact.

- [ ] **Step 3: Blank the label's variable areas**

The official label file ships with placeholder texts ("XX", "Brand/Trademark", "Model identifier"). Determine each placeholder's bounding box by reading the image visually, then blank the three regions with a small one-off PHP GD script (fill with the exact background colour sampled at the region's corner via `imagecolorat`). Iterate visually until no placeholder remnants remain and no fixed element is damaged. Record the three bounding boxes (px, at final template size) in `README.md` — Task 5 needs them. Save as `label-template.png`.

- [ ] **Step 4: Fetch Inter fonts**

Download the latest Inter release zip from `https://github.com/rsms/inter/releases`, extract exactly `Inter-Regular.ttf`, `Inter-SemiBold.ttf`, `Inter-ExtraBold.ttf` (in current releases these live under `extras/ttf/` or as `InterVariable` — use the static TTFs, not variable fonts), plus the OFL license file (save as `Inter-LICENSE.txt`).

- [ ] **Step 5: Place files + provenance README**

Move files to the paths listed under **Files**. Write `README.md` covering: source URLs, retrieval date (2026-07-15 or actual), conversion commands used, the three blanked bounding boxes, and the legal note "artwork per Reg. (EU) 2025/1960 Annexes I/II; notice must never be modified; label variable fields are composited at runtime by GuaranteeLabelGenerator."

- [ ] **Step 6: Verify + commit**

Verify: `php -r "var_dump(getimagesize('source/Core/GuaranteeLabel/assets/label-template.png'), getimagesize('source/out/pictures/guarantee/notice-de.png'), getimagesize('source/out/pictures/guarantee/notice-en.png'));"` — all return arrays, type PNG, sizes per Step 2 targets.

```bash
git add source/Core/GuaranteeLabel/assets source/out/pictures/guarantee
git commit -m "feat(#219): official EU guarantee artwork (notice de/en, label template) + Inter fonts"
```

---

### Task 4: GuaranteeLabelGenerator service

**Files:**
- Create: `source/Core/GuaranteeLabelGenerator.php`
- Modify: `source/Core/Autoload/UnifiedNameSpaceClassMap.php` (new entry, alphabetical position among `OxidEsales\Eshop\Core\*` entries)
- Test: `tests/Unit/Core/Guarantee/GuaranteeLabelGeneratorTest.php`

**Interfaces:**
- Consumes: Task 3 asset paths (default wiring; tests inject synthetic ones).
- Produces:
  - `GuaranteeLabelGenerator::getLabelUrl(string $articleId, int $years, string $guarantor, string $model): ?string` — URL of the cached/just-composited PNG, or null on any failure (never throws).
  - Test seams: `setAssetDir(string $dir): void`, `setTargetDir(string $dir): void`, `setTargetUrl(string $url): void`, `setLayout(array $layout): void`.
  - Class constant `TEMPLATE_VERSION` (bump ⇒ cache invalidation) and `LAYOUT` (per-field placement spec, fractions of template width/height).

- [ ] **Step 1: Write the failing tests**

```php
<?php

// ... standard O3-Shop GPL-3 header ...

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Guarantee;

use OxidEsales\Eshop\Core\GuaranteeLabelGenerator;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * GD composition + content-hash caching for the EU durability-guarantee
 * label (#219). Tests run against a SYNTHETIC template (solid colour PNG)
 * so they exercise composition mechanics without the official artwork.
 */
class GuaranteeLabelGeneratorTest extends UnitTestCase
{
    private string $workDir;
    private GuaranteeLabelGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir() . '/guarantee_' . uniqid();
        mkdir($this->workDir . '/assets', 0777, true);
        mkdir($this->workDir . '/target', 0777, true);

        // synthetic 500x520 white template
        $im = imagecreatetruecolor(500, 520);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        imagepng($im, $this->workDir . '/assets/label-template.png');
        imagedestroy($im);

        // real Inter fonts are required for imagettftext; copy from the repo
        foreach (['Inter-Regular.ttf', 'Inter-SemiBold.ttf', 'Inter-ExtraBold.ttf'] as $font) {
            copy(
                OX_BASE_PATH . 'Core/GuaranteeLabel/assets/' . $font,
                $this->workDir . '/assets/' . $font
            );
        }

        $this->generator = oxNew(GuaranteeLabelGenerator::class);
        $this->generator->setAssetDir($this->workDir . '/assets/');
        $this->generator->setTargetDir($this->workDir . '/target/');
        $this->generator->setTargetUrl('http://shop.local/out/pictures/generated/guarantee/');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->workDir . '/{assets,target}/*', GLOB_BRACE) ?: [] as $f) {
            unlink($f);
        }
        rmdir($this->workDir . '/assets');
        rmdir($this->workDir . '/target');
        rmdir($this->workDir);
        parent::tearDown();
    }

    public function testComposesValidPngWithTemplateDimensions(): void
    {
        $url = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');

        $this->assertNotNull($url);
        $file = $this->workDir . '/target/' . basename($url);
        $this->assertFileExists($file);
        $info = getimagesize($file);
        $this->assertSame(500, $info[0]);
        $this->assertSame(520, $info[1]);
        $this->assertSame('image/png', $info['mime']);
    }

    public function testCompositedTextChangesPixelsAgainstBlankTemplate(): void
    {
        $url = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');
        $im = imagecreatefrompng($this->workDir . '/target/' . basename($url));

        // The white synthetic template must have gained non-white pixels
        // (the rendered text). Scan for any non-white pixel.
        $found = false;
        for ($x = 0; $x < 500 && !$found; $x += 2) {
            for ($y = 0; $y < 520 && !$found; $y += 2) {
                if ((imagecolorat($im, $x, $y) & 0xFFFFFF) !== 0xFFFFFF) {
                    $found = true;
                }
            }
        }
        imagedestroy($im);
        $this->assertTrue($found, 'Composited label must contain rendered text pixels.');
    }

    public function testCacheHitReturnsSameUrlWithoutRegenerating(): void
    {
        $url1 = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');
        $file = $this->workDir . '/target/' . basename($url1);
        $mtime = filemtime($file);
        touch($file, $mtime - 100); // age it so regeneration would be visible
        clearstatcache();

        $url2 = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');

        $this->assertSame($url1, $url2);
        clearstatcache();
        $this->assertSame($mtime - 100, filemtime($file), 'Cache hit must not rewrite the file.');
    }

    public function testContentChangeProducesNewFilename(): void
    {
        $url1 = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');
        $url2 = $this->generator->getLabelUrl('art1', 6, 'ACME GmbH', 'X-2000');

        $this->assertNotSame($url1, $url2);
        $this->assertStringStartsWith('art1_', basename($url1));
        $this->assertStringStartsWith('art1_', basename($url2));
    }

    public function testMissingTemplateReturnsNullAndDoesNotThrow(): void
    {
        unlink($this->workDir . '/assets/label-template.png');
        $this->assertNull($this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000'));
    }

    public function testMissingFontReturnsNullAndDoesNotThrow(): void
    {
        unlink($this->workDir . '/assets/Inter-ExtraBold.ttf');
        $this->assertNull($this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000'));
    }

    public function testUnwritableTargetDirReturnsNull(): void
    {
        $this->generator->setTargetDir($this->workDir . '/does-not-exist-and-mkdir-fails/../../../../../../root/x/');
        $this->assertNull($this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000'));
    }

    public function testArticleIdIsSanitizedForFilesystem(): void
    {
        $url = $this->generator->getLabelUrl('../evil/../id', 5, 'ACME GmbH', 'X-2000');

        $this->assertNotNull($url);
        $this->assertStringNotContainsString('..', basename($url));
        $this->assertStringNotContainsString('/', substr($url, strlen('http://shop.local/out/pictures/generated/guarantee/')));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./docker.sh test --fast tests/Unit/Core/Guarantee/GuaranteeLabelGeneratorTest.php`
Expected: FAIL — class `GuaranteeLabelGenerator` not found.

- [ ] **Step 3: Implement the generator**

`source/Core/GuaranteeLabelGenerator.php` (GPL header + strict_types as always):

```php
namespace OxidEsales\EshopCommunity\Core;

use OxidEsales\Eshop\Core\Registry;

/**
 * Composites the official EU durability-guarantee label (Reg. (EU) 2025/1960
 * Annex II) with the three trader-filled fields - duration in years,
 * producer/guarantor name, model identifier - using GD + FreeType and the
 * prescribed Inter typeface (#219).
 *
 * Caching: content-addressed. The target filename embeds
 * md5(years|guarantor|model|TEMPLATE_VERSION), so a stale file can never be
 * served and no save-hooks or invalidation logic exist. Writes are atomic
 * (temp file + rename); concurrent requests race benignly to identical bytes.
 *
 * Failure policy: NEVER throws out of getLabelUrl(). Any problem (missing
 * template/font, GD/FreeType unavailable, unwritable target) is logged and
 * yields null - the storefront then renders the text fallback instead of
 * the label (graceful degradation; templates handle null).
 */
class GuaranteeLabelGenerator
{
    /**
     * Bump whenever label-template.png or LAYOUT changes; invalidates every
     * cached label via the content hash.
     */
    public const TEMPLATE_VERSION = 1;

    /**
     * Field placement, as FRACTIONS of template width/height so the layout
     * survives template re-exports at other resolutions.
     *   x, y      - anchor point (y = text BASELINE), centred horizontally
     *   size      - font size as a fraction of template HEIGHT
     *   font      - TTF filename in the asset dir
     * Values calibrated visually against the official artwork in the
     * calibration task; adjust there, not ad hoc.
     */
    public const LAYOUT = [
        'years' => ['x' => 0.50, 'y' => 0.46, 'size' => 0.170, 'font' => 'Inter-ExtraBold.ttf'],
        'guarantor' => ['x' => 0.50, 'y' => 0.80, 'size' => 0.032, 'font' => 'Inter-SemiBold.ttf'],
        'model' => ['x' => 0.50, 'y' => 0.86, 'size' => 0.032, 'font' => 'Inter-Regular.ttf'],
    ];

    private const TEMPLATE_FILE = 'label-template.png';
    private const TEXT_COLOR = [0, 0, 0]; // black per Annex II spec

    /** @var string|null test seam; null = repo default */
    private ?string $assetDir = null;

    /** @var string|null test seam; null = <picture dir>/generated/guarantee/ */
    private ?string $targetDir = null;

    /** @var string|null test seam; null = <picture url>/generated/guarantee/ */
    private ?string $targetUrl = null;

    /** @var array|null test seam; null = self::LAYOUT */
    private ?array $layout = null;

    public function setAssetDir(string $dir): void
    {
        $this->assetDir = rtrim($dir, '/') . '/';
    }

    public function setTargetDir(string $dir): void
    {
        $this->targetDir = rtrim($dir, '/') . '/';
    }

    public function setTargetUrl(string $url): void
    {
        $this->targetUrl = rtrim($url, '/') . '/';
    }

    public function setLayout(array $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * @param string $articleId  article OXID (used in the cache filename only)
     * @param int    $years      guarantee duration in whole years (caller
     *                           guarantees eligibility; not re-checked here)
     * @param string $guarantor  producer/brand name to composite
     * @param string $model      model identifier to composite
     *
     * @return string|null public URL of the label PNG, or null on failure
     */
    public function getLabelUrl(string $articleId, int $years, string $guarantor, string $model): ?string
    {
        try {
            $filename = $this->buildFilename($articleId, $years, $guarantor, $model);
            $targetDir = $this->getTargetDir();
            $targetFile = $targetDir . $filename;

            if (is_file($targetFile)) {
                return $this->getTargetUrl() . $filename;
            }

            if (!$this->ensureDirectory($targetDir)) {
                return null;
            }
            if (!$this->compose($targetFile, $years, $guarantor, $model)) {
                return null;
            }

            return $this->getTargetUrl() . $filename;
        } catch (\Throwable $e) {
            Registry::getLogger()->error(
                __METHOD__ . " - Label composition failed for article-ID '$articleId': '{$e->getMessage()}'.",
                ['exception' => $e]
            );
            return null;
        }
    }

    private function buildFilename(string $articleId, int $years, string $guarantor, string $model): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $articleId);
        $hash = md5($years . '|' . $guarantor . '|' . $model . '|' . self::TEMPLATE_VERSION);
        return $safeId . '_' . $hash . '.png';
    }

    /**
     * @return bool true when the target file was written
     */
    private function compose(string $targetFile, int $years, string $guarantor, string $model): bool
    {
        if (!function_exists('imagettftext')) {
            Registry::getLogger()->error(
                __METHOD__ . ' - GD FreeType support (imagettftext) is unavailable. The EU guarantee label cannot be generated.'
            );
            return false;
        }

        $templatePath = $this->getAssetDir() . self::TEMPLATE_FILE;
        if (!is_file($templatePath)) {
            Registry::getLogger()->error(
                __METHOD__ . " - Label template '$templatePath' is missing. The EU guarantee label cannot be generated."
            );
            return false;
        }

        $texts = [
            'years' => (string) $years,
            'guarantor' => $guarantor,
            'model' => $model,
        ];
        $layout = $this->layout ?? self::LAYOUT;

        foreach ($layout as $field => $spec) {
            if (!is_file($this->getAssetDir() . $spec['font'])) {
                Registry::getLogger()->error(
                    __METHOD__ . " - Font file '{$spec['font']}' is missing from '{$this->getAssetDir()}'. The EU guarantee label cannot be generated."
                );
                return false;
            }
        }

        $image = imagecreatefrompng($templatePath);
        if ($image === false) {
            Registry::getLogger()->error(
                __METHOD__ . " - Label template '$templatePath' could not be read as PNG."
            );
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $color = imagecolorallocate($image, ...self::TEXT_COLOR);

        foreach ($layout as $field => $spec) {
            $fontFile = $this->getAssetDir() . $spec['font'];
            $sizePx = $spec['size'] * $height;
            // imagettftext expects points; GD converts at 96 dpi -> pt = px * 72/96.
            $sizePt = $sizePx * 72 / 96;
            $text = $texts[$field];

            $box = imagettfbbox($sizePt, 0, $fontFile, $text);
            if ($box === false) {
                imagedestroy($image);
                Registry::getLogger()->error(
                    __METHOD__ . " - Measuring text for field '$field' failed with font '$fontFile'."
                );
                return false;
            }
            $textWidth = $box[2] - $box[0];
            $x = (int) round($spec['x'] * $width - $textWidth / 2);
            $y = (int) round($spec['y'] * $height);

            imagettftext($image, $sizePt, 0, $x, $y, $color, $fontFile, $text);
        }

        $tmpFile = $targetFile . '.' . uniqid('', true) . '.tmp';
        $written = imagepng($image, $tmpFile);
        imagedestroy($image);

        if (!$written || !rename($tmpFile, $targetFile)) {
            @unlink($tmpFile);
            Registry::getLogger()->error(
                __METHOD__ . " - Writing composited label to '$targetFile' failed. Check directory permissions."
            );
            return false;
        }

        Registry::getLogger()->info(
            __METHOD__ . " - Generated EU guarantee label '$targetFile'."
        );
        return true;
    }

    private function ensureDirectory(string $dir): bool
    {
        if (is_dir($dir)) {
            return is_writable($dir);
        }
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            Registry::getLogger()->error(
                __METHOD__ . " - Target directory '$dir' could not be created."
            );
            return false;
        }
        return true;
    }

    private function getAssetDir(): string
    {
        return $this->assetDir ?? OX_BASE_PATH . 'Core/GuaranteeLabel/assets/';
    }

    private function getTargetDir(): string
    {
        if ($this->targetDir !== null) {
            return $this->targetDir;
        }
        return Registry::getConfig()->getPictureDir(false) . 'generated/guarantee/';
    }

    private function getTargetUrl(): string
    {
        if ($this->targetUrl !== null) {
            return $this->targetUrl;
        }
        return Registry::getConfig()->getPictureUrl(null, false) . 'generated/guarantee/';
    }
}
```

Why `getPictureDir(false)` / `getPictureUrl(null, false)`: `Email::send()` passes exactly these two values to `_includeImages()` as the dynamic-image mapping, so any label `<img src>` in an order email is automatically inlined as a base64 `cid:` attachment — zero email-code changes.

- [ ] **Step 4: Register the unified-namespace alias**

In `source/Core/Autoload/UnifiedNameSpaceClassMap.php`, add alphabetically among the `OxidEsales\Eshop\Core\*` entries (copy the exact array shape of the neighbouring entries):

```php
    'OxidEsales\Eshop\Core\GuaranteeLabelGenerator' => [
        'editionClassName' => \OxidEsales\EshopCommunity\Core\GuaranteeLabelGenerator::class,
        'isAbstract' => false,
        'isInterface' => false,
        'isDeprecated' => false,
    ],
```

(Match the neighbouring entries' key set exactly — if they carry fewer/other keys, mirror them.)

- [ ] **Step 5: Run tests to verify they pass**

Run: `./docker.sh test --fast tests/Unit/Core/Guarantee/GuaranteeLabelGeneratorTest.php`
Expected: PASS (8 tests).

- [ ] **Step 6: cs-fixer + commit**

```bash
./docker.sh cs-fixer
git add source/Core/GuaranteeLabelGenerator.php source/Core/Autoload/UnifiedNameSpaceClassMap.php tests/Unit/Core/Guarantee/GuaranteeLabelGeneratorTest.php
git commit -m "feat(#219): GuaranteeLabelGenerator - GD label composition with content-hash cache"
```

---

### Task 5: Calibrate the layout against the official artwork

**Files:**
- Modify: `source/Core/GuaranteeLabelGenerator.php` (the `LAYOUT` constant values only)
- Create: `tests/Unit/Core/Guarantee/GuaranteeLabelArtworkSmokeTest.php`

**Interfaces:**
- Consumes: Task 3 real artwork + Task 4 generator.
- Produces: final `LAYOUT` values; a smoke test pinning the real template's presence and composability.

- [ ] **Step 1: Generate a calibration sample**

```bash
docker exec o3shop-shop-ce-1 php -r '
require "bootstrap.php";
$g = oxNew(\OxidEsales\Eshop\Core\GuaranteeLabelGenerator::class);
$g->setTargetDir("/var/www/html/source/out/pictures/generated/guarantee/");
$g->setTargetUrl("calib/");
echo $g->getLabelUrl("calibration", 5, "ACME Example GmbH", "Model X-2000"), PHP_EOL;
'
```

(Adapt the in-container shop root path if it differs — find it with `docker exec o3shop-shop-ce-1 pwd` / `ls`. If `bootstrap.php` isn't at that location, locate it: `docker exec o3shop-shop-ce-1 find / -maxdepth 3 -name bootstrap.php 2>/dev/null`.)

- [ ] **Step 2: Iterate visually**

Open the generated PNG with the Read tool. Compare against the official Annex II depiction (Task 3's pre-blanking original — keep it in scratchpad): the years figure must sit where "XX" sat, sized like the original placeholder; guarantor on the "Brand/Trademark" line; model on the "Model identifier" line; all horizontally centred like the original; nothing overlapping fixed artwork (title, calendar symbol, QR, translation block). Adjust `LAYOUT` fractions (use Task 3's recorded bounding boxes: x-centre = (x1+x2)/2/width, baseline ≈ y2/height, size ≈ 0.75 × boxHeight/height), regenerate (delete the old file first — same inputs hash to the same name), re-inspect. Repeat until visually faithful. Long guarantor/model strings must shrink-to-fit rather than overflow: if the measured text width exceeds 90% of the blanked box width, reduce the size proportionally — add this clamp to `compose()` if the visual check shows overflow with the 40-char string `"Extraordinarily Long Brand Name GmbH & Co"`.

- [ ] **Step 3: Write the artwork smoke test**

```php
<?php

// ... standard O3-Shop GPL-3 header ...

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Guarantee;

use OxidEsales\Eshop\Core\GuaranteeLabelGenerator;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Smoke test against the REAL shipped artwork + fonts (no synthetic
 * fixtures): assets exist, are valid PNGs/TTFs, and composition succeeds.
 */
class GuaranteeLabelArtworkSmokeTest extends UnitTestCase
{
    public function testShippedAssetsExistAndAreValid(): void
    {
        $assetDir = OX_BASE_PATH . 'Core/GuaranteeLabel/assets/';

        $template = getimagesize($assetDir . 'label-template.png');
        $this->assertNotFalse($template);
        $this->assertSame('image/png', $template['mime']);
        $this->assertGreaterThanOrEqual(1000, $template[0], 'Label template must be >= 1000px wide.');

        foreach (['Inter-Regular.ttf', 'Inter-SemiBold.ttf', 'Inter-ExtraBold.ttf'] as $font) {
            $this->assertFileExists($assetDir . $font);
            $this->assertGreaterThan(10000, filesize($assetDir . $font), "Font '$font' looks truncated.");
        }

        foreach (['notice-de.png', 'notice-en.png'] as $notice) {
            $info = getimagesize(OX_BASE_PATH . 'out/pictures/guarantee/' . $notice);
            $this->assertNotFalse($info, "Notice artwork '$notice' must exist.");
            $this->assertSame('image/png', $info['mime']);
        }
    }

    public function testCompositionSucceedsWithRealArtwork(): void
    {
        $targetDir = sys_get_temp_dir() . '/guarantee_smoke_' . uniqid() . '/';
        $generator = oxNew(GuaranteeLabelGenerator::class);
        $generator->setTargetDir($targetDir);
        $generator->setTargetUrl('http://shop.local/labels/');

        $url = $generator->getLabelUrl('smoke', 5, 'ACME GmbH', 'X-2000');

        $this->assertNotNull($url);
        $file = $targetDir . basename($url);
        $template = getimagesize(OX_BASE_PATH . 'Core/GuaranteeLabel/assets/label-template.png');
        $generated = getimagesize($file);
        $this->assertSame($template[0], $generated[0]);
        $this->assertSame($template[1], $generated[1]);

        unlink($file);
        rmdir($targetDir);
    }
}
```

- [ ] **Step 4: Run the smoke test**

Run: `./docker.sh test --fast tests/Unit/Core/Guarantee/GuaranteeLabelArtworkSmokeTest.php`
Expected: PASS. Also re-run Task 4's tests (`./docker.sh test --fast tests/Unit/Core/Guarantee/GuaranteeLabelGeneratorTest.php`) — still PASS.

- [ ] **Step 5: Send the final calibration sample to the orchestrator for visual sign-off, then commit**

```bash
./docker.sh cs-fixer
git add source/Core/GuaranteeLabelGenerator.php tests/Unit/Core/Guarantee/GuaranteeLabelArtworkSmokeTest.php
git commit -m "feat(#219): calibrate label layout against official artwork + artwork smoke test"
```

---

### Task 6: Article label-URL getter + ViewConfig getters

**Files:**
- Modify: `source/Application/Model/Article.php` (after Task 2's guarantee getters)
- Modify: `source/Core/ViewConfig.php` (after `getRevocationLinkVisible()`, ~line 763)
- Test: `tests/Unit/Application/Model/ArticleGuaranteeLabelUrlTest.php`
- Test: `tests/Unit/Core/Guarantee/ViewConfigGuaranteeTest.php`

**Interfaces:**
- Consumes: Task 2 predicates/fallbacks, Task 4 `GuaranteeLabelGenerator::getLabelUrl()`, Task 3 notice assets at `out/pictures/guarantee/notice-<abbr>.png`.
- Produces (the theme plan renders exactly these):
  - `Article::getDurabilityGuaranteeLabelUrl(): ?string` — null when: master switch `blShowDurabilityGuaranteeLabel` off, OR not eligible, OR guarantor unresolvable, OR generation failed. Theme fallback rule: show the text fallback iff `ViewConfig::getDurabilityGuaranteeLabelsEnabled() && $article->isDurabilityGuaranteeEligible() && $article->getGuaranteeGuarantor() !== '' && $article->getDurabilityGuaranteeLabelUrl() === null`.
  - `Article::setGuaranteeLabelGenerator(GuaranteeLabelGenerator $generator): void` — test seam.
  - `ViewConfig::getDurabilityGuaranteeLabelsEnabled(): bool` — the master switch.
  - `ViewConfig::getGuaranteeNoticeUrl(): ?string` — notice artwork URL for the active language; EN fallback + warning log; null when `blShowLegalGuaranteeNotice` off or no asset at all.

- [ ] **Step 1: Write the failing tests**

`tests/Unit/Application/Model/ArticleGuaranteeLabelUrlTest.php`:

```php
<?php

// ... standard O3-Shop GPL-3 header ...

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\GuaranteeLabelGenerator;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;

class ArticleGuaranteeLabelUrlTest extends UnitTestCase
{
    private function makeArticle(int $years, string $guarantor = 'ACME', string $model = 'X-1'): Article
    {
        $article = oxNew(Article::class);
        $article->setId('_labelurltest');
        $article->oxarticles__o3guaranteeyears = new Field($years);
        $article->oxarticles__o3guaranteeguarantor = new Field($guarantor);
        $article->oxarticles__o3guaranteemodel = new Field($model);
        return $article;
    }

    private function stubGenerator(?string $returnUrl): GuaranteeLabelGenerator
    {
        $generator = $this->getMockBuilder(GuaranteeLabelGenerator::class)
            ->onlyMethods(['getLabelUrl'])
            ->getMock();
        $generator->method('getLabelUrl')->willReturn($returnUrl);
        return $generator;
    }

    public function testReturnsNullWhenMasterSwitchOff(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', false);
        $article = $this->makeArticle(5);
        $article->setGuaranteeLabelGenerator($this->stubGenerator('http://x/label.png'));

        $this->assertNull($article->getDurabilityGuaranteeLabelUrl());
    }

    public function testReturnsNullWhenNotEligible(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->makeArticle(2);
        $article->setGuaranteeLabelGenerator($this->stubGenerator('http://x/label.png'));

        $this->assertNull($article->getDurabilityGuaranteeLabelUrl());
    }

    public function testReturnsNullWhenGuarantorUnresolvable(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $article->method('getManufacturer')->willReturn(null);
        $article->oxarticles__o3guaranteeyears = new Field(5);
        $article->oxarticles__o3guaranteeguarantor = new Field('');
        $article->oxarticles__o3guaranteemodel = new Field('X-1');
        $article->setGuaranteeLabelGenerator($this->stubGenerator('http://x/label.png'));

        $this->assertNull($article->getDurabilityGuaranteeLabelUrl());
    }

    public function testReturnsGeneratorUrlWhenEligible(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->makeArticle(5);
        $article->setGuaranteeLabelGenerator($this->stubGenerator('http://x/label.png'));

        $this->assertSame('http://x/label.png', $article->getDurabilityGuaranteeLabelUrl());
    }

    public function testReturnsNullWhenGenerationFails(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->makeArticle(5);
        $article->setGuaranteeLabelGenerator($this->stubGenerator(null));

        $this->assertNull($article->getDurabilityGuaranteeLabelUrl());
    }
}
```

`tests/Unit/Core/Guarantee/ViewConfigGuaranteeTest.php`:

```php
<?php

// ... standard O3-Shop GPL-3 header ...

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Guarantee;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\TestingLibrary\UnitTestCase;

class ViewConfigGuaranteeTest extends UnitTestCase
{
    private string $noticeDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->noticeDir = Registry::getConfig()->getOutDir(true) . 'pictures/guarantee/';
    }

    public function testDurabilitySwitchGetterReflectsConfig(): void
    {
        $viewConfig = oxNew(ViewConfig::class);

        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $this->assertTrue($viewConfig->getDurabilityGuaranteeLabelsEnabled());

        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', false);
        $this->assertFalse($viewConfig->getDurabilityGuaranteeLabelsEnabled());
    }

    public function testNoticeUrlNullWhenSwitchOff(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', false);
        $this->assertNull(oxNew(ViewConfig::class)->getGuaranteeNoticeUrl());
    }

    public function testNoticeUrlUsesActiveLanguageAsset(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        Registry::getLang()->setBaseLanguage(0); // 0 = de in the test fixture

        $url = oxNew(ViewConfig::class)->getGuaranteeNoticeUrl();

        $this->assertNotNull($url);
        $this->assertStringEndsWith('pictures/guarantee/notice-de.png', $url);
    }

    public function testNoticeUrlFallsBackToEnglishWhenLanguageAssetMissing(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        // Simulate a shop language without an asset by asking for a
        // non-existent abbreviation through the test seam.
        $viewConfig = oxNew(ViewConfig::class);
        $url = $viewConfig->getGuaranteeNoticeUrlForLanguage('fr');

        $this->assertNotNull($url);
        $this->assertStringEndsWith('pictures/guarantee/notice-en.png', $url);
    }

    public function testNoticeUrlNullWhenNoAssetsAtAll(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        $viewConfig = $this->getMockBuilder(ViewConfig::class)
            ->onlyMethods(['guaranteeNoticeAssetExists'])
            ->getMock();
        $viewConfig->method('guaranteeNoticeAssetExists')->willReturn(false);

        $this->assertNull($viewConfig->getGuaranteeNoticeUrl());
    }
}
```

- [ ] **Step 2: Run both to verify they fail**

Run: `./docker.sh test --fast tests/Unit/Application/Model/ArticleGuaranteeLabelUrlTest.php` and `./docker.sh test --fast tests/Unit/Core/Guarantee/ViewConfigGuaranteeTest.php`
Expected: FAIL — methods undefined.

- [ ] **Step 3: Implement `Article::getDurabilityGuaranteeLabelUrl()`**

In `source/Application/Model/Article.php`, after `getGuaranteeConditions()` (Task 2), add a property near the other `protected` properties at the top of the class and the methods below:

```php
    /** @var \OxidEsales\Eshop\Core\GuaranteeLabelGenerator|null lazy; settable for tests */
    protected $_oGuaranteeLabelGenerator = null;
```

```php
    /**
     * Test seam / DI point for the label generator.
     */
    public function setGuaranteeLabelGenerator(\OxidEsales\Eshop\Core\GuaranteeLabelGenerator $generator): void
    {
        $this->_oGuaranteeLabelGenerator = $generator;
    }

    /**
     * URL of the composited EU durability-guarantee label PNG for this
     * article, or null when the label must not / cannot render:
     * master switch off, not eligible (<= 2 years), guarantor unresolvable
     * (mandatory label component), or composition failed (already logged by
     * the generator). Templates render the text fallback when this is null
     * but the article IS eligible and enabled - see the theme plan.
     *
     * @return string|null
     */
    public function getDurabilityGuaranteeLabelUrl(): ?string
    {
        if (!\OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('blShowDurabilityGuaranteeLabel', false)) {
            return null;
        }
        if (!$this->isDurabilityGuaranteeEligible()) {
            return null;
        }
        $guarantor = $this->getGuaranteeGuarantor();
        if ($guarantor === '') {
            return null;
        }

        if ($this->_oGuaranteeLabelGenerator === null) {
            $this->_oGuaranteeLabelGenerator = oxNew(\OxidEsales\Eshop\Core\GuaranteeLabelGenerator::class);
        }

        return $this->_oGuaranteeLabelGenerator->getLabelUrl(
            (string) $this->getId(),
            $this->getGuaranteeYears(),
            $guarantor,
            $this->getGuaranteeModel()
        );
    }
```

- [ ] **Step 4: Implement the ViewConfig getters**

In `source/Core/ViewConfig.php`, directly after `getRevocationLinkVisible()` (~line 763):

```php
    /**
     * Master switch for the per-product EU durability-guarantee labels
     * (#219). Fresh installs default ON via initial_data.sql; the code
     * default FALSE covers upgraded shops (operator opts in consciously).
     *
     * @return bool
     */
    public function getDurabilityGuaranteeLabelsEnabled(): bool
    {
        return (bool) \OxidEsales\Eshop\Core\Registry::getConfig()
            ->getConfigParam('blShowDurabilityGuaranteeLabel', false);
    }

    /**
     * URL of the official per-language legal-guarantee notice artwork
     * (Reg. (EU) 2025/1960 Annex I) for the active shop language, or null
     * when the feature is off / no artwork is available at all. Falls back
     * to the English asset (with a logged warning) when the active
     * language has no bundled artwork.
     *
     * @return string|null
     */
    public function getGuaranteeNoticeUrl(): ?string
    {
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();
        if (!$config->getConfigParam('blShowLegalGuaranteeNotice', false)) {
            return null;
        }
        $abbr = \OxidEsales\Eshop\Core\Registry::getLang()
            ->getLanguageAbbr(\OxidEsales\Eshop\Core\Registry::getLang()->getBaseLanguage());

        return $this->getGuaranteeNoticeUrlForLanguage((string) $abbr);
    }

    /**
     * Language-explicit variant (also the test seam for the fallback path).
     * Not gated on the config switch - callers gate.
     *
     * @param string $abbr two-letter language abbreviation, e.g. 'de'
     *
     * @return string|null
     */
    public function getGuaranteeNoticeUrlForLanguage(string $abbr): ?string
    {
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();

        if ($this->guaranteeNoticeAssetExists($abbr)) {
            return $config->getOutUrl(null, false) . 'pictures/guarantee/notice-' . $abbr . '.png';
        }

        if ($abbr !== 'en' && $this->guaranteeNoticeAssetExists('en')) {
            \OxidEsales\Eshop\Core\Registry::getLogger()->warning(
                __METHOD__ . " - No legal-guarantee notice artwork for language '$abbr'. Falling back to the 'en' asset. Bundle 'notice-$abbr.png' under 'out/pictures/guarantee/' to fix this."
            );
            return $config->getOutUrl(null, false) . 'pictures/guarantee/notice-en.png';
        }

        \OxidEsales\Eshop\Core\Registry::getLogger()->error(
            __METHOD__ . " - No legal-guarantee notice artwork found for language '$abbr' and no 'en' fallback exists under 'out/pictures/guarantee/'. The notice cannot render."
        );
        return null;
    }

    /**
     * @param string $abbr two-letter language abbreviation
     *
     * @return bool whether notice artwork is bundled for this language
     */
    protected function guaranteeNoticeAssetExists(string $abbr): bool
    {
        $abbr = preg_replace('/[^a-z]/', '', strtolower($abbr));
        return is_file(
            \OxidEsales\Eshop\Core\Registry::getConfig()->getOutDir(true) . 'pictures/guarantee/notice-' . $abbr . '.png'
        );
    }
```

- [ ] **Step 5: Run tests to verify they pass**

Run both test files again. Expected: PASS. (If `getOutUrl(null, false)` signature complains, check `Config::getOutUrl($ssl = null, $admin = null, $nativeImg = false)` at `source/Core/Config.php:1255` and call accordingly.)

- [ ] **Step 6: cs-fixer + commit**

```bash
./docker.sh cs-fixer
git add source/Application/Model/Article.php source/Core/ViewConfig.php tests/Unit/Application/Model/ArticleGuaranteeLabelUrlTest.php tests/Unit/Core/Guarantee/ViewConfigGuaranteeTest.php
git commit -m "feat(#219): article label-URL getter + ViewConfig notice/switch getters"
```

---

### Task 7: Admin config page (GuaranteeConfigController)

**Files:**
- Create: `source/Application/Controller/Admin/GuaranteeConfigController.php`
- Create: `source/Application/views/admin/tpl/guarantee_config.tpl`
- Modify: `source/Core/Routing/ShopControllerMapProvider.php` (after the `revocation_*` entries ~line 110)
- Modify: `source/Core/Autoload/BackwardsCompatibilityClassMap.php` (alphabetical, near `'revocation_config'` ~line 248)
- Modify: `source/Core/Autoload/UnifiedNameSpaceClassMap.php` (alphabetical among Admin controller entries, near line 1244)
- Modify: `source/Application/views/admin/menu.xml` (after the revocation SUBMENUs, ~line 188)
- Test: `tests/Unit/Application/Controller/Admin/GuaranteeConfigControllerTest.php`

**Interfaces:**
- Consumes: config param names from Global Constraints.
- Produces: admin route `cl=guarantee_config`; view-data key `guarantee` (array with both bool switch values). Task 8 adds the admin translation keys this template references.

- [ ] **Step 1: Write the failing test**

```php
<?php

// ... standard O3-Shop GPL-3 header ...

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Controller\Admin\GuaranteeConfigController;
use OxidEsales\TestingLibrary\UnitTestCase;

class GuaranteeConfigControllerTest extends UnitTestCase
{
    public function testRenderExposesPersistedConfigValues(): void
    {
        Registry::getConfig()->setConfigParam('blShowLegalGuaranteeNotice', true);
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', false);

        $controller = oxNew(GuaranteeConfigController::class);
        $template = $controller->render();

        $viewData = $controller->getViewData();
        $this->assertSame('guarantee_config.tpl', $template);
        $this->assertTrue($viewData['guarantee']['blShowLegalGuaranteeNotice']);
        $this->assertFalse($viewData['guarantee']['blShowDurabilityGuaranteeLabel']);
    }

    public function testSavePersistsBothSwitches(): void
    {
        $this->setRequestParameter('blShowLegalGuaranteeNotice', '1');
        $this->setRequestParameter('blShowDurabilityGuaranteeLabel', '1');

        $controller = oxNew(GuaranteeConfigController::class);
        $controller->save();

        $config = Registry::getConfig();
        $this->assertTrue((bool) $config->getConfigParam('blShowLegalGuaranteeNotice'));
        $this->assertTrue((bool) $config->getConfigParam('blShowDurabilityGuaranteeLabel'));
    }

    public function testSaveUncheckedCheckboxesPersistsOff(): void
    {
        Registry::getConfig()->saveShopConfVar('bool', 'blShowLegalGuaranteeNotice', '1');
        // unchecked checkboxes are absent from the POST
        $controller = oxNew(GuaranteeConfigController::class);
        $controller->save();

        $this->assertFalse((bool) Registry::getConfig()->getConfigParam('blShowLegalGuaranteeNotice'));
    }

    public function testAdminRouteResolves(): void
    {
        $map = oxNew(\OxidEsales\Eshop\Core\Routing\ShopControllerMapProvider::class)->getControllerMap();
        $this->assertSame(
            \OxidEsales\Eshop\Application\Controller\Admin\GuaranteeConfigController::class,
            $map['guarantee_config']
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./docker.sh test --fast tests/Unit/Application/Controller/Admin/GuaranteeConfigControllerTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement controller, template, wiring**

`source/Application/Controller/Admin/GuaranteeConfigController.php`:

```php
namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Core\Registry;

/**
 * EU guarantee labels (#219) - admin configuration page.
 *
 * Owns the two oxconfig switches:
 *   - blShowLegalGuaranteeNotice     (bool) - shop-global legal-guarantee
 *     notice (Reg. (EU) 2025/1960 Annex I) in the storefront footer
 *   - blShowDurabilityGuaranteeLabel (bool) - per-product durability-
 *     guarantee labels (Annex II)
 *
 * Fresh installs seed both ON (initial_data.sql); upgraded shops read the
 * code default FALSE until the operator opts in here. No cross-field rules,
 * no blocking validation - both switches are independent.
 */
class GuaranteeConfigController extends AdminDetailsController
{
    /** @var string */
    protected $_sThisTemplate = 'guarantee_config.tpl';

    private const FIELDS_BOOL = [
        'blShowLegalGuaranteeNotice',
        'blShowDurabilityGuaranteeLabel',
    ];

    /**
     * @return string admin template name to render
     */
    public function render()
    {
        $config = Registry::getConfig();
        $bag = [];
        foreach (self::FIELDS_BOOL as $key) {
            $bag[$key] = (bool) $config->getConfigParam($key, false);
        }
        $this->_aViewData['guarantee'] = $bag;

        return parent::render();
    }

    /**
     * @return void
     */
    public function save()
    {
        $request = Registry::getRequest();
        $config = Registry::getConfig();
        foreach (self::FIELDS_BOOL as $key) {
            $value = (bool) $request->getRequestParameter($key, 0);
            $config->saveShopConfVar('bool', $key, $value ? '1' : '');
        }
    }
}
```

`source/Application/views/admin/tpl/guarantee_config.tpl`:

```smarty
[{include file="headitem.tpl" title="O3_GUARANTEE_ADMIN_NAV_LABEL"|oxmultilangassign}]

[{* EU guarantee labels (#219) - two independent feature switches. *}]
[{* The label/notice artwork is fixed EU design (Reg. (EU) 2025/1960); *}]
[{* operators only decide WHETHER to render, never how it looks. *}]

<form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="guarantee_config">
    <input type="hidden" name="fnc" value="save">

    <fieldset>
        <legend>[{oxmultilang ident="O3_GUARANTEE_ADMIN_NAV_LABEL"}]</legend>

        <p>
            <label>
                <input type="checkbox" name="blShowLegalGuaranteeNotice" value="1"
                       [{if $guarantee.blShowLegalGuaranteeNotice}]checked="checked"[{/if}]>
                [{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_SHOW_NOTICE_LABEL"}]
            </label>
            <br><small>[{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_SHOW_NOTICE_HINT"}]</small>
        </p>

        <p>
            <label>
                <input type="checkbox" name="blShowDurabilityGuaranteeLabel" value="1"
                       [{if $guarantee.blShowDurabilityGuaranteeLabel}]checked="checked"[{/if}]>
                [{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_SHOW_LABEL_LABEL"}]
            </label>
            <br><small>[{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_SHOW_LABEL_HINT"}]</small>
        </p>

        <p>
            <input type="submit" class="edittext" value="[{oxmultilang ident="GENERAL_SAVE"}]">
        </p>
    </fieldset>
</form>

[{include file="bottomitem.tpl"}]
```

Wiring — mirror the revocation pattern exactly:
- `ShopControllerMapProvider.php` after line 110: `'guarantee_config' => \OxidEsales\Eshop\Application\Controller\Admin\GuaranteeConfigController::class,`
- `BackwardsCompatibilityClassMap.php` (alphabetical): `'guarantee_config' => 'OxidEsales\\Eshop\\Application\\Controller\\Admin\\GuaranteeConfigController',`
- `UnifiedNameSpaceClassMap.php` (alphabetical, same array shape as the `RevocationConfigController` entry at line 1244): map `'OxidEsales\Eshop\Application\Controller\Admin\GuaranteeConfigController'` to `\OxidEsales\EshopCommunity\Application\Controller\Admin\GuaranteeConfigController::class`.
- `menu.xml` after the revocation config SUBMENU (~line 188):

```xml
            <!-- EU guarantee labels (issue #219) -->
            <SUBMENU id="mxguaranteeconfig" cl="guarantee_config" list="guarantee_config">
                <TAB id="tbclguarantee_config" cl="guarantee_config" />
            </SUBMENU>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `./docker.sh test --fast tests/Unit/Application/Controller/Admin/GuaranteeConfigControllerTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: cs-fixer + commit**

```bash
./docker.sh cs-fixer
git add source/Application/Controller/Admin/GuaranteeConfigController.php source/Application/views/admin/tpl/guarantee_config.tpl source/Core/Routing/ShopControllerMapProvider.php source/Core/Autoload/BackwardsCompatibilityClassMap.php source/Core/Autoload/UnifiedNameSpaceClassMap.php source/Application/views/admin/menu.xml
git add tests/Unit/Application/Controller/Admin/GuaranteeConfigControllerTest.php
git commit -m "feat(#219): admin guarantee config page with two feature switches"
```

---

### Task 8: Article "Extended" tab fields + admin translations & help

**Files:**
- Modify: `source/Application/views/admin/tpl/article_extend.tpl` (new fieldset after the external-URL row, ~line 133)
- Modify: `source/Application/Controller/Admin/ArticleExtend.php` (`render()`, ~line 63)
- Modify: `source/Application/views/admin/de/lang.php`, `source/Application/views/admin/en/lang.php` (append before the closing `];`)
- Modify: `source/Application/views/admin/de/help_lang.php`, `source/Application/views/admin/en/help_lang.php`
- Test: `tests/Unit/Application/Controller/Admin/ArticleExtendGuaranteeTest.php`

**Interfaces:**
- Consumes: Task 1 columns (persisted automatically via the standard `editval[oxarticles__*]` mechanism — `ArticleExtend::save()` needs NO changes for persistence), Task 2 `isDurabilityGuaranteeEligible()` / `getGuaranteeGuarantor()`.
- Produces: view-data key `guaranteeWarnings` (string[] of translation keys) consumed by the template; admin translation keys listed in Step 3.

- [ ] **Step 1: Write the failing test**

```php
<?php

// ... standard O3-Shop GPL-3 header ...

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Application\Controller\Admin\ArticleExtend;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Non-blocking guarantee-field advisories on the article "Extended" tab.
 * They inform, never block (house graceful-degradation rule).
 */
class ArticleExtendGuaranteeTest extends UnitTestCase
{
    private function renderWithArticle(int $years, string $guarantor, ?string $manufacturerTitle = null): array
    {
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $manufacturer = null;
        if ($manufacturerTitle !== null) {
            $manufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
            $manufacturer->oxmanufacturers__oxtitle = new Field($manufacturerTitle);
        }
        $article->method('getManufacturer')->willReturn($manufacturer);
        $article->oxarticles__o3guaranteeyears = new Field($years);
        $article->oxarticles__o3guaranteeguarantor = new Field($guarantor);

        $controller = $this->getMockBuilder(ArticleExtend::class)
            ->onlyMethods(['getEditObjectId', 'loadCurrentArticle'])
            ->getMock();
        $controller->method('getEditObjectId')->willReturn('_x');
        $controller->method('loadCurrentArticle')->willReturn($article);
        $controller->render();

        return $controller->getViewData()['guaranteeWarnings'] ?? [];
    }

    public function testNoWarningsWhenNoGuaranteeEntered(): void
    {
        $this->assertSame([], $this->renderWithArticle(0, ''));
    }

    public function testShortDurationYieldsNotEligibleInfo(): void
    {
        $warnings = $this->renderWithArticle(2, 'ACME');
        $this->assertContains('O3_GUARANTEE_ADMIN_WARN_NOT_ELIGIBLE', $warnings);
    }

    public function testEligibleWithoutResolvableGuarantorYieldsWarning(): void
    {
        $warnings = $this->renderWithArticle(5, '', null);
        $this->assertContains('O3_GUARANTEE_ADMIN_WARN_NO_GUARANTOR', $warnings);
    }

    public function testEligibleWithManufacturerFallbackYieldsNoGuarantorWarning(): void
    {
        $warnings = $this->renderWithArticle(5, '', 'Brand Co');
        $this->assertNotContains('O3_GUARANTEE_ADMIN_WARN_NO_GUARANTOR', $warnings);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./docker.sh test --fast tests/Unit/Application/Controller/Admin/ArticleExtendGuaranteeTest.php`
Expected: FAIL — `loadCurrentArticle` does not exist yet (mock setup error) or empty warnings where keys expected.

- [ ] **Step 3: Implement**

**`ArticleExtend.php`** — first read the current `render()` (~line 63). It already loads the edited article; refactor the article-loading lines into a new `protected function loadCurrentArticle(): \OxidEsales\Eshop\Application\Model\Article` (returning the loaded article; keep existing behaviour identical) IF the existing code shape permits a clean extraction — otherwise add `loadCurrentArticle()` as a thin `oxNew(Article::class)` + `load(getEditObjectId())` helper used only by the new advisory code. Then append to `render()` before the `return`:

```php
        $this->_aViewData['guaranteeWarnings'] = $this->collectGuaranteeAdvisories();
```

And add:

```php
    /**
     * Non-blocking advisories for the EU durability-guarantee fields (#219).
     * Saving is NEVER blocked - the label simply does not render while the
     * data is incomplete/ineligible; these hints tell the operator why.
     *
     * @return string[] translation keys
     */
    protected function collectGuaranteeAdvisories(): array
    {
        $article = $this->loadCurrentArticle();
        if ($article === null) {
            return [];
        }

        $warnings = [];
        $years = $article->getGuaranteeYears();

        if ($years > 0 && !$article->isDurabilityGuaranteeEligible()) {
            $warnings[] = 'O3_GUARANTEE_ADMIN_WARN_NOT_ELIGIBLE';
        }
        if ($article->isDurabilityGuaranteeEligible() && $article->getGuaranteeGuarantor() === '') {
            $warnings[] = 'O3_GUARANTEE_ADMIN_WARN_NO_GUARANTOR';
        }

        return $warnings;
    }
```

**`article_extend.tpl`** — after the external-URL row (~line 133), inside the existing table structure, add (mirror the file's exact `<tr><td class="edittext">` cell conventions visible in the neighbouring rows):

```smarty
        [{* EU guarantee labels (#219): producer durability guarantee - Reg. (EU) 2025/1960 *}]
        [{if $guaranteeWarnings}]
            <tr><td colspan="2">
                <div class="messagebox">
                    [{foreach from=$guaranteeWarnings item=warningKey}]
                        <p>[{oxmultilang ident=$warningKey}]</p>
                    [{/foreach}]
                </div>
            </td></tr>
        [{/if}]
        <tr><td class="edittext" colspan="2"><b>[{oxmultilang ident="O3_GUARANTEE_ADMIN_FIELDS_HEADING"}]</b> [{include file="help.tpl" helpid=o3_guarantee_fields}]</td></tr>
        <tr>
            <td class="edittext">[{oxmultilang ident="O3_GUARANTEE_ADMIN_YEARS_LABEL"}]</td>
            <td class="edittext">
                <input type="text" class="editinput" size="4" name="editval[oxarticles__o3guaranteeyears]" value="[{$edit->oxarticles__o3guaranteeyears->value}]" [{$readonly}]>
                [{oxmultilang ident="O3_GUARANTEE_ADMIN_YEARS_UNIT"}]
            </td>
        </tr>
        <tr>
            <td class="edittext">[{oxmultilang ident="O3_GUARANTEE_ADMIN_GUARANTOR_LABEL"}]</td>
            <td class="edittext">
                <input type="text" class="editinput" size="40" maxlength="255" name="editval[oxarticles__o3guaranteeguarantor]" value="[{$edit->oxarticles__o3guaranteeguarantor->value}]" [{$readonly}]>
            </td>
        </tr>
        <tr>
            <td class="edittext">[{oxmultilang ident="O3_GUARANTEE_ADMIN_MODEL_LABEL"}]</td>
            <td class="edittext">
                <input type="text" class="editinput" size="40" maxlength="255" name="editval[oxarticles__o3guaranteemodel]" value="[{$edit->oxarticles__o3guaranteemodel->value}]" [{$readonly}]>
            </td>
        </tr>
        <tr>
            <td class="edittext">[{oxmultilang ident="O3_GUARANTEE_ADMIN_CONDITIONS_LABEL"}]</td>
            <td class="edittext">
                <textarea class="editinput" cols="60" rows="6" name="editval[oxarticles__o3guaranteeconditions]" [{$readonly}]>[{$edit->oxarticles__o3guaranteeconditions->value}]</textarea>
            </td>
        </tr>
```

**Admin translation keys** — append to `source/Application/views/admin/de/lang.php` before the closing `];` (en analogous; translate faithfully):

```php
// EU-Garantie-Labels (Issue #219, Reg. (EU) 2025/1960)
'mxguaranteeconfig'                                => 'EU-Garantie-Labels',
'tbclguarantee_config'                             => 'Einstellungen',
'O3_GUARANTEE_ADMIN_NAV_LABEL'                     => 'EU-Garantie-Labels',
'O3_GUARANTEE_ADMIN_CONFIG_SHOW_NOTICE_LABEL'      => 'Gewährleistungs-Hinweis (EU-einheitlich) im Shop anzeigen',
'O3_GUARANTEE_ADMIN_CONFIG_SHOW_NOTICE_HINT'       => 'Zeigt den EU-einheitlichen Hinweis auf die gesetzliche Gewährleistung im Footer. Pflicht für B2C-Shops ab dem 27.09.2026. Reine B2B-Shops sind nicht betroffen.',
'O3_GUARANTEE_ADMIN_CONFIG_SHOW_LABEL_LABEL'       => 'Garantie-Label (EU-einheitlich) an Produkten anzeigen',
'O3_GUARANTEE_ADMIN_CONFIG_SHOW_LABEL_HINT'        => 'Zeigt das EU-einheitliche Label für Haltbarkeitsgarantien des Herstellers an qualifizierenden Produkten (Produktseite, Bestellabschluss, Bestellbestätigung). Es qualifizieren nur kostenlose Herstellergarantien über 2 Jahre für das ganze Produkt, deren Informationen dem Händler vorliegen.',
'O3_GUARANTEE_ADMIN_FIELDS_HEADING'                => 'Herstellergarantie (EU-Garantie-Label)',
'O3_GUARANTEE_ADMIN_YEARS_LABEL'                   => 'Garantiedauer',
'O3_GUARANTEE_ADMIN_YEARS_UNIT'                    => 'Jahre (ganzzahlig)',
'O3_GUARANTEE_ADMIN_GUARANTOR_LABEL'               => 'Garantiegeber (Hersteller/Marke)',
'O3_GUARANTEE_ADMIN_MODEL_LABEL'                   => 'Modellbezeichnung',
'O3_GUARANTEE_ADMIN_CONDITIONS_LABEL'              => 'Garantiebedingungen (§ 479 BGB)',
'O3_GUARANTEE_ADMIN_WARN_NOT_ELIGIBLE'             => 'Hinweis: Das EU-Garantie-Label wird erst ab einer Garantiedauer von mehr als 2 Jahren angezeigt (Art. 6 Abs. 1 lit. la Verbraucherrechte-RL). Das Speichern ist trotzdem möglich.',
'O3_GUARANTEE_ADMIN_WARN_NO_GUARANTOR'             => 'Achtung: Es ist kein Garantiegeber ermittelbar (Feld leer, kein Hersteller verknüpft). Das Label kann ohne Garantiegeber nicht angezeigt werden.',
```

**Help texts** — in `help_lang.php` (de; en analogous), following the file's existing key convention (inspect it first; keys are `HELP_*` or plain idents referenced by `helpid=`):

```php
'HELP_O3_GUARANTEE_FIELDS' => 'Nur ausfüllen, wenn der HERSTELLER (nicht der Händler) eine kostenlose Haltbarkeitsgarantie über mehr als 2 Jahre für das gesamte Produkt gewährt UND Ihnen diese Information vorliegt (Produktdatenblatt, Verpackung, B2B-Katalog). Eine Nachforschungspflicht besteht nicht. Mit Anzeige des Labels werben Sie mit der Garantie und müssen die Garantiebedingungen bereitstellen (§ 479 BGB) - nutzen Sie dafür das Bedingungsfeld. Nicht ganzzahlige Laufzeiten (z. B. 30 Monate) sind im EU-Label nicht darstellbar und daher nicht label-fähig.',
```

(If the file uses a different key pattern for `helpid=o3_guarantee_fields`, match it — look at how an existing `helpid=article_unit` resolves.)

- [ ] **Step 4: Run test to verify it passes**

Run: `./docker.sh test --fast tests/Unit/Application/Controller/Admin/ArticleExtendGuaranteeTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: cs-fixer + commit**

```bash
./docker.sh cs-fixer
git add source/Application/views/admin/tpl/article_extend.tpl source/Application/Controller/Admin/ArticleExtend.php source/Application/views/admin/de/lang.php source/Application/views/admin/en/lang.php source/Application/views/admin/de/help_lang.php source/Application/views/admin/en/help_lang.php tests/Unit/Application/Controller/Admin/ArticleExtendGuaranteeTest.php
git commit -m "feat(#219): guarantee fields on article Extended tab with non-blocking advisories"
```

---

### Task 9: Frontend translation keys (de/en)

**Files:**
- Modify: `source/Application/translations/de/lang.php` (append before closing `];`, after the `O3_CAPTCHA_*` block)
- Modify: `source/Application/translations/en/lang.php` (same position)

**Interfaces:**
- Consumes: nothing.
- Produces: the exact keys the theme plan's templates reference. Do not rename.

- [ ] **Step 1: Append the German keys**

```php
// EU-Garantie-Labels (Issue #219, Reg. (EU) 2025/1960) - Storefront-Texte.
// Die Texte IN den Labels selbst sind festes EU-Artwork, keine Übersetzungen.
'O3_GUARANTEE_NOTICE_IMG_ALT'          => 'EU-einheitlicher Hinweis auf die gesetzliche Gewährleistung von mindestens zwei Jahren',
'O3_GUARANTEE_LABEL_IMG_ALT'           => 'EU-Garantie-Label: Haltbarkeitsgarantie des Herstellers für dieses Produkt',
'O3_GUARANTEE_LABEL_UNAVAILABLE_TEXT'  => 'Dieses Produkt hat eine Haltbarkeitsgarantie des Herstellers von %d Jahren (Garantiegeber: %s). Das offizielle EU-Garantie-Label kann derzeit leider nicht angezeigt werden.',
'O3_GUARANTEE_LEGAL_REMINDER'          => 'Unabhängig davon gilt die gesetzliche Gewährleistung von mindestens zwei Jahren.',
'O3_GUARANTEE_CONDITIONS_HEADING'      => 'Garantiebedingungen',
'O3_GUARANTEE_EMAIL_ITEM_LINE'         => 'Herstellergarantie: %d Jahre (Garantiegeber: %s). Zusätzlich gilt die gesetzliche Gewährleistung von mindestens zwei Jahren.',
```

- [ ] **Step 2: Append the English keys**

```php
// EU guarantee labels (issue #219, Reg. (EU) 2025/1960) - storefront texts.
// Texts INSIDE the labels are fixed EU artwork, not translations.
'O3_GUARANTEE_NOTICE_IMG_ALT'          => 'Harmonised EU notice on the legal guarantee of conformity of at least two years',
'O3_GUARANTEE_LABEL_IMG_ALT'           => 'EU guarantee label: producer durability guarantee for this product',
'O3_GUARANTEE_LABEL_UNAVAILABLE_TEXT'  => 'This product carries a producer durability guarantee of %d years (guarantor: %s). The official EU guarantee label cannot be displayed right now.',
'O3_GUARANTEE_LEGAL_REMINDER'          => 'Regardless of this guarantee, the statutory legal guarantee of at least two years applies.',
'O3_GUARANTEE_CONDITIONS_HEADING'      => 'Guarantee conditions',
'O3_GUARANTEE_EMAIL_ITEM_LINE'         => 'Producer guarantee: %d years (guarantor: %s). The statutory legal guarantee of at least two years additionally applies.',
```

- [ ] **Step 3: Run the language-integrity tests**

Run: `./docker.sh test --fast tests/Unit/Application/Views/LangIntegrityTest.php` — if that path 404s, find the right file with `grep -rl "testColonsAtTheEnd" tests/` and run it. Expected: PASS (keys in both languages, no trailing colons, no duplicates).

- [ ] **Step 4: cs-fixer + commit**

```bash
./docker.sh cs-fixer
git add source/Application/translations/de/lang.php source/Application/translations/en/lang.php
git commit -m "feat(#219): O3_GUARANTEE_* storefront translation keys (de/en)"
```

---

### Task 10: Full quality gate

**Files:** none new — this is the `/finish` protocol for the whole branch.

- [ ] **Step 1: Full suite + coverage**

Run: `./docker.sh test-all-coverage`
Expected: cs-fixer clean, ALL tests green (not only the new ones — the `_isFieldEmpty` and `article_extend.tpl` edits touch shared code paths). If pre-existing failures unrelated to this branch appear, diff against `b-1.6` behaviour before blaming the feature — then report to the orchestrator.

- [ ] **Step 2: Manual smoke (evidence, not vibes)**

With the env up (`./docker.sh start`), via admin (http://localhost:8080/admin/, admin@example.com / admin123):
1. Enable both switches under "EU-Garantie-Labels".
2. On any article's "Extended" tab set: years 5, guarantor "ACME GmbH", model "X-2000", conditions text. Save.
3. `curl -s http://localhost:8080/ | grep -o 'pictures/guarantee/notice-[a-z]*\.png'` — footer notice URL present is NOT yet expected (templates come with the theme plan). Instead verify the getters directly: `docker exec o3shop-shop-ce-1 php -r 'require "bootstrap.php"; $a = oxNew(\OxidEsales\Eshop\Application\Model\Article::class); $a->load("<the-article-oxid>"); var_dump($a->getDurabilityGuaranteeLabelUrl()); $v = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class); var_dump($v->getGuaranteeNoticeUrl());'` — both return URLs; fetch the label URL with curl and confirm HTTP 200 + `image/png`.
4. Open the generated label PNG (under `source/out/pictures/generated/guarantee/`) with the Read tool — visually correct.

- [ ] **Step 3: Update shared memory**

Per repo protocol: read `.claude/memory/MEMORY.md`; record anything non-obvious learned (e.g. the `_isFieldEmpty` zero-value whitelist requirement for inheritable int columns, the `_includeImages` picture-dir mapping that makes email embedding free).

- [ ] **Step 4: Report to orchestrator**

Report: suite results, coverage delta, the smoke-test evidence (URLs + PNG check), and anything deferred. Do NOT push, do NOT open a PR.

---

## What is deliberately NOT in this plan

- **Theme templates/CSS/email templates** — separate plan (`o3-Theme` + `wave-theme` repos) consuming the interfaces defined in Tasks 6/9.
- **Core/Email.php changes** — none needed; `_includeImages()` already embeds picture-dir images.
- **QR generation** — none exists; both QR codes are baked into the official artwork (settled against the primary text).
- **`sLegalGuaranteeNoticePlacement` config** — cut (YAGNI); footer placement is fixed.
- **Months-based storage / `O3GUARANTEETYPE`** — rejected in the design (see spec §3).
- **Languages beyond de/en, per-language conditions, feed export** — follow-ups per spec §8.
