# EU Guarantee Labels — Design (Issue #219)

**Date:** 2026-07-15
**Status:** Approved by Nick (section-by-section review)
**Scope:** Full compliance in one delivery. Core feature in shop-ce + cross-repo theme PRs (o3-Theme, wave-theme).
**Legal deadline:** 2026-09-27 (Directive (EU) 2024/825 / Implementing Regulation (EU) 2025/1960; German transposition effective same day).

This design was produced from a clean slate. The prior branch `219-eu-guarantee-labels` is an archive only; no decisions were carried over from it.

## 1. Legal grounding (verified against primary sources)

Research report from EUR-Lex primary texts (CELEX 32024L0825, OJ L 2025/1960), full copies in session scratchpad (`empco.txt`, `reg1960.txt`):

1. **Trigger scope (settled):** The durability-label duty arises only when the **producer** (never the seller) offers a commercial durability guarantee **at no additional cost**, covering the **entire good**, for **more than two years**, and **made that information available** to the trader (Art. 6(1)(la) CRD as inserted by 2024/825). The trader has **no duty to research** (Recitals 26/36). Seller guarantees and ≤2-year guarantees never trigger the label.
2. **Two cumulative artifacts:**
   - **Harmonised notice** on the legal guarantee of conformity ("Gewährleistungs-Label", Annex I): shop-global duty for every B2C trader, **per-language** artwork, **no element editable**, static per-language QR to the Your-Europe portal baked into the artwork.
   - **Harmonised label** for producer durability guarantees ("Garantie-Label", Annex II): product-specific, **language-neutral** (one artwork for the whole EU), exactly **three editable fields** — duration **in whole years** ("XX"), producer name, model identifier — in the **Inter** typeface; QR is fixed artwork (one EU-wide URL).
3. **Duration is years-only.** The regulation has no rendering rule for fractional years; a 30-month guarantee triggers the duty but cannot be rendered — an acknowledged regulatory gap.
4. **Online display:** colour mandatory (RGB), **no minimum size online** (A4 / 95×100 mm rules apply only off-line), nested/layered display permitted for the **label** (full label on first click/roll-over/touch).
5. **Placement:** both are pre-contractual information. The **label** (not the notice) must additionally appear "in a clear and prominent manner, directly before the consumer places his order" (Art. 8(2) CRD; § 312j Abs. 2 BGB n.F. in Germany) — i.e. on the order-final page. Recital 28: label "directly next to the picture of the good"; notice "as a general reminder on the website" — once per shop suffices.
6. **Email:** the order confirmation (durable medium, Art. 8(7) CRD / § 312f Abs. 2 BGB) must contain the Art. 6(1) information including the label information — include the label in the confirmation email (confidence: medium; the safe reading).
7. **Artwork:** the Commission publishes ready-made files (notice per-language; label template). Re-typesetting the notice is prohibited; the label allows only the three substitutions. Colours: #003399 / #FFED00 / #000000 / #FFFFFF; label fonts Inter Regular/SemiBold/ExtraBold.

## 2. Decisions

| # | Decision | Choice |
|---|---|---|
| 1 | Prior v1 work | Ignored entirely; clean-slate design |
| 2 | Packaging | Core feature in shop-ce (no module) |
| 3 | Scope | Full compliance, one delivery |
| 4 | Label rendering | GD-composited PNG per article — one path for storefront and email |
| 5 | Config | Two bool switches; fresh installs ON, upgrades OFF; footer placement fixed (no placement setting) |
| 6 | § 479 conditions | Longtext on `oxarticles`, single-language (documented limitation) |
| 7 | Themes | o3-Theme + wave-theme, cross-linked cross-repo PRs |
| 8 | Notice languages | DE + EN assets bundled; EN fallback with logged warning; more languages = asset drop-in follow-up |
| 9 | PNG lifecycle | Dedicated generator service, content-hash cache, generate-on-miss at render time |
| 10 | Failure mode | Eligible-but-unavailable renders a translated **text fallback** carrying the guarantee facts — never blank, never a broken page |

## 3. Data model & qualification

Four new columns on `oxarticles` (self-documenting MariaDB comments; all optional — empty-by-default is legally correct):

