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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Constraint\ConstraintUpdate;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ComposerJsonConstraintWriter;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\LiveExecutor;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\PerRepoActions;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessOutcome;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\CandidatePlan;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ConstraintEditPlan;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\DefaultBranchResolver;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ReleasePlan;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Snapshot\FromSnapshot;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Tag\TagCutResult;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Version\VersionResolution;
use PHPUnit\Framework\TestCase;

/**
 * Tests for §15 — live execution orchestration.
 *
 * Each test builds a small ReleasePlan, two on-disk fake repo dirs
 * with a real composer.json (so the writer has something to mutate),
 * and lets LiveExecutor run end-to-end with a FakeProcessExecutor
 * recording every gh / git invocation.
 */
final class LiveExecutorTest extends TestCase
{
    private const TAG_SHA = '1111111111111111111111111111111111111111';

    /** @var array<int,string> dirs created during the test, cleaned up in tearDown */
    private array $tmpDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpDirs as $dir) {
            $this->rmrf($dir);
        }
        $this->tmpDirs = [];
    }

    public function testCandidateThenOrchestratorIsTaggedInOrderAndDraftReleaseCreated(): void
    {
        $shopCePath = $this->mkRepo('{"require":{"o3-shop/shop-doctrine-migration-wrapper":"v1.0.2"}}');
        $o3ShopPath = $this->mkRepo('{"require":{"o3-shop/shop-ce":"v1.6.0"}}');
        $exec = new FakeProcessExecutor([], new ProcessOutcome(0, 'https://github.com/o3-shop/X/releases/tag/Y', ''));

        $plan = $this->buildPlan(
            'v1.6.0',
            'v1.6.1-RC1',
            [
                $this->cuttingCandidate('o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
            ],
            [
                $this->edit('o3-shop/shop-ce', 'require', 'o3-shop/shop-doctrine-migration-wrapper', 'v1.0.2', 'v1.0.3'),
                $this->edit('o3-shop/o3-shop', 'require', 'o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
            ],
            'AGGREGATED-NOTES-BODY'
        );

        $executor = new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver()
        );
        $executor->execute($plan, [
            'o3-shop/shop-ce' => $shopCePath,
            'o3-shop/o3-shop' => $o3ShopPath,
        ]);

        $commands = $this->cmdStrings($exec);
        // Order: shop-ce gets touched (commit + tag + push + release) BEFORE o3-shop.
        $shopCeCommitIdx = $this->indexOfFirst($commands, 'git commit -m');
        $shopCeReleaseIdx = $this->indexOfNth($commands, 'gh release create v1.6.1-RC1', 1);
        $o3ShopCommitIdx = $this->indexOfNth($commands, 'git commit -m', 2);
        $o3ShopReleaseIdx = $this->indexOfNth($commands, 'gh release create v1.6.1-RC1', 2);

        $this->assertGreaterThan(-1, $shopCeCommitIdx);
        $this->assertGreaterThan($shopCeCommitIdx, $shopCeReleaseIdx);
        $this->assertGreaterThan($shopCeReleaseIdx, $o3ShopCommitIdx);
        $this->assertGreaterThan($o3ShopCommitIdx, $o3ShopReleaseIdx);

        // o3-shop release uses --notes (override) with the aggregated body
        $aggregatedReleaseCall = $this->callAt($exec, $o3ShopReleaseIdx);
        $this->assertContains('--notes', $aggregatedReleaseCall['command']);
        $notesArgIdx = array_search('--notes', $aggregatedReleaseCall['command'], true);
        $this->assertSame('AGGREGATED-NOTES-BODY', $aggregatedReleaseCall['command'][$notesArgIdx + 1]);

        // shop-ce release uses --generate-notes (no override)
        $shopCeReleaseCall = $this->callAt($exec, $shopCeReleaseIdx);
        $this->assertContains('--generate-notes', $shopCeReleaseCall['command']);

        // composer.json constraint actually got rewritten on disk.
        // Fixtures use compact JSON ("key":"val") and the writer
        // preserves whatever whitespace is around the colon, so we
        // assert without leading space to match the fixture format.
        $this->assertStringContainsString('"o3-shop/shop-doctrine-migration-wrapper":"v1.0.3"', file_get_contents($shopCePath . '/composer.json'));
        $this->assertStringContainsString('"o3-shop/shop-ce":"v1.6.1-RC1"', file_get_contents($o3ShopPath . '/composer.json'));

        // Release URLs captured for partial-state recovery
        $this->assertSame([
            'o3-shop/shop-ce' => 'https://github.com/o3-shop/X/releases/tag/Y',
            'o3-shop/o3-shop' => 'https://github.com/o3-shop/X/releases/tag/Y',
        ], $executor->releaseUrls());
    }

    public function testRcShopToDoesNotOpenMergeBackPrs(): void
    {
        $shopCePath = $this->mkRepo('{"require":{}}');
        $o3ShopPath = $this->mkRepo('{"require":{}}');
        $exec = new FakeProcessExecutor([], new ProcessOutcome(0, 'https://example.invalid/draft', ''));

        $plan = $this->buildPlan(
            'v1.6.0',
            'v1.6.1-RC1',
            [
                $this->cuttingCandidate('o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
            ],
            [],
            ''
        );

        $executor = new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver()
        );
        $executor->execute($plan, [
            'o3-shop/shop-ce' => $shopCePath,
            'o3-shop/o3-shop' => $o3ShopPath,
        ]);

        $commands = $this->cmdStrings($exec);
        foreach ($commands as $cmd) {
            $this->assertStringNotContainsString('gh pr create', $cmd, 'no merge-back PR for RC');
        }
        $this->assertSame([], $executor->mergeBackUrls());
    }

    public function testFinalShopToOpensMergeBackPrForCandidatesAndOrchestrator(): void
    {
        $shopCePath = $this->mkRepo('{"require":{}}');
        $o3ShopPath = $this->mkRepo('{"require":{}}');
        $exec = new FakeProcessExecutor(
            $this->mergeBackGitResponses(['v1.6.1']),
            new ProcessOutcome(0, 'https://example.invalid/url', '')
        );

        $plan = $this->buildPlan(
            'v1.6.0',
            'v1.6.1',
            [
                $this->cuttingCandidate('o3-shop/shop-ce', 'v1.6.0', 'v1.6.1'),
            ],
            [],
            ''
        );

        $executor = new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver()
        );
        $executor->execute($plan, [
            'o3-shop/shop-ce' => $shopCePath,
            'o3-shop/o3-shop' => $o3ShopPath,
        ]);

        $prCalls = $this->prCreateCalls($exec);
        // One per candidate + one for o3-shop = 2
        $this->assertCount(2, $prCalls);
        $repoSlugs = array_map(fn (array $cmd): string => $this->optionValue($cmd, '--repo'), $prCalls);
        $this->assertContains('o3-shop/shop-ce', $repoSlugs);
        $this->assertContains('o3-shop/o3-shop', $repoSlugs);
        $this->assertCount(2, $executor->mergeBackUrls());

        // The PR head is a branch pinned to the tag, never the moving release branch.
        foreach ($prCalls as $cmd) {
            $this->assertSame('merge-back-v1.6.1', $this->optionValue($cmd, '--head'));
        }
        $pushes = array_values(array_filter(
            $this->cmdStrings($exec),
            static fn (string $cmd): bool => $cmd === 'git push origin ' . self::TAG_SHA . ':refs/heads/merge-back-v1.6.1'
        ));
        $this->assertCount(2, $pushes, 'one tag-pinned branch pushed per repo');
    }

    public function testFinalShopToMergeBackUsesEachCandidatesOwnTag(): void
    {
        $wrapperPath = $this->mkRepo('{"require":{}}');
        $o3ShopPath = $this->mkRepo('{"require":{}}');
        $exec = new FakeProcessExecutor(
            $this->mergeBackGitResponses(['v1.0.3', 'v1.6.1']),
            new ProcessOutcome(0, 'https://example.invalid/url', '')
        );

        $plan = $this->buildPlan(
            'v1.6.0',
            'v1.6.1',
            [
                $this->cuttingCandidate('o3-shop/shop-doctrine-migration-wrapper', 'v1.0.2', 'v1.0.3'),
            ],
            [],
            ''
        );

        $executor = new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver()
        );
        $executor->execute($plan, [
            'o3-shop/shop-doctrine-migration-wrapper' => $wrapperPath,
            'o3-shop/o3-shop' => $o3ShopPath,
        ]);

        $headsByRepo = [];
        foreach ($this->prCreateCalls($exec) as $cmd) {
            $headsByRepo[$this->optionValue($cmd, '--repo')] = $this->optionValue($cmd, '--head');
            $this->assertSame('Merge v1.6.1 release into main', $this->optionValue($cmd, '--title'));
        }
        $this->assertSame([
            'o3-shop/shop-doctrine-migration-wrapper' => 'merge-back-v1.0.3',
            'o3-shop/o3-shop' => 'merge-back-v1.6.1',
        ], $headsByRepo);

        // Each repo's tag is resolved and pushed in that repo's own clone:
        // the same tag name can exist in several repos at different commits.
        $cwdByCommand = [];
        foreach ($exec->calls as $call) {
            $cwdByCommand[implode(' ', $call['command'])] = $call['cwd'];
        }
        $this->assertSame($wrapperPath, $cwdByCommand['git rev-parse --verify v1.0.3^{commit}']);
        $this->assertSame($wrapperPath, $cwdByCommand['git push origin ' . self::TAG_SHA . ':refs/heads/merge-back-v1.0.3']);
        $this->assertSame($o3ShopPath, $cwdByCommand['git rev-parse --verify v1.6.1^{commit}']);
        $this->assertSame($o3ShopPath, $cwdByCommand['git push origin ' . self::TAG_SHA . ':refs/heads/merge-back-v1.6.1']);
    }

    public function testFailedMergeBackDoesNotStopTheRemainingRepos(): void
    {
        $wrapperPath = $this->mkRepo('{"require":{}}');
        $o3ShopPath = $this->mkRepo('{"require":{}}');
        $responses = $this->mergeBackGitResponses(['v1.0.3', 'v1.6.1']);
        $responses['git rev-parse --verify v1.0.3^{commit}'] = new ProcessOutcome(128, '', 'fatal: Needed a single revision');
        $exec = new FakeProcessExecutor($responses, new ProcessOutcome(0, 'https://example.invalid/url', ''));

        $plan = $this->buildPlan(
            'v1.6.0',
            'v1.6.1',
            [
                $this->cuttingCandidate('o3-shop/shop-doctrine-migration-wrapper', 'v1.0.2', 'v1.0.3'),
            ],
            [],
            ''
        );

        $executor = new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver()
        );

        $thrown = null;
        try {
            $executor->execute($plan, [
                'o3-shop/shop-doctrine-migration-wrapper' => $wrapperPath,
                'o3-shop/o3-shop' => $o3ShopPath,
            ]);
        } catch (\RuntimeException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'the failed merge-back is still reported');
        $this->assertStringStartsWith('1 merge-back PR(s) failed. For each: fix the cause, push merge-back-<tag>', $thrown->getMessage());
        $this->assertStringContainsString('o3-shop/shop-doctrine-migration-wrapper (v1.0.3)', $thrown->getMessage());
        $this->assertStringContainsString('git rev-parse --verify v1.0.3^{commit} failed', $thrown->getMessage());
        $this->assertNotNull($thrown->getPrevious(), 'original failure is chained');
        $this->assertStringContainsString('git rev-parse --verify v1.0.3^{commit} failed', $thrown->getPrevious()->getMessage());
        $this->assertSame(['o3-shop/o3-shop'], array_keys($executor->mergeBackUrls()), 'o3-shop still gets its PR');
    }

    public function testFinalShopToSkipsMergeBackForMainLineRepos(): void
    {
        $themePath = $this->mkRepo('{"require":{}}');
        $o3ShopPath = $this->mkRepo('{"require":{}}');
        $exec = new FakeProcessExecutor(
            $this->mergeBackGitResponses(['v1.6.1']),
            new ProcessOutcome(0, 'https://example.invalid/url', '')
        );

        $plan = $this->buildPlan(
            'v1.6.0',
            'v1.6.1',
            [
                // o3-theme releases straight from main: nothing to merge back.
                $this->cuttingCandidate('o3-shop/o3-theme', 'v1.3.0', 'v1.3.1'),
            ],
            [],
            ''
        );

        $executor = new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver()
        );
        $executor->execute($plan, [
            'o3-shop/o3-theme' => $themePath,
            'o3-shop/o3-shop' => $o3ShopPath,
        ]);

        $repoSlugs = array_map(fn (array $cmd): string => $this->optionValue($cmd, '--repo'), $this->prCreateCalls($exec));
        $this->assertSame(['o3-shop/o3-shop'], $repoSlugs);
        $this->assertSame(['o3-shop/o3-shop'], array_keys($executor->mergeBackUrls()));
        foreach ($this->cmdStrings($exec) as $cmd) {
            $this->assertStringNotContainsString('merge-back-v1.3.1', $cmd);
        }
    }

    public function testMissingRepoPathForCandidateThrows(): void
    {
        $exec = new FakeProcessExecutor();
        $plan = $this->buildPlan(
            'v1.6.0',
            'v1.6.1-RC1',
            [
                $this->cuttingCandidate('o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
            ],
            [],
            ''
        );

        $executor = new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no local repo path supplied for o3-shop/shop-ce');
        // No repo paths supplied
        $executor->execute($plan, []);
    }

    public function testNextBumpDeletionIsTriggeredFromCandidatesTagCutResult(): void
    {
        $shopFactsPath = $this->mkRepo('{"require":{}}');
        $o3ShopPath = $this->mkRepo('{"require":{}}');
        $exec = new FakeProcessExecutor([], new ProcessOutcome(0, 'https://example.invalid/url', ''));

        $candidate = new CandidatePlan(
            'o3-shop/shop-facts',
            'v1.0.4',
            'v1.0.5',
            new VersionResolution('o3-shop/shop-facts', VersionResolution::CASE_NEEDS_NEW_TAG, null, [], 'v1.0.4'),
            new TagCutResult('v1.0.5', /* deleteNextBump = */ true, TagCutResult::SOURCE_NEXT_BUMP_FILE)
        );
        $plan = $this->buildPlan('v1.6.0', 'v1.6.1-RC1', [$candidate], [], '');

        $executor = new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver()
        );
        $executor->execute($plan, [
            'o3-shop/shop-facts' => $shopFactsPath,
            'o3-shop/o3-shop' => $o3ShopPath,
        ]);

        $commands = $this->cmdStrings($exec);
        $hasRm = false;
        foreach ($commands as $cmd) {
            if (strpos($cmd, 'git rm --ignore-unmatch .next-bump') !== false) {
                $hasRm = true;
                break;
            }
        }
        $this->assertTrue($hasRm, 'expected `git rm --ignore-unmatch .next-bump` for shop-facts');
    }

    public function testThemeRepoGetsThemePhpVersionLineRewrittenAndStagedAlongsideComposerJson(): void
    {
        $themePath = $this->mkRepo('{"require":{}}');
        file_put_contents($themePath . '/theme.php', "<?php\n\$aTheme = ['id' => 'o3-theme', 'version' => '1.0.0'];");
        $o3ShopPath = $this->mkRepo('{"require":{}}');
        $exec = new FakeProcessExecutor([], new ProcessOutcome(0, 'https://example.invalid/url', ''));

        $plan = $this->buildPlan(
            'v1.6.0',
            'v1.6.1-RC1',
            [$this->cuttingCandidate('o3-shop/o3-theme', 'v1.3.0', 'v1.3.1')],
            [],
            ''
        );

        $executor = new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver()
        );
        $executor->execute($plan, [
            'o3-shop/o3-theme' => $themePath,
            'o3-shop/o3-shop' => $o3ShopPath,
        ]);

        // theme.php was rewritten on disk: leading 'v' stripped, line updated
        $this->assertStringContainsString(
            "'version' => '1.3.1'",
            file_get_contents($themePath . '/theme.php')
        );
        // ... and was staged in the same per-repo commit (git add args include theme.php)
        $addCalls = array_values(array_filter(
            $exec->commands(),
            static fn (array $cmd): bool => isset($cmd[0], $cmd[1]) && $cmd[0] === 'git' && $cmd[1] === 'add'
        ));
        $themeAddSeen = false;
        foreach ($addCalls as $cmd) {
            if (in_array('theme.php', $cmd, true)) {
                $themeAddSeen = true;
                break;
            }
        }
        $this->assertTrue($themeAddSeen, 'expected `git add` to include theme.php for the theme repo');
    }

    public function testNonThemeRepoDoesNotStageThemeFile(): void
    {
        $shopCePath = $this->mkRepo('{"require":{}}'); // no theme.php
        $o3ShopPath = $this->mkRepo('{"require":{}}');
        $exec = new FakeProcessExecutor([], new ProcessOutcome(0, 'https://example.invalid/url', ''));

        $plan = $this->buildPlan(
            'v1.6.0',
            'v1.6.1-RC1',
            [$this->cuttingCandidate('o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1')],
            [],
            ''
        );

        $executor = new LiveExecutor(
            new PerRepoActions($exec),
            new ComposerJsonConstraintWriter(),
            new DefaultBranchResolver()
        );
        $executor->execute($plan, [
            'o3-shop/shop-ce' => $shopCePath,
            'o3-shop/o3-shop' => $o3ShopPath,
        ]);

        $allStagedArgs = [];
        foreach ($exec->commands() as $cmd) {
            if (isset($cmd[0], $cmd[1]) && $cmd[0] === 'git' && $cmd[1] === 'add') {
                $allStagedArgs = array_merge($allStagedArgs, array_slice($cmd, 2));
            }
        }
        $this->assertNotContains('theme.php', $allStagedArgs, 'non-theme repo must not stage theme.php');
        $this->assertFalse(is_file($shopCePath . '/theme.php'), 'sanity: no theme.php fixture');
    }

    /* -------- helpers -------- */

    /**
     * @param array<int,CandidatePlan>      $candidates
     * @param array<int,ConstraintEditPlan> $edits
     */
    private function buildPlan(
        string $fromTag,
        string $toTag,
        array $candidates,
        array $edits,
        string $aggregatedNotes
    ): ReleasePlan {
        return new ReleasePlan(
            $fromTag,
            $toTag,
            new FromSnapshot([]),
            $candidates,
            $edits,
            $aggregatedNotes,
            []
        );
    }

    /**
     * Git outcomes for the merge-back step: each tag resolves to TAG_SHA
     * and no merge-back branch exists on the remote yet.
     *
     * @param  array<int,string>            $tags
     * @return array<string,ProcessOutcome>
     */
    private function mergeBackGitResponses(array $tags): array
    {
        $responses = [];
        foreach ($tags as $tag) {
            $responses["git rev-parse --verify {$tag}^{commit}"] = new ProcessOutcome(0, self::TAG_SHA . "\n", '');
            $responses["git ls-remote --heads origin refs/heads/merge-back-{$tag}"] = new ProcessOutcome(0, '', '');
            $responses['git push origin ' . self::TAG_SHA . ":refs/heads/merge-back-{$tag}"] = new ProcessOutcome(0, '', '');
        }
        return $responses;
    }

    /** @return array<int,array<int,string>> */
    private function prCreateCalls(FakeProcessExecutor $exec): array
    {
        return array_values(array_filter(
            $exec->commands(),
            static fn (array $cmd): bool => isset($cmd[1], $cmd[2]) && $cmd[1] === 'pr' && $cmd[2] === 'create'
        ));
    }

    /** @param array<int,string> $cmd */
    private function optionValue(array $cmd, string $option): string
    {
        $idx = array_search($option, $cmd, true);
        $this->assertNotFalse($idx, "option {$option} missing");
        return $cmd[$idx + 1];
    }

    private function cuttingCandidate(string $package, string $fromPin, string $newTag): CandidatePlan
    {
        return new CandidatePlan(
            $package,
            $fromPin,
            $newTag,
            new VersionResolution($package, VersionResolution::CASE_NEEDS_NEW_TAG, null, [], $fromPin),
            new TagCutResult($newTag, false, TagCutResult::SOURCE_DEFAULT_PATCH)
        );
    }

    private function edit(
        string $parent,
        string $key,
        string $dep,
        string $oldConstraint,
        string $newConstraint
    ): ConstraintEditPlan {
        return new ConstraintEditPlan(
            $parent,
            $key,
            $dep,
            new ConstraintUpdate($oldConstraint, $newConstraint, ConstraintUpdate::SHAPE_EXACT_REPLACED)
        );
    }

    private function mkRepo(string $composerJson): string
    {
        $dir = sys_get_temp_dir() . '/live-executor-test-' . bin2hex(random_bytes(4));
        mkdir($dir);
        mkdir($dir . '/.git');
        file_put_contents($dir . '/composer.json', $composerJson);
        $this->tmpDirs[] = $dir;
        return $dir;
    }

    private function rmrf(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->rmrf($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /** @return array<int,string> joined commands per call, for substring assertions */
    private function cmdStrings(FakeProcessExecutor $exec): array
    {
        $strings = [];
        foreach ($exec->commands() as $cmd) {
            $strings[] = implode(' ', $cmd);
        }
        return $strings;
    }

    /** @param array<int,string> $strings */
    private function indexOfFirst(array $strings, string $needle): int
    {
        foreach ($strings as $i => $s) {
            if (strpos($s, $needle) !== false) {
                return $i;
            }
        }
        return -1;
    }

    /** @param array<int,string> $strings */
    private function indexOfNth(array $strings, string $needle, int $nth): int
    {
        $count = 0;
        foreach ($strings as $i => $s) {
            if (strpos($s, $needle) !== false) {
                $count++;
                if ($count === $nth) {
                    return $i;
                }
            }
        }
        return -1;
    }

    /** @return array{command:array<int,string>,cwd:string|null,timeout:int} */
    private function callAt(FakeProcessExecutor $exec, int $idx): array
    {
        return $exec->calls[$idx];
    }
}
