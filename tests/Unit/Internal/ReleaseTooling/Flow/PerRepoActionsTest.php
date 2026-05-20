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

    public function testOpenMergeBackPrIssuesGhPrCreateWithCanonicalTitle(): void
    {
        $exec = new FakeProcessExecutor([], new ProcessOutcome(0, "https://github.com/o3-shop/shop-ce/pull/123\n", ''));
        $actions = new PerRepoActions($exec);
        $url = $actions->openMergeBackPr('o3-shop/shop-ce', 'b-1.6', 'v1.6.1');

        $cmd = $exec->commands()[0];
        $this->assertSame('gh', $cmd[0]);
        $this->assertSame(['pr', 'create'], [$cmd[1], $cmd[2]]);
        $this->assertContains('--repo', $cmd);
        $this->assertContains('o3-shop/shop-ce', $cmd);
        $this->assertContains('--base', $cmd);
        $this->assertContains('main', $cmd);
        $this->assertContains('--head', $cmd);
        $this->assertContains('b-1.6', $cmd);
        $this->assertContains('--title', $cmd);
        $this->assertContains('Merge v1.6.1 release into main', $cmd);
        $this->assertSame('https://github.com/o3-shop/shop-ce/pull/123', $url);
    }

    public function testOpenMergeBackPrThrowsOnGhFailure(): void
    {
        $exec = new FakeProcessExecutor([], new ProcessOutcome(1, '', 'gh: branch not pushed'));
        $actions = new PerRepoActions($exec);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/gh pr create failed/');
        $actions->openMergeBackPr('o3-shop/shop-ce', 'b-1.6', 'v1.6.1');
    }
}
