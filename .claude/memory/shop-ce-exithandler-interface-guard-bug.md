---
name: shop-ce-exithandler-interface-guard-bug
description: bootstrap.php guards ExitHandler registration with class_exists() on an INTERFACE name (always false) → fresh-install Setup redirect dies in a DB-error loop
type: reference
---

# `class_exists()` on an interface breaks fresh-install Setup (shop-ce, PR #156)

Symptom: a fresh `composer create-project o3-shop/o3-shop` of **v1.6.1-RC8**
(and any shop-ce carrying PR #156 / issue #166) shows the **Maintenance page**
with an endless `DatabaseNotConfiguredException` loop, instead of redirecting to
**/Setup** (RC3 worked). Unrelated to the metapackage fold-out (#169) — RC8 just
ships the newer shop-ce.

Root cause in `source/bootstrap.php`:

```php
if (class_exists(\OxidEsales\Eshop\Core\ExitHandlerInterface::class)) {   // <-- BUG
    \OxidEsales\Eshop\Core\Registry::set(
        \OxidEsales\Eshop\Core\ExitHandlerInterface::class,
        new \OxidEsales\Eshop\Core\ExitHandler()
    );
}
```

`ExitHandlerInterface` is an **interface**, and `class_exists()` returns **false**
for interfaces (`interface_exists()` is the right check). So the guard is never
true and the `ExitHandler` is never registered.

`source/overridablefunctions.php::redirectIfShopNotConfigured()` (the
fresh-install → Setup redirect) ends with:

```php
\OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Core\ExitHandlerInterface::class)->exit(0, $message);
```

With no registered handler, `Registry::get()` falls through to
`oxNew(ExitHandlerInterface)` → `ModuleChainsGenerator` → `getModuleVarFromDB('aModules')`
→ DB → `DatabaseNotConfiguredException` on a not-yet-configured shop → uncaught →
global `ExceptionHandler::exitApplication()` does the *same* `Registry::get` →
same cascade → infinite loop → Maintenance page. (The global exception-exit path
hits the identical wall, so even non-Setup errors loop on a DB-less shop.)

Fix (one word): `class_exists(` → `interface_exists(` in `source/bootstrap.php`
(or `class_exists($x) || interface_exists($x)`). Verified in a container install:
`Registry::get(ExitHandlerInterface)` then returns the `ExitHandler`, and
`curl /` returns `302 → Setup/index.php` with a clean (empty) log.

Lesson: never use `class_exists()` to probe a name that may be an interface or
trait — use `interface_exists()` / `trait_exists()`, or all three.
