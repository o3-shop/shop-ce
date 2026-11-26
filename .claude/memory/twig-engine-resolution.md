---
name: twig-engine-resolution
description: How the template engine (Twig vs Smarty) is chosen per render call, and where Twig docs/traps live
type: reference
---

- Engine is resolved per render call, never cached per request: admin mode -> Smarty; storefront -> theme `templateEngine` (`theme.php`, default `smarty`, child inherits parent). `Core/Email` turns admin mode off, so e-mails use the storefront engine even from admin controllers.
- `parseThroughSmarty` (CMS content, long descriptions, newsletters) stays Smarty; its HTML needs `|raw` in Twig.
- Controllers keep `.tpl` names; the shop resolves `.html.twig`. Module extension files in `views/twig/extensions/themes/<themeId|default>/`; a new extension file in an active module needs `oe:cache:clear`.
- Code in `source/Internal/Framework/Twig/` is adopted from OXID twig-component v1.1.0 (chain from v2.0.1); never copy from 8.x (proprietary).
- User docs: `docs/twig.md`; upgrade note (pin `o3-shop/o3-theme:^1`) in `CHANGELOG.md`.
