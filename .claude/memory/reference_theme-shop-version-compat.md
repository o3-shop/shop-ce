---
name: theme-shop-version-compat
description: Themes call shop-core methods via Smarty — guard new ViewConfig methods with method_exists or old shops fatal (SystemComponentException from Base::__call)
type: reference
---

# Theme ↔ shop-core version compatibility

Themes (o3-Theme, wave-theme) require only `o3-shop/shop-ce: ^1.2` in composer, so a new
theme release installs on old shop cores. Any template call to a ViewConfig method that
doesn't exist there throws `SystemComponentException` from `Base::__call()` and kills the
whole page.

**Pattern:** when a theme template calls a recently added core method, wrap it:

```smarty
[{if method_exists($oViewConf, 'getCaptchaWidget')}][{$oViewConf->getCaptchaWidget('contact')}][{/if}]
```

- `method_exists` works in Smarty 2 `[{if}]` (security off outside demo mode) and returns
  false for `__call` magic — exactly what's needed.
- The closing tags must be exact: a malformed `[{/block}]` makes OXID's block prefilter
  skip the pair and Smarty errors with `unrecognized tag 'block'`.
- Applied 2026-07-02 to all 7 `captcha_form` blocks in o3-Theme (main, after v1.5.2 tag)
  and wave-theme (main). `ViewConfig::getCaptchaWidget()` exists only since shop-ce
  v1.6.2-RC2.
- Old-style captcha modules were never broken by the block change itself: block name
  `captcha_form` is unchanged, their block extensions still override it, and
  `CaptchaService::verifyForForm()` returns true when no provider is configured.
