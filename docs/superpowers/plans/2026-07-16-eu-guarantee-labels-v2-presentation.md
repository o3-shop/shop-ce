# EU Guarantee Labels (#219) — V2 Presentation per EU Practical Guidelines (Plan 3)

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development. One **Opus 4.8** implementer per task; orchestrator (Fable 5) reviews between tasks, writes no production code. STOP and escalate on ambiguity or two unexpected failures.

**Goal:** Replace the current label presentation with the EU Commission's own pattern (Practical Guidelines, DG JUST, April 2026): official **nested GARAN banner** in the buy area expanding to the full label on first click; the notice behind a plain **"Ihre gesetzlichen Gewährleistungsrechte"** footer/checkout text link revealing the full label; official Commission artwork throughout.

**Approved design:** artifact "EU-Garantie-Labels — Design-Vorschau" v2 (`eu-guidelines-v2`), approved by Nick + boss. Preview source: `scratchpad/guarantee-design-preview.html`. Research evidence: the Practical Guidelines PDF + official asset packages, downloaded in scratchpad (`leitfaden-guidelines-en.pdf`, `garan-label-package/`, `nested-figure.svg`, mockups).

**Binding legal/design rules (from the Guidelines):** colour mandatory online; original proportions; no filters/effects; QR untouched and ≥2×2cm when displayed; label legible at default display size; nested state = official banner asset with ONLY the year editable; full label appears in its entirety on first click/roll-over/tap; notice behind a clearly named text link is the EU's own modelled pattern.

## Global Constraints

(as Plans 1–2: branches `garantie-label-2` / `219-eu-guarantee-labels-v2`; commits Nick-only, no AI attribution, never push; Smarty 2.6 rules — no array literals, no `|cat` in method args; `method_exists` guards on all new getters in templates; `./docker.sh cs-fixer` + tests per shop-ce task; official artwork NEVER modified beyond the designated editable fields.)

Asset URLs (verified working 2026-07-16):
- GARAN package (SVG/PNG/JPG × colour/bw/nested): `https://commission.europa.eu/document/download/435fbeb1-fccc-4ead-bfa9-96625962ba09_en?filename=GARAN%20label%20for%20website.zip` (already extracted in scratchpad `garan-label-package/`)
- Notice 24-language package: `https://commission.europa.eu/document/download/29acbfc0-a26e-4c21-85af-8bc2b167103e_en?filename=Harmonised%20notice%20in%2024%20languages%20colour%20and%20black%20and%20white_0.zip`
- Scratchpad: `/private/tmp/claude-501/-Users-nick-o3-shop-ce/ef2c0b73-f726-46eb-83f3-46280e563791/scratchpad`

---

### Task 1 (shop-ce): Official artwork v2 — templates for full label + nested banner + notices

**Files:** replace/create under `source/Core/GuaranteeLabel/assets/`: `label-template.png` (new, from official SVG), `nested-template.png` (new), README.md update; replace `source/out/pictures/guarantee/notice-de.png` + `notice-en.png` (official package versions, colour).