| Column | Type | Meaning |
|---|---|---|
| `O3GUARANTEEYEARS` | int, default 0 | Producer durability guarantee in whole years; 0 = none/not communicated |
| `O3GUARANTEEGUARANTOR` | varchar(255), default '' | Producer/brand name as it must appear on the label |
| `O3GUARANTEEMODEL` | varchar(255), default '' | Model identifier as it must appear on the label |
| `O3GUARANTEECONDITIONS` | longtext | § 479 BGB guarantee-conditions text |

- **Years, not months** (deviation from the issue draft, justified by §1.3): integer years make unrenderable states unrepresentable. Non-integer producer guarantees are documented in admin help as not label-eligible until the EU closes the gap.
- **Qualification is one predicate in one place:** `Article::isDurabilityGuaranteeEligible()` → `O3GUARANTEEYEARS > 2`. Producer-only / no-extra-cost / whole-good are admin help-text concerns. No `O3GUARANTEETYPE` column.
- **Fallback chains (getter-level, not stored):** guarantor: own field → linked `oxmanufacturers` title → unresolvable ⇒ label cannot render + non-blocking admin warning. Model: own field → `OXARTNUM` → article title.
- **Variants:** standard parent/child inheritance (`_assignParentFieldValues`, empty child inherits parent). Int-0-is-empty semantics verified by test.
- **Admin validation never blocks saving:** years 1–2 → info "not label-eligible (law requires >2 years)"; eligible but unresolvable guarantor → warning "label will not render".

## 4. Label generation service

**New oxNew-able core class `OxidEsales\EshopCommunity\Core\GuaranteeLabelGenerator`.** Single responsibility: `(years, guarantor, model)` → path/URL of a ready PNG, generating on cache miss.

**Bundled assets** (outside webroot, `source/Core/GuaranteeLabel/assets/`):
- Official EU label artwork (language-neutral, QR baked in) as high-res PNG template with the three variable areas blank.
- Inter TTF subset: Regular, SemiBold, ExtraBold (SIL OFL). First `imagettftext()` use in the codebase — no existing TTF infrastructure.

**Flow:**
1. Article resolves variable components via §3 fallback chains.
2. Cache key `md5(years|guarantor|model|templateVersion)` → `out/pictures/generated/guarantee/<oxid>_<hash>.png`. Hit → return URL.
3. Miss → GD composite: template + `imagettftext` (years ExtraBold at "XX" position; guarantor/model per annex font-size ratios) → temp file → atomic `rename()`. No lock files; concurrent generators race benignly to identical content.
4. Failure (missing font/template, GD error, unwritable dir) → log error, return null.

**Template-facing API:**
- `Article::getDurabilityGuaranteeLabelUrl()` → URL or null (null when: ineligible, master switch off, guarantor unresolvable, or generation failed). Templates distinguish *ineligible* (render nothing) from *eligible-but-unavailable* (render text fallback with the guarantee facts, `O3_GUARANTEE_LABEL_UNAVAILABLE_*`).
- `ViewConfig::getGuaranteeNoticeUrl()` → notice artwork URL for the active language, EN fallback + logged warning, null when switch off. Notice assets ship in core webroot `out/pictures/guarantee/` (theme-agnostic; language extension = asset drop-in).

**Email:** `Email::_includeImages()` already inlines shop-image `<img src>` as base64 `cid:` attachments; the label file exists by render time (getter guarantees it), so email templates use the same getter.

**Display:** full label inline on the product page (nested display permitted but not needed; no JS).

## 5. Configuration, admin & migration

**Config switches:**
- `blShowLegalGuaranteeNotice` — shop-global notice (footer).
- `blShowDurabilityGuaranteeLabel` — master switch for product labels.
- Fresh installs: both `'1'` via `Setup/Sql/initial_data.sql`. Upgrades: absent row + `getConfigParam($name, false)` ⇒ default off; release-notes callout. (Same asymmetry as the revocation feature.)

**Admin:**
1. Article → Extended tab (`ArticleExtend` + `article_extend.tpl`): the four fields, inline help covering the legal trigger, the no-research-duty rule, and the § 479 consequence. Non-blocking validation per §3.
2. `GuaranteeConfigController` (pattern: `RevocationConfigController`; `saveShopConfVar('bool', …)`), own `menu.xml` entry.
3. CMS snippet `o3_guarantee_notice_info` (`oxcontents`, seeded inactive/empty, `INSERT IGNORE` on `OXLOADID`): operator-editable supplementary text below the notice. The notice artwork itself is never editable.

