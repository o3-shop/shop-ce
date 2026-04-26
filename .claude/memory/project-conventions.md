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

## PHP version and type safety
- Supported runtime: **PHP 7.4 – 8.x** (`composer.json` requires `"^7.4 || ^8.0"`). Every line of new code MUST run on the lowest supported version (7.4).
- **All NEW code is strictly typed.** Concretely, every NEW file:
  - Starts with `declare(strict_types=1);`
  - Uses parameter type declarations on every method/function
  - Uses return type declarations on every method/function (use `: void` or `: never`-equivalent docblock when nothing is returned — `never` itself needs PHP 8.1+, so do not use it)
  - Uses typed properties (`private int $foo;`) — supported since 7.4
- **PHP-7.4-only constraints — do NOT use these features in new code:**
  - Union types (`int|string`) — use a docblock `@param int|string` instead
  - `mixed` type — omit the hint and document with `@param` / `@return`
  - Constructor property promotion (`public function __construct(private Foo $foo)`)
  - Named arguments (`foo(name: 'x')`)
  - `match` expression — use `switch`
  - Nullsafe operator (`?->`) — use `isset()` / explicit null checks
  - `readonly` properties / classes
  - Enums — use class constants or sealed pseudo-enum classes
  - First-class callable syntax (`$x = strlen(...)`)
  - Intersection types (`Foo&Bar`)
  - Standalone `true` / `false` / `null` types
- **Allowed in 7.4 (use freely):** scalar types, return types, `?Type` (nullable), `void`, `iterable`, typed properties, arrow functions (`fn() => ...`), null-coalescing assignment (`??=`), spread in arrays (`[...$a]`).
- **Adding code to an existing untyped file (e.g. `Core/Email.php`):** add types to the new method's signature, but do **NOT** insert `declare(strict_types=1)` into a file that doesn't already have it — that flips the strict-types semantics of every existing method in the file and risks breaking callers. A "convert this file to strict types" refactor is a separate task.
- **Inherited / overridden methods:** the signature MUST match the parent. If the parent (e.g. an old OXID core class) uses no types or weaker types, you stay compatible with that. PHP's LSP enforcement will reject incompatible overrides. Do not try to be cleverer than the parent.
- **Why:** keeps the codebase forward-compatible with PHP 8.x while remaining installable on the typical hoster running 7.4. Strict types catch a class of bugs that pure docblocks miss. Captured during scoping of issue #99 / change `add-electronic-revocation` on 2026-04-26.
- **How to apply:** before opening any PR, mentally run two checks: (1) "would this parse on 7.4?" and (2) "does every method in every new file have parameter and return types?". If a feature genuinely needs 8.0+ syntax, the PR description must say so, and the bump goes in `composer.json` as part of that PR.

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

## Branding: prefer "O3" over "OXID" in new prose
- This codebase is **O3-Shop**, a fork of OXID eShop. Its current and forward-looking identity is O3, not OXID.
- In new prose (proposal docs, design docs, ADRs, READMEs, comments, log messages, user-facing UI text, commit messages, PR descriptions): use **"O3"** when describing this project's conventions, patterns, idioms, framework, or behaviour — e.g. "the O3 session challenge token", "an O3 controller", "the O3 translation engine", "every O3 table has an `OXTIMESTAMP` column".
- **Keep "OXID" only when:**
  - referencing a literal namespace, class, method, or path that is still spelled `OxidEsales\…` in the code (e.g. `OxidEsales\EshopCommunity\Core\Email`)
  - referencing the upstream project itself, the upstream history, or the inherited heritage (e.g. "inherited from upstream OXID", "OXID-era convention we keep")
  - describing the literal `OX*` SQL column-name prefix (the prefix is `OX`, not "OXID-the-brand")
- **Why:** consistent branding signals that O3-Shop has its own identity and conventions; sloppy "OXID" mentions in new prose make the project feel like a thin re-skin and create friction for newcomers who reasonably expect "the framework is called O3". Captured during scoping of issue #99 / change `add-electronic-revocation` on 2026-04-26.
- **How to apply:** when writing new prose, default to "O3". If you reach for "OXID", ask whether you're naming a literal symbol/path or describing project conventions — the former keeps OXID, the latter switches to O3.
