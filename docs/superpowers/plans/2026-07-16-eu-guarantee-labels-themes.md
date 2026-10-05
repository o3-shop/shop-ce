# EU Guarantee Labels (#219) — Theme Delivery Implementation Plan (Plan 2)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development. One **Opus 4.8** implementer subagent per task; the orchestrator (Fable 5) reviews between tasks and writes no production code. Implementers: on ambiguity or two unexpected failures, STOP and escalate to the orchestrator.

**Goal:** Render the EU guarantee artifacts in both storefront themes: the shop-global legal-guarantee notice in the footer, and the per-product durability label on the product page, the order-final page (§ 312j BGB), and the order-confirmation email.

**Architecture:** Two new include templates per theme (`guaranteenotice.tpl`, `guaranteelabel.tpl`) consuming the frozen core getters, grafted into existing block structures; one SCSS partial per theme compiled through each theme's own build. Everything `method_exists`-guarded so a theme update against an older shop-ce never fatals.

**Tech Stack:** Smarty 2.6 templates, SCSS (gulp in o3-theme / grunt in wave-theme), no PHP.

**Repos and branches (already created off up-to-date main):**
- `/Users/nick/o3/o3-theme` → branch `219-eu-guarantee-labels-v2` (Bootstrap 5, v1.5.3)
- `/Users/nick/o3/wave-theme` → branch `219-eu-guarantee-labels-v2` (v1.2.5)
- Task 1 only: `/Users/nick/o3/shop-ce` → branch `garantie-label-2`

## Frozen core interfaces (shop-ce, garantie-label-2 — consume verbatim, never re-implement)

- `$oViewConf->getGuaranteeNoticeUrl(): ?string` — per-language notice artwork URL; null when switch off / no assets.
- `$oViewConf->getDurabilityGuaranteeLabelsEnabled(): bool` — master switch.
- Article: `getDurabilityGuaranteeLabelUrl(): ?string` (null = switch off OR ineligible OR guarantor unresolvable OR generation failed), `isDurabilityGuaranteeEligible(): bool`, `getGuaranteeYears(): int`, `getGuaranteeGuarantor(): string`, `getGuaranteeConditions(): string`.
- Fallback rule (eligible-but-unavailable): show text fallback iff `getDurabilityGuaranteeLabelsEnabled() && isDurabilityGuaranteeEligible() && getGuaranteeGuarantor() !== '' && getDurabilityGuaranteeLabelUrl() === null`.
- CMS snippet ident: `o3_guarantee_notice_info` (seeded inactive; strictly supplementary text below the notice — must NEVER gate the notice image).
- Frontend lang keys (after Task 1): `O3_GUARANTEE_NOTICE_IMG_ALT`, `O3_GUARANTEE_LABEL_IMG_ALT`, `O3_GUARANTEE_LABEL_FALLBACK_DURATION` (1 arg), `O3_GUARANTEE_LABEL_FALLBACK_GUARANTOR` (1 arg), `O3_GUARANTEE_LABEL_FALLBACK_NOTE`, `O3_GUARANTEE_LEGAL_REMINDER`, `O3_GUARANTEE_CONDITIONS_HEADING`.

## Global Constraints

- Commits in Nick's name only — NO Co-Authored-By, NO AI attribution, no session links. **Never push any repo.**
- Smarty 2.6 pitfalls: `|cat` inside a method-call argument is invalid (build strings with `[{assign}]` first — recorded in shared memory known-pitfalls). No Smarty-3 syntax (no inline array literals, no `{$var}` braces).
- Every call to a NEW ViewConfig/Article guarantee method in templates is guarded: `[{if method_exists($oViewConf, '...')}]` — same idiom as the captcha blocks in `tpl/form/*.tpl`.
- `oxmultilang` args: scalar only from Smarty 2.6 (`sprintf`) — never assume array passing works.
- New CSS class names must survive o3-theme's purgecss (they do when present in committed templates; never generate class names dynamically).
- Compiled CSS is committed in BOTH repos (o3: `out/o3-theme/src/css/`, wave: `out/wave/src/css/`) — run the build and commit its output.
- Colour rendering of both artifacts is legally mandatory online — never add CSS filters/grayscale on the label/notice images.
- All storefront text outside the fixed artwork comes from `O3_GUARANTEE_*` core keys — no hardcoded strings in templates.

