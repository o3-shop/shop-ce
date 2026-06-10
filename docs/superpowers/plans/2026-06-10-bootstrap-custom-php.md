# `bootstrap.custom.php` Support Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Optionally load `source/bootstrap.custom.php` at the end of the core bootstrap so developers can add local/environment-specific overrides without patching tracked core files.

**Architecture:** Add a guarded `require` at the very end of `source/bootstrap.php` (after all core services are registered). The concrete file is gitignored; a committed `.dist` documents the pattern. A unit test mirrors the load guard in isolation (same style as the existing `BootstrapTmpDirTest`).

**Tech Stack:** PHP 8, PHPUnit 9, O3-Shop bootstrap.

**Spec:** `docs/superpowers/specs/2026-06-10-bootstrap-custom-php-design.md`

---

## File Structure

- **Modify** `source/bootstrap.php` — add guarded `require` of `bootstrap.custom.php` at end of file.
- **Modify** `.gitignore` — ignore `/source/bootstrap.custom.php`.
- **Create** `source/bootstrap.custom.php.dist` — committed documented example (commented-out, no-op when copied verbatim).
- **Create** `tests/Unit/Bootstrap/BootstrapCustomFileTest.php` — covers absent (no-op/no-warning) and present (executed) cases.
- **Modify** `README.md` — add a "Local bootstrap overrides" subsection.

---

### Task 1: Unit test for the custom-bootstrap load guard

**Files:**
- Test: `tests/Unit/Bootstrap/BootstrapCustomFileTest.php`

The test mirrors the exact guard used in `source/bootstrap.php`
(`is_readable($dir . 'bootstrap.custom.php')` + `require`) against a temp
directory, so it is hermetic and does not boot the real `source/` tree — the
same approach as `tests/Unit/Bootstrap/BootstrapTmpDirTest.php`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Bootstrap/BootstrapCustomFileTest.php`:

```php
<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Bootstrap;

use PHPUnit\Framework\TestCase;

/**
 * Tests the optional bootstrap.custom.php load guard added to source/bootstrap.php.
 *
 * Mirrors the exact conditional in source/bootstrap.php in isolation against a
 * temp directory, the same way BootstrapTmpDirTest mirrors the tmp-dir logic.
 *
 * @see source/bootstrap.php
 */
class BootstrapCustomFileTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/o3shop_bootstrap_custom_test_' . uniqid('', true);
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    /**
     * When no bootstrap.custom.php is present, the guard must not fire and must
     * not raise any warning/error.
     */
    public function testAbsentCustomFileIsSkippedWithoutWarning(): void
    {
        $loaded = $this->runCustomBootstrapLoadLogic($this->tempDir, $sideEffect);

        $this->assertFalse($loaded, 'Guard must not fire when bootstrap.custom.php is absent.');
        $this->assertNull($sideEffect, 'No custom file means no side effect.');
    }

    /**
     * When bootstrap.custom.php is present, it must be executed.
     */
    public function testPresentCustomFileIsExecuted(): void
    {
        file_put_contents(
            $this->tempDir . DIRECTORY_SEPARATOR . 'bootstrap.custom.php',
            "<?php \$sideEffect = 'executed';\n"
        );

        $loaded = $this->runCustomBootstrapLoadLogic($this->tempDir, $sideEffect);

        $this->assertTrue($loaded, 'Guard must fire when bootstrap.custom.php is present.');
        $this->assertSame('executed', $sideEffect, 'Custom file must run and set its side effect.');
    }

    /**
     * Mirrors the exact guard in source/bootstrap.php:
     *   if (is_readable(OX_BASE_PATH . 'bootstrap.custom.php')) {
     *       require OX_BASE_PATH . 'bootstrap.custom.php';
     *   }
     *
     * $sideEffect is passed by reference so a required file can be observed to
     * have run (it shares this method's local scope, exactly as the required
     * file shares bootstrap.php's scope).
     */
    private function runCustomBootstrapLoadLogic(string $baseDir, &$sideEffect): bool
    {
        $sideEffect = null;
        $customFile = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'bootstrap.custom.php';
        if (is_readable($customFile)) {
            require $customFile;
            return true;
        }
        return false;
    }

    private function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $path . DIRECTORY_SEPARATOR . $entry;
            is_dir($full) ? $this->removeDir($full) : unlink($full);
        }
        rmdir($path);
    }
}
```

- [ ] **Step 2: Run the test to verify it passes**

Run: `./docker.sh test --fast tests/Unit/Bootstrap/BootstrapCustomFileTest.php`
Expected: PASS (2 tests). This test mirrors the guard logic in isolation, so it
is green immediately — it documents and locks the intended behavior. The real
`source/bootstrap.php` change in Task 2 makes that behavior live; Task 4's
manual check proves it end-to-end.

- [ ] **Step 3: Commit**

```bash
git add tests/Unit/Bootstrap/BootstrapCustomFileTest.php
git commit -m "test: cover bootstrap.custom.php load guard (#163)"
```

---

### Task 2: Load `bootstrap.custom.php` at the end of bootstrap

**Files:**
- Modify: `source/bootstrap.php` (append at end of file, after the `writeToLog` function definition, ~line 358)

- [ ] **Step 1: Append the guarded require to `source/bootstrap.php`**

Add this block at the very end of the file, after the closing `}` of the
`writeToLog()` function:

```php

