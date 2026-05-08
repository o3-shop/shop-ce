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
            new FromSnapshot([], false, null),
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
            new FromSnapshot([], false, null),
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
            new FromSnapshot([], false, null),
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

    /* ---------- live mode is not yet wired ---------- */

    public function testLiveModeShowsNotYetWiredNotice(): void
    {
        $tester = $this->tester();
        $status = $tester->execute([
            '--from' => 'v1.6.0',
            '--to' => 'v1.6.1-RC1',
            // no --dry-run
        ]);
        $this->assertSame(ReleaseCommand::EXIT_OK, $status);
        $this->assertStringContainsString('Live execution', $tester->getDisplay());
        $this->assertStringContainsString('Section 14', $tester->getDisplay());
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

    private function planThatAborts(): ReleasePlan
    {
        $abortingReport = new \OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightReport([
            \OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\GateOutcome::abort('test', ['boom']),
        ]);
        return new ReleasePlan(
            'v1.6.0',
            'v1.6.1-RC1',
            new FromSnapshot([], false, null),
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
        $this->callCount++;
        return $this->plan;
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
