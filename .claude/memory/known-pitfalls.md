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

## Use shop-ce's bundled composer (`vendor/bin/composer`), not PATH composer, for release tooling
- The host's PATH composer (`/opt/homebrew/bin/composer` etc.) might be 2.7+ or 2.9+ — versions with aggressive resolver-time advisory blocks. Production o3-shop installs use composer 2.2.x (the version bundled via `o3-shop/shop-composer-plugin`'s transitive `composer/composer ^1.0 || ^2.0` constraint, which resolves to 2.2.27). For pre-flight gates and any release-time composer calls, invoke `<shop-ce>/vendor/bin/composer` directly to match production behavior and avoid version-skew. `ReleaseCommand::resolveBundledComposer()` does this automatically. Discovered during the v1.6.1-RC1 cut when host composer 2.9 rejected `--no-audit` (renamed to `--no-security-blocking`) and blocked the gate; `bin/release` now uses the bundled binary via `realpath(__DIR__ . '/../../../../vendor/bin/composer')`.

## composer audit-flag rename across versions
- `composer install --no-audit` exists in 2.7-2.8 only; 2.6 lacks the audit feature entirely (and rejects the flag), and 2.9+ replaces it with `--no-security-blocking`. Don't unconditionally append audit-skip flags — bundled composer 2.2.x has no audit at all, so passing any audit-skip flag fails. Current behavior: gate uses bundled 2.2.x, no audit happens, no flag needed. If the bundle is ever upgraded past 2.6, the gate will need version-aware logic.

## Packagist sync delay after publishing GitHub releases
- Even when `gh release create` and `git push <tag>` both succeed, packagist's indexing pipeline can take ~5–60 minutes to surface the new tags in `https://repo.packagist.org/p2/<package>.json`. `composer install` against a fresh `o3-shop v1.6.1-RC1` aborted because `tinymce-editor v1.0.1` and `smarty v2.6.35` weren't yet on packagist while the other 20 cut packages had already synced. Manual fix: log into packagist.org → navigate to the package → click "Update". Auto-resolves within an hour. Workflow expectation: after `bin/release`, allow a sync window before declaring "release done."

## `composer.lock` semantics — gitignored vs committed
- `shop-ce/composer.lock` is **gitignored** (this repo's convention for libraries) — the lock that exists locally is per-developer dev-environment state, not a release artifact. The release-tooling `ComposerInstallGate` should treat gitignored locks as "skip the lock-validation gate" since the lock isn't shipped to consumers. Currently the gate skips when the lock is _absent_ (§16.6) — correct enough for fresh checkouts but doesn't catch the maintainer-side stale-local-lock case. If pre-flight ever aborts on a "your lock is stale" diagnostic on shop-ce specifically, the practical fix is `rm composer.lock && composer install` to regenerate; the gate will then skip on the absent-lock state. (After the v1.6.1-RC1 cut new tags exist with loosened constraints, fresh `composer install` regenerates a clean lock.)

## Use `dev-*` in peer-constraint pins, not `dev-<specific-branch-alias>`
- Composer reports the root package's version from a few sources, in priority order: a literal `version` field, a matching `branch-alias` for the current branch, the `COMPOSER_ROOT_VERSION` env var, or as a fallback `dev-{branchname}` (or `dev-master`/`dev-main`). Libraries that depend on shop-ce as a peer (`shop-composer-plugin`, `testing-library`, `codeception-modules`) and reference shop-ce's dev branch with `dev-dev-b-1.x` style aliases break every time shop-ce branches off a new release line — the alias goes stale immediately. **Always use `dev-*`** (the wildcard for "any dev branch") in peer-constraint pins. That accepts every git-checkout-time root version composer might report — `dev-main`, `dev-b-1.6`, `dev-feat-X`, etc. — without any maintenance per release line. Loosening to `^1.2 || dev-*` is one-time work that benefits every future release.

## Pipeline-masking: `$?` after a pipe is the LAST command's exit, not the first
- `./docker.sh test-all | tee log` then `echo $?` always reports 0 (tee succeeded), even when the inner `docker.sh test-all` exited 127 or 139. Use `${PIPESTATUS[0]}` or `set -o pipefail` to preserve the inner exit. False-green reports of "test wrapper says success when phpunit failed" almost always trace to this (filed against shop-ce as o3-shop/o3-shop#127; couldn't reproduce — the chain is correct, the trap was the report's `echo $?` reading the wrong link in the pipeline). Sentinel script `bin/check-exit-propagation.sh` asserts the chain end-to-end including real SIGSEGV.

## Don't ship cross-package upgrades on the same maintenance branch
- `shop-doctrine-migration-wrapper`'s `b-1.6` was carrying in-progress migrations 3.x / dbal 3.x work that anticipated shop-ce's PR #101. shop-ce v1.6.x still ships dbal 2.x. Cutting `wrapper v1.0.3` from `b-1.6` HEAD while `shop-ce v1.6.1-RC1` requires dbal 2.x produced an unsatisfiable consumer install. Resolution: preserve the dbal-3 work on a separate `b-2.0.0` branch (matches the eventual major bump when shop-ce switches to dbal 3.x), reset `b-1.6` to v1.0.2 + the §1.5 archive.exclude commit. **Pattern:** if a lib's release branch is named to match the shop's release line (`b-1.6`), don't accumulate commits there that anticipate a future major shop release. Use a separate branch (`b-2.0.0`, `feature/dbal3`) for that work and merge back to the primary line only when the shop is ready.

## `getenv()` vs `$_ENV` — phpdotenv createImmutable() only populates `$_ENV`
- `Dotenv::createImmutable()` (used in `config.inc.php`) writes values to `$_ENV` and `$_SERVER` but does NOT call `putenv()`. This means `getenv()` will return `false` for any var defined only in `.env`. Always use `$_ENV['VAR'] ?? ''` (not `getenv('VAR')`) when reading shop config env vars — consistent with how `config.inc.php` reads all other `O3SHOP_CONF_*` vars. In tests, set `$_ENV['VAR']` directly (not `putenv()`).
