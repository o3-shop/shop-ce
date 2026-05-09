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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\GateOutcome;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\UpToDateGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessOutcome;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Flow\FakeProcessExecutor;
use PHPUnit\Framework\TestCase;

final class UpToDateGateTest extends TestCase
{
    public function testInSyncPasses(): void
    {
        $exec = $this->execWithRevList('0	0');
        $gate = new UpToDateGate($exec);
        $outcome = $gate->evaluate('/tmp/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertSame(UpToDateGate::NAME, $outcome->gateName());
        $this->assertSame(GateOutcome::STATUS_PASSED, $outcome->status());
    }

    public function testAheadOnlyWarns(): void
    {
        $exec = $this->execWithRevList("0\t3");
        $gate = new UpToDateGate($exec);
        $outcome = $gate->evaluate('/tmp/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertSame(GateOutcome::STATUS_WARNING, $outcome->status());
        $messages = implode("\n", $outcome->messages());
        $this->assertStringContainsString('3 commits ahead', $messages);
        $this->assertStringContainsString('will ship in this release', $messages);
    }

    public function testBehindOnlyAborts(): void
    {
        $exec = $this->execWithRevList("5\t0");
        $gate = new UpToDateGate($exec);
        $outcome = $gate->evaluate('/tmp/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertSame(GateOutcome::STATUS_ABORT, $outcome->status());
        $messages = implode("\n", $outcome->messages());
        $this->assertStringContainsString('5 commits behind', $messages);
        $this->assertStringContainsString('pull and re-run', $messages);
    }

    public function testDivergedAborts(): void
    {
        $exec = $this->execWithRevList("2\t4");
        $gate = new UpToDateGate($exec);
        $outcome = $gate->evaluate('/tmp/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertSame(GateOutcome::STATUS_ABORT, $outcome->status());
        $messages = implode("\n", $outcome->messages());
        $this->assertStringContainsString('diverged', $messages);
        $this->assertStringContainsString('rebase or merge', $messages);
    }

    public function testFetchFailureAborts(): void
    {
        $exec = new FakeProcessExecutor([
            'git fetch origin b-1.6' => new ProcessOutcome(128, '', "fatal: unable to access 'github.com'\n"),
        ]);
        $gate = new UpToDateGate($exec);
        $outcome = $gate->evaluate('/tmp/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertSame(GateOutcome::STATUS_ABORT, $outcome->status());
        $messages = implode("\n", $outcome->messages());
        $this->assertStringContainsString('git fetch origin b-1.6 failed', $messages);
    }

    public function testRevListFailureAborts(): void
    {
        $exec = new FakeProcessExecutor([
            'git fetch origin b-1.6' => new ProcessOutcome(0, '', ''),
            'git rev-list --left-right --count origin/b-1.6...HEAD'
                => new ProcessOutcome(128, '', "fatal: ambiguous argument\n"),
        ]);
        $gate = new UpToDateGate($exec);
        $outcome = $gate->evaluate('/tmp/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertSame(GateOutcome::STATUS_ABORT, $outcome->status());
        $this->assertStringContainsString('git rev-list', implode("\n", $outcome->messages()));
    }

    public function testMalformedRevListOutputAborts(): void
    {
        $exec = $this->execWithRevList('only-one-token');
        $gate = new UpToDateGate($exec);
        $outcome = $gate->evaluate('/tmp/repo', 'b-1.6', 'o3-shop/shop-ce');

        $this->assertSame(GateOutcome::STATUS_ABORT, $outcome->status());
        $this->assertStringContainsString('unexpected git rev-list output', implode("\n", $outcome->messages()));
    }

    public function testSingularPluralWordingForOneCommitAhead(): void
    {
        $exec = $this->execWithRevList("0\t1");
        $gate = new UpToDateGate($exec);
        $outcome = $gate->evaluate('/tmp/repo', 'b-1.6', 'o3-shop/shop-ce');

        $messages = implode("\n", $outcome->messages());
        $this->assertStringContainsString('1 commit ahead', $messages);
        $this->assertStringNotContainsString('1 commits ahead', $messages);
    }

    private function execWithRevList(string $revListStdout): FakeProcessExecutor
    {
        return new FakeProcessExecutor([
            'git fetch origin b-1.6' => new ProcessOutcome(0, '', ''),
            'git rev-list --left-right --count origin/b-1.6...HEAD'
                => new ProcessOutcome(0, $revListStdout, ''),
        ]);
    }
}
