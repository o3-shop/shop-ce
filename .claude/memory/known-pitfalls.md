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
