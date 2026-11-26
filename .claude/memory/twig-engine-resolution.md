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
