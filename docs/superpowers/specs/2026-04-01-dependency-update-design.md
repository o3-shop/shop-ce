# Dependency Update Design

**Date:** 2026-04-01  
**Branch:** req/update-dependencies  
**Scope:** Update o3-shop/shop-ce direct dependencies to latest versions compatible with PHP ^7.4 || ^8.0, without breaking module or theme compatibility.

---

## Goals

- Update library dependencies as far as PHP 7.4 compatibility allows
- Preserve the shop's public PHP API (classes, interfaces, methods) used by modules
- Preserve Smarty template variables, blocks, and hooks used by themes
- Do NOT drop PHP 7.4 or 8.0 support

## Non-Goals

- PHP version bump (deferred)
- Monolog 3.x, PHPMailer 7.x, PHP_CodeSniffer 4.x (blocked on PHP 8.1+)
- smarty/smarty migration (abandoned package, separate effort)

---

## Target Versions

| Package | From | To | Risk |
|---|---|---|---|
| `symfony/event-dispatcher` | `^3.4` | `^5.4` | Medium — deprecated APIs removed |
| `symfony/dependency-injection` | `^3.4.26` | `^5.4` | Medium — deprecated APIs removed |
| `symfony/config` | `~3.3 \|\| ~4.0` | `^5.4` | Low |
| `symfony/yaml` | `~3.4 \|\| ~4.0` | `^5.4` | Low |
| `symfony/expression-language` | `^4.4.30` | `^5.4` | Low |
| `symfony/lock` | `^3.4` | `^5.4` | Low |
| `symfony/console` | `^v3.4.15` | `^5.4` | Medium — Command/IO API changes |
| `symfony/finder` | `^3.4` | `^5.4` | Low |
| `symfony/filesystem` | `^4.4.17` | `^5.4` | Low |
| `doctrine/dbal` | `<=2.12.1` | `^3.9` | High — fetch* API removed, type system changed |
| `doctrine/collections` | `^1.4.0` | `^2.0` | Low — minor BC breaks |
| `psr/container` | `1.0.*` | `^1.1 \|\| ^2.0` | Low |
| `monolog/monolog` | `^v2.10` | unchanged (`^2.10`) | n/a — 3.x needs PHP 8.1 |
| `phpmailer/phpmailer` | `^v6.5.0` | unchanged (`^6.5`) | n/a — 7.x needs PHP 8.0+ |
| `incenteev/composer-parameter-handler` | `~v2.0` | `^2.3` | Low |
| `squizlabs/php_codesniffer` | `^3.5.4` | unchanged (`^3.x`) | n/a — 4.x needs PHP 8.1 |
| **Composer tool** | `2.2.26` | latest `2.x` | Low — update binary in container/CI |

---

## Compatibility Constraints

### Module Compatibility
- All public classes in `source/` (Core, Application, Internal) must keep their existing method signatures
- Public interfaces must not add required methods
- Extension points (hooks, events, DI tags) must remain functional

### Theme Compatibility
- Smarty template variables assigned in controllers must remain unchanged
- Template block names and includes must remain unchanged
- No removals from `source/Application/views/`

---

## Key Technical Challenges

### 1. Symfony 3.4 → 5.4

Symfony removed deprecated APIs between 3.4→4.0 and 4.4→5.0. Areas to audit:

- **EventDispatcher**: `addListener`/`dispatch` signature changed — `dispatch($event, $eventName)` became `dispatch($event)` with typed events in 5.x; legacy string-first dispatch was dropped
- **DependencyInjection**: `ContainerBuilder` service definition API, `get()` return types, tagged services iteration
- **Console**: `Command::execute()` return type (must return int), `InputInterface`/`OutputInterface` usage
- **Config**: `FileLocator`, `ConfigCache` usage
- **Lock**: Store interface changed between 3.4 and 5.4

### 2. Doctrine DBAL 2.12 → 3.9

DBAL 3 is a major rewrite. Breaking changes:

- `Connection::fetchAll()`, `fetchAssoc()`, `fetchArray()`, `fetchColumn()` removed → replaced by `fetchAllAssociative()`, `fetchOne()`, `fetchAllNumeric()`, etc.
- `Connection::query()` removed → use `executeQuery()`
- `Connection::exec()` removed → use `executeStatement()`
- `Statement` interface changed — `execute()` no longer accepts parameters
- `QueryBuilder::execute()` split into `executeQuery()` / `executeStatement()`
- Platform detection and type system changes

### 3. Doctrine Collections 1 → 2

- `Selectable` interface tweaked
- Minor type-hint additions (mostly additive, low breakage risk)

---

## Approach

1. Update `composer.json` constraints
2. Run `composer update` in container to resolve new lockfile
3. Fix compilation errors (type errors, missing methods) systematically by package
4. Run existing test suite to catch regressions
5. Verify module extension points still function

---

## Composer Tool Upgrade

The container currently runs Composer 2.2.26 (LTS branch). We'll bump to the latest Composer 2.x (`composer self-update --2`). This also means updating any Dockerfile or CI configuration that pins the Composer version.

A `composer-runtime-api` constraint will be added to `composer.json` to formally document the minimum Composer version requirement.

---

## Out of Scope

- Rewriting module or theme code (that is the responsibility of module/theme authors after this update)
- Adding new features while fixing compatibility
- Refactoring shop code beyond what is required for compatibility