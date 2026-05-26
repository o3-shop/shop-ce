# IncenteevScriptHandlerWrapper — fix Composer warning on --no-dev install

**Issue:** o3-shop/o3-shop#157  
**Date:** 2026-05-21

## Problem

`composer.json` scripts reference `Incenteev\ParameterHandler\ScriptHandler::buildParameters` in both `post-install-cmd` and `post-update-cmd`. The package is declared in `require-dev`. On `composer install --no-dev` (production deployments), Composer cannot autoload the class and emits a warning, even though the install succeeds.

## Solution

### 1. Wrapper class — `source/Core/IncenteevScriptHandlerWrapper.php`

New class `OxidEsales\EshopCommunity\Core\IncenteevScriptHandlerWrapper` following the pattern of `ShopVersionGenerator`:

- Static `buildParameters(Event $event): void` method
- Guards with `class_exists(\Incenteev\ParameterHandler\ScriptHandler::class)`
- If class is absent (prod install): silently returns — no warning, no exception
- If class is present (dev install): delegates to `Incenteev\ParameterHandler\ScriptHandler::buildParameters($event)`

### 2. `composer.json` script update

Replace both `post-install-cmd` and `post-update-cmd` entries:

```
- "Incenteev\\ParameterHandler\\ScriptHandler::buildParameters"
+ "OxidEsales\\EshopCommunity\\Core\\IncenteevScriptHandlerWrapper::buildParameters"
```

No other changes to `composer.json`.

## Testing

`tests/Unit/Core/IncenteevScriptHandlerWrapperTest.php`:

- Verifies `buildParameters` exists and is callable as a static method
- Verifies it does not throw when Incenteev's class is present (integration-style assertion)

Note: the "class absent" branch cannot be exercised in a standard PHPUnit run because the class is loaded. The guard is functionally verified by production `--no-dev` installs.

## Out of scope

- Moving `incenteev/composer-parameter-handler` to `require` (wrong — it's a dev tool)
- Removing the script from `post-install-cmd` (would break dev onboarding)