---

### Task 1: Restructure the two multi-arg frontend lang keys (shop-ce)

**Repo/branch:** `/Users/nick/o3/shop-ce`, `garantie-label-2`
**Files:**
- Modify: `source/Application/translations/de/lang.php`, `source/Application/translations/en/lang.php`

**Why:** `O3_GUARANTEE_LABEL_UNAVAILABLE_TEXT` and `O3_GUARANTEE_EMAIL_ITEM_LINE` use `%d …%s` (two placeholders). `function.oxmultilang.php` needs an ARRAY for multi-arg (`vsprintf`), and Smarty 2.6 templates cannot express arrays. Branch is unpushed; the theme is the only consumer → restructure into self-contained single-arg fragments.

**Interfaces produced:** the three fragment keys listed in "Frozen core interfaces" replace the two old keys.

- [ ] **Step 1: Replace the keys in `de/lang.php`**

Remove `O3_GUARANTEE_LABEL_UNAVAILABLE_TEXT` and `O3_GUARANTEE_EMAIL_ITEM_LINE`; add in their place:

```php
'O3_GUARANTEE_LABEL_FALLBACK_DURATION'  => 'Haltbarkeitsgarantie des Herstellers: %s Jahre',
'O3_GUARANTEE_LABEL_FALLBACK_GUARANTOR' => 'Garantiegeber: %s',
'O3_GUARANTEE_LABEL_FALLBACK_NOTE'      => 'Das offizielle EU-Garantie-Label kann derzeit leider nicht angezeigt werden.',
```

- [ ] **Step 2: Same in `en/lang.php`**

```php
'O3_GUARANTEE_LABEL_FALLBACK_DURATION'  => 'Producer durability guarantee: %s years',
'O3_GUARANTEE_LABEL_FALLBACK_GUARANTOR' => 'Guarantor: %s',
'O3_GUARANTEE_LABEL_FALLBACK_NOTE'      => 'The official EU guarantee label cannot be displayed right now.',
```

- [ ] **Step 3: Verify no other consumer + integrity test**

Run: `grep -rn "O3_GUARANTEE_LABEL_UNAVAILABLE_TEXT\|O3_GUARANTEE_EMAIL_ITEM_LINE" source/ tests/` → must return only nothing (keys fully gone). Then `./docker.sh test --fast tests/Unit/Core/Smarty/LangIntegrityTest.php` → OK.

- [ ] **Step 4: cs-fixer + commit**

```bash
./docker.sh cs-fixer
git add source/Application/translations/de/lang.php source/Application/translations/en/lang.php
git commit -m "refactor(#219): split multi-arg guarantee lang keys into Smarty-2.6-safe single-arg fragments"
```

---

### Task 2: o3-theme — the two include templates

**Repo/branch:** `/Users/nick/o3/o3-theme`, `219-eu-guarantee-labels-v2`
**Files:**
- Create: `tpl/layout/inc/guaranteenotice.tpl`
- Create: `tpl/page/details/inc/guaranteelabel.tpl`

**Interfaces:**
- Consumes: core getters (see header).
- Produces: `guaranteenotice.tpl` (no parameters; self-guarded). `guaranteelabel.tpl` — contract: caller `[{assign var="oGuaranteeArticle" value=<article>}]` and optional `[{assign var="sGuaranteeContext" value="o3-guarantee-label--detail|--order"}]` before `[{include file="page/details/inc/guaranteelabel.tpl"}]`; the include renders nothing unless enabled+eligible.

- [ ] **Step 1: Write `tpl/layout/inc/guaranteenotice.tpl`**

