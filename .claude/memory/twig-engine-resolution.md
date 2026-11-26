---
name: twig-engine-resolution
description: How the template engine (Twig vs Smarty) is chosen per render call, and where Twig docs/traps live
type: reference
---

- Engine is resolved per render call, never cached per request: admin mode -> Smarty; storefront -> theme `templateEngine` (`theme.php`, default `smarty`, child inherits parent). `Core/Email` turns admin mode off, so e-mails use the storefront engine even from admin controllers.
- `parseThroughSmarty` (CMS content, long descriptions, newsletters) stays Smarty: `UtilsView::getRenderedContent()` renders with `LegacySmartyEngineInterface` directly, never through the delegating engine. Its HTML needs `|raw` in Twig.
- `Core/Email`: every storefront mail render goes through private `renderAsStorefront()` (admin off, restore in finally). Exception: `sendPricealarmToCustomer` (template only in `views/admin`). Pass logical template names to the renderer, never `getTemplatePath()` (empty under Twig).
- `DelegatingTemplateEngine::addGlobal` hits only the resolved engine and replays remembered globals on the other engine when it is next resolved (keeps Twig unbuilt on Smarty pages). New Twig globals set during rendering must be pre-registered in Twig `services.yaml` (Twig 3 rejects new globals after init).
- Controllers keep `.tpl` names; the shop resolves `.html.twig`. Module extension files in `views/twig/extensions/themes/<themeId|default>/`; a new extension file in an active module needs `oe:cache:clear`.
- Code in `source/Internal/Framework/Twig/` is adopted from OXID twig-component v1.1.0 (chain from v2.0.1); never copy from 8.x (proprietary).
- User docs: `docs/twig.md`; upgrade note (pin `o3-shop/o3-theme:^1`) in `CHANGELOG.md`.

## Theme port pitfalls (Task 5, checkout)
- (Fixed in C1, see below.) The Twig `ifcontent` node used to output its body without Smarty parsing.
- Twig attribute access on a `Base` object that is not a `BaseModel` (e.g. `BasketItem`) for a missing property goes to `Base::__call` and throws (`basketitem.oxarticles__oxunitname`); `BaseModel` objects (DeliverySet…) return null.
- Many view getters return values the core already HTML-escaped (`getInvoiceAddress`, `getDeliveryAddress`, `getDynValue`, `getOrderRemark`, `getCardMessage`, `getActiveUsername`, `getPaymentErrorText`, voucher numbers): print them `|raw` (and `|raw|nl2br`), or they are double-encoded and corrupted on resubmit.
- `DatabaseProvider::getDb()->getRow()` returns numeric keys by default; reading `$row['col']` gives null (zeroed article stock in a test fixture).

**CMS content in Twig (C1, 2026-11-26):** `{% ifcontent %}` gets a clone of the content with stored `oxcontent`/`oxtitle` parsed by Smarty (`IfContentLogic::getParsedContent`); NEVER pass rendered Twig output to Smarty (second-order SSTI via user data, security finding on C1); `include_content()` (needs_context) uses `getRenderedContent($value, $twigContext, …)` so CMS texts see `$shop`/`$user`/`$order` like `[{oxcontent}]`. Smarty errors in include_content are logged and return '' — a test asserting '' may hide a Smarty syntax error (`[{ 5+2 }]` is invalid Smarty 2). With the o3-theme checkout on `twig-port`, `RssTest::testRender` and `EmailTwigTest::testSmartyThemeStillRendersTpl` fail at baseline.

## Theme port pitfalls (Task 6, account/forms/info)
- Smarty 2 widgets (`oxid_include_widget`) silently saw the including template's variables (shared `_tpl_vars`); Twig widgets only get their own view data. Read widget params via the widget's getters (`oxwArticleBox::getRemoveFunction()/getWishId()/getRecommId()`), or remove buttons etc. vanish. Same leak: `oDetailsProduct` in listitem links.
- CMS text parsed with a fresh `FrontendController` (`parseThroughSmarty($v, $id, null)`, e.g. `ContentController::getParsedContent`) lacks component vars (`$oxcmp_shop`) under Twig — under Smarty they came from the shared Smarty object. Passing the active view fixes it.
- Not every `.value` is core-escaped: `OrderArticle` `oxtitle`/`oxpersparam`, `UserBasketItem`, `Links.oxurldesc`, `News.oxlongdesc` are T_RAW (`_setFieldData`). Grep the model before `.value|raw`.
- After switching the theme branch (engine), the first requests can still use the old engine (TEMPLATENOTFOUND `.tpl`); clear cache and poll a page until it is complete before capturing.

- CMS/Smarty fragments on a Twig theme: Smarty page vars (oxcmp_*) only reached CMS text because the Smarty page render left them on the shared static Smarty instance. `UtilsView::getRenderedContent()` now merges the active views' data under the caller context (core C2, e893619e).

## Theme port pitfalls (Task 7, e-mails)
- Smarty 2 newline rule (measured): the newline after control tags (assign/if/foreach/capture/comment) is dropped, like Twig; output tags keep it. OXID `[{block}]`/`[{/block}]` are textually transparent, so the newline after them survives unless a control tag precedes. Twig drops it after every `%}`/`#}` → plain mails need an empty line after such block-tag lines for 1.x text layout. Smarty also drops a trailing newline at EOF.
- Smarty plain mails saw variables the HTML mail assigned just before (shared Smarty object, also across mails in one PHP process: `$style` leaked into later mails). Twig plain templates must set `shop`/`user`/… themselves; compare mails one PHP process per mail.
- Twig cannot reach magic `__get` properties (`Order::oDelSet`) nor unset dynamic ones (`BasketItem::aDiscounts`): `Base::__call` throws `SystemComponentException` (not BadMethodCallException, so Twig does not swallow it).
- `Email::setAltBody()`/`setSubject()` decode one level of entities, so plain mails/subjects print core-escaped `.value` as is.

## Theme port pitfalls (Task 8, escaping audit)
- Several values are stored request-escaped by the core, so they need `|raw` (escaping again double-encodes): basket/order persparams (`BasketComponent` reads `persparam`/`aproducts` escaped, keys too), recommlist article notes (`addToRecomm`, `recomm_txt`), voucher numbers, `SelectList::getLabel()`/`VariantSelectList::getLabel()`/`Selection::getName()` (htmlspecialchars). SQL-inserted test fixtures hide this; inject hostile values through the storefront forms and compare with `main`.
- Converted templates keep 1.x dead code as one big `{# … #}` (from `[{* … *}]`, e.g. `basketcontents_list.html.twig` lines 62–136). An edit there is dead, and a `{# … #}` comment added inside closes the outer comment early (Twig "Unexpected endblock"). Check `{#` before editing; compile-check with the Twig env (`compileSource`) after edits, the lint does not parse.
- JS strings in templates: `|e('js')` on decoded text (`getRawValue()`, or `.value` of T_RAW fields), never HTML-escaped `.value|raw`.
- Translations with arguments: never `translate({…args…})|raw`; escape the lang text and insert the escaped argument (`translate({ident:"X"})|e|replace({'%s': arg})|raw`) or pass a decoded value and let autoescape work. `build/lint-twig.php` reports both (`transraw`, `rawtext`).
