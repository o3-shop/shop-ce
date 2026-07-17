---
name: ci-lockless-composer-vendor-cache
description: CI has no committed composer.lock — never cache vendor/; a stale vendor cache breaks tests when a dep releases a new version
type: reference
---

# CI resolves deps lockless — do NOT cache `vendor/`

`composer.lock` is **gitignored** (`.gitignore`), so the `Code Quality` workflow
(`.github/workflows/code-quality.yml`) runs `composer install` with no lock →
composer "Updates dependencies to latest" on every run. This is intentional: CI
tests against the newest compatible deps.

## The trap (cost a green build on 2026-07-17)

The cache step keyed `vendor/` on `hashFiles('composer.lock')`. Because the lock
file doesn't exist at cache-restore time, `hashFiles` returns `''`, so the key is
a **constant** `Linux-php-X.Y-` (made worse by the `restore-keys` prefix). Result:
CI restores a **frozen `vendor/`** forever. A lockless `composer install` then
no-ops (`0 installs, 0 updates, 1 removal`) because packages already exist — so a
stale `o3-shop/testing-library` persisted, its `bootstrap.php` wasn't where PHPUnit
expected, PHPUnit couldn't bootstrap, no `coverage.xml` was written, and the
`Enforce Coverage Threshold` gate failed with `clover report not readable`.
The real failure is 4 steps upstream of where CI goes red.

Commit `cee7542` tried "fix cache key" by changing `**/composer.lock` →
`composer.lock` — didn't help, the file still isn't there.

## Rule

Caching `vendor/` is **fundamentally incompatible** with lockless "install latest":
no key from a committed file can invalidate it when a *transitive* dep releases a
new version (`composer.json` constraints like `^v1.2.0` never change). Cache only
Composer's **download cache** (`~/.cache/composer`, keyed on `composer.json`) and
let `vendor/` rebuild fresh each run — correct, self-healing, still fast.

If CI is ever made reproducible instead, commit `composer.lock` AND switch back to
`composer install --no-... ` from the lock; only then may `vendor/` be cached (keyed
on the committed lock hash).

## Debugging tip

When a CI test job dies with a missing vendor file but "Install Dependencies" is
green, check the install log for `0 installs ... N removal` — that means the cache
served a stale `vendor/` and composer didn't rebuild it.
