---
name: Adding a new CORE admin controller (Eshop alias + 3 maps)
description: A new core admin controller needs 3 class-map entries + the unified-namespace regenerated, or the admin menu silently falls back to the shop main page
type: reference
---

Adding a new **core** admin controller (e.g. `Application/Controller/Admin/FooController`, reached via `cl=foo`) requires registering it in **three** maps — otherwise clicking its admin menu entry just lands on the shop main page (OXID can't resolve the `cl` to a class). A unit test that does `oxNew(FooController::class)` will NOT catch this — it bypasses cl-resolution. Add a `class_exists()`-on-the-mapped-class regression test against `ShopControllerMapProvider` instead.

The subtle part: the `OxidEsales\Eshop\…\FooController` **unified-namespace alias is NOT auto-generated** for new core classes. It's produced by the unified-namespace generator, which reads a *committed class-map file*, not the filesystem/composer classmap. So:

1. `source/Core/Autoload/UnifiedNameSpaceClassMap.php` — add:
   ```php
   'OxidEsales\Eshop\Application\Controller\Admin\FooController' => [
       'editionClassName' => \OxidEsales\EshopCommunity\Application\Controller\Admin\FooController::class,
       'isAbstract' => false, 'isInterface' => false, 'isDeprecated' => false,
   ],
   ```
   This is what makes `vendor/bin/oe-eshop-unified_namespace_generator` emit `vendor/o3-shop/shop-unified-namespace-generator/generated/OxidEsales/Eshop/.../FooController.php` (the alias). That generated file is gitignored; CI/fresh installs regenerate it from this map during composer install — so committing this map entry is what matters.
2. `source/Core/Autoload/BackwardsCompatibilityClassMap.php` — add `'foo' => 'OxidEsales\\Eshop\\Application\\Controller\\Admin\\FooController'`.
3. `source/Core/Routing/ShopControllerMapProvider.php` — add `'foo' => \OxidEsales\Eshop\Application\Controller\Admin\FooController::class`.
4. Re-run `vendor/bin/oe-eshop-unified_namespace_generator` locally (composer install does it on CI), then `oe:cache:clear`.

`RevocationConfigController` is the reference (it appears in all three). Hit during #213 (CaptchaConfigController): the admin menu fell back to the main page because only ShopControllerMapProvider had it and the Eshop alias didn't exist. See [[captcha-provider-layer]].
