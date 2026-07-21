---
name: ci-lockless-composer-vendor-cache
description: composer.lock is now COMMITTED and pinned to a PHP 7.4 platform floor; never cache vendor/; a committed lock must be resolved for the lowest matrix PHP or install fails
type: reference
---

# Committed composer.lock must be pinned to the lowest matrix PHP (7.4)

`composer.lock` is **committed** (since `d451227`, so Dependabot can see transitive
deps). The `Code Quality` workflow (`.github/workflows/code-quality.yml`) runs a
matrix of PHP `['7.4','8.0','8.1','8.2']` and does `composer install` — which, with a
committed lock, **verifies the locked set against each platform** rather than
re-resolving.

## The trap (broke the `b-2.0` build 2026-07-21)

A single committed lock cannot satisfy a multi-PHP matrix if it was resolved on a
modern PHP: composer greedily picks the newest transitive/dev versions, which drop
old-PHP support (laminas/laminas-code 4.17 → PHP 8.2+, phpspec/prophecy 1.26 → 8.2+,
symfony/string 6.4 + *-contracts 3.7 → 8.1+, behat/gherkin 4.17, doctrine/instantiator
2.0 → 8.1+). Then `composer install` fails **fast** (exit 2, not a hang — even with
`COMPOSER_PROCESS_TIMEOUT: 0`) on the 7.4/8.0/8.1 legs with:
`Your lock file does not contain a compatible set of packages. Please run composer update.`

## Fix / rule

Pin the lock to the **lowest** supported PHP so it installs across the whole matrix:
`composer.json` → `config.platform.php = "7.4.33"`, then regenerate the lock
(`composer update`). With the platform override, composer resolves 7.4-compatible
versions even when run on the container's PHP 8.2. Those older versions still run fine
on 8.1/8.2. Verify with `composer install --dry-run` — the line
"Verifying lock file contents can be installed on current platform." must not error.
Trade-off: Dependabot will keep proposing bumps that break the 7.4 floor (expect noise);
that was the accepted cost of keeping 7.4–8.0 support. See [[console-commands-and-php-floor]].

## Never cache `vendor/`

Cache only Composer's **download cache** (`~/.cache/composer`, keyed on `composer.json`)
and let `vendor/` rebuild each run. A `vendor/` cache keyed on a file that isn't present
at restore time freezes a stale tree (a no-op `install` then serves a stale
`o3-shop/testing-library`, PHPUnit can't find `bootstrap.php`, no `coverage.xml`, the
coverage gate fails 4 steps downstream). If you see `0 installs ... N removal` in the
install log while a later step dies on a missing vendor file, that's the stale-cache tell.
