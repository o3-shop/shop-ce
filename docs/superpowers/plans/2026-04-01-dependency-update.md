# Dependency Update Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update o3-shop/shop-ce PHP dependencies to the latest versions compatible with PHP ^7.4 || ^8.0 without breaking module or theme compatibility.

**Architecture:** Fix source code to be compatible with new library APIs before updating constraints, then run a single `composer update` to install all new versions, then verify with tests.

**Tech Stack:** PHP 7.4+, Doctrine DBAL 3.9, Symfony 5.4, Composer 2.9.x, Docker

---

## Files to Modify

| File | Change |
|---|---|
| `docker/Dockerfile` | Bump Composer image tag from `2.2` to `2` (latest stable) |
| `.github/workflows/tests.yml` | Remove pinned `--version=2.2.21`, use latest 2.x |
| `composer.json` | Update all version constraints |
| `source/Core/Base.php:227` | Fix deprecated EventDispatcher `dispatch()` signature |
| `source/Core/Database/Adapter/Doctrine/Database.php` | Replace `fetchAll`, `fetchColumn`, `executeUpdate`, `exec` |
| `source/Core/Database/Adapter/Doctrine/ResultSet.php:109-111` | `Statement::execute()` now returns `Result` |
| `source/Internal/Domain/Review/Dao/ReviewDao.php` | QueryBuilder `execute()->fetchAll()` → `executeQuery()->fetchAllAssociative()` |
| `source/Internal/Domain/Review/Dao/RatingDao.php` | Same |
| `source/Internal/Domain/Review/Dao/ProductRatingDao.php` | QueryBuilder `execute()` → `executeStatement()` / `executeQuery()->fetchAssociative()` |
| `source/Internal/Framework/Module/Setting/SettingDao.php` | QueryBuilder `execute()->fetch()` → `executeQuery()->fetchAssociative()` |
| `source/Internal/Framework/Module/TemplateExtension/TemplateBlockExtensionDao.php` | Same |
| `source/Internal/Framework/DIContainer/Service/ShopStateService.php:96` | `Connection::exec()` → `Connection::executeStatement()` |
| `source/Internal/Framework/Module/Command/ClearCacheCommand.php` | Add `int` return type to `execute()` |
| `source/Internal/Framework/Module/Command/ModuleActivateCommand.php` | Same |
| `source/Internal/Framework/Module/Command/ModuleDeactivateCommand.php` | Same |
| `source/Internal/Framework/Module/Command/InstallModuleConfigurationCommand.php` | Same |
| `source/Internal/Framework/Module/Command/UninstallModuleConfigurationCommand.php` | Same |
| `source/Internal/Framework/Module/Command/ApplyModulesConfigurationCommand.php` | Same |
| `tests/Integration/Internal/Framework/Console/Fixtures/TestCommand.php` | Same |

---

## Task 1: Upgrade Composer Tool in Docker and CI

**Files:**
- Modify: `docker/Dockerfile:67`
- Modify: `.github/workflows/tests.yml:95`

- [ ] **Step 1: Update Dockerfile**

In `docker/Dockerfile`, change line 67:
```dockerfile
# From:
COPY --from=composer:2.2 /usr/bin/composer /usr/local/bin/composer
# To:
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
```

- [ ] **Step 2: Update CI workflow**

In `.github/workflows/tests.yml`, replace the Composer install block (lines 94–97):
```yaml
# From:
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --version=2.2.21
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
# To:
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

- [ ] **Step 3: Verify inside container**

Run: `docker exec o3shop-app composer --version`
Expected: `Composer version 2.9.x ...`

- [ ] **Step 4: Commit**

```bash
git add docker/Dockerfile .github/workflows/tests.yml
git commit -m "chore: upgrade Composer tool from 2.2 to latest 2.x"
```

---

## Task 2: Update Low-Risk Composer Constraints

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Update `require` constraints**

In `composer.json`, change these lines:
```json
"psr/container": "1.0.*",
```
to:
```json
"psr/container": "^1.0 || ^2.0",
```

And change:
```json
"doctrine/collections": "^1.4.0",
```
to:
```json
"doctrine/collections": "^1.4 || ^2.0",
```

- [ ] **Step 2: Update `require-dev` constraints**

Change:
```json
"incenteev/composer-parameter-handler": "~v2.0",
```
to:
```json
"incenteev/composer-parameter-handler": "^2.3",
```

- [ ] **Step 3: Commit**

```bash
git add composer.json
git commit -m "chore: relax psr/container, doctrine/collections, incenteev constraints"
```

---

## Task 3: Fix Symfony Console — Add `int` Return Type to All Commands

In Symfony 5, `Command::execute()` must return an `int`. In Symfony 4.4 it was deprecated to return `null`; in Symfony 5 it is an error.

**Files:**
- Modify: `source/Internal/Framework/Module/Command/ClearCacheCommand.php`
- Modify: `source/Internal/Framework/Module/Command/ModuleActivateCommand.php`
- Modify: `source/Internal/Framework/Module/Command/ModuleDeactivateCommand.php`
- Modify: `source/Internal/Framework/Module/Command/InstallModuleConfigurationCommand.php`
- Modify: `source/Internal/Framework/Module/Command/UninstallModuleConfigurationCommand.php`
- Modify: `source/Internal/Framework/Module/Command/ApplyModulesConfigurationCommand.php`
- Modify: `source/Internal/Framework/Console/AbstractShopAwareCommand.php` (check if it has `execute()`)
- Modify: `tests/Integration/Internal/Framework/Console/Fixtures/TestCommand.php`

- [ ] **Step 1: Fix ClearCacheCommand**

In `source/Internal/Framework/Module/Command/ClearCacheCommand.php`, change:
```php
protected function execute(InputInterface $input, OutputInterface $output)
{
    $output->writeln('Clearing cache...');
    // ... existing body ...
    $output->writeln('Cache cleared successfully.');
}
```
to:
```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $output->writeln('Clearing cache...');
    // ... existing body ...
    $output->writeln('Cache cleared successfully.');
    return self::SUCCESS;
}
```

- [ ] **Step 2: Fix ModuleActivateCommand**

In `source/Internal/Framework/Module/Command/ModuleActivateCommand.php`, find:
```php
protected function execute(InputInterface $input, OutputInterface $output)
```
and change to:
```php
protected function execute(InputInterface $input, OutputInterface $output): int
```
Then ensure the method ends with `return self::SUCCESS;` before the closing brace (check if there is already a `return` — if there is, leave the value but add `: int` to the signature; if it falls off the end, add `return self::SUCCESS;`).

- [ ] **Step 3: Fix remaining 4 command files**

Apply the same pattern to each of these files — add `: int` to the `execute()` signature and add `return self::SUCCESS;` at the end if no return statement exists:
- `source/Internal/Framework/Module/Command/ModuleDeactivateCommand.php`
- `source/Internal/Framework/Module/Command/InstallModuleConfigurationCommand.php`
- `source/Internal/Framework/Module/Command/UninstallModuleConfigurationCommand.php`
- `source/Internal/Framework/Module/Command/ApplyModulesConfigurationCommand.php`

- [ ] **Step 4: Fix test fixture**

In `tests/Integration/Internal/Framework/Console/Fixtures/TestCommand.php`, apply the same fix.

- [ ] **Step 5: Commit**

```bash
git add source/Internal/Framework/Module/Command/ tests/Integration/Internal/Framework/Console/Fixtures/TestCommand.php
git commit -m "fix: add int return type to Console Command::execute() for Symfony 5 compatibility"
```

---

## Task 4: Fix EventDispatcher Dispatch Signature

Symfony 5 removed the old `dispatch($eventName, $event)` (string-first) form. The new form is `dispatch($event, $eventName = null)`.

**Files:**
- Modify: `source/Core/Base.php:223-228`

- [ ] **Step 1: Fix the dispatch call**

In `source/Core/Base.php`, change:
```php
public function dispatchEvent(\Symfony\Component\EventDispatcher\Event $event)
{
    $container = \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory::getInstance()->getContainer();
    $dispatcher = $container->get(EventDispatcherInterface::class);
    return $dispatcher->dispatch($event::NAME, $event);
}
```
to:
```php
public function dispatchEvent(\Symfony\Component\EventDispatcher\Event $event)
{
    $container = \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory::getInstance()->getContainer();
    $dispatcher = $container->get(EventDispatcherInterface::class);
    return $dispatcher->dispatch($event, $event::NAME);
}
```

- [ ] **Step 2: Commit**

```bash
git add source/Core/Base.php
git commit -m "fix: update EventDispatcher dispatch() to Symfony 5 signature (event-first)"
```

---

## Task 5: Fix DBAL — Core Database Adapter

Doctrine DBAL 3 removed several methods from `Connection`. Key changes:
- `Connection::fetchAll($sql, $params)` → `Connection::fetchAllAssociative($sql, $params)`
- `Connection::fetchColumn($sql, $params)` → `Connection::fetchOne($sql, $params)`
- `Connection::executeUpdate($sql, ...)` → `Connection::executeStatement($sql, ...)`
- `Connection::exec($sql)` → `Connection::executeStatement($sql)` (on DBAL Connection objects)
- `$result->fetchAll()` on a `Result` object → `$result->fetchAllAssociative()`

**Files:**
- Modify: `source/Core/Database/Adapter/Doctrine/Database.php`

- [ ] **Step 1: Fix `getOne()` — fetchColumn → fetchOne**

At line 326, change:
```php
return $this->getConnection()->fetchColumn($query, $parameters);
```
to:
```php
return $this->getConnection()->fetchOne($query, $parameters);
```

- [ ] **Step 2: Fix `getCol()` — fetchAll → fetchAllAssociative**

At line 758, change:
```php
$rows = $this->getConnection()->fetchAll($query, $parameters);
```
to:
```php
$rows = $this->getConnection()->fetchAllAssociative($query, $parameters);
```

- [ ] **Step 3: Fix `executeUpdate()` — Connection::executeUpdate → executeStatement**

At line 809, change:
```php
$affectedRows = $this->getConnection()->executeUpdate($query, $parameters, $types);
```
to:
```php
$affectedRows = $this->getConnection()->executeStatement($query, $parameters, $types);
```

- [ ] **Step 4: Fix `getAll()` — Result::fetchAll → fetchAllAssociative**

At line 1050, change:
```php
$result = $statement->fetchAll();
```
to:
```php
$result = $statement->fetchAllAssociative();
```

- [ ] **Step 5: Fix `metaColumns()` — Result::fetchAll → fetchAllAssociative**

At line 1110, change:
```php
$columns = $connection->executeQuery($query)->fetchAll();
```
to:
```php
$columns = $connection->executeQuery($query)->fetchAllAssociative();
```

- [ ] **Step 6: Commit**

```bash
git add source/Core/Database/Adapter/Doctrine/Database.php
git commit -m "fix: migrate DBAL 2 fetch/execute methods to DBAL 3 API in Database adapter"
```

---

## Task 6: Fix DBAL — ResultSet Adapter

In DBAL 3, `Statement::execute()` returns a `Result` object. Fetch methods no longer live on `Statement` itself — they must be called on the returned `Result`.

**Files:**
- Modify: `source/Core/Database/Adapter/Doctrine/ResultSet.php:106-112`

- [ ] **Step 1: Fix fetchAll() to use Result object**

In `source/Core/Database/Adapter/Doctrine/ResultSet.php`, change:
```php
public function fetchAll()
{
    $this->close();
    $this->getStatement()->execute();

    return $this->getStatement()->fetchAll();
}
```
to:
```php
public function fetchAll()
{
    $this->close();
    $result = $this->getStatement()->execute();

    return $result->fetchAllAssociative();
}
```

- [ ] **Step 2: Commit**

```bash
git add source/Core/Database/Adapter/Doctrine/ResultSet.php
git commit -m "fix: use Result object from Statement::execute() for DBAL 3 compatibility"
```

---

## Task 7: Fix DBAL — QueryBuilder in DAO Files

In DBAL 3, `QueryBuilder::execute()` was split:
- For SELECT queries: use `executeQuery()` — returns a `Result`
- For INSERT/UPDATE/DELETE queries: use `executeStatement()` — returns an `int`

The `Result` object methods: `fetchAllAssociative()`, `fetchAssociative()`, `fetchOne()`.

**Files:**
- Modify: `source/Internal/Domain/Review/Dao/ReviewDao.php`
- Modify: `source/Internal/Domain/Review/Dao/RatingDao.php`
- Modify: `source/Internal/Domain/Review/Dao/ProductRatingDao.php`
- Modify: `source/Internal/Framework/Module/Setting/SettingDao.php`
- Modify: `source/Internal/Framework/Module/TemplateExtension/TemplateBlockExtensionDao.php`
- Modify: `source/Application/Model/SeoEncoderCategory.php`
- Modify: `source/Core/UtilsCount.php`

- [ ] **Step 1: Fix ReviewDao**

In `source/Internal/Domain/Review/Dao/ReviewDao.php`:

Change line 72:
```php
return $this->mapReviews($queryBuilder->execute()->fetchAll());
```
to:
```php
return $this->mapReviews($queryBuilder->executeQuery()->fetchAllAssociative());
```

Change line 85 (delete query):
```php
->execute();
```
to:
```php
->executeStatement();
```

- [ ] **Step 2: Fix RatingDao**

In `source/Internal/Domain/Review/Dao/RatingDao.php`:

Change line 72:
```php
return $this->mapRatings($queryBuilder->execute()->fetchAll());
```
to:
```php
return $this->mapRatings($queryBuilder->executeQuery()->fetchAllAssociative());
```

Change line 85 (delete query):
```php
->execute();
```
to:
```php
->executeStatement();
```

Change line 111:
```php
return $this->mapRatings($queryBuilder->execute()->fetchAll());
```
to:
```php
return $this->mapRatings($queryBuilder->executeQuery()->fetchAllAssociative());
```

- [ ] **Step 3: Fix ProductRatingDao**

In `source/Internal/Domain/Review/Dao/ProductRatingDao.php`:

Change line 68 (UPDATE query):
```php
$queryBuilder->execute();
```
to:
```php
$queryBuilder->executeStatement();
```

Change line 95 (SELECT query):
```php
$queryBuilder->execute()->fetch()
```
to:
```php
$queryBuilder->executeQuery()->fetchAssociative()
```

- [ ] **Step 4: Fix SettingDao**

In `source/Internal/Framework/Module/Setting/SettingDao.php`:

Change line 266:
```php
$result = $queryBuilder->execute()->fetch();
```
to:
```php
$result = $queryBuilder->executeQuery()->fetchAssociative();
```

Change line 295:
```php
$result = $queryBuilder->execute()->fetch();
```
to:
```php
$result = $queryBuilder->executeQuery()->fetchAssociative();
```

- [ ] **Step 5: Fix TemplateBlockExtensionDao**

In `source/Internal/Framework/Module/TemplateExtension/TemplateBlockExtensionDao.php`:

Change line 114:
```php
return (bool) $queryBuilder->execute()->fetchOne();
```
to:
```php
return (bool) $queryBuilder->executeQuery()->fetchOne();
```

Change line 136:
```php
$blocksData = $queryBuilder->execute()->fetchAll();
```
to:
```php
$blocksData = $queryBuilder->executeQuery()->fetchAllAssociative();
```

- [ ] **Step 6: Fix SeoEncoderCategory**

In `source/Application/Model/SeoEncoderCategory.php` at line 346, change any call of the form:
```php
$result->fetchAll()
```
to:
```php
$result->fetchAllAssociative()
```

- [ ] **Step 7: Fix UtilsCount**

In `source/Core/UtilsCount.php` at line 251, change any call of the form:
```php
->fetchAll()
```
to:
```php
->fetchAllAssociative()
```
(If the call is on a DBAL `Connection` directly, use `fetchAllAssociative($sql, $params)` instead of `fetchAll($sql, $params)`.)

- [ ] **Step 8: Commit**

```bash
git add source/Internal/Domain/Review/Dao/ source/Internal/Framework/Module/Setting/SettingDao.php source/Internal/Framework/Module/TemplateExtension/TemplateBlockExtensionDao.php source/Application/Model/SeoEncoderCategory.php source/Core/UtilsCount.php
git commit -m "fix: migrate QueryBuilder execute() to executeQuery()/executeStatement() for DBAL 3"
```

---

## Task 8: Fix Remaining DBAL Usage — ShopStateService

**Files:**
- Modify: `source/Internal/Framework/DIContainer/Service/ShopStateService.php:96`

- [ ] **Step 1: Fix Connection::exec → executeStatement**

In `source/Internal/Framework/DIContainer/Service/ShopStateService.php`, change:
```php
$connection->exec(
    'SELECT 1 FROM ' . $this->basicContext->getConfigTableName() . ' LIMIT 1'
);
```
to:
```php
$connection->executeStatement(
    'SELECT 1 FROM ' . $this->basicContext->getConfigTableName() . ' LIMIT 1'
);
```

- [ ] **Step 2: Commit**

```bash
git add source/Internal/Framework/DIContainer/Service/ShopStateService.php
git commit -m "fix: replace Connection::exec() with executeStatement() for DBAL 3 compatibility"
```

---

## Task 9: Update All Symfony and DBAL Constraints + Run composer update

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Update `require` Symfony and DBAL constraints**

In `composer.json`, update the `require` section to:
```json
"doctrine/dbal": "^3.9",
"symfony/event-dispatcher": "^5.4",
"symfony/dependency-injection": "^5.4",
"symfony/config": "^5.4",
"symfony/yaml": "^5.4",
"symfony/expression-language": "^5.4",
"symfony/lock": "^5.4",
"symfony/console": "^5.4",
"symfony/finder": "^5.4",
"symfony/filesystem": "^5.4",
```

- [ ] **Step 2: Run composer update inside the container**

Run: `docker exec o3shop-app composer update --prefer-dist --no-scripts 2>&1`

Expected: packages are updated, lock file regenerated, no unresolvable conflicts.

If conflicts appear, read the error carefully — it will name a specific package that also needs its constraint updated. Check if the conflicting package is a dependency of an o3-shop/* package that may also need updating.

- [ ] **Step 3: Run composer install to execute scripts**

Run: `docker exec o3shop-app composer install 2>&1`

Expected: scripts run successfully, autoload rebuilt, no fatal errors.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore: update Symfony to ^5.4 and Doctrine DBAL to ^3.9"
```

