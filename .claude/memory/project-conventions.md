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

## Internationalisation (translation engine)
- **Every** user-facing string MUST go through the translation engine — never hardcode literals (German, English, or otherwise) in templates, controllers, models, or mail templates. This includes button labels, form labels, validation messages, page headings, footer/menu text, admin labels, email subjects and email bodies.
- In Smarty templates use `{oxmultilang ident="IDENT_KEY"}`; in PHP use `Registry::getLang()->translateString('IDENT_KEY', $iLang, $blAdminMode)`.
- Lang files live under `source/Application/views/{wave,admin}/<langcode>/lang.php` (frontend) and `source/Application/translations/<langcode>/lang.php` (core/admin). Ship at minimum `de` and `en` defaults for every new key.
- When a spec, issue, or design quotes a German wording (e.g. "Vertrag widerrufen"), treat it as the default `de` lang-file value, not a hardcoded literal. Define a translation key and put the wording in the lang file.
- **Why:** O3-Shop is a multi-language platform; hardcoded strings break non-DE shops, fail review, and create rework. Reason captured: feedback during the §356a revocation feature scoping (issue #99).
- **How to apply:** Whenever you add or modify any string a user can see, define a translation key first, add it to `de` and `en` lang files, then reference the key from the template/controller/mail. If you catch yourself typing a literal sentence into a template or `->sendMail(...)` call, stop and convert it to a translation key.

## Branches
- Main branch: `b-1.5`
- Feature branches: `NNN-short-description` (NNN = GitHub issue number)
- Commit prefix: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`