/**
 * Optional local / environment-specific bootstrap overrides.
 *
 * This file is intentionally NOT committed (see .gitignore). Copy
 * bootstrap.custom.php.dist to bootstrap.custom.php to add local overrides
 * such as custom DI bindings, Whoops/Debugbar, or ini_set() tweaks.
 *
 * Loaded last, so the composer autoloader, the shop ConfigFile, the
 * ExitHandler, oxNew() and all overridable functions are already available.
 * The is_readable() guard means no error or warning is raised when the file
 * is absent (the default state of the repository).
 */
if (is_readable(OX_BASE_PATH . 'bootstrap.custom.php')) {
    require OX_BASE_PATH . 'bootstrap.custom.php';
}
```

- [ ] **Step 2: Lint the modified file**

Run: `php -l source/bootstrap.php`
Expected: `No syntax errors detected in source/bootstrap.php`

- [ ] **Step 3: Commit**

```bash
git add source/bootstrap.php
git commit -m "feat: conditionally load bootstrap.custom.php at end of bootstrap (#163)"
```

---

### Task 3: Add `.gitignore` entry and the `.dist` example

**Files:**
- Modify: `.gitignore` (under the "# Shop configuration" group, after `/source/config.inc.php`)
- Create: `source/bootstrap.custom.php.dist`

- [ ] **Step 1: Add the gitignore entry**

In `.gitignore`, change the "Shop configuration" group from:

```
# Shop configuration
/source/config.inc.php
```

to:

```
# Shop configuration
/source/config.inc.php
/source/bootstrap.custom.php
```

- [ ] **Step 2: Create `source/bootstrap.custom.php.dist`**

```php
<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

/**
 * Local / environment-specific bootstrap overrides.
 *
 * HOW TO USE
 *   Copy this file to "bootstrap.custom.php" (same directory). The concrete
 *   file is gitignored and never committed, so local and CI/staging setups can
 *   diverge without touching tracked core files.
 *
 * WHEN IT RUNS
 *   bootstrap.custom.php is required at the very END of source/bootstrap.php,
 *   after all core services are in place. At this point you can rely on:
 *     - the composer autoloader (vendor/autoload.php)
 *     - the shop ConfigFile in the Registry
 *     - the registered ExitHandler
 *     - oxNew() and all overridable functions
 *     - the default session ini_set() values (you may override them below)
 *
 * The examples below are intentionally commented out, so copying this file
 * verbatim is a safe no-op. Uncomment and adapt what you need.
 */

/*
 * Example 1: register or override a service in the DI container.
 *
 * use OxidEsales\EshopCommunity\Internal\Framework\DependencyInjection\ContainerFactory;
 *
 * $container = ContainerFactory::getInstance()->getContainer();
 * // ... interact with the container as needed for your local setup ...
 */

/*
 * Example 2: enable a developer error handler such as Whoops.
 *
 * if (class_exists(\Whoops\Run::class)) {
 *     $whoops = new \Whoops\Run();
 *     $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
 *     $whoops->register();
 * }
 */

/*
 * Example 3: tweak PHP ini settings for local debugging.
 *
 * ini_set('display_errors', '1');
 * error_reporting(E_ALL);
 */
