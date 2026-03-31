---
name: Project Conventions
description: PSR-12, Doctrine DBAL patterns, namespace rules, Smarty, branch naming
type: reference
---

## Code Style
- PSR-12, enforced by PHP-CS-Fixer with `.php-cs-fixer.dist.php`
- Single quotes for strings (unless interpolation needed)
- Array short syntax `[]`, trailing commas in multi-line arrays
- Imports ordered alphabetically, no unused imports

## Database
- Doctrine DBAL ≤2.12 — use `QueryBuilder`, never raw PDO or string-concatenated SQL
- Access DB via `\OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactory`
- Test DB name: `o3shop-test` (switched automatically by `run-tests.sh`)

## Namespaces
- Application code: `OxidEsales\EshopCommunity\` → `source/`
- Tests: `OxidEsales\EshopCommunity\Tests\` → `tests/`
- Modules must NOT use classes from `source/Internal/` (blacklisted)

## Templates
- Smarty ~2.6
- Template files: `source/Application/views/{admin,wave}/`
- Cache: `source/tmp/smarty/` — clear when templates misbehave

## Branches
- Main branch: `b-1.5`
- Feature branches: `NNN-short-description` (NNN = GitHub issue number)
- Commit prefix: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`
