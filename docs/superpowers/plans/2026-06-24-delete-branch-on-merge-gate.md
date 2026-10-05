# delete_branch_on_merge Pre-flight Gate — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `bin/release` abort (fail-closed) on a final release when any repo it will open a merge-back PR against has GitHub's `delete_branch_on_merge = true`, so merging the merge-back PR can never delete the release line.

**Architecture:** A new remote-only `PreFlightGate` (`DeleteBranchOnMergeGate`) runs `gh api repos/<slug> --jq .delete_branch_on_merge`. `ReleaseCommand` invokes it — for final releases only (`MergeBackPolicy::shouldOpenForShopTo`) — over the merge-back set (tagged candidates + `o3-shop/o3-shop`), after planning and before the dry-run/live split, so it runs in both dry-run and live. A `true` (or any unverifiable result) aborts with `EXIT_PRE_FLIGHT_ABORT` and an actionable fix command.

**Tech Stack:** PHP 7.4/8.x, PHPUnit 9, Symfony Console, `gh` CLI via `ProcessExecutor`. Spec: `docs/superpowers/specs/2026-06-24-delete-branch-on-merge-gate-design.md`.

**Environment:** Work runs in Docker. Before running tests: `./docker.sh start`. Run a single test file fast with `./docker.sh test --fast <path>`. This worktree's container is `o3shop-190-delete-branch-on-merge-gate-1`.

---

## File Structure

- **Create** `source/Internal/ReleaseTooling/Flow/Gates/DeleteBranchOnMergeGate.php` — the gate (one responsibility: verify one repo's setting).
- **Create** `tests/Unit/Internal/ReleaseTooling/Flow/Gates/DeleteBranchOnMergeGateTest.php` — gate unit tests.
- **Modify** `source/Internal/ReleaseTooling/Command/ReleaseCommand.php` — inject the gate, add `verifyDeleteBranchOnMerge()`, call it for final releases before the dry-run/live split.
- **Modify** `tests/Unit/Internal/ReleaseTooling/Command/ReleaseCommandTest.php` — 2 new tests + inject a passing gate into the 3 existing final-`--to` tests.
- **Create** `docs/release/delete-branch-on-merge-prerequisite.md` — wiki prerequisite snippet (scope item 2).

---

### Task 1: `DeleteBranchOnMergeGate`

**Files:**
- Create: `source/Internal/ReleaseTooling/Flow/Gates/DeleteBranchOnMergeGate.php`
- Test: `tests/Unit/Internal/ReleaseTooling/Flow/Gates/DeleteBranchOnMergeGateTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Internal/ReleaseTooling/Flow/Gates/DeleteBranchOnMergeGateTest.php`:

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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Flow\Gates;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\DeleteBranchOnMergeGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessOutcome;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Flow\FakeProcessExecutor;
use PHPUnit\Framework\TestCase;

class DeleteBranchOnMergeGateTest extends TestCase
{
    private const CMD = 'gh api repos/o3-shop/shop-ce --jq .delete_branch_on_merge';

    public function testPassesWhenSettingIsFalse(): void
    {
        $exec = new FakeProcessExecutor([self::CMD => new ProcessOutcome(0, "false\n", '')]);
        $gate = new DeleteBranchOnMergeGate($exec);
        $outcome = $gate->evaluate('', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->isPassed());
        $this->assertSame('delete-branch-on-merge', $gate->name());
    }

    public function testAbortsWithFixCommandWhenSettingIsTrue(): void
    {
        $exec = new FakeProcessExecutor([self::CMD => new ProcessOutcome(0, "true\n", '')]);
        $outcome = (new DeleteBranchOnMergeGate($exec))->evaluate('', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $messages = implode("\n", $outcome->messages());
        $this->assertStringContainsString('o3-shop/shop-ce', $messages);
        $this->assertStringContainsString("'b-1.6'", $messages);
        $this->assertStringContainsString(
            'gh api -X PATCH repos/o3-shop/shop-ce -F delete_branch_on_merge=false',
            $messages
        );
    }

    public function testFailsClosedWhenGhFails(): void
    {
        $exec = new FakeProcessExecutor([], new ProcessOutcome(1, '', "could not authenticate\n"));
        $outcome = (new DeleteBranchOnMergeGate($exec))->evaluate('', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $this->assertStringContainsString('gh api failed', $outcome->messages()[0]);
    }

    public function testFailsClosedOnUnexpectedOutput(): void
    {
        $exec = new FakeProcessExecutor([self::CMD => new ProcessOutcome(0, "null\n", '')]);
        $outcome = (new DeleteBranchOnMergeGate($exec))->evaluate('', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $this->assertStringContainsString('unexpected value', $outcome->messages()[0]);
    }

    public function testResolvesRenamedSlugForGhApi(): void
    {
        $cmd = 'gh api repos/o3-shop/o3-Theme --jq .delete_branch_on_merge';
        $exec = new FakeProcessExecutor([$cmd => new ProcessOutcome(0, "false\n", '')]);
        $outcome = (new DeleteBranchOnMergeGate($exec))->evaluate('', 'b-1.6', 'o3-shop/o3-theme');
        $this->assertTrue($outcome->isPassed());
        $this->assertContains(
            ['gh', 'api', 'repos/o3-shop/o3-Theme', '--jq', '.delete_branch_on_merge'],
            $exec->commands()
        );
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./docker.sh test --fast tests/Unit/Internal/ReleaseTooling/Flow/Gates/DeleteBranchOnMergeGateTest.php`
Expected: FAIL — `Class "OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\DeleteBranchOnMergeGate" not found`.

- [ ] **Step 3: Write the gate**

Create `source/Internal/ReleaseTooling/Flow/Gates/DeleteBranchOnMergeGate.php`:

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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer\PackageRepoSlug;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\GateOutcome;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessExecutor;

/**
 * Verifies a release repo keeps `delete_branch_on_merge = false`, so
 * merging the merge-back PR (whose head IS the release branch) cannot
 * delete the maintenance line.
 *
 * Remote-only: queries GitHub via `gh api` keyed on the repo slug, so
 * it needs no local checkout (and `$repoPath` is intentionally unused).
 * Fails CLOSED — any unverifiable result aborts.
 */
class DeleteBranchOnMergeGate implements PreFlightGate
{
    public const NAME = 'delete-branch-on-merge';

    private ProcessExecutor $exec;
    private string $ghBin;

    public function __construct(ProcessExecutor $exec, string $ghBin = 'gh')
    {
        $this->exec = $exec;
        $this->ghBin = $ghBin;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function evaluate(string $repoPath, string $expectedBranch, string $packageName): GateOutcome
    {
        $slug = PackageRepoSlug::resolve($packageName);
        $outcome = $this->exec->execute(
            [$this->ghBin, 'api', 'repos/' . $slug, '--jq', '.delete_branch_on_merge'],
            null,
            60
        );
        if (!$outcome->isSuccess()) {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    'gh api failed for %s; cannot verify delete_branch_on_merge. Aborting: %s',
                    $packageName,
                    trim($outcome->stderr())
                ),
            ]);
        }

        $value = trim($outcome->stdout());
        if ($value === 'false') {
            return GateOutcome::passed(self::NAME);
        }
        if ($value === 'true') {
            return GateOutcome::abort(self::NAME, [
                sprintf(
                    "%s has delete_branch_on_merge = true; merging the merge-back PR "
                    . "would delete the release branch '%s'.",
                    $packageName,
                    $expectedBranch
                ),
                sprintf('  Fix: gh api -X PATCH repos/%s -F delete_branch_on_merge=false', $slug),
            ]);
        }

        return GateOutcome::abort(self::NAME, [
            sprintf(
                "gh api returned unexpected value '%s' for %s delete_branch_on_merge; "
                . 'cannot verify. Aborting.',
                $value,
                $packageName
            ),
        ]);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `./docker.sh test --fast tests/Unit/Internal/ReleaseTooling/Flow/Gates/DeleteBranchOnMergeGateTest.php`
Expected: PASS (5 tests, all green).

- [ ] **Step 5: Commit**

```bash
git add source/Internal/ReleaseTooling/Flow/Gates/DeleteBranchOnMergeGate.php \
        tests/Unit/Internal/ReleaseTooling/Flow/Gates/DeleteBranchOnMergeGateTest.php
git commit -m "feat(#190): add DeleteBranchOnMergeGate (remote-only, fail-closed)"
```

---

### Task 2: Wire the gate into `ReleaseCommand` (final releases, dry-run + live)

**Files:**
- Modify: `source/Internal/ReleaseTooling/Command/ReleaseCommand.php`
- Test: `tests/Unit/Internal/ReleaseTooling/Command/ReleaseCommandTest.php`

- [ ] **Step 1: Write the failing command tests**

Add these two methods to `ReleaseCommandTest.php` (inside the `ReleaseCommandTest` class), and add the imports below to the file's `use` block:

```php
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\DeleteBranchOnMergeGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessOutcome;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Flow\FakeProcessExecutor;
```

```php
public function testFinalReleaseAbortsWhenDeleteBranchOnMergeIsTrue(): void
{
    $exec = new FakeProcessExecutor([
        'gh api repos/o3-shop/o3-shop --jq .delete_branch_on_merge'
            => new ProcessOutcome(0, "true\n", ''),
    ]);
    $gate = new DeleteBranchOnMergeGate($exec);
    $stub = new StubReleasePlanner(new ReleasePlan(
        'v1.6.1',
        'v1.6.2',
        new FromSnapshot([]),
        [],
        [],
        '',
        []
    ));
    $tester = new CommandTester(new ReleaseCommand($stub, null, null, $gate));
    $status = $tester->execute([
        '--from' => 'v1.6.1',
        '--to' => 'v1.6.2',
        '--dry-run' => true,
    ]);
    $this->assertSame(ReleaseCommand::EXIT_PRE_FLIGHT_ABORT, $status);
    $this->assertStringContainsString('delete_branch_on_merge', $tester->getDisplay());
}

public function testPreReleaseSkipsDeleteBranchOnMergeCheck(): void
{
    $exec = new FakeProcessExecutor([
        'gh api repos/o3-shop/o3-shop --jq .delete_branch_on_merge'
            => new ProcessOutcome(0, "true\n", ''),
    ]);
    $gate = new DeleteBranchOnMergeGate($exec);
    $stub = new StubReleasePlanner(new ReleasePlan(
        'v1.6.1',
        'v1.6.2-RC1',
        new FromSnapshot([]),
        [],
        [],
        '',
        []
    ));
    $tester = new CommandTester(new ReleaseCommand($stub, null, null, $gate));
    $status = $tester->execute([
        '--from' => 'v1.6.1',
        '--to' => 'v1.6.2-RC1',
        '--dry-run' => true,
    ]);
    $this->assertSame(ReleaseCommand::EXIT_OK, $status);
    $this->assertSame([], $exec->calls);
}
```

- [ ] **Step 2: Run the new tests to verify they fail**

Run: `./docker.sh test --fast tests/Unit/Internal/ReleaseTooling/Command/ReleaseCommandTest.php`
Expected: FAIL — `ReleaseCommand::__construct()` does not accept a 4th argument yet (`ArgumentCountError` / too many arguments), and the abort test fails because the check does not exist.

- [ ] **Step 3: Add the constructor parameter and import in `ReleaseCommand.php`**

Add to the `use` block (alphabetical among the existing `Flow\Gates\*` imports):

```php
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\DeleteBranchOnMergeGate;
```

Add (after the existing `Flow\LiveExecutor` import):

```php
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\MergeBackPolicy;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ReleasePlan;
```

Change the property block (currently lines 96–98) and constructor (currently 104–113) to:

```php
private ?ReleasePlanner $planner;
private DryRunPrinter $printer;
private ?LiveExecutor $liveExecutor;
private ?DeleteBranchOnMergeGate $deleteBranchGate;

/**
 * All arguments are optional so production invocations build
 * defaults inline; tests inject fakes via the constructor.
 */
public function __construct(
    ?ReleasePlanner $planner = null,
    ?DryRunPrinter $printer = null,
    ?LiveExecutor $liveExecutor = null,
    ?DeleteBranchOnMergeGate $deleteBranchGate = null
) {
    parent::__construct();
    $this->planner = $planner;
    $this->printer = $printer ?? new DryRunPrinter();
    $this->liveExecutor = $liveExecutor;
    $this->deleteBranchGate = $deleteBranchGate;
}
```

- [ ] **Step 4: Insert the check in `execute()` before the dry-run/live split**

In `execute()`, find the existing block (currently lines 247–253):

```php
        if ($plan->shouldAbort()) {
            $output->writeln(
                '<error>Pre-flight gates aborted the release. '
                . 'Resolve the issues above and re-run.</error>'
            );
            return self::EXIT_PRE_FLIGHT_ABORT;
        }
```

Immediately AFTER that block (and before `if ($dryRun) {`), insert:

```php
        // Merge-back deletion guard (#190): final releases open a
        // merge-back PR whose head IS the release branch. If any target
        // repo auto-deletes head branches on merge, that merge deletes
        // the release line. Verify it is off — fail closed. Runs in
        // dry-run too (remote-only/read-only), so a dry-run previews
        // whether the real release would be blocked.
        if (MergeBackPolicy::shouldOpenForShopTo($to)) {
            $gate = $this->deleteBranchGate
                ?? new DeleteBranchOnMergeGate(new SymfonyProcessExecutor());
            if (!$this->verifyDeleteBranchOnMerge($gate, $plan, $output)) {
                return self::EXIT_PRE_FLIGHT_ABORT;
            }
        }
```

- [ ] **Step 5: Add the `verifyDeleteBranchOnMerge()` helper**

Add this private method to `ReleaseCommand` (e.g. directly after `execute()`):

```php
/**
 * Runs the delete_branch_on_merge gate over every repo that will
 * receive a merge-back PR (tagged candidates + the o3-shop project).
 * Returns true when all pass; prints diagnostics and returns false
 * when any aborts.
 */
private function verifyDeleteBranchOnMerge(
    DeleteBranchOnMergeGate $gate,
    ReleasePlan $plan,
    OutputInterface $output
): bool {
    $branchResolver = new DefaultBranchResolver();
    $packages = [];
    foreach ($plan->candidates() as $candidate) {
        if ($candidate->tagCut() !== null) {
            $packages[] = $candidate->package();
        }
    }
    $packages[] = ReleasePlanner::O3_SHOP_PROJECT;

    $aborted = false;
    foreach ($packages as $package) {
        $outcome = $gate->evaluate('', $branchResolver($package), $package);
        if ($outcome->aborts()) {
            $aborted = true;
            foreach ($outcome->messages() as $message) {
                $output->writeln(sprintf('<error>[%s] %s</error>', $gate->name(), $message));
            }
        }
    }
    return !$aborted;
}
```

- [ ] **Step 6: Keep the 3 existing final-`--to` tests hermetic**

The live-execution tests that use `'--to' => 'v1.6.1'` would now invoke the real `gh` via the default gate. Inject a passing gate into them.

First add a helper to `ReleaseCommandTest`:

```php
private function passingDeleteBranchGate(): DeleteBranchOnMergeGate
{
    return new DeleteBranchOnMergeGate(
        new FakeProcessExecutor([], new ProcessOutcome(0, "false\n", ''))
    );
}
```

Find the three call sites (live-execution tests with a final `--to`):

```bash
grep -n "new CommandTester(new ReleaseCommand(\$stub, null, \$executor))" \
    tests/Unit/Internal/ReleaseTooling/Command/ReleaseCommandTest.php
```

In each of the three tests whose `--to` is `'v1.6.1'` (no `-RC`), change:

```php
$tester = new CommandTester(new ReleaseCommand($stub, null, $executor));
```

to:

```php
$tester = new CommandTester(
    new ReleaseCommand($stub, null, $executor, $this->passingDeleteBranchGate())
);
```

(Leave the RC-tagged tests unchanged — `MergeBackPolicy::shouldOpenForShopTo('v1.6.1-RC1')` is false, so the check is skipped and no `gh` call happens.)

- [ ] **Step 7: Run the command tests to verify they pass**

Run: `./docker.sh test --fast tests/Unit/Internal/ReleaseTooling/Command/ReleaseCommandTest.php`
Expected: PASS — including the two new tests and the three updated final-release tests.

- [ ] **Step 8: Commit**

```bash
git add source/Internal/ReleaseTooling/Command/ReleaseCommand.php \
        tests/Unit/Internal/ReleaseTooling/Command/ReleaseCommandTest.php
git commit -m "feat(#190): enforce delete_branch_on_merge on final releases (dry-run + live)"
```

---

### Task 3: Wiki prerequisite snippet

**Files:**
- Create: `docs/release/delete-branch-on-merge-prerequisite.md`

- [ ] **Step 1: Write the doc**

Create `docs/release/delete-branch-on-merge-prerequisite.md`:

```markdown
# Release prerequisite: `delete_branch_on_merge` must be OFF

> Paste this into the "Create a Release" wiki page prerequisites
> (projects.wiki.tro.net).

## Policy

The release-line repos MUST keep GitHub's **"Automatically delete head
branches"** setting **off** (`delete_branch_on_merge = false`):

- `o3-shop/shop-ce`
- `o3-shop/shop-metapackage-ce`
- `o3-shop/o3-shop`

## Why

`bin/release` opens its merge-back PR with the **release branch itself**
as the head (`b-1.x → main`). With auto-delete on, merging that PR
**deletes the release line branch**. The next release then walks the
(now missing) `b-1.x` and fails. This bit v1.6.1: merging merge-back
PR #168 deleted `shop-ce@b-1.6`.

## Verify (per repo)

```bash
gh api repos/o3-shop/shop-ce --jq .delete_branch_on_merge   # expect: false
```

## Fix (if it returns `true`)

```bash
gh api -X PATCH repos/o3-shop/shop-ce -F delete_branch_on_merge=false
```

## Automated enforcement

`bin/release` verifies this automatically on **final** releases (it runs
in `--dry-run` too, so you can preview before cutting). It aborts with
the fix command if any target repo has the setting on. Still verify
manually when **adding a new release-line repo**, since the gate checks
exactly the repos a given release touches.
```

- [ ] **Step 2: Commit**

```bash
git add docs/release/delete-branch-on-merge-prerequisite.md
git commit -m "docs(#190): add delete_branch_on_merge release prerequisite snippet"
```

---

### Task 4: Full verification (cs-fixer + full suite)

- [ ] **Step 1: Ensure Docker is up**

Run: `./docker.sh start`
Expected: containers running.

- [ ] **Step 2: Run cs-fixer + full unit suite**

Run: `./docker.sh test-all`
Expected: cs-fixer reports no violations (or auto-fixes; re-stage if it changes files), full suite green.

- [ ] **Step 3: Commit any cs-fixer changes**

```bash
git add -A
git commit -m "style(#190): php-cs-fixer" || echo "nothing to fix"
```

- [ ] **Step 4: Run `/finish`** (project quality gate) before marking the task complete, then proceed to code review / PR per the normal workflow (do not push unless asked).

---

## Self-Review

**Spec coverage:**
- Gate verifies `delete_branch_on_merge == false`, aborts on `true` with fix command → Task 1 ✓
- Fail-closed on gh error / unexpected output → Task 1 (`testFailsClosedWhenGhFails`, `testFailsClosedOnUnexpectedOutput`) ✓
- Dynamic repo scope = merge-back set (tagged candidates + o3-shop), no hardcoded list → Task 2 `verifyDeleteBranchOnMerge()` ✓
- Final-release-only (`MergeBackPolicy::shouldOpenForShopTo`) → Task 2 (`testPreReleaseSkipsDeleteBranchOnMergeCheck`) ✓
- Runs in dry-run too; placed before the `if ($dryRun)` split → Task 2 Step 4 (tests use `--dry-run`) ✓
- Slug rename handled (`PackageRepoSlug`) → Task 1 (`testResolvesRenamedSlugForGhApi`) ✓
- Wiki prerequisite snippet → Task 3 ✓
- #189 (throwaway merge-back branch) NOT in scope → not present ✓

**Placeholder scan:** No TBD/TODO; every code/test step shows full code; the only grep-and-edit step (Task 2 Step 6) gives the exact before/after string and a locating grep. ✓

**Type consistency:** `DeleteBranchOnMergeGate::evaluate(string,string,string): GateOutcome` matches `PreFlightGate`. `GateOutcome::passed/abort`, `aborts()`, `messages()`, `isPassed()`, `name()` match the real class. `MergeBackPolicy::shouldOpenForShopTo(string): bool`, `ReleasePlanner::O3_SHOP_PROJECT`, `$plan->candidates()` (array of `CandidatePlan` with `package()`/`tagCut()`), `FakeProcessExecutor($responses, $defaultOutcome)` + `->calls` + `->commands()`, `ProcessOutcome(int,string,string)` all verified against source. ✓
```