Steps:
1. From `garan-label-package/`: take `GARAN Label_colour.svg` and `GARAN Label_nested display.svg`. In COPIES, blank the editable text elements only (full label: XX + Brand/Trademark + Model identifier `<text>` contents → empty; nested: XX → empty). Record which SVG element IDs/positions were blanked.
2. Rasterize both blanked SVGs to PNG: full label at ~1400px width, nested at ~2200px width (6x of its 368.5×56.69 viewBox). SVG rasterizer options on this host: `docker run --rm -v <dir>:/d minidocks/librsvg rsvg-convert ...` or python3 `cairosvg` (pip install --user) or headless Chrome. Fonts: the SVGs reference Inter — ensure the rasterizer resolves Inter (install the repo's `source/Core/GuaranteeLabel/assets/Inter-*.ttf` into the rasterizer env) so the FIXED texts render correctly; verify visually against the official colour PNG/JPG.
3. Extract the official notice ZIP; take the German + English COLOUR files; convert to web PNG ≤1675px height if needed; replace the two files in `source/out/pictures/guarantee/` (same filenames — ViewConfig contract unchanged).
4. Record the blanked-field bounding boxes (px at final raster sizes) for BOTH templates in README (Task 2 calibrates from them). Update README provenance (new source URLs, commands).
5. Verify: `getimagesize` on all four outputs; visual Read-tool check (colours, QR crisp, no placeholder remnants, fixed texts in proper Inter).
6. Commit: `feat(#219): official Commission artwork v2 - label + nested templates, notice de/en`.

**Produces:** the two template PNGs + their blanked-region boxes; notice files swapped in place.

---

### Task 2 (shop-ce): Generator v2 — nested banner composition + recalibration

**Files:** `source/Core/GuaranteeLabelGenerator.php`; tests `tests/Unit/Core/Guarantee/GuaranteeLabelGeneratorTest.php` (extend), `GuaranteeLabelArtworkSmokeTest.php` (extend).

1. Add `public function getNestedBannerUrl(string $articleId, int $years): ?string` — same content-hash caching (`<safeId>_nested_<md5(years|TEMPLATE_VERSION)>.png` in the same target dir), same never-throws policy, composing ONLY the year (ExtraBold, centered in the recorded XX box) onto `nested-template.png`. Add `NESTED_LAYOUT` const (fractions, same conventions as LAYOUT).
2. Recalibrate `LAYOUT` for the NEW full-label template from Task 1's recorded boxes (positions differ from the OJ-derived template). Bump `TEMPLATE_VERSION` to 3 (one bump covers both template swaps).
3. Calibration loop for BOTH outputs in-container (pattern of Plan 1 Task 5; sample values 5/"ACME Example GmbH"/"Model X-2000", plus long-string clamp checks on the full label). **SIGN-OFF GATE:** copy final samples to scratchpad (`calib2-full.png`, `calib2-nested.png`, long variants) and return AWAITING_SIGNOFF — commit only after the orchestrator's go.
4. Tests: nested-banner unit tests mirroring the existing suite (valid PNG, template dimensions, cache-hit no-rewrite, content-change new name, missing template → null); smoke test extended to nested template asset. All existing generator tests must stay green.
5. After sign-off: cs-fixer, commit `feat(#219): nested banner generation + v2 template calibration`.

**Produces:** `getNestedBannerUrl(string,int): ?string`; recalibrated full-label composition.

---

### Task 3 (shop-ce): Article getter + lang keys

**Files:** `source/Application/Model/Article.php`, `source/Application/translations/{de,en}/lang.php`, test `tests/Unit/Application/Model/ArticleGuaranteeLabelUrlTest.php` (extend).

1. `Article::getDurabilityGuaranteeNestedUrl(): ?string` — same gate chain as `getDurabilityGuaranteeLabelUrl()` (switch → eligible → guarantor resolvable), delegating to `getNestedBannerUrl($this->getId(), $this->getGuaranteeYears())`; same lazy generator + existing setter seam. Tests mirror the existing URL-getter tests (switch off / ineligible / unresolvable / success / generator-null).
2. Lang keys (de/en): ADD `'O3_GUARANTEE_RIGHTS_LINK' => 'Ihre gesetzlichen Gewährleistungsrechte'` / `'Your legal guarantee rights'`; ADD `'O3_GUARANTEE_CLOSE' => 'Schließen'` / `'Close'`. KEEP all existing keys (fallback texts still cover generation failure; alt texts unchanged). LangIntegrity green.
3. cs-fixer; commit `feat(#219): nested-banner article getter + rights-link lang keys`.

**Produces:** `getDurabilityGuaranteeNestedUrl(): ?string`; keys `O3_GUARANTEE_RIGHTS_LINK`, `O3_GUARANTEE_CLOSE`.

---

### Task 4 (o3-theme): v2 presentation

**Files:** `tpl/page/details/inc/guaranteelabel.tpl` (rewrite), `tpl/layout/inc/guaranteenotice.tpl` (rewrite), `tpl/page/details/inc/pictures.tpl` + `tpl/page/details/inc/productmain.tpl` (move graft to buy area), `tpl/layout/footer.tpl` (strip → services-column link), `tpl/widget/footer/services.tpl` (if that's where the link belongs — mirror the revocation link's home), `tpl/page/checkout/order.tpl` (nested per item), `build/scss/widget/_guarantee.scss` + build.

1. **guaranteelabel.tpl v2** (shared include; same entry contract `oGuaranteeArticle`/`sGuaranteeContext`): CSS-only `<details class="o3-guarantee">` — `<summary>` = nested banner `<img src=getDurabilityGuaranteeNestedUrl()>` (alt = existing label alt key); open content = full label img (anchor click-to-full, zoomable) + `Garantiebedingungen` as inner `<details>` when conditions exist. Keep the text fallback branch (eligible + guarantor + both URLs null → existing FALLBACK_* text). All getters `method_exists`-guarded; nested-URL null but full-label URL present → degrade to showing the full label directly (small), never nothing.
2. **Buy-area graft**: move the detail-page include from the picture column into the buy box — directly under the price/VAT block, before the to-basket form (find the price block in `productmain.tpl`; the EU pattern places it "associated with the product, visible before purchase"). Remove the pictures.tpl graft.
3. **Footer**: remove the strip include from `footer.tpl`; add to the services links column (next to the revocation link, block name `o3_footer_guarantee_notice` kept): `<details class="o3-guarantee-rights">` with `<summary>` = `O3_GUARANTEE_RIGHTS_LINK` text (styled like sibling links, underline); open = reveal panel (notice img click-to-full + CMS snippet + close hint) — rewrite guaranteenotice.tpl accordingly (guard chain unchanged: switch off ⇒ nothing; snippet never gates image).
4. **order.tpl**: per qualifying item render the shared include (context `--order`) — now yields the nested banner + expand, per approved design. Also add the rights-link details (footer include) once above/below the order summary? NO — the footer renders on checkout pages too; the EU wants the notice link at checkout, footer placement satisfies it. Leave order.tpl to the per-item labels only.
5. **SCSS**: replace card/strip rules with v2: `.o3-guarantee summary` (list-style none, cursor pointer, focus ring, banner max-width 250px; order context 170px), open-state spacing, `.o3-guarantee-rights` panel (wash bg #F3F6FC, border #D9E1F0, padding, notice img 200px, responsive wrap), reveal animation none (respect reduced motion by default). No filters ever. Build via the symlink harness (see p2-task-4 report), commit compiled CSS.
6. Balanced-tag checks; verify by render (in-shop clone ff-merge + smarty clear + curl: homepage has rights-link summary markup; detail page has nested banner in buy box; order markup unchanged structure); commit `feat(#219): EU-guidelines presentation - nested banner, rights-link notice`.

---

### Task 5 (wave-theme): mirror Task 4

Same rewrite with wave's structures (buy area in `productmain.tpl`; footer services widget `tpl/widget/footer/services.tpl` next to revocation; shared includes byte-identical to o3-theme's v2 versions; SCSS partial mirrored; CSS via node:14 container build — fallback surgical append per p2-task-7 report). Same verification steps (activate wave, check, restore o3-theme). Commit message identical.

---

### Task 6: Verification + orchestrator browser pass

Agent part: both themes — homepage (rights link present, expands markup-wise), detail (nested banner in buy area, expandable, full label + conditions), order page markup, degradation (years=0 article: nothing; generation failure: fallback text), email unchanged (spot-check one send to Mailpit), no new log errors; leave in-shop clones on v2 branches. Orchestrator part: interactive browser pass (expand interactions, click-to-full, checkout flow with the parked-captcha config fix), screenshots for Nick + boss.

---

## Deliberately unchanged
- Email rendering (full label embedded — matches Guidelines "included in the confirmation email").
- Admin, migration, data model, eligibility logic — untouched.
- Notice languages: de+en shipped (official package versions); other 22 = drop-in follow-up (package URL in README).
