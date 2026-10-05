---
name: cs-fixer-pollutes-nested-clones
description: ./docker.sh cs-fixer reformats the nested clones (testing-library, themes, demodata) and dirties those separate repos → aborts bin/release pre-flight
type: reference
---

# `./docker.sh cs-fixer` dirties the nested clones → release pre-flight aborts

`./docker.sh cs-fixer` runs php-cs-fixer over the **whole shop-ce tree**, which
includes the nested clones that `./docker.sh start` sets up:

- `testing-library/`
- `source/Application/views/o3-theme/`, `.../wave/`
- `shop-demodata-ce/`

Each of those is its **own git repo**. cs-fixer reformats their PHP files
(e.g. it reformatted 103 files in `testing-library/`), which shows up as
uncommitted changes **inside those repos**. `bin/release`'s `WorkingTreeGate`
then ABORTs the cut: *"working tree at .../testing-library has uncommitted
changes."* Test runs also leave untracked `oxc_*.txt` OXID cache dumps in
`testing-library/`.

**Before a release cut**, make sure the nested clones are clean:

```
git -C testing-library checkout -- . && git -C testing-library clean -fd
rm -f source/Application/views/o3-theme/out/.DS_Store
```

(testing-library / themes / demodata are gitignored snapshots in shop-ce —
discarding cs-fixer churn + cache dumps in them is safe; they are not part of
the release content.)

**It also breaks the TEST HARNESS, not just `bin/release` (#213, 2026-06-25):**
cs-fixer's churn in `testing-library/` can relocate that clone's files (the
working tree ended up with root `bootstrap.php`/`composer.json` **deleted** and
copies under an untracked `source/`). `vendor/o3-shop/testing-library` is a
**symlink** to that clone, and both `./docker.sh test --fast`
(`--bootstrap vendor/o3-shop/testing-library/bootstrap.php`) and
`tests/phpunit.xml` (`bootstrap="bootstrap.php"` → `tests/bootstrap.php`)
resolve through it. When it's dirtied you get:

- `Cannot open file "vendor/o3-shop/testing-library/bootstrap.php"`, or
- full-bootstrap tests (anything using `UnitTestCase` + shop `Registry`) crash
  silently at **exit 255** with
  `Undefined constant "OxidEsales\TestingLibrary\TEST_LIBRARY_HELPERS_PATH"`.

Pure-unit tests that don't need the shop bootstrap may still pass, masking it.
**Recovery (same as the release pre-flight clean — safe, it's a gitignored
snapshot):** `git -C testing-library checkout -- . && git -C testing-library clean -fd`.
**Order of ops:** run cs-fixer on your shop-ce changes, then clean the clones,
THEN run the test suite — otherwise the suite runs against a broken bootstrap.

**Root fix (follow-up):** scope `.php-cs-fixer.dist.php`'s Finder to exclude
the nested clone paths so cs-fixer never touches them. See
[[release-tooling-intermediate-node-retag-gap]] for the fold-out release work
(o3-shop/o3-shop#169) where this first bit.
