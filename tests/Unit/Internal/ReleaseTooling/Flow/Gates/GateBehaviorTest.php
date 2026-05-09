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

    public function testWorkingTreeGateAbortsWhenGitStatusFails(): void
    {
        $exec = new FakeProcessExecutor([
            'git status --porcelain' => new ProcessOutcome(128, '', "fatal: not a git repository\n"),
        ]);
        $outcome = (new WorkingTreeGate($exec))->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $this->assertStringContainsString('git status failed', $outcome->messages()[0]);
        $this->assertSame(WorkingTreeGate::NAME, (new WorkingTreeGate($exec))->name());
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

    public function testBranchGateAbortsWhenGitRevParseFails(): void
    {
        $exec = new FakeProcessExecutor([
            'git rev-parse --abbrev-ref HEAD' => new ProcessOutcome(128, '', "fatal: Not a git repository\n"),
        ]);
        $gate = new \OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\BranchGate($exec);
        $outcome = $gate->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $this->assertStringContainsString('git rev-parse failed', $outcome->messages()[0]);
        $this->assertSame('on-release-branch', $gate->name());
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
        $tmp = sys_get_temp_dir() . '/release-cmd-test-' . bin2hex(random_bytes(4));
        mkdir($tmp);
        file_put_contents($tmp . '/composer.lock', '{}');
        try {
            $exec = new FakeProcessExecutor([], new ProcessOutcome(0, 'all good', ''));
            $outcome = (new ComposerInstallGate($exec))->evaluate($tmp, 'b-1.6', 'o3-shop/shop-ce');
            $this->assertTrue($outcome->isPassed());
        } finally {
            @unlink($tmp . '/composer.lock');
            @rmdir($tmp);
        }
    }

    public function testComposerInstallGateSkipsWhenLockfileMissing(): void
    {
        $tmp = sys_get_temp_dir() . '/release-cmd-test-' . bin2hex(random_bytes(4));
        mkdir($tmp);
        try {
            $exec = new FakeProcessExecutor();
            $outcome = (new ComposerInstallGate($exec))->evaluate($tmp, 'b-1.6', 'o3-shop/lib-only');
            $this->assertTrue($outcome->isPassed());
            // No composer command was even invoked
            $this->assertSame([], $exec->commands());
        } finally {
            @rmdir($tmp);
        }
    }

    public function testComposerInstallGateAddsNoAuditFlagWhenSkipAuditTrue(): void
    {
        $tmp = sys_get_temp_dir() . '/release-cmd-test-' . bin2hex(random_bytes(4));
        mkdir($tmp);
        file_put_contents($tmp . '/composer.lock', '{}');
        try {
            $exec = new FakeProcessExecutor([], new ProcessOutcome(0, '', ''));
            $gate = new ComposerInstallGate($exec, 'composer', true);
            $outcome = $gate->evaluate($tmp, 'b-1.6', 'o3-shop/shop-ce');
            $this->assertTrue($outcome->isPassed());
            $cmd = implode(' ', $exec->commands()[0]);
            $this->assertStringContainsString('--no-audit', $cmd);
        } finally {
            @unlink($tmp . '/composer.lock');
            @rmdir($tmp);
        }
    }

    public function testComposerInstallGateDoesNotAddNoAuditByDefault(): void
    {
        $tmp = sys_get_temp_dir() . '/release-cmd-test-' . bin2hex(random_bytes(4));
        mkdir($tmp);
        file_put_contents($tmp . '/composer.lock', '{}');
        try {
            $exec = new FakeProcessExecutor([], new ProcessOutcome(0, '', ''));
            (new ComposerInstallGate($exec))->evaluate($tmp, 'b-1.6', 'o3-shop/shop-ce');
            $cmd = implode(' ', $exec->commands()[0]);
            $this->assertStringNotContainsString('--no-audit', $cmd);
        } finally {
            @unlink($tmp . '/composer.lock');
            @rmdir($tmp);
        }
    }

    public function testComposerInstallGateAbortsOnNonZeroExit(): void
    {
        $tmp = sys_get_temp_dir() . '/release-cmd-test-' . bin2hex(random_bytes(4));
        mkdir($tmp);
        file_put_contents($tmp . '/composer.lock', '{}');
        try {
            $exec = new FakeProcessExecutor([], new ProcessOutcome(2, '', 'Could not resolve constraints'));
            $outcome = (new ComposerInstallGate($exec))->evaluate($tmp, 'b-1.6', 'o3-shop/shop-ce');
            $this->assertTrue($outcome->aborts());
            $this->assertStringContainsString('Could not resolve', $outcome->messages()[0]);
        } finally {
            @unlink($tmp . '/composer.lock');
            @rmdir($tmp);
        }
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

    public function testTestSuiteGateTailTruncatesOver20Lines(): void
    {
        $longOut = implode("\n", array_map(static fn (int $i): string => "out-$i", range(1, 30)));
        $exec = new FakeProcessExecutor([], new ProcessOutcome(1, $longOut, ''));
        $gate = new TestSuiteGate(
            $exec,
            static fn (string $_p, string $_r): array => ['phpunit']
        );
        $outcome = $gate->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertTrue($outcome->aborts());
        $tail = $outcome->messages()[1];
        $this->assertStringContainsString('out-30', $tail, 'last line preserved');
        $this->assertStringNotContainsString('out-1' . "\n", $tail, 'first line dropped');
        $this->assertSame(20, substr_count($tail, "\n") + 1, 'exactly 20 lines retained');
        $this->assertSame('tests-pass', $gate->name());
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

    public function testIncomingPrGateWarnsButProceedsWhenGhFails(): void
    {
        $exec = new FakeProcessExecutor([], new ProcessOutcome(1, '', "could not authenticate\n"));
        $gate = new \OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\Gates\IncomingPrGate($exec);
        $outcome = $gate->evaluate('/repo', 'b-1.6', 'o3-shop/shop-ce');
        $this->assertSame(\OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\GateOutcome::STATUS_WARNING, $outcome->status());
        $this->assertStringContainsString('gh pr list failed', $outcome->messages()[0]);
        $this->assertSame('incoming-prs', $gate->name());
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