**Migration:** one Doctrine migration in `source/migration/data/`: four columns (idempotent `hasColumn()` guards), CMS seed, clean `down()`. Plus `database_schema.sql` for fresh installs. Auto-applied by #192 auto-migrations on `composer update`.

**Translations:** all user-visible strings outside the fixed artwork under `O3_GUARANTEE_` prefix, de + en (frontend, admin, admin help).

## 6. Storefront & email (theme repos)

Core stays theme-agnostic (translation keys + getters, no theme branching). Each theme repo gets one branch/PR, identical structure:

**New include templates:**
- `guaranteenotice.tpl` — notice artwork `<img>` via `getGuaranteeNoticeUrl()` + CMS snippet below (snippet guard must never gate the image). Included from the footer.
- `guaranteelabel.tpl` — label `<img>` via `getDurabilityGuaranteeLabelUrl()`, text fallback for eligible-but-unavailable, § 479 conditions as expandable section beneath.

**Graft points (block-wrapped):**
1. Product detail page — label directly next to the product picture area, full label inline.
2. Checkout order-final page (`page/checkout/order.tpl`) — label per qualifying basket item (§ 312j duty). Notice deliberately not duplicated here (Art. 8(2) requires the label, not the notice; the shop-wide footer covers the notice).
3. Order confirmation email — HTML: label `<img>` per qualifying item (auto-embedded `cid:`); plain text: one line per qualifying item (years + guarantor + legal-guarantee reminder).

**Compatibility:** every new getter call in theme templates is `method_exists`-guarded (theme update against older shop-ce must not fatal).

**CSS:** `.o3-guarantee-*` partial per theme, compiled through each theme's existing build (gulp / grunt).

## 7. Testing & verification

**Unit (shop-ce):**
- Article: eligibility (0/1/2 → false, 3+ → true), guarantor + model fallback chains, variant inheritance incl. int-0-is-empty.
- GuaranteeLabelGenerator: cache hit does no GD work; miss composites + atomic write; content change → new hash/file; missing font/template or unwritable dir → null + error logged, no exception escapes. Output is valid PNG of expected dimensions; pixel-sampling of the three text regions proves text landed (no golden-image brittleness).
- ViewConfig: notice URL per language, EN fallback + warning log, null when off.
- GuaranteeConfigController: save round-trip of both switches.

**Integration:**
- Migration: columns + comments exist, snippet seeded once (run-twice idempotency), clean `down()`.
- Email: basket with one qualifying + one non-qualifying item → exactly one embedded label `cid:` in HTML, fallback line in plain text.
- Controllers: detail + order page expose label data only when switch on and article eligible.

**Manual (both themes):** switches on, eligible product seeded; detail page, footer notice (DE and EN), order-final page, Mailpit email; QR scannability at rendered size; degradation: switch off, ineligible article, deleted generated file regenerates, broken font → text fallback.

**Quality gate:** `./docker.sh test-all-coverage` green + cs-fixer clean (`/finish` protocol).

## 8. Out of scope

- Greenwashing/UWG blacklist side of EmpCo; right-to-repair Directive 2024/1799; digital content/services (no label).
- Bulk import of guarantee data (follow-up once data model is stable).
- Automated retrieval of producer guarantee data (no legal research duty; we will not fake one).
- Per-language § 479 conditions (documented limitation, follow-up on demand).
- Notice languages beyond DE/EN (asset drop-in follow-up).
- Category/list-view labels (no duty identified; explicitly not rendered there).

## 9. Open items to resolve during implementation

1. **Official artwork acquisition:** download the Commission's published files (notice DE+EN, label template); verify pixel dimensions, QR scannability, and that the label template's variable areas are blank (or blank them from the annex-conform original). Source: commission.europa.eu publications page for the harmonised notice/label.
2. **Exact text-placement coordinates** on the label template (positions/sizes for the three fields) — derive from Annex II geometry at the chosen template resolution.
3. **Inter font subset** files (Regular/SemiBold/ExtraBold) — obtain from the official Inter release (SIL OFL), subset to Latin if size matters.
