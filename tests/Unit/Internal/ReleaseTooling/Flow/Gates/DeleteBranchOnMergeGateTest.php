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
        $exec = new FakeProcessExecutor([self::CMD => new ProcessOutcome(1, '', "could not authenticate\n")]);
        $outcome = (new DeleteBranchOnMergeGate($exec))->evaluate('', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $this->assertStringContainsString('gh api failed', $outcome->messages()[0]);
        $this->assertCount(1, $exec->commands());
    }

    public function testFailsClosedOnUnexpectedOutput(): void
    {
        $exec = new FakeProcessExecutor([self::CMD => new ProcessOutcome(0, "null\n", '')]);
        $outcome = (new DeleteBranchOnMergeGate($exec))->evaluate('', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $this->assertStringContainsString('unexpected value', $outcome->messages()[0]);
    }

    /**
     * A repo released from `main` has no separate maintenance line, so
     * no merge-back PR can exist and auto-delete cannot reach a release
     * branch. Aborting there would let a benign setting on any theme or
     * satellite repo block the whole release.
     */
    public function testSkipsRepoReleasedFromMainWithoutQueryingGitHub(): void
    {
        $exec = new FakeProcessExecutor([]);
        $outcome = (new DeleteBranchOnMergeGate($exec))->evaluate('', 'main', 'o3-shop/wave-theme');
        $this->assertTrue($outcome->isPassed());
        $this->assertSame([], $exec->commands(), 'Skipped repos must not cost a gh round-trip.');
    }

    public function testStillAbortsForMaintenanceLineRepoEvenWhenNamedLikeMain(): void
    {
        $cmd = 'gh api repos/o3-shop/smarty --jq .delete_branch_on_merge';
        $exec = new FakeProcessExecutor([$cmd => new ProcessOutcome(0, "true\n", '')]);
        $outcome = (new DeleteBranchOnMergeGate($exec))->evaluate('', 'support/2.6', 'o3-shop/smarty');
        $this->assertTrue($outcome->aborts());
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