```

- [ ] **Step 3: Lint the `.dist` file**

Run: `php -l source/bootstrap.custom.php.dist`
Expected: `No syntax errors detected in source/bootstrap.custom.php.dist`

- [ ] **Step 4: Verify the gitignore rule works**

Run:
```bash
cp source/bootstrap.custom.php.dist source/bootstrap.custom.php
git status --porcelain source/bootstrap.custom.php
```
Expected: empty output (the concrete file is ignored). Then clean up:
```bash
rm source/bootstrap.custom.php
```

- [ ] **Step 5: Commit**

```bash
git add .gitignore source/bootstrap.custom.php.dist
git commit -m "feat: add bootstrap.custom.php.dist example and gitignore entry (#163)"
```

---

### Task 4: End-to-end verification that the real bootstrap loads the file

**Files:** none (manual verification using the running container)

This proves the live `source/bootstrap.php` guard (Task 2) actually executes a
real `source/bootstrap.custom.php`, not just the mirrored logic from Task 1.

- [ ] **Step 1: Create a temporary real custom file with an observable effect**

```bash
printf '<?php file_put_contents(sys_get_temp_dir() . "/o3_custom_bootstrap_proof.txt", "loaded\\n");\n' > source/bootstrap.custom.php
```

- [ ] **Step 2: Trigger the bootstrap inside the container and confirm the effect**

Run (replace the container name with this worktree's container — see CLAUDE.md):
```bash
docker exec o3shop-163-feat-add-bootstrapcustomphp-support-for-localenvironment-specific-initialization-1 \
  sh -c 'rm -f /tmp/o3_custom_bootstrap_proof.txt; php source/bin/cron.php >/dev/null 2>&1 || true; cat /tmp/o3_custom_bootstrap_proof.txt'
```
Expected: prints `loaded` — proving the real bootstrap required the custom file.
(If `cron.php` is unsuitable, any entry point that requires `source/bootstrap.php`
works, e.g. `bin/oe-console`.)

- [ ] **Step 3: Confirm absence is silent**

```bash
rm source/bootstrap.custom.php
docker exec o3shop-163-feat-add-bootstrapcustomphp-support-for-localenvironment-specific-initialization-1 \
  php bin/oe-console 2>&1 | head -5
```
Expected: normal console output, no warning/error about `bootstrap.custom.php`.

- [ ] **Step 4: Ensure no stray file remains**

Run: `git status --porcelain`
Expected: no untracked `source/bootstrap.custom.php`.

---

### Task 5: Document the pattern in `README.md`

**Files:**
- Modify: `README.md` (insert after the "#### Adminer" subsection, before "### Working on the storefront theme")

- [ ] **Step 1: Add the documentation subsection**

After the Adminer block:

```
Adminer is included in the standard installation. Try http://localhost:8081.
```

insert:

```markdown

### Local bootstrap overrides

Need local-only or environment-specific initialization (custom DI bindings, a
Whoops error page, Debugbar, `ini_set()` tweaks) without patching tracked core
files? Copy the committed example and edit your copy:

```bash
cp source/bootstrap.custom.php.dist source/bootstrap.custom.php
```

`source/bootstrap.custom.php` is gitignored, so it never ends up in a commit or
a diff. It is `require`d at the very end of `source/bootstrap.php`, after the
autoloaders, the shop configuration, the `ExitHandler` and `oxNew()` are all
available — so it is the right place for overrides. If the file is absent
(the default), bootstrap proceeds with no warning. See
`source/bootstrap.custom.php.dist` for documented examples.
```

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: document bootstrap.custom.php local override pattern (#163)"
```

---

### Task 6: Final quality gate

- [ ] **Step 1: Run the finish protocol**

Invoke `/finish` (runs `./docker.sh test-all-coverage`: cs-fixer + full tests +
coverage) and update `.claude/memory/` with any lessons learned.
Expected: cs-fixer clean, full suite green (including
`BootstrapCustomFileTest`).

- [ ] **Step 2: Commit any cs-fixer changes**

```bash
git add -A
git commit -m "style: php-cs-fixer for bootstrap.custom.php support (#163)"
```
(Skip if cs-fixer produced no changes.)

---

## Self-Review

**Spec coverage:**

| Spec requirement | Task |
|---|---|
| Core bootstrap conditionally loads `bootstrap.custom.php` | Task 2 |
| `bootstrap.custom.php` added to `.gitignore` | Task 3 |
| `bootstrap.custom.php.dist` with inline docs/examples | Task 3 |
| No error/warning if absent | Task 1 (test), Task 4 (live) |
| Documented in README | Task 5 |
| Unit test (present/absent) | Task 1 |

**Placeholder scan:** No TBD/TODO; every code/command step shows full content.

**Type/path consistency:** Guard string `OX_BASE_PATH . 'bootstrap.custom.php'`
is identical in the test (mirrored), `source/bootstrap.php`, the `.dist` header,
and the README. File paths consistent throughout. Container name matches this
worktree's branch per CLAUDE.md naming.
