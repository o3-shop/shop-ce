---
name: reference_eu-guarantee-labels
description: Non-obvious implementation lessons from the EU guarantee-labels core feature (#219)
type: reference
---

# EU guarantee labels (#219) — core implementation lessons

Feature: two EU-mandated guarantee artifacts (Reg. (EU) 2025/1960): a shop-global
legal-guarantee notice (`ViewConfig::getGuaranteeNoticeUrl()`) and a per-product
producer-durability-guarantee label composited by `Core\GuaranteeLabelGenerator`.
Four `oxarticles` columns, two bool config switches
(`blShowLegalGuaranteeNotice`, `blShowDurabilityGuaranteeLabel`). Theme templates
are a separate plan; shop-ce only ships the interfaces.

## 1. `O3GUARANTEEYEARS` is nullable; `_isFieldEmpty()` whitelist still covers explicit 0

Per the PR #192 review, `oxarticles.O3GUARANTEEYEARS` is **`INT NULL DEFAULT NULL`**
(NOT the original `NOT NULL DEFAULT 0`) — a default 0 confused operators (every
article looked like it had a 0-year guarantee). Unset now reads empty. Keep the
migration and `Setup/Sql/database_schema.sql` column defs byte-identical; the
integration `GuaranteeMigrationTest` asserts `Null = YES` / `Default = NULL`.

Variant inheritance still works two ways: `Article::_isFieldEmpty()` already returns
true for `NULL` (`is_null` check up top), so an unset child inherits the parent; and
the `$aZeroValueFields` whitelist entry `oxarticles__o3guaranteeyears` additionally
makes an explicit `0` inherit. **Keep the whitelist entry** even though the column is
nullable — dropping it would make a deliberately-entered `0` shadow the parent's
value differently than NULL, and it pins the existing
`testVariantChildWithZeroYearsInheritsParentValue`. Any future inheritable INT column
whose legitimate `0` must inherit has the same whitelist requirement.
(`source/Application/Model/Article.php`, method `_isFieldEmpty`.)

`getGuaranteeYears()` reads via `getRawFieldData('o3guaranteeyears')` (like the sibling
getters); `(int)` casts NULL → 0 → never label-eligible.

## 1b. Admin: `<textarea>` renders darker than text inputs (facelift CSS gap)

`source/out/admin/src/main_facelift.css` colours `input[type="text"], input[type="password"], select`
`#555` (grey) but does **not** include `textarea`, so an admin `<textarea class="editinput">`
inherits the darker body colour `#34495e` and looks "black" next to grey inputs.
Fix locally with an inline `style="color: #555;"` on the textarea (done for the
guarantee-conditions field in `article_extend.tpl`) rather than touching the shared
CSS. The guarantee fields also live in their own right-side `<fieldset>` (like the
Media/UpdatePrices panels), not inline in the left column.

## 2. Serving artwork under `out/pictures/` makes email embedding free

`Core\Email::_includeImages()` rewrites every `<img src=...>` whose URL starts with
the shop picture URL (`getPictureUrl(null,false)` → `out/pictures/`) into an embedded
base64 `cid:` image. So placing the notice/label PNGs under
`out/pictures/guarantee/` (which is exactly what the `ViewConfig` getters and the
generator's `getLabelUrl()` return) means the images embed automatically in HTML
mails — **no `Core/Email.php` change was needed**. Do NOT invent a custom out-dir
for mailable assets; keep them under `out/pictures/` to inherit this behaviour.

## 3. Doctrine migrations on `oxarticles` must register the enum→string type mapping

`oxarticles` has `ENUM` columns that Doctrine DBAL ≤2.12 cannot introspect
("Unknown database type enum requested"). Any migration that reflects the table
schema must first call, in **both** `up()` and `down()`:

```php
$this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
```

(See `source/migration/data/Version20260715090000.php`.) The migration adds columns
via raw `addSql("ALTER TABLE ... ADD COLUMN ...")` with column definitions carrying
legal-semantics `COMMENT`s, and seeds an `oxcontents` snippet with `INSERT IGNORE`.

## 4. The label is a content-hash-cached composite; fresh installs default the switches ON

`GuaranteeLabelGenerator::getLabelUrl($oxid, $years, $guarantor, $model)` composites
the official artwork (`source/Core/GuaranteeLabel/assets/label-template.png`) with
three text fields via GD/FreeType (bundled Inter fonts) into
`out/pictures/generated/guarantee/<oxid>_<contenthash>.png`, regenerating only when
inputs change. `Article::getDurabilityGuaranteeLabelUrl()` returns null unless the
switch is on, `getGuaranteeYears() > 2`, and a guarantor resolves (own field →
linked manufacturer title). Config reads use `getConfigParam($name, false)` so
upgraded shops default OFF; `initial_data.sql` seeds `'1'` so fresh installs are ON.

## 5. v3 = official Commission artwork; nested banner; calibration derives from SVG boxes

Since `TEMPLATE_VERSION = 3` the templates are rasterized from the Commission's own
SVG package (label 1400×1474 @ scale 5.19886, nested banner 2211×340 @ 6×). The
generator also exposes `getNestedBannerUrl($oxid, $years)` (filename marker
`_nested_`, hash `md5(years|TEMPLATE_VERSION)`), sharing one generic
`compose($templatePath, $layout, $texts, $targetFile)`.

Non-obvious calibration lessons:
- **Don't calibrate by eyeballing** — LAYOUT/NESTED_LAYOUT fractions are *derived*
  from the blanked-field ink boxes + SVG `<text>` anchors recorded in
  `source/Core/GuaranteeLabel/assets/README.md` (baseline y, ink-left x, SVG font
  size × raster scale). First derivation matched the official render, zero
  iterations needed.
- **Both brand and model are Inter-Regular 9px in the official SVG** — an earlier
  guess used SemiBold for the guarantor; v3 corrected it to Regular.
- Same-hash caching bites calibration loops: identical inputs → identical filename
  → cache hit. Delete stale samples in the scratch target dir before regenerating.
