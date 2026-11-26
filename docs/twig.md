# Twig templates in O3-Shop 2.0

O3-Shop 2.0 ships Twig 3 next to Smarty. The active storefront theme decides which engine renders the shop.

## Upgrade note (read before updating)

To keep a Smarty storefront, pin `o3-shop/o3-theme` to `^1` in your project's `composer.json` *before* updating to O3-Shop 2.0; otherwise O3-Theme is updated to its Twig version.

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

## Autoescaping

- Storefront templates are autoescaped (`html` strategy).
- Templates under `email/plain/` are not escaped.
- Mark HTML explicitly with `|raw`: CMS content, long descriptions, HTML translations.
- `seo_url` and `add_url_parameters` return plain URLs; do not add `|raw`.
- `include_widget`, `include_dynamic`, `insert_new_basket_item`, `mailto`, `script` and `style` return safe markup already.
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

## Admin warnings

While a Twig theme is active (and on theme activation), the admin lists active modules that have storefront Smarty output but no Twig templates (theme page and admin home; logged once on activation). Activation is not blocked.

## Adopted code

The engine is adopted from OXID twig-component v1.1.0 (GPL-3.0) and ported to Twig 3; the template chain is ported from twig-component v2.0.1. Code lives in `source/Internal/Framework/Twig/`; see the file headers and `CHANGELOG.md`.