---

## Task 10: Fix Test Files for DBAL 3

**Files:**
- Modify: `tests/Integration/Core/Database/Adapter/DatabaseInterfaceImplementationTest.php`
- Modify: `tests/Integration/Core/Database/Adapter/Doctrine/DatabaseTest.php`
- Modify: `tests/Integration/Core/Database/Adapter/DatabaseInterfaceImplementationBaseTest.php`
- Modify: `tests/Integration/Core/Database/Adapter/Doctrine/ResultSetTest.php`
- Modify: `tests/Integration/Core/DatabaseTest.php`

- [ ] **Step 1: Find all `fetchAll()` in test files**

Run: `docker exec o3shop-app grep -rn "->fetchAll()" tests/ 2>&1`

For each occurrence, determine context:
- If called on a DBAL `Result` or `Statement` object: replace with `->fetchAllAssociative()`
- If called on a shop's own `ResultSet` class (OxidEsales adapter): leave unchanged (the adapter's public method is still called `fetchAll()`)

- [ ] **Step 2: Find all `->exec(` in test files**

Run: `docker exec o3shop-app grep -rn "->exec(" tests/ 2>&1`

Replace DBAL `Connection::exec(` calls with `Connection::executeStatement(`. Skip raw PDO `$pdo->exec()` calls — those are unaffected.