```smarty
[{* EU legal-guarantee notice (Reg. (EU) 2025/1960 Annex I) - shop-global fixed artwork, issue #219. *}]
[{* The CMS snippet below is strictly supplementary and must never gate the image. *}]
[{if method_exists($oViewConf, 'getGuaranteeNoticeUrl')}]
    [{assign var="sGuaranteeNoticeUrl" value=$oViewConf->getGuaranteeNoticeUrl()}]
    [{if $sGuaranteeNoticeUrl}]
        <div class="o3-guarantee-notice">
            <img src="[{$sGuaranteeNoticeUrl}]" alt="[{oxmultilang ident="O3_GUARANTEE_NOTICE_IMG_ALT"}]" loading="lazy" class="o3-guarantee-notice__img">
            [{oxifcontent ident="o3_guarantee_notice_info" object="oGuaranteeCont"}]
                <div class="o3-guarantee-notice__info">[{$oGuaranteeCont->oxcontents__oxcontent->value}]</div>
            [{/oxifcontent}]
        </div>
    [{/if}]
[{/if}]
```

- [ ] **Step 2: Write `tpl/page/details/inc/guaranteelabel.tpl`**

```smarty
[{* EU durability-guarantee label (Reg. (EU) 2025/1960 Annex II) - per-product, issue #219. *}]
[{* Contract: caller assigns oGuaranteeArticle (and optionally sGuaranteeContext) before including. *}]
[{if $oGuaranteeArticle && method_exists($oViewConf, 'getDurabilityGuaranteeLabelsEnabled') && $oViewConf->getDurabilityGuaranteeLabelsEnabled() && method_exists($oGuaranteeArticle, 'isDurabilityGuaranteeEligible') && $oGuaranteeArticle->isDurabilityGuaranteeEligible()}]
    [{assign var="sGuaranteeLabelUrl" value=$oGuaranteeArticle->getDurabilityGuaranteeLabelUrl()}]
    [{assign var="sGuaranteeGuarantor" value=$oGuaranteeArticle->getGuaranteeGuarantor()}]
    <div class="o3-guarantee-label [{$sGuaranteeContext}]">
        [{if $sGuaranteeLabelUrl}]
            <img src="[{$sGuaranteeLabelUrl}]" alt="[{oxmultilang ident="O3_GUARANTEE_LABEL_IMG_ALT"}]" loading="lazy" class="o3-guarantee-label__img">
        [{elseif $sGuaranteeGuarantor}]
            <p class="o3-guarantee-label__fallback">
                [{oxmultilang ident="O3_GUARANTEE_LABEL_FALLBACK_DURATION" args=$oGuaranteeArticle->getGuaranteeYears()}]
                ([{oxmultilang ident="O3_GUARANTEE_LABEL_FALLBACK_GUARANTOR" args=$sGuaranteeGuarantor}]).
                [{oxmultilang ident="O3_GUARANTEE_LABEL_FALLBACK_NOTE"}]
                [{oxmultilang ident="O3_GUARANTEE_LEGAL_REMINDER"}]
            </p>
        [{/if}]
        [{assign var="sGuaranteeConditions" value=$oGuaranteeArticle->getGuaranteeConditions()}]
        [{if $sGuaranteeConditions}]
            <details class="o3-guarantee-label__conditions">
                <summary>[{oxmultilang ident="O3_GUARANTEE_CONDITIONS_HEADING"}]</summary>
                <div>[{$sGuaranteeConditions|escape:"html"|nl2br}]</div>
            </details>
        [{/if}]
        [{assign var="sGuaranteeContext" value=""}]
    </div>
[{/if}]
```

(The trailing `sGuaranteeContext` reset prevents context leakage into a later include on the same page.)

- [ ] **Step 3: Syntax check + commit**

Sanity: `php -l` does not apply to Smarty; instead verify balanced tags by grep-counting `[{if` vs `[{/if` in each new file (must be equal).

```bash
git -C /Users/nick/o3/o3-theme add tpl/layout/inc/guaranteenotice.tpl tpl/page/details/inc/guaranteelabel.tpl
git -C /Users/nick/o3/o3-theme commit -m "feat(#219): EU guarantee notice + durability label include templates"
```

---

### Task 3: o3-theme — graft points

**Repo/branch:** `/Users/nick/o3/o3-theme`, `219-eu-guarantee-labels-v2`
**Files:**
- Modify: `tpl/layout/footer.tpl` (`.footer__legal` container, after block `dd_footer_copyright`)
- Modify: `tpl/page/details/inc/productmain.tpl` (after block `details_productmain_morepics`, ~line 112)
- Modify: `tpl/page/checkout/order.tpl` (inside/after block `order_basket`)
- Modify: `tpl/email/html/order_cust.tpl` (inside block `email_html_order_cust_basketitem`, ~line 127)
- Modify: `tpl/email/plain/order_cust.tpl` (inside block `email_plain_order_cust_basketitem`, ~line 31)

**Interfaces:** Consumes Task 2's include contract. Read each target file around the named block BEFORE editing — line numbers are approximate; block names are authoritative.

- [ ] **Step 1: Footer** — inside `.footer__legal`, after the `dd_footer_copyright` block, add:

```smarty
[{block name="o3_footer_guarantee_notice"}]
    [{include file="layout/inc/guaranteenotice.tpl"}]
[{/block}]
```

- [ ] **Step 2: Product detail** — directly after the `details_productmain_morepics` block (label "directly next to the picture of the good", Recital 28), add (verify the product variable name used elsewhere in the file — expected `$oDetailsProduct`):

```smarty
[{block name="o3_details_guarantee_label"}]
    [{assign var="oGuaranteeArticle" value=$oDetailsProduct}]
    [{assign var="sGuaranteeContext" value="o3-guarantee-label--detail"}]
    [{include file="page/details/inc/guaranteelabel.tpl"}]
[{/block}]
```

- [ ] **Step 3: Order-final page** (§ 312j Abs. 2 BGB) — in `order.tpl`, immediately after the `order_basket` block's basket include, add a compact per-item loop (order page only — deliberately NOT in the shared `basketcontents_list.tpl`, which the editable basket page also uses):

```smarty
[{block name="o3_order_guarantee_labels"}]
    [{foreach from=$oxcmp_basket->getContents() item=guaranteeBasketItem}]
        [{assign var="oGuaranteeArticle" value=$guaranteeBasketItem->getArticle()}]
        [{assign var="sGuaranteeContext" value="o3-guarantee-label--order"}]
        [{include file="page/details/inc/guaranteelabel.tpl"}]
    [{/foreach}]
[{/block}]
```

- [ ] **Step 4: HTML email** — inside block `email_html_order_cust_basketitem`, after the existing item cells' content (keep table structure valid — add a full-width row or append inside the title cell, matching the table layout you find), add:

```smarty
[{assign var="oGuaranteeArticle" value=$basketitem->getArticle()}]
[{if $oGuaranteeArticle && method_exists($oViewConf, 'getDurabilityGuaranteeLabelsEnabled') && $oViewConf->getDurabilityGuaranteeLabelsEnabled() && method_exists($oGuaranteeArticle, 'isDurabilityGuaranteeEligible') && $oGuaranteeArticle->isDurabilityGuaranteeEligible()}]
    [{assign var="sGuaranteeLabelUrl" value=$oGuaranteeArticle->getDurabilityGuaranteeLabelUrl()}]
    [{if $sGuaranteeLabelUrl}]
        <div style="margin:8px 0;">
            <img src="[{$sGuaranteeLabelUrl}]" alt="[{oxmultilang ident="O3_GUARANTEE_LABEL_IMG_ALT"}]" width="220" style="width:220px;max-width:100%;height:auto;border:0;">
        </div>
    [{else}]
        <p style="margin:8px 0;font-size:12px;">
            [{oxmultilang ident="O3_GUARANTEE_LABEL_FALLBACK_DURATION" args=$oGuaranteeArticle->getGuaranteeYears()}]
            ([{oxmultilang ident="O3_GUARANTEE_LABEL_FALLBACK_GUARANTOR" args=$oGuaranteeArticle->getGuaranteeGuarantor()}]).
            [{oxmultilang ident="O3_GUARANTEE_LEGAL_REMINDER"}]
        </p>
    [{/if}]
[{/if}]
```

(The absolute label URL under `out/pictures/generated/guarantee/` is auto-embedded as a `cid:` inline attachment by `Email::_includeImages()` — no email PHP changes.)

