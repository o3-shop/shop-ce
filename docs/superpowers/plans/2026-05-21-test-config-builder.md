# IncenteevScriptHandlerWrapper Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a `IncenteevScriptHandlerWrapper` wrapper that delegates to `Incenteev\ParameterHandler\ScriptHandler::buildParameters` on dev installs and silently no-ops on prod (`--no-dev`) installs, eliminating the Composer autoload warning.

**Architecture:** A single static wrapper class in `source/Core/` mirrors the pattern of `ShopVersionGenerator`. `composer.json` scripts are updated to call the wrapper instead of the Incenteev class directly.

**Tech Stack:** PHP 7.4+, PHPUnit 9, Composer scripts API (`Composer\Script\Event`)

---

### Task 1: Write the failing test

**Files:**
- Create: `tests/Unit/Core/IncenteevScriptHandlerWrapperTest.php`

- [ ] **Step 1: Create the test file**

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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\EshopCommunity\Core\IncenteevScriptHandlerWrapper;

class IncenteevScriptHandlerWrapperTest extends \OxidTestCase
{
    public function testBuildParametersMethodExists(): void
    {
        $this->assertTrue(
            method_exists(IncenteevScriptHandlerWrapper::class, 'buildParameters'),
            'IncenteevScriptHandlerWrapper::buildParameters must exist'
        );
    }

    public function testBuildParametersIsStatic(): void
    {
        $reflection = new \ReflectionMethod(IncenteevScriptHandlerWrapper::class, 'buildParameters');
        $this->assertTrue($reflection->isStatic(), 'buildParameters must be a static method');
    }

    public function testBuildParametersAcceptsComposerEvent(): void
    {
        $reflection = new \ReflectionMethod(IncenteevScriptHandlerWrapper::class, 'buildParameters');
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
    }
}
```

Save to `tests/Unit/Core/IncenteevScriptHandlerWrapperTest.php`.

- [ ] **Step 2: Run the test to confirm it fails**

```bash
./docker.sh test --fast tests/Unit/Core/IncenteevScriptHandlerWrapperTest.php
```

Expected output: error about class `IncenteevScriptHandlerWrapper` not found. (Not a FAIL — a fatal class-not-found error is expected here.)

---

### Task 2: Create IncenteevScriptHandlerWrapper

**Files:**
- Create: `source/Core/IncenteevScriptHandlerWrapper.php`

- [ ] **Step 1: Create the class**

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

namespace OxidEsales\EshopCommunity\Core;

use Composer\Script\Event;

class IncenteevScriptHandlerWrapper
{
    public static function buildParameters(Event $event): void
    {
        if (!class_exists(\Incenteev\ParameterHandler\ScriptHandler::class)) {
            return;
        }
        \Incenteev\ParameterHandler\ScriptHandler::buildParameters($event);
    }
}
```

Save to `source/Core/IncenteevScriptHandlerWrapper.php`.

- [ ] **Step 2: Run the test to confirm it passes**

```bash
./docker.sh test --fast tests/Unit/Core/IncenteevScriptHandlerWrapperTest.php
```

Expected: all 3 tests PASS, 0 failures.

- [ ] **Step 3: Commit**

```bash
git add source/Core/IncenteevScriptHandlerWrapper.php tests/Unit/Core/IncenteevScriptHandlerWrapperTest.php
git commit -m "feat(#157): add IncenteevScriptHandlerWrapper wrapper to silence --no-dev composer warning

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>"
```

---

### Task 3: Update composer.json scripts

**Files:**
- Modify: `composer.json` lines 74 and 79

- [ ] **Step 1: Replace both script entries**

In `composer.json`, change lines 74 and 79 (both occurrences of the Incenteev direct reference):

Old (appears twice — in `post-install-cmd` and `post-update-cmd`):
```
"Incenteev\\ParameterHandler\\ScriptHandler::buildParameters",
```

New:
```
"OxidEsales\\EshopCommunity\\Core\\IncenteevScriptHandlerWrapper::buildParameters",
```

The resulting `scripts` block should look like:

```json
"scripts": {
    "post-install-cmd": [
        "OxidEsales\\EshopCommunity\\Core\\ShopVersionGenerator::generate",
        "OxidEsales\\EshopCommunity\\Core\\IncenteevScriptHandlerWrapper::buildParameters",
        "@oe:ide-helper:generate"
    ],
    "post-update-cmd": [
        "OxidEsales\\EshopCommunity\\Core\\ShopVersionGenerator::generate",
        "OxidEsales\\EshopCommunity\\Core\\IncenteevScriptHandlerWrapper::buildParameters",
        "@oe:ide-helper:generate"
    ],
    ...
}
```

- [ ] **Step 2: Verify JSON is valid**

```bash
php -r "json_decode(file_get_contents('composer.json')); echo json_last_error() === JSON_ERROR_NONE ? 'OK' : 'INVALID';"
```

Expected: `OK`

- [ ] **Step 3: Commit**

```bash
git add composer.json
git commit -m "fix(#157): route composer scripts through IncenteevScriptHandlerWrapper wrapper

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>"
```

---

### Task 4: Run full test suite

- [ ] **Step 1: Run full tests with coverage**

```bash
./docker.sh test-all-coverage
```

Expected: all tests pass, 0 failures, 0 errors. cs-fixer should report no changes needed.

- [ ] **Step 2: If cs-fixer makes changes, commit them**

```bash
# Only run if Step 1 reported cs-fixer fixes
git add source/Core/IncenteevScriptHandlerWrapper.php
git commit -m "style: apply cs-fixer to IncenteevScriptHandlerWrapper

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>"
```

Then re-run `./docker.sh test-all-coverage` to confirm clean pass.