- [ ] **Step 3: Apply fixes and verify**

After editing each test file, run the specific test class to confirm it passes:

```bash
docker exec o3shop-app vendor/bin/phpunit tests/Integration/Core/Database/Adapter/DatabaseInterfaceImplementationTest.php 2>&1 | tail -20
docker exec o3shop-app vendor/bin/phpunit tests/Integration/Core/Database/Adapter/Doctrine/DatabaseTest.php 2>&1 | tail -20
docker exec o3shop-app vendor/bin/phpunit tests/Integration/Core/Database/Adapter/Doctrine/ResultSetTest.php 2>&1 | tail -20
```

Expected: tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/
git commit -m "fix: update test files to DBAL 3 Result API"
```

---

## Task 11: Run Full Test Suite and Verify

- [ ] **Step 1: Run unit tests**

```bash
docker exec o3shop-app vendor/bin/phpunit tests/Unit/ 2>&1 | tail -30
```

Expected: all pass (or same failures as before — note any pre-existing failures).

- [ ] **Step 2: Run integration tests**

```bash
docker exec o3shop-app vendor/bin/phpunit tests/Integration/ 2>&1 | tail -30
```

Expected: all pass.

- [ ] **Step 3: Verify console commands work**

```bash
docker exec o3shop-app vendor/bin/oe-console list 2>&1
```

Expected: lists all available commands without errors.

- [ ] **Step 4: Verify DI container compiles**

```bash
docker exec o3shop-app php -r "
require 'vendor/autoload.php';
\$container = \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory::getInstance()->getContainer();
echo 'Container OK' . PHP_EOL;
" 2>&1
```

Expected: `Container OK` with no fatal errors.

- [ ] **Step 5: Final commit with updated composer.lock**

```bash
git add composer.lock
git commit -m "chore: finalize dependency update — Symfony 5.4, DBAL 3.9, Composer 2.9"
```