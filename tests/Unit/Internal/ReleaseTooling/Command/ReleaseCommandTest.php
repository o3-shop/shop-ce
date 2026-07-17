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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Command;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Command\ReleaseCommand;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\LiveExecutor;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\DryRunPrinter;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ReleasePlan;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ReleasePlanner;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Snapshot\FromSnapshot;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class ReleaseCommandTest extends TestCase
{
    private function tester(?ReleasePlanner $planner = null, ?DryRunPrinter $printer = null): CommandTester
    {
        $planner = $planner ?? $this->stubPlannerForEmptyPlan();
        return new CommandTester(new ReleaseCommand($planner, $printer));
    }

    private function stubPlannerForEmptyPlan(): ReleasePlanner
    {
        return new StubReleasePlanner(new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([]),
            [],
            [],
            '',
            []
        ));
    }

    /* ---------- flag parsing (Section 3 contract preserved) ---------- */

    public function testRunWithBothMandatoryFlagsExitsZero(): void
    {
        $tester = $this->tester();
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--dry-run' => true,
        ]);
        $this->assertSame(ReleaseCommand::EXIT_OK, $status);
    }

    public function testRunWithoutFromExitsUsageError(): void
    {
        $tester = $this->tester();
        $status = $tester->execute(['--to' => 'v1.6.1-RC1']);
        $this->assertSame(ReleaseCommand::EXIT_USAGE_ERROR, $status);
        $this->assertStringContainsString('--from is required', $tester->getDisplay());
    }

    public function testRunWithoutToExitsUsageError(): void
    {
        $tester = $this->tester();
        $status = $tester->execute(['--from' => 'v1.6.0']);
        $this->assertSame(ReleaseCommand::EXIT_USAGE_ERROR, $status);
        $this->assertStringContainsString('--to is required', $tester->getDisplay());
    }

    public function testRunWithBothFlagsEmptyStringExitsUsageError(): void
    {
        $tester = $this->tester();
        $status = $tester->execute(['--from' => '', '--to' => 'v1.6.1-RC1']);
        $this->assertSame(ReleaseCommand::EXIT_USAGE_ERROR, $status);
    }

    public function testRunWithMalformedBumpExitsUsageError(): void
    {
        $tester = $this->tester();
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--bump' => ['testing-library=bogus'],
        ]);
        $this->assertSame(ReleaseCommand::EXIT_USAGE_ERROR, $status);
        $this->assertStringContainsString('Malformed --bump level', $tester->getDisplay());
    }

    /* ---------- bump-flag parsing into the planner ---------- */

    public function testBumpFlagsArePassedToPlannerAsSlugLevelMap(): void
    {
        $stub = new StubReleasePlanner(new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([]),
            [],
            [],
            '',
            []
        ));
        $this->tester($stub)->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--bump' => ['testing-library=minor', 'shop-facts=v2.0.0'],
            '--dry-run' => true,
        ]);

        $this->assertSame([
            'testing-library' => 'minor',
            'shop-facts' => 'v2.0.0',
        ], $stub->lastBumpFlags);
        $this->assertSame('v1.6.0', $stub->lastFromTag);
        $this->assertSame('v1.6.1-RC1', $stub->lastToTag);
    }

    /* ---------- 11.1 + 11.5: dry-run never invokes state-changing actions ---------- */

    public function testDryRunPrintsPlanAndDoesNotInvokeAnyStateChange(): void
    {
        $stub = new StubReleasePlanner(new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([]),
            [],
            [],
            '',
            []
        ));
        $tester = $this->tester($stub);
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--dry-run' => true,
        ]);
        $this->assertSame(ReleaseCommand::EXIT_OK, $status);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Dry-run complete', $display);
        $this->assertStringContainsString('Release plan: --from v1.6.0 --to v1.6.1-RC1', $display);

        // Planner was called exactly once with no state-changing path
        $this->assertSame(1, $stub->callCount);
    }

    /* ---------- 11.4: pre-flight abort -> non-zero exit ---------- */

    public function testPreFlightAbortYieldsNonZeroExit(): void
    {
        $abortingPlan = $this->planThatAborts();
        $tester = $this->tester(new StubReleasePlanner($abortingPlan));
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--dry-run' => true,
        ]);
        $this->assertSame(ReleaseCommand::EXIT_PRE_FLIGHT_ABORT, $status);
    }

    /* ---------- live mode (§15 wiring + §16 discovery) ---------- */

    public function testMalformedRepoPathExitsUsageError(): void
    {
        $tester = $this->tester();
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--repo-path' => ['no-equals-sign'],
        ]);
        $this->assertSame(ReleaseCommand::EXIT_USAGE_ERROR, $status);
        $this->assertStringContainsString('Malformed --repo-path', $tester->getDisplay());
    }

    public function testLiveModeWithRepoPathInvokesExecutorAndExitsZero(): void
    {
        $tmpRepoPath = $this->makeFakeGitRepo();
        $stub = new StubReleasePlanner(new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([]),
            [],
            [],
            '',
            []
        ));
        $executor = new RecordingLiveExecutor();
        $tester = new CommandTester(new ReleaseCommand($stub, null, $executor));
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--repo-path' => ['o3-shop/shop-ce=' . $tmpRepoPath],
        ]);
        $this->assertSame(ReleaseCommand::EXIT_OK, $status);
        $this->assertSame(1, $executor->callCount);
        $this->assertSame(['o3-shop/shop-ce' => $tmpRepoPath], $executor->lastRepoPaths);
        $this->assertSame('v1.6.0', $stub->lastFromTag);
        $this->assertSame(['o3-shop/shop-ce' => $tmpRepoPath], $stub->lastRepoPaths);
        $this->cleanupFakeGitRepo($tmpRepoPath);
    }

    public function testLiveModePreFlightAbortShortCircuitsBeforeExecutor(): void
    {
        $tmpRepoPath = $this->makeFakeGitRepo();
        $abortingPlan = $this->planThatAborts();
        $executor = new RecordingLiveExecutor();
        $tester = new CommandTester(new ReleaseCommand(
            new StubReleasePlanner($abortingPlan),
            null,
            $executor
        ));
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--repo-path' => ['o3-shop/shop-ce=' . $tmpRepoPath],
        ]);
        $this->assertSame(ReleaseCommand::EXIT_PRE_FLIGHT_ABORT, $status);
        $this->assertSame(0, $executor->callCount, 'executor must not run on pre-flight abort');
        $this->cleanupFakeGitRepo($tmpRepoPath);
    }

    public function testLiveExecutorFailureSurfacedAsPlanErrorExit(): void
    {
        $tmpRepoPath = $this->makeFakeGitRepo();
        $stub = new StubReleasePlanner(new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([]),
            [],
            [],
            '',
            []
        ));
        $executor = new RecordingLiveExecutor(true);
        $tester = new CommandTester(new ReleaseCommand($stub, null, $executor));
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--repo-path' => ['o3-shop/shop-ce=' . $tmpRepoPath],
        ]);
        $this->assertSame(ReleaseCommand::EXIT_PLAN_ERROR, $status);
        $this->assertStringContainsString('Live execution failed', $tester->getDisplay());
        $this->cleanupFakeGitRepo($tmpRepoPath);
    }

    private function makeFakeGitRepo(): string
    {
        $dir = sys_get_temp_dir() . '/release-cmd-test-' . bin2hex(random_bytes(4));
        mkdir($dir);
        mkdir($dir . '/.git');
        return $dir;
    }

    private function cleanupFakeGitRepo(string $dir): void
    {
        @rmdir($dir . '/.git');
        @rmdir($dir);
    }

    /* ---------- validateBumpValue() unit (preserved from Section 3) ---------- */

    /** @dataProvider validBumpValueProvider */
    public function testValidBumpValueReturnsNullFromValidator(string $value): void
    {
        $command = new ReleaseCommand();
        $this->assertNull($command->validateBumpValue($value));
    }

    public function validBumpValueProvider(): array
    {
        return [
            ['testing-library=patch'],
            ['testing-library=minor'],
            ['shop-facts=major'],
            ['shop-facts=v2.0.0'],
            ['gdpr-optin-module=v1.1.0'],
            ['o3-theme=v1.6.1-RC1'],
            ['shop-demodata-ce=v0.0.0-alpha.1'],
        ];
    }

    /** @dataProvider invalidBumpValueProvider */
    public function testInvalidBumpValueReturnsErrorFromValidator(
        string $value,
        string $expectFragment
    ): void {
        $command = new ReleaseCommand();
        $error = $command->validateBumpValue($value);
        $this->assertNotNull($error);
        $this->assertStringContainsString($expectFragment, $error);
    }

    public function invalidBumpValueProvider(): array
    {
        return [
            'no equals sign' => ['testing-library', 'Expected <repo>=<level>'],
            'leading equals' => ['=patch', 'Expected <repo>=<level>'],
            'trailing equals' => ['testing-library=', 'Expected <repo>=<level>'],
            'unknown level' => ['testing-library=bogus', 'Malformed --bump level'],
            'missing v prefix' => ['testing-library=1.0.0', 'Malformed --bump level'],
            'uppercase repo' => ['Testing-Library=patch', 'Malformed --bump repo slug'],
            'slash in repo' => ['o3-shop/testing-library=patch', 'Malformed --bump repo slug'],
        ];
    }

    /* ---------- planner failure path ---------- */

    public function testPlannerFailurePropagatesAsPlanErrorExit(): void
    {
        $tester = $this->tester(new ThrowingPlanner());
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--dry-run' => true,
        ]);
        $this->assertSame(ReleaseCommand::EXIT_PLAN_ERROR, $status);
        $this->assertStringContainsString('Plan failed', $tester->getDisplay());
    }

    /* ---------- pre-flight abort prints diagnostic ---------- */

    public function testPreFlightAbortInDryRunPrintsDiagnostic(): void
    {
        $tester = $this->tester(new StubReleasePlanner($this->planThatAborts()));
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--dry-run' => true,
        ]);
        $this->assertSame(ReleaseCommand::EXIT_PRE_FLIGHT_ABORT, $status);
        $this->assertStringContainsString('Pre-flight gates aborted', $tester->getDisplay());
    }

    /* ---------- partial-state printing on live success ---------- */

    public function testLiveSuccessPrintsCapturedReleaseAndMergeBackUrls(): void
    {
        $tmpRepoPath = $this->makeFakeGitRepo();
        $stub = new StubReleasePlanner(new ReleasePlan(
            'v1.6.0',
            'v1.6.1',
            new FromSnapshot([]),
            [],
            [],
            '',
            []
        ));
        $executor = new RecordingLiveExecutor(
            false,
            ['o3-shop/shop-ce' => 'https://github.com/o3-shop/shop-ce/releases/draft/v1.6.1'],
            ['o3-shop/shop-ce' => 'https://github.com/o3-shop/shop-ce/pull/200']
        );
        $tester = new CommandTester(new ReleaseCommand($stub, null, $executor));
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1',
            '--repo-path' => ['o3-shop/shop-ce=' . $tmpRepoPath],
        ]);

        $display = $tester->getDisplay();
        $this->assertSame(ReleaseCommand::EXIT_OK, $status);
        $this->assertStringContainsString('Draft GitHub releases created:', $display);
        $this->assertStringContainsString('o3-shop/shop-ce -> https://github.com/o3-shop/shop-ce/releases/draft/v1.6.1', $display);
        $this->assertStringContainsString('Merge-back PRs opened:', $display);
        $this->assertStringContainsString('o3-shop/shop-ce -> https://github.com/o3-shop/shop-ce/pull/200', $display);
        $this->cleanupFakeGitRepo($tmpRepoPath);
    }

    public function testLiveFailureStillPrintsPartialState(): void
    {
        $tmpRepoPath = $this->makeFakeGitRepo();
        $stub = new StubReleasePlanner(new ReleasePlan(
            'v1.6.0',
            'v1.6.1',
            new FromSnapshot([]),
            [],
            [],
            '',
            []
        ));
        $executor = new RecordingLiveExecutor(
            true,
            ['o3-shop/shop-ce' => 'https://github.com/o3-shop/shop-ce/releases/draft/v1.6.1'],
            []
        );
        $tester = new CommandTester(new ReleaseCommand($stub, null, $executor));
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1',
            '--repo-path' => ['o3-shop/shop-ce=' . $tmpRepoPath],
        ]);

        $this->assertSame(ReleaseCommand::EXIT_PLAN_ERROR, $status);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Live execution failed', $display);
        $this->assertStringContainsString('Draft GitHub releases created:', $display);
        $this->cleanupFakeGitRepo($tmpRepoPath);
    }

    /* ---------- --repo-path malformed-input branches ---------- */

    /** @dataProvider malformedRepoPathProvider */
    public function testMalformedRepoPathExitsUsageErrorWithExpectedFragment(
        string $argValue,
        string $expectedFragment,
        bool $createDir = false,
        bool $createGit = false
    ): void {
        $tmp = sys_get_temp_dir() . '/release-cmd-test-' . bin2hex(random_bytes(4));
        if ($createDir) {
            mkdir($tmp);
            if ($createGit) {
                mkdir($tmp . '/.git');
            }
            $argValue = str_replace('__TMP__', $tmp, $argValue);
        }
        $tester = $this->tester();
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            '--repo-path' => [$argValue],
        ]);
        $this->assertSame(ReleaseCommand::EXIT_USAGE_ERROR, $status);
        $this->assertStringContainsString($expectedFragment, $tester->getDisplay());

        if ($createDir) {
            @rmdir($tmp . '/.git');
            @rmdir($tmp);
        }
    }

    public function malformedRepoPathProvider(): array
    {
        return [
            'no equals sign' => ['no-equals', 'Malformed --repo-path'],
            'leading equals' => ['=/abs/path', 'Malformed --repo-path'],
            'trailing equals' => ['o3-shop/shop-ce=', 'Malformed --repo-path'],
            'package without slash' => ['shopce=/abs/path', 'Malformed --repo-path package'],
            'relative path' => ['o3-shop/shop-ce=relative/path', 'Malformed --repo-path absolute path'],
            'non-existent path' => ['o3-shop/shop-ce=/definitely/does/not/exist', 'is not a directory'],
            'existing dir but not a git tree' => ['o3-shop/shop-ce=__TMP__', 'does not look like a git working tree', true, false],
        ];
    }

    /* ---------- production-mode builders smoke test (reflection) ---------- */

    public function testProductionModeBuildersConstructWithoutErrors(): void
    {
        $command = new ReleaseCommand();
        $reflector = new \ReflectionClass(ReleaseCommand::class);

        $buildPlanner = $reflector->getMethod('buildDefaultPlanner');
        $buildPlanner->setAccessible(true);
        $planner = $buildPlanner->invoke($command, null, true);
        $this->assertInstanceOf(ReleasePlanner::class, $planner);

        $buildPlannerNoPreFlight = $buildPlanner->invoke($command, null, false);
        $this->assertInstanceOf(ReleasePlanner::class, $buildPlannerNoPreFlight);

        $buildLiveExecutor = $reflector->getMethod('buildDefaultLiveExecutor');
        $buildLiveExecutor->setAccessible(true);
        $live = $buildLiveExecutor->invoke($command, null);
        $this->assertInstanceOf(LiveExecutor::class, $live);
    }

    private function planThatAborts(): ReleasePlan
    {
        $abortingReport = new \OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightReport([
            \OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\GateOutcome::abort('test', ['boom']),
        ]);
        return new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([]),
            [],
            [],
            '',
            ['o3-shop/shop-ce' => $abortingReport]
        );
    }
}

/**
 * Test double: returns a pre-canned plan and records the inputs.
 * Extends ReleasePlanner so it satisfies the type without a real
 * service tree behind it.
 */
final class StubReleasePlanner extends ReleasePlanner
{
    public string $lastFromTag = '';
    public string $lastToTag = '';
    public array $lastBumpFlags = [];
    public array $lastRepoPaths = [];
    public int $callCount = 0;

    private ReleasePlan $plan;

    public function __construct(ReleasePlan $plan)
    {
        // NB: deliberately skip the parent constructor — stub doesn't need
        // any of the real services. PHP allows this when the child only
        // overrides public methods that don't touch parent state.
        $this->plan = $plan;
    }

    public function plan(string $fromTag, string $toTag, array $bumpFlags, array $repoPaths = []): ReleasePlan
    {
        $this->lastFromTag = $fromTag;
        $this->lastToTag = $toTag;
        $this->lastBumpFlags = $bumpFlags;
        $this->lastRepoPaths = $repoPaths;
        $this->callCount++;
        return $this->plan;
    }
}

final class RecordingLiveExecutor extends LiveExecutor
{
    public int $callCount = 0;
    public array $lastRepoPaths = [];
    private bool $shouldThrow;
    /** @var array<string,string> */
    private array $cannedReleaseUrls;
    /** @var array<string,string> */
    private array $cannedMergeBackUrls;

    /**
     * @param array<string,string> $cannedReleaseUrls
     * @param array<string,string> $cannedMergeBackUrls
     */
    public function __construct(
        bool $shouldThrow = false,
        array $cannedReleaseUrls = [],
        array $cannedMergeBackUrls = []
    ) {
        // Skip parent constructor — see StubReleasePlanner.
        $this->shouldThrow = $shouldThrow;
        $this->cannedReleaseUrls = $cannedReleaseUrls;
        $this->cannedMergeBackUrls = $cannedMergeBackUrls;
    }

    public function execute(ReleasePlan $plan, array $repoPaths): void
    {
        $this->callCount++;
        $this->lastRepoPaths = $repoPaths;
        if ($this->shouldThrow) {
            throw new RuntimeException('synthetic live executor failure');
        }
    }

    public function releaseUrls(): array
    {
        return $this->cannedReleaseUrls;
    }

    public function mergeBackUrls(): array
    {
        return $this->cannedMergeBackUrls;
    }
}

final class ThrowingPlanner extends ReleasePlanner
{
    public function __construct()
    {
        // Skip parent constructor — see StubReleasePlanner.
    }

    public function plan(string $fromTag, string $toTag, array $bumpFlags, array $repoPaths = []): ReleasePlan
    {
        throw new RuntimeException('synthetic planner failure');
    }
}
