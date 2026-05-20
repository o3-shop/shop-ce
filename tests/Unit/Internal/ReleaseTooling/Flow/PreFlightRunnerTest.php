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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Flow;

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\GateOutcome;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PreFlightRunner;
use PHPUnit\Framework\TestCase;

class PreFlightRunnerTest extends TestCase
{
    public function testAllGatesPassedReportSatisfiesNoAbort(): void
    {
        $runner = new PreFlightRunner([
            new StubGate('gate-a', GateOutcome::passed('gate-a')),
            new StubGate('gate-b', GateOutcome::passed('gate-b')),
        ]);
        $report = $runner->runFor('/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertFalse($report->shouldAbort());
        $this->assertFalse($report->hasWarnings());
        $this->assertSame([], $report->allMessages());
    }

    public function testWarningGateDoesNotAbortButShowsInReport(): void
    {
        $runner = new PreFlightRunner([
            new StubGate('gate-a', GateOutcome::passed('gate-a')),
            new StubGate('gate-b', GateOutcome::warning('gate-b', ['heads-up: pending PR #99'])),
        ]);
        $report = $runner->runFor('/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertFalse($report->shouldAbort());
        $this->assertTrue($report->hasWarnings());
        $this->assertContains('[gate-b] heads-up: pending PR #99', $report->allMessages());
    }

    public function testAbortGateRaisesShouldAbort(): void
    {
        $runner = new PreFlightRunner([
            new StubGate('gate-a', GateOutcome::passed('gate-a')),
            new StubGate('gate-b', GateOutcome::abort('gate-b', ['unmerged merge-back PR #42'])),
            new StubGate('gate-c', GateOutcome::passed('gate-c')),
        ]);
        $report = $runner->runFor('/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertTrue($report->shouldAbort());
        $this->assertContains('[gate-b] unmerged merge-back PR #42', $report->allMessages());
    }

    public function testAllGatesEvaluatedEvenAfterAbort(): void
    {
        // Per spec 10.7: collect all failures so the operator gets one
        // combined diagnostic, not just the first abort.
        $gateA = new StubGate('a', GateOutcome::abort('a', ['fail-a']));
        $gateB = new StubGate('b', GateOutcome::abort('b', ['fail-b']));
        $runner = new PreFlightRunner([$gateA, $gateB]);

        $report = $runner->runFor('/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertTrue($report->shouldAbort());
        $messages = $report->allMessages();
        $this->assertContains('[a] fail-a', $messages);
        $this->assertContains('[b] fail-b', $messages);
        $this->assertTrue($gateA->wasEvaluated);
        $this->assertTrue($gateB->wasEvaluated);
    }

    public function testGatesReceiveRepoPathExpectedBranchAndPackageName(): void
    {
        $gate = new StubGate('check', GateOutcome::passed('check'));
        $runner = new PreFlightRunner([$gate]);
        $runner->runFor('/abs/repo/path', 'b-1.6', 'o3-shop/shop-facts');

        $this->assertSame('/abs/repo/path', $gate->lastRepoPath);
        $this->assertSame('b-1.6', $gate->lastExpectedBranch);
        $this->assertSame('o3-shop/shop-facts', $gate->lastPackageName);
    }
}

/**
 * Test double: returns a pre-supplied outcome and records what
 * inputs it was called with.
 */
final class StubGate implements PreFlightGate
{
    public string $lastRepoPath = '';
    public string $lastExpectedBranch = '';
    public string $lastPackageName = '';
    public bool $wasEvaluated = false;

    private string $name;
    private GateOutcome $outcome;

    public function __construct(string $name, GateOutcome $outcome)
    {
        $this->name = $name;
        $this->outcome = $outcome;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function evaluate(string $repoPath, string $expectedBranch, string $packageName): GateOutcome
    {
        $this->wasEvaluated = true;
        $this->lastRepoPath = $repoPath;
        $this->lastExpectedBranch = $expectedBranch;
        $this->lastPackageName = $packageName;
        return $this->outcome;
    }
}
