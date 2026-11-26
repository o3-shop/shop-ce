# Twig templates in O3-Shop 2.0

O3-Shop 2.0 ships Twig 3 next to Smarty. The active storefront theme decides which engine renders the shop.

## Upgrade note (read before updating)

To keep a Smarty storefront, stay on O3-Theme 1.x *before* updating to O3-Shop 2.0; otherwise O3-Theme is updated to its Twig version (2.x). **Open item:** every released O3-Theme 1.x tag requires `o3-shop/shop-ce: ^1.2`, so pinning `o3-shop/o3-theme` to `^1` does not resolve next to O3-Shop 2.0 yet. That needs an O3-Theme 1.x release whose `composer.json` also allows shop-ce 2.0 (not released yet); until then keep the theme checkout/package on the 1.x line manually.

The core never switches the active theme or engine on its own. A child theme written in Smarty on a Twig parent theme cannot work; the admin shows a warning naming both themes.

## Choosing the engine

A theme declares its engine in `theme.php`:

```php
'templateEngine' => 'twig', // or 'smarty'
```

- No key: `smarty` (all existing themes keep working).
- A child theme inherits the parent's engine unless it declares its own.
- If the configured theme no longer exists, the shop falls back to Smarty and logs a warning.

The engine is chosen on every render call:

| What | Engine |
|---|---|
| Admin pages (admin mode) | Smarty, always |
| Storefront pages and widgets | the theme's engine |
| E-mails (also when sent from the admin) | the theme's engine |
| Customer price-alarm mail sent from the admin (template in the admin theme) | Smarty |
| CMS content parsed with `parseThroughSmarty` (content pages/snippets, long descriptions, newsletters), also under a Twig theme | Smarty |

## Twig templates

Twig templates use the extension `.html.twig`. Controllers keep naming `.tpl` templates (for example `page/checkout/basket.tpl`); under a Twig theme the shop resolves `page/checkout/basket.html.twig`.

Changed templates are recompiled when "check templates" (`blCheckTemplates`) is on, as for Smarty; never in productive mode.

Plain-text mails (`email/plain/**`): Twig drops the newline after every `{% … %}` and `{# … #}` tag, Smarty only after control tags (`[{block}]` lines kept theirs). A block override in a plain mail therefore needs an empty line after its `{% block … %}` and `{% endblock %}` lines to keep the 1.x text layout.

## CMS content

CMS content keeps its Smarty syntax and is parsed by Smarty, also under a Twig theme. Twig syntax in CMS content is not executed.

- `{% ifcontent ident "oxagb" set oCont %}…{% endifcontent %}` loads active content (by `ident` or `oxid`) into `oCont` (default name) and renders the body only if it exists. The content's stored `oxcontent` and `oxtitle` fields are already parsed by Smarty (when they contain `[{`), so print them with `|raw`. The template body itself is never passed to Smarty.
- `{{ include_content('oxemailfooter') }}` replaces Smarty's `[{oxcontent ident="oxemailfooter"}]`: `include_content(ident, field = 'oxcontent', oxid = null)` returns the field of the active content, parsed by Smarty with the template's variables, unescaped. Missing or inactive content returns `''` in productive mode and a "content not found" hint otherwise; with `deactivateSmartyForCmsContent` (or in the demo shop) the field is returned unparsed.

## Autoescaping

- Storefront templates are autoescaped (`html` strategy).
- Templates under `email/plain/` are not escaped.
- Mark HTML explicitly with `|raw`: CMS content, long descriptions, HTML translations.
- `seo_url` and `add_url_parameters` return plain URLs; do not add `|raw`.
- `include_widget`, `include_dynamic`, `insert_new_basket_item`, `include_content`, `mailto`, `script` and `style` return safe markup already.
- `mailto` outputs its `text`, `address` and `extra` arguments raw: do not pass user data to it.
- `translate` output is escaped.

## Modules

- **Page templates:** keep the `templates` mapping in `metadata.php` unchanged and add a `.html.twig` file as sibling of the mapped `.tpl`.
- **Own templates:** put them in `views/twig/` and address them as `@<moduleId>/<path>.html.twig`.
- **Extending theme templates:** add a file at
  `views/twig/extensions/themes/<themeId>/<template>` (or `views/twig/extensions/themes/default/<template>` for any theme) using `{% extends %}` and `{% block %}`.
  Per module the first match wins: the active (child) theme, its parent theme, then `default`.
  Extensions of all active modules are chained in module activation order. Extended layouts and included partials (`{% include %}`, `include()`, `include_dynamic`) go through the chain as well.
- Smarty `blocks` apply to Smarty themes only.
- Not supported yet: a sorting configuration (order is activation order; tracked as a follow-up), `embed`, and dynamic (non-constant) names in `extends`/`include`.
- An extension file added to an already active module needs a template cache clear (`oe:cache:clear`).

## O3-Theme 2.x for module authors

O3-Theme 2.x (Twig) keeps the 1.x block names, with these contract changes (details: the theme's `CHANGELOG.md`, section 2.0.0):

- **New block `content`.** Pages `{% extends "layout/page.html.twig" %}` and fill `content` (inside `content_main`) instead of `[{capture append="oxidBlock_content"}]`. It is the only block without a 1.x counterpart. To change a page's main area, extend `content` (or the page's own blocks); variables the layout reads (`blHideBreadcrumb`) are set at the page's top level.
- **Detail tabs (`page/details/inc/tabs.html.twig`).** Each `details_tabs_*` block prints its nav link when `tabPart == 'nav'` and its pane when `tabPart == 'content'`; the template renders every tab block twice (nav first, then content if the nav part printed anything). One block yields at most one tab, and all output and side effects of a tab block (includes, widgets, `script()`) must be gated by `tabPart`. Add a tab by overriding one of the empty tab blocks the same way.
- **Popup pages keep the 1.x shape.** `page/account/forgotpwd`, `page/account/register`, `page/account/register_success`, `page/info/content_plain` and `page/privatesales/login` render in `layout/popup`, which has no `content` block: they still `capture append="oxidBlock_content"` and include the layout, as do `page/details/details` and `page/privatesales/invite`. Extend their own blocks, not `content`.
- Blocks that 1.x defined twice with different bodies are defined once and rendered again with `{{ block('…') }}`; an override applies to every place, as in 1.x (list in the theme CHANGELOG).

## Admin warnings

While a Twig theme is active (and on theme activation), the admin lists active modules that have storefront Smarty output but no Twig templates (theme page and admin home; logged once on activation). Activation is not blocked.

## Adopted code

The engine is adopted from OXID twig-component v1.1.0 (GPL-3.0) and ported to Twig 3; the template chain is ported from twig-component v2.0.1. Code lives in `source/Internal/Framework/Twig/`; see the file headers and `CHANGELOG.md`.