- [ ] **Step 5: Plain email** — inside block `email_plain_order_cust_basketitem`, after the item line:

```smarty
[{assign var="oGuaranteeArticle" value=$basketitem->getArticle()}]
[{if $oGuaranteeArticle && method_exists($oViewConf, 'getDurabilityGuaranteeLabelsEnabled') && $oViewConf->getDurabilityGuaranteeLabelsEnabled() && method_exists($oGuaranteeArticle, 'isDurabilityGuaranteeEligible') && $oGuaranteeArticle->isDurabilityGuaranteeEligible()}]
[{oxmultilang ident="O3_GUARANTEE_LABEL_FALLBACK_DURATION" args=$oGuaranteeArticle->getGuaranteeYears()}] ([{oxmultilang ident="O3_GUARANTEE_LABEL_FALLBACK_GUARANTOR" args=$oGuaranteeArticle->getGuaranteeGuarantor()}]). [{oxmultilang ident="O3_GUARANTEE_LEGAL_REMINDER"}]
[{/if}]
```

- [ ] **Step 6: Balanced-tag check + commit**

For each modified file: `[{if` count equals `[{/if` count, `[{block` equals `[{/block`, `[{foreach` equals `[{/foreach`.

```bash
git -C /Users/nick/o3/o3-theme add tpl/layout/footer.tpl tpl/page/details/inc/productmain.tpl tpl/page/checkout/order.tpl tpl/email/html/order_cust.tpl tpl/email/plain/order_cust.tpl
git -C /Users/nick/o3/o3-theme commit -m "feat(#219): graft guarantee notice (footer) and durability label (detail, order, email)"
```

---

### Task 4: o3-theme — SCSS, build, version

