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
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\BranchGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\ComposerInstallGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\IncomingPrGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\MergeBackPrGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\TestSuiteGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\WorkingTreeGate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessOutcome;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ReleaseTooling\Flow\FakeProcessExecutor;
use PHPUnit\Framework\TestCase;

class GateBehaviorTest extends TestCase
{
    /* ---------- 10.1 — WorkingTreeGate ---------- */

    public function testWorkingTreeGatePassesWhenStatusEmpty(): void
    {
        $exec = new FakeProcessExecutor([
            'git status --porcelain' => new ProcessOutcome(0, '', ''),
        ]);
        $outcome = (new WorkingTreeGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->isPassed());
    }

    public function testWorkingTreeGateAbortsOnDirtyTree(): void
    {
        $exec = new FakeProcessExecutor([
            'git status --porcelain' => new ProcessOutcome(0, " M foo.php\n?? bar.txt\n", ''),
        ]);
        $outcome = (new WorkingTreeGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $this->assertStringContainsString('uncommitted changes', $outcome->messages()[0]);
    }

    /* ---------- 10.2 — BranchGate ---------- */

    public function testBranchGatePassesWhenOnExpectedBranch(): void
    {
        $exec = new FakeProcessExecutor([
            'git rev-parse --abbrev-ref HEAD' => new ProcessOutcome(0, "b-1.6\n", ''),
        ]);
        $outcome = (new BranchGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->isPassed());
    }

    public function testBranchGateAbortsWhenOnDifferentBranch(): void
    {
        $exec = new FakeProcessExecutor([
            'git rev-parse --abbrev-ref HEAD' => new ProcessOutcome(0, "feature/foo\n", ''),
        ]);
        $outcome = (new BranchGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $this->assertStringContainsString("'feature/foo'", $outcome->messages()[0]);
        $this->assertStringContainsString("'b-1.6'", $outcome->messages()[0]);
    }

    /* ---------- 10.3 — ComposerInstallGate ---------- */

    public function testComposerInstallGatePassesOnZeroExit(): void
    {
        $exec = new FakeProcessExecutor([
            'composer install --dry-run --no-scripts --no-interaction'
                => new ProcessOutcome(0, 'all good', ''),
        ]);
        $outcome = (new ComposerInstallGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->isPassed());
    }

    public function testComposerInstallGateAbortsOnNonZeroExit(): void
    {
        $exec = new FakeProcessExecutor([
            'composer install --dry-run --no-scripts --no-interaction'
                => new ProcessOutcome(2, '', 'Could not resolve constraints'),
        ]);
        $outcome = (new ComposerInstallGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $this->assertStringContainsString('Could not resolve', $outcome->messages()[0]);
    }

    /* ---------- 10.4 — TestSuiteGate ---------- */

    public function testTestSuiteGateSkipsWhenResolverReturnsNull(): void
    {
        $exec = new FakeProcessExecutor();
        $resolver = static fn (string $pkg, string $path): ?array => null;
        $outcome = (new TestSuiteGate($exec, $resolver))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->isPassed());
        $this->assertSame([], $exec->commands());
    }

    public function testTestSuiteGatePassesWhenCommandSucceeds(): void
    {
        $exec = new FakeProcessExecutor([
            './run-tests' => new ProcessOutcome(0, '10 tests passed', ''),
        ]);
        $resolver = static fn (string $pkg, string $path): array => ['./run-tests'];
        $outcome = (new TestSuiteGate($exec, $resolver))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->isPassed());
    }

    public function testTestSuiteGateAbortsAndIncludesTailOnFailure(): void
    {
        $tail = "FAILURES!\nERROR: 1 failed\n";
        $exec = new FakeProcessExecutor([
            './run-tests' => new ProcessOutcome(1, $tail, ''),
        ]);
        $resolver = static fn (string $pkg, string $path): array => ['./run-tests'];
        $outcome = (new TestSuiteGate($exec, $resolver))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $messages = implode("\n", $outcome->messages());
        $this->assertStringContainsString('FAILURES!', $messages);
    }

    /* ---------- 10.5 — IncomingPrGate ---------- */

    public function testIncomingPrGatePassesWhenNoOpenPrs(): void
    {
        $cmd = 'gh pr list --repo o3-shop/shop-ce --state open --base b-1.6 --json number,title,url --limit 100';
        $exec = new FakeProcessExecutor([
            $cmd => new ProcessOutcome(0, "[]\n", ''),
        ]);
        $outcome = (new IncomingPrGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->isPassed());
    }

    public function testIncomingPrGateWarnsButProceedsWhenPrsExist(): void
    {
        $body = json_encode([
            ['number' => 99, 'title' => 'feat: thing', 'url' => 'https://github.com/o3-shop/shop-ce/pull/99'],
        ]);
        $cmd = 'gh pr list --repo o3-shop/shop-ce --state open --base b-1.6 --json number,title,url --limit 100';
        $exec = new FakeProcessExecutor([
            $cmd => new ProcessOutcome(0, $body, ''),
        ]);
        $outcome = (new IncomingPrGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertSame(GateOutcome::STATUS_WARNING, $outcome->status());
        $messages = implode("\n", $outcome->messages());
        $this->assertStringContainsString('#99', $messages);
        $this->assertStringContainsString('feat: thing', $messages);
    }

    /* ---------- 10.6 — MergeBackPrGate ---------- */

    public function testMergeBackPrGatePassesWhenNoOpenPrs(): void
    {
        $cmd = 'gh pr list --repo o3-shop/shop-ce --state open --base main --head b-1.6 --json number,title,url --limit 50';
        $exec = new FakeProcessExecutor([
            $cmd => new ProcessOutcome(0, "[]\n", ''),
        ]);
        $outcome = (new MergeBackPrGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->isPassed());
    }

    public function testMergeBackPrGatePassesWhenOpenPrsDoNotMatchTitlePattern(): void
    {
        $body = json_encode([
            ['number' => 50, 'title' => 'docs: fix typo', 'url' => 'https://github.com/o3-shop/shop-ce/pull/50'],
        ]);
        $cmd = 'gh pr list --repo o3-shop/shop-ce --state open --base main --head b-1.6 --json number,title,url --limit 50';
        $exec = new FakeProcessExecutor([
            $cmd => new ProcessOutcome(0, $body, ''),
        ]);
        $outcome = (new MergeBackPrGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->isPassed());
    }

    public function testMergeBackPrGateAbortsWhenCanonicalTitleMatches(): void
    {
        $body = json_encode([
            [
                'number' => 42,
                'title' => 'Merge v1.6.0 release into main',
                'url' => 'https://github.com/o3-shop/shop-ce/pull/42',
            ],
        ]);
        $cmd = 'gh pr list --repo o3-shop/shop-ce --state open --base main --head b-1.6 --json number,title,url --limit 50';
        $exec = new FakeProcessExecutor([
            $cmd => new ProcessOutcome(0, $body, ''),
        ]);
        $outcome = (new MergeBackPrGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $messages = implode("\n", $outcome->messages());
        $this->assertStringContainsString('#42', $messages);
        $this->assertStringContainsString('v1.6.0', $messages);
    }

    public function testMergeBackPrGateAbortsWhenGhFails(): void
    {
        $cmd = 'gh pr list --repo o3-shop/shop-ce --state open --base main --head b-1.6 --json number,title,url --limit 50';
        $exec = new FakeProcessExecutor([
            $cmd => new ProcessOutcome(1, '', 'gh: not authenticated'),
        ]);
        $outcome = (new MergeBackPrGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $this->assertStringContainsString('cannot verify', $outcome->messages()[0]);
    }
}
