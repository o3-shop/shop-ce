---
name: Known Pitfalls
description: Bugs and non-obvious mistakes already encountered in this codebase
type: feedback
---

## Bot/Guest Request Handling
- `UtilsComponent::toCompareList()` (and similar utils methods) can crash on bot requests where session/user context is not fully initialised. Always guard with a user/session existence check before accessing user-dependent data.

## Article List Checks
- `Article::isInList()` must check both wish list and notice list independently. A missing check on one list caused a bug (fixed in eb4c3c8).

## Directory Creation
- Do not use ad-hoc `mkdir()` calls scattered through setup code. Use the centralised safe helper introduced in eb4c3c8. It handles race conditions and permission errors gracefully.

## php-cs-fixer Cache
- `.php-cs-fixer.cache` is gitignored but speeds up repeated runs significantly. If fixer seems to miss files, delete the cache and re-run.

## String-renames break paired tests silently
- When you change a user-facing string literal in production code (error messages, log messages, "wiring pending" notices, etc.), grep the test suite for any `assertStringContainsString` / `assertSame` that still asserts on the old value. cs-fixer and static analysis don't catch this — only CI does. Common after section/task renumbers, version-string edits, copy-paste fixups.

## Audit release-eligible repos from the dep walk, not a manual list
- The o3-shop release graph has 22 release-eligible repos, not the 17 visible in `o3-shop/composer.json`'s direct `require` / `require-dev`. Five (`smarty`, `shop-doctrine-migration-wrapper`, `shop-db-views-generator`, `shop-demodata-installer`, `php-selenium`) only show up as transitive deps of `shop-ce` or `testing-library`. A manual audit will miss them. The authoritative source is `bin/release --dry-run` itself — its Step 2 walk emits every `walking o3-shop/<repo>@<branch>` line that's a release candidate. When doing org-wide work that needs to cover "all release-eligible repos" (branch protection, default-branch normalization, etc.), drive the list from a dry-run's walk output. (Discovered during the v1.6.1-RC1 dry-run; §14 had to be extended to cover the 5 missing repos.)