**Repo/branch:** `/Users/nick/o3/o3-theme`, `219-eu-guarantee-labels-v2`
**Files:**
- Create: `build/scss/widget/_guarantee.scss`
- Modify: `build/scss/main.bundle.scss` (add `@import 'widget/guarantee';` with the other widget imports at the bottom)
- Modify: `theme.php` (version bump 1.5.3 → 1.6.0), `CHANGELOG.md` (entry per the file's existing format)
- Commit: compiled `out/o3-theme/src/css/` output

- [ ] **Step 1: Write the partial**

```scss
// EU guarantee labels (#219). Colour rendering is legally mandatory - never filter these images.
.o3-guarantee-notice {
    margin: 1rem 0;

    &__img {
        display: block;
        max-width: 340px;
        width: 100%;
        height: auto;
    }

    &__info {
        margin-top: .5rem;
        font-size: .875rem;
    }
}

.o3-guarantee-label {
    margin: 1rem 0;

    &__img {
        display: block;
        max-width: 260px;
        width: 100%;
        height: auto;
    }

    &__fallback {
        font-size: .875rem;
        margin: .5rem 0;
    }

    &__conditions {
        margin-top: .5rem;
        font-size: .875rem;

        summary {
            cursor: pointer;
        }
    }

    &--order {
        margin: .5rem 0 .5rem 1rem;

        .o3-guarantee-label__img {
            max-width: 200px;
        }
    }
}
```

- [ ] **Step 2: Register + build**

Add the `@import` to `main.bundle.scss`; run `npm ci` (if node_modules missing) then `npm run build`. Verify `grep -c "o3-guarantee" out/o3-theme/src/css/main.css` > 0 (purgecss kept the classes — they exist in committed templates).

- [ ] **Step 3: Version + changelog + commit**

`theme.php` version → `1.6.0`; CHANGELOG entry: "Added: EU guarantee labels (#219) — shop-global legal-guarantee notice (footer) and producer durability-guarantee label (product page, order page, order email). Requires shop-ce with #219 core (graceful no-op on older shops)."

```bash
git -C /Users/nick/o3/o3-theme add build/scss/widget/_guarantee.scss build/scss/main.bundle.scss theme.php CHANGELOG.md out/o3-theme/src/css/
git -C /Users/nick/o3/o3-theme commit -m "feat(#219): guarantee label styles, compiled CSS, version 1.6.0"
```

---

### Task 5: wave-theme — the two include templates

**Repo/branch:** `/Users/nick/o3/wave-theme`, `219-eu-guarantee-labels-v2`
**Files:** Create `tpl/layout/inc/guaranteenotice.tpl` and `tpl/page/details/inc/guaranteelabel.tpl` — **identical content to Task 2** (copy the exact code blocks from Task 2 Steps 1–2; the templates are theme-agnostic by design).

- [ ] Step 1: Write both files (verbatim from Task 2).
- [ ] Step 2: Balanced-tag check; commit:

```bash
git -C /Users/nick/o3/wave-theme add tpl/layout/inc/guaranteenotice.tpl tpl/page/details/inc/guaranteelabel.tpl
git -C /Users/nick/o3/wave-theme commit -m "feat(#219): EU guarantee notice + durability label include templates"
```

---

### Task 6: wave-theme — graft points

**Repo/branch:** `/Users/nick/o3/wave-theme`, `219-eu-guarantee-labels-v2`
**Files:**
- Modify: `tpl/layout/footer.tpl` — wave's legal area is the post-`</footer>` `.legal` container (`oxifcontent ident="oxstdfooter"` block): add the same `o3_footer_guarantee_notice` block from Task 3 Step 1 after `dd_footer_copyright`. IMPORTANT: place it so it renders even when the `oxstdfooter` CMS content is inactive — if `dd_footer_copyright` sits inside the `oxifcontent` guard, add the guarantee block OUTSIDE that guard (own `<div class="legal">`-styled container) so an operator's disabled footer text cannot legally hide the notice.
- Modify: `tpl/page/details/inc/productmain.tpl` — after block `details_productmain_zoom` (~line 55) / its `morepics` sibling: same graft as Task 3 Step 2 (verify product variable name in THIS file).
- Modify: `tpl/page/checkout/order.tpl` — after the `order_basket` block's include (~line 235): same loop as Task 3 Step 3.
- Modify: `tpl/email/html/order_cust.tpl` + `tpl/email/plain/order_cust.tpl` — same grafts as Task 3 Steps 4–5 (blocks `email_html_order_cust_basketitem` / `email_plain_order_cust_basketitem` — identical names in wave).

- [ ] Step 1–5: apply the grafts (code verbatim from Task 3, wave block locations).
- [ ] Step 6: balanced-tag check; commit:

```bash
git -C /Users/nick/o3/wave-theme add tpl/layout/footer.tpl tpl/page/details/inc/productmain.tpl tpl/page/checkout/order.tpl tpl/email/html/order_cust.tpl tpl/email/plain/order_cust.tpl
git -C /Users/nick/o3/wave-theme commit -m "feat(#219): graft guarantee notice (footer) and durability label (detail, order, email)"
```

---

### Task 7: wave-theme — SCSS, build, version

**Repo/branch:** `/Users/nick/o3/wave-theme`, `219-eu-guarantee-labels-v2`
**Files:**
- Create: `build/scss/_guarantee.scss` (same content as Task 4 Step 1)
- Modify: `build/scss/style.scss` (add `@import 'guarantee';` with the other partial imports)
- Modify: `theme.php` (1.2.5 → 1.3.0), `CHANGELOG.md` (same entry text as Task 4)
- Commit: compiled `out/wave/src/css/styles.css` + `styles.min.css`

- [ ] Step 1: partial + import.
- [ ] Step 2: build with `npm ci` (if needed) then `grunt` (default task); verify `grep -c "o3-guarantee" out/wave/src/css/styles.css` > 0.
- [ ] Step 3: version/changelog; commit:

```bash
git -C /Users/nick/o3/wave-theme add build/scss/_guarantee.scss build/scss/style.scss theme.php CHANGELOG.md out/wave/src/css/
git -C /Users/nick/o3/wave-theme commit -m "feat(#219): guarantee label styles, compiled CSS, version 1.3.0"
```

---

### Task 8: In-shop verification (both themes)

**Repos:** the in-shop clones `source/Application/views/o3-theme` and `source/Application/views/wave` (independent git clones inside shop-ce, git-ignored). This task intentionally switches them to the v2 branches for verification; they go back to `main` at the end.

- [ ] **Step 1: Sync branches into the in-shop clones**

```bash
git -C source/Application/views/o3-theme fetch /Users/nick/o3/o3-theme 219-eu-guarantee-labels-v2:219-eu-guarantee-labels-v2 && git -C source/Application/views/o3-theme checkout 219-eu-guarantee-labels-v2
git -C source/Application/views/wave fetch /Users/nick/o3/wave-theme 219-eu-guarantee-labels-v2:219-eu-guarantee-labels-v2 && git -C source/Application/views/wave checkout 219-eu-guarantee-labels-v2
rm -rf source/tmp/smarty/*
```

- [ ] **Step 2: Verify storefront (active theme first, then flip)**

Prereq state (already true): switches on, article `1126` has guarantee data (5y, ACME GmbH, X-2000).
1. Homepage `curl -s http://localhost:8080/` → contains `o3-guarantee-notice` and `pictures/guarantee/notice-de.png`; HTTP 200; `tail` of `source/log/oxideshop.log` shows no new ERROR.
2. Detail page of article 1126 (find its seo/`index.php?cl=details&anid=1126` URL) → contains `o3-guarantee-label` and a `generated/guarantee/1126_` img URL; fetch that URL → 200 image/png.
3. Degradation: article without guarantee (years 0) renders NO `o3-guarantee-label` markup on its detail page.

- [ ] **Step 3: Verify email via Mailpit**

Trigger a confirmation for an existing order containing article 1126 — if none exists, take any existing oxorder and send its mail (read-only against order data):
`docker exec --env-file docker/.env -w /var/www/html o3shop-shop-ce-shop-1 php -r 'require "source/bootstrap.php"; $o = oxNew(\OxidEsales\Eshop\Application\Model\Order::class); /* load an order id you find via the model */ ...'` — the implementer discovers a valid order OXID first (via admin or model listing), sends with `oxNew(Email::class)->sendOrderEmailToUser($order)`, then checks Mailpit: `curl -s http://localhost:8025/api/v1/messages` → newest message; fetch it and confirm an inline `cid:` PNG attachment is present when the order contains a qualifying item (and a plain-text guarantee line in the text part). If the only available orders have no qualifying item, add article 1126 to a NEW order via the storefront is out of scope — instead verify template rendering by rendering `email/html/order_cust.tpl` through the shop's TemplateRenderer with a mocked basket if feasible; otherwise report exactly what was and wasn't verifiable and STOP for orchestrator guidance.
4. **Checkout order-final page** requires a session with basket + login — SKIP in this task; the orchestrator verifies it interactively in the browser afterwards.

- [ ] **Step 4: Theme flip**

Switch active theme to the other one (`docker exec --env-file docker/.env -w /var/www/html o3shop-shop-ce-shop-1 vendor/bin/oe-console oe:theme:activate <theme>` if that command exists — check `vendor/bin/oe-console list` — otherwise via admin or `saveShopConfVar('str','sTheme',...)`), clear `source/tmp/smarty/*`, repeat Step 2 checks for the second theme. Restore the originally active theme afterwards.

- [ ] **Step 5: Report + restore**

Write the evidence (URLs checked, grep hits, Mailpit message id, log-tail state) to the report file. Then restore both in-shop clones to `main`: `git -C source/Application/views/o3-theme checkout main; git -C source/Application/views/wave checkout main; rm -rf source/tmp/smarty/*` — UNLESS the orchestrator's browser pass is still pending; in that case leave them on the v2 branches and say so in the report.

---

## What is deliberately NOT in this plan

- Pushing / opening the three cross-linked PRs (shop-ce → b-1.6, o3-theme → main, wave-theme → main) — Nick authorizes that separately.
- Checkout-page browser verification — orchestrator does it interactively (needs basket + login session).
- Nested/layered label display (permitted, not required — full inline label shipped).
- Any changes to the shared `basketcontents_list.tpl` (would leak the label onto the editable basket page).
