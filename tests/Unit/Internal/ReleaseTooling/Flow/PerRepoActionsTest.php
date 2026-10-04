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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PerRepoActions;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessOutcome;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PerRepoActionsTest extends TestCase
{
    /* ---------- 10.8 — commit + push ---------- */

    public function testCommitChangesAndPushIssuesGitAddCommitPushSequence(): void
    {
        $exec = new FakeProcessExecutor();  // all commands succeed by default
        $actions = new PerRepoActions($exec);

        $actions->commitChangesAndPush(
            '/repo/path',
            'b-1.6',
            ['composer.json'],
            false,
            'chore: bump shop-ce to v1.6.1-RC1'
        );

        $this->assertEquals([
            ['git', 'add', 'composer.json'],
            ['git', 'commit', '-m', 'chore: bump shop-ce to v1.6.1-RC1'],
            ['git', 'push', 'origin', 'b-1.6'],
        ], $exec->commands());
    }

    public function testCommitChangesAndPushDeletesNextBumpFileWhenRequested(): void
    {
        $exec = new FakeProcessExecutor();
        $actions = new PerRepoActions($exec);

        $actions->commitChangesAndPush(
            '/repo/path',
            'b-1.6',
            ['composer.json'],
            true, // deleteNextBump
            'release v1.0.5'
        );

        $commands = $exec->commands();
        $this->assertSame(['git', 'rm', '--ignore-unmatch', '.next-bump'], $commands[0]);
        $this->assertSame(['git', 'add', 'composer.json'], $commands[1]);
        $this->assertSame(['git', 'commit', '-m', 'release v1.0.5'], $commands[2]);
        $this->assertSame(['git', 'push', 'origin', 'b-1.6'], $commands[3]);
    }

    public function testCommitChangesAndPushSkipsAddWhenStagePathsEmpty(): void
    {
        $exec = new FakeProcessExecutor();
        $actions = new PerRepoActions($exec);

        // unchanged candidate triggers no constraint changes; only .next-bump
        // deletion + commit/push
        $actions->commitChangesAndPush('/repo/path', 'b-1.6', [], true, 'consume .next-bump');

        $commands = $exec->commands();
        $this->assertSame(['git', 'rm', '--ignore-unmatch', '.next-bump'], $commands[0]);
        $this->assertSame(['git', 'commit', '-m', 'consume .next-bump'], $commands[1]);
        $this->assertSame(['git', 'push', 'origin', 'b-1.6'], $commands[2]);
        $this->assertCount(3, $commands);
    }

    public function testCommitChangesAndPushBubblesFailureFromAnyStep(): void
    {
        $exec = new FakeProcessExecutor([
            'git push origin b-1.6' => new ProcessOutcome(1, '', 'rejected: non-fast-forward'),
        ]);
        $actions = new PerRepoActions($exec);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/git push origin b-1.6 failed/');
        $actions->commitChangesAndPush('/repo', 'b-1.6', ['composer.json'], false, 'msg');
    }

    /* ---------- 10.9 — tag ---------- */

    public function testCreateTagIssuesAnnotatedTagAndPush(): void
    {
        $exec = new FakeProcessExecutor();
        $actions = new PerRepoActions($exec);

        $actions->createTag('/repo', 'v1.6.1-RC1');

        $this->assertEquals([
            ['git', 'tag', '-a', 'v1.6.1-RC1', '-m', 'Release v1.6.1-RC1'],
            ['git', 'push', 'origin', 'v1.6.1-RC1'],
        ], $exec->commands());
    }

    public function testCreateTagAcceptsCustomMessage(): void
    {
        $exec = new FakeProcessExecutor();
        (new PerRepoActions($exec))->createTag('/repo', 'v1.0.5', 'patch: hotfix bundle');

        $this->assertEquals([
            ['git', 'tag', '-a', 'v1.0.5', '-m', 'patch: hotfix bundle'],
            ['git', 'push', 'origin', 'v1.0.5'],
        ], $exec->commands());
    }

    /* ---------- 10.10 — draft release ---------- */

    public function testCreateDraftReleaseUsesGenerateNotesByDefault(): void
    {
        $exec = new FakeProcessExecutor([
            'gh release create v1.6.1-RC1 --repo o3-shop/shop-ce --draft --title v1.6.1-RC1 --generate-notes'
                => new ProcessOutcome(0, "https://github.com/o3-shop/shop-ce/releases/tag/v1.6.1-RC1\n", ''),
        ]);
        $actions = new PerRepoActions($exec);
        $url = $actions->createDraftRelease('o3-shop/shop-ce', 'v1.6.1-RC1');
        $this->assertSame('https://github.com/o3-shop/shop-ce/releases/tag/v1.6.1-RC1', $url);
    }

    public function testCreateDraftReleaseUsesBodyOverrideWhenProvided(): void
    {
        $body = "## o3-shop/shop-ce\n\n## Unchanged in this release\n\n- foo\n";
        $exec = new FakeProcessExecutor();
        (new PerRepoActions($exec))->createDraftRelease('o3-shop/o3-shop', 'v1.6.1-RC1', $body);

        $cmd = $exec->commands()[0];
        $notesIdx = array_search('--notes', $cmd, true);
        $this->assertIsInt($notesIdx, '--notes flag missing from command');
        $this->assertSame($body, $cmd[$notesIdx + 1]);
        $this->assertNotContains('--generate-notes', $cmd);
    }

    public function testCreateDraftReleaseThrowsOnGhFailure(): void
    {
        $exec = new FakeProcessExecutor([], new ProcessOutcome(1, '', 'gh: API error'));
        $actions = new PerRepoActions($exec);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/gh release create failed/');
        $actions->createDraftRelease('o3-shop/shop-ce', 'v1.6.1-RC1');
    }

    /* ---------- 10.11 — auto-merge-back PR ---------- */

    private const TAG_SHA = '1111111111111111111111111111111111111111';

    public function testOpenMergeBackPrPushesBranchAtTagAndOpensPrFromIt(): void
    {
        $exec = new FakeProcessExecutor($this->mergeBackResponses(''), $this->prCreated());
        $actions = new PerRepoActions($exec);
        $url = $actions->openMergeBackPr('o3-shop/shop-ce', '/repo', 'v1.6.1', 'v1.6.1');

        $commands = $exec->commands();
        $this->assertSame(['git', 'rev-parse', '--verify', 'v1.6.1^{commit}'], $commands[0]);
        $this->assertSame(['git', 'ls-remote', '--heads', 'origin', 'refs/heads/merge-back-v1.6.1'], $commands[1]);
        $this->assertSame(
            ['git', 'push', 'origin', self::TAG_SHA . ':refs/heads/merge-back-v1.6.1'],
            $commands[2]
        );
        $this->assertSame('/repo', $exec->calls[2]['cwd']);

        $cmd = $commands[3];
        $this->assertSame('gh', $cmd[0]);
        $this->assertSame(['pr', 'create'], [$cmd[1], $cmd[2]]);
        $this->assertSame('o3-shop/shop-ce', $this->optionValue($cmd, '--repo'));
        $this->assertSame('main', $this->optionValue($cmd, '--base'));
        $this->assertSame('merge-back-v1.6.1', $this->optionValue($cmd, '--head'));
        $this->assertSame('Merge v1.6.1 release into main', $this->optionValue($cmd, '--title'));
        $this->assertStringContainsString('Create a merge commit', $this->optionValue($cmd, '--body'));
        $this->assertSame('https://github.com/o3-shop/shop-ce/pull/123', $url);
    }

    public function testOpenMergeBackPrUsesPackageTagForBranchAndShopVersionForTitle(): void
    {
        $exec = new FakeProcessExecutor($this->mergeBackResponses('', 'v1.0.3'), $this->prCreated());
        $actions = new PerRepoActions($exec);
        $actions->openMergeBackPr('o3-shop/shop-doctrine-migration-wrapper', '/repo', 'v1.0.3', 'v1.6.1');

        $cmd = $exec->commands()[3];
        $this->assertSame('merge-back-v1.0.3', $this->optionValue($cmd, '--head'));
        $this->assertSame('Merge v1.6.1 release into main', $this->optionValue($cmd, '--title'));
    }

    public function testOpenMergeBackPrReusesExistingBranchThatPointsAtTag(): void
    {
        $exec = new FakeProcessExecutor($this->mergeBackResponses(
            self::TAG_SHA . "\trefs/heads/merge-back-v1.6.1\n"
        ), $this->prCreated());
        $actions = new PerRepoActions($exec);
        $actions->openMergeBackPr('o3-shop/shop-ce', '/repo', 'v1.6.1', 'v1.6.1');

        $commands = $exec->commands();
        $this->assertCount(3, $commands, 'rev-parse, ls-remote, gh pr create — no push');
        $this->assertSame('gh', $commands[2][0]);
    }

    public function testOpenMergeBackPrRefusesExistingBranchThatDoesNotPointAtTag(): void
    {
        $exec = new FakeProcessExecutor($this->mergeBackResponses(
            "2222222222222222222222222222222222222222\trefs/heads/merge-back-v1.6.1\n"
        ), $this->prCreated());
        $actions = new PerRepoActions($exec);

        $thrown = null;
        try {
            $actions->openMergeBackPr('o3-shop/shop-ce', '/repo', 'v1.6.1', 'v1.6.1');
        } catch (RuntimeException $e) {
            $thrown = $e;
        }
        $this->assertNotNull($thrown, 'expected RuntimeException');
        $this->assertStringContainsString('merge-back-v1.6.1', $thrown->getMessage());
        $this->assertStringContainsString('does not point at v1.6.1', $thrown->getMessage());
        $this->assertCount(2, $exec->commands(), 'nothing pushed, no PR opened');
    }

    public function testOpenMergeBackPrThrowsWhenTagIsUnknown(): void
    {
        $exec = new FakeProcessExecutor([
            'git rev-parse --verify v1.6.1^{commit}' => new ProcessOutcome(128, '', 'fatal: Needed a single revision'),
        ]);
        $actions = new PerRepoActions($exec);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/git rev-parse --verify v1\.6\.1\^\{commit\} failed/');
        $actions->openMergeBackPr('o3-shop/shop-ce', '/repo', 'v1.6.1', 'v1.6.1');
    }

    public function testOpenMergeBackPrThrowsOnGhFailure(): void
    {
        $exec = new FakeProcessExecutor($this->mergeBackResponses(''), new ProcessOutcome(1, '', 'gh: API error'));
        $actions = new PerRepoActions($exec);
        $this->expectException(RuntimeException::class);
        // The branch is already pushed at this point; name it so it can be finished by hand.
        $this->expectExceptionMessageMatches('/gh pr create failed.*Branch merge-back-v1\.6\.1 is on origin at v1\.6\.1/');
        $actions->openMergeBackPr('o3-shop/shop-ce', '/repo', 'v1.6.1', 'v1.6.1');
    }

    public function testOpenMergeBackPrIgnoresRemoteRefThatOnlyEndsWithBranchName(): void
    {
        // ls-remote matches its pattern as a path suffix.
        $exec = new FakeProcessExecutor($this->mergeBackResponses(
            "2222222222222222222222222222222222222222\trefs/heads/x/refs/heads/merge-back-v1.6.1\n"
        ), $this->prCreated());
        $actions = new PerRepoActions($exec);
        $actions->openMergeBackPr('o3-shop/shop-ce', '/repo', 'v1.6.1', 'v1.6.1');

        $this->assertSame(
            ['git', 'push', 'origin', self::TAG_SHA . ':refs/heads/merge-back-v1.6.1'],
            $exec->commands()[2],
            'the look-alike ref is not the branch: push it'
        );
    }

    /**
     * Canned outcomes for the git half of openMergeBackPr(). The gh call
     * falls through to the executor's default outcome.
     *
     * @return array<string,ProcessOutcome>
     */
    private function mergeBackResponses(string $lsRemoteStdout, string $tag = 'v1.6.1'): array
    {
        $branch = 'merge-back-' . $tag;
        return [
            "git rev-parse --verify {$tag}^{commit}" => new ProcessOutcome(0, self::TAG_SHA . "\n", ''),
            "git ls-remote --heads origin refs/heads/{$branch}" => new ProcessOutcome(0, $lsRemoteStdout, ''),
            'git push origin ' . self::TAG_SHA . ":refs/heads/{$branch}" => new ProcessOutcome(0, '', ''),
        ];
    }

    private function prCreated(): ProcessOutcome
    {
        return new ProcessOutcome(0, "https://github.com/o3-shop/shop-ce/pull/123\n", '');
    }

    /** @param array<int,string> $cmd */
    private function optionValue(array $cmd, string $option): string
    {
        $idx = array_search($option, $cmd, true);
        $this->assertNotFalse($idx, "option {$option} missing");
        return $cmd[$idx + 1];
    }
}
