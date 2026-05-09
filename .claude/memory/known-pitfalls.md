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

## CI's "Code Quality" workflow does not run the unit suite
- Lightweight PRs that touch only ReleaseTooling internals trigger only the `Code Quality` workflow on push (cs-fixer + lint), NOT the matrix `test (7.4|8.0|8.1|8.2)` workflow that runs the full suite. That means a broken test can be merged without anyone noticing. Always run `./docker.sh test-all-coverage` locally before merging anything that touches PHP — don't rely on CI to catch test failures on every branch. (PR #126 merged with `final class LiveExecutor` blocking a child stub class, and only `./docker.sh test-all-coverage` caught it on a follow-up branch.)

## Don't mark a class `final` when tests need to extend it for stubbing
- If a test file uses `extends X` to build a recording stub (test double that overrides specific methods while reusing the rest), declaring `final class X` causes a `Class Y cannot extend final class X` fatal at test-load time. Prefer `class` over `final class` for any service that has a counterpart `RecordingX` / `StubX` in the test suite. The pattern in this repo is consistent: `ReleasePlanner`, `LiveExecutor` are NOT final; their stub doubles in `ReleaseCommandTest` extend them.
