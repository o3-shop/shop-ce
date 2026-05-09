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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ProcessOutcome;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\RepoCloneUrlResolver;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\RepoPathDiscovery;
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\DefaultBranchResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RepoPathDiscoveryTest extends TestCase
{
    /** @var array<int,string> dirs created during the test */
    private array $tmpDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpDirs as $dir) {
            $this->rmrf($dir);
        }
        $this->tmpDirs = [];
    }

    public function testExistingClonesReused(): void
    {
        $base = $this->mkdir();
        $shopCe = $this->mkdir();

        // Pre-create three release-eligible repos as existing clones.
        // Names mirror the GitHub repo names (PackageRepoSlug-resolved).
        $this->mkGitWorkingTree($base . '/testing-library');
        $this->mkGitWorkingTree($base . '/o3-Theme');
        $this->mkGitWorkingTree($base . '/MinkSeleniumDriver');

        $exec = new FakeProcessExecutor();
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $resolved = $discovery->discoverAll($base, $shopCe, [
            'o3-shop/shop-ce',
            'o3-shop/testing-library',
            'o3-shop/o3-theme',
            'o3-shop/mink-selenium-driver',
        ]);

        $this->assertSame($shopCe, $resolved['o3-shop/shop-ce']);
        $this->assertSame($base . '/testing-library', $resolved['o3-shop/testing-library']);
        $this->assertSame($base . '/o3-Theme', $resolved['o3-shop/o3-theme']);
        $this->assertSame($base . '/MinkSeleniumDriver', $resolved['o3-shop/mink-selenium-driver']);

        // No clone commands invoked
        foreach ($exec->commands() as $command) {
            $this->assertNotSame('clone', $command[1] ?? null, 'unexpected git clone for existing dir');
        }
    }

    public function testMissingClonesAreAutoCloned(): void
    {
        $base = $this->mkdir();
        $shopCe = $this->mkdir();

        $exec = new FakeProcessExecutor([], new ProcessOutcome(0, '', ''));
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );

        $resolved = $discovery->discoverAll($base, $shopCe, [
            'o3-shop/shop-ce',
            'o3-shop/testing-library',
            'o3-shop/o3-theme',
        ]);

        // Two clone commands expected (testing-library + o3-Theme)
        $cloneCalls = array_values(array_filter(
            $exec->commands(),
            static fn (array $cmd): bool => isset($cmd[0], $cmd[1]) && $cmd[0] === 'git' && $cmd[1] === 'clone'
        ));
        $this->assertCount(2, $cloneCalls);

        $cloneTargets = array_map(static fn (array $cmd): string => end($cmd), $cloneCalls);
        $this->assertContains($base . '/testing-library', $cloneTargets);
        $this->assertContains($base . '/o3-Theme', $cloneTargets);

        $cloneBranches = [];
        foreach ($cloneCalls as $cmd) {
            $branchIdx = array_search('--branch', $cmd, true);
            $cloneBranches[end($cmd)] = $cmd[$branchIdx + 1];
        }
        $this->assertSame('b-1.6', $cloneBranches[$base . '/testing-library']);
        $this->assertSame('main', $cloneBranches[$base . '/o3-Theme']);

        $this->assertSame($base . '/testing-library', $resolved['o3-shop/testing-library']);
        $this->assertSame($base . '/o3-Theme', $resolved['o3-shop/o3-theme']);
    }

    public function testShopCeNeverAutoClonedOverSelf(): void
    {
        $base = $this->mkdir();
        $shopCe = $this->mkdir();
        // Pretend shop-ce is at <base>/shop-ce — but we're running from
        // a different path. Discovery should still return the running
        // path (not <base>/shop-ce) for the shop-ce package.
        $this->mkGitWorkingTree($base . '/shop-ce');

        $exec = new FakeProcessExecutor();
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $resolved = $discovery->discoverAll($base, $shopCe, ['o3-shop/shop-ce']);

        $this->assertSame($shopCe, $resolved['o3-shop/shop-ce']);
    }

    public function testNonGitDirectoryAborts(): void
    {
        $base = $this->mkdir();
        $shopCe = $this->mkdir();
        // Create a non-git directory in the spot where a clone would go
        $strayPath = $base . '/testing-library';
        mkdir($strayPath);
        $this->tmpDirs[] = $strayPath;

        $exec = new FakeProcessExecutor();
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exists but is not a git working tree');
        $discovery->discoverAll($base, $shopCe, ['o3-shop/testing-library']);
    }

    public function testCloneFailureAborts(): void
    {
        $base = $this->mkdir();
        $shopCe = $this->mkdir();
        $exec = new FakeProcessExecutor([], new ProcessOutcome(128, '', "fatal: could not read from remote\n"));

        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('auto-clone failed for o3-shop/testing-library');
        $discovery->discoverAll($base, $shopCe, ['o3-shop/testing-library']);
    }

    public function testNonExistentBaseDirAborts(): void
    {
        $shopCe = $this->mkdir();
        $exec = new FakeProcessExecutor();
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('--repo-base /nonexistent/path is not a directory');
        $discovery->discoverAll('/nonexistent/path', $shopCe);
    }

    public function testProgressCallableInvokedPerPackage(): void
    {
        $base = $this->mkdir();
        $shopCe = $this->mkdir();
        $this->mkGitWorkingTree($base . '/testing-library');

        $messages = [];
        $exec = new FakeProcessExecutor([], new ProcessOutcome(0, '', ''));
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver(),
            static function (string $msg) use (&$messages): void {
                $messages[] = $msg;
            }
        );
        $discovery->discoverAll($base, $shopCe, [
            'o3-shop/shop-ce',
            'o3-shop/testing-library',
            'o3-shop/o3-theme',
        ]);

        $allMessages = implode("\n", $messages);
        $this->assertStringContainsString('using running clone', $allMessages);
        $this->assertStringContainsString('found existing clone', $allMessages);
        $this->assertStringContainsString('auto-cloning', $allMessages);
    }

    /* ---------- nested-clone scan (set up by docker.sh) ---------- */

    public function testNestedCloneInsideShopCePreferredOverSibling(): void
    {
        $base = $this->mkdir();
        $shopCe = $this->mkdir();
        // Sibling exists too — but the nested clone (from ./docker.sh)
        // should win.
        $this->mkGitWorkingTree($base . '/o3-Theme');
        $this->mkNestedGitWorkingTree(
            $shopCe . '/source/Application/views/o3-theme',
            'https://github.com/o3-shop/o3-Theme.git'
        );

        $exec = new FakeProcessExecutor();
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $resolved = $discovery->discoverAll($base, $shopCe, ['o3-shop/o3-theme']);

        $this->assertSame(
            $shopCe . '/source/Application/views/o3-theme',
            $resolved['o3-shop/o3-theme']
        );
        // No clone command invoked
        $this->assertSame([], $exec->commands());
    }

    public function testNestedCloneAtShopCeRoot(): void
    {
        $base = $this->mkdir();
        $shopCe = $this->mkdir();
        // Demodata satellite at the shop-ce root
        $this->mkNestedGitWorkingTree(
            $shopCe . '/shop-demodata-ce',
            'git@github.com:o3-shop/shop-demodata-ce.git' // SSH origin works too
        );

        $exec = new FakeProcessExecutor();
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $resolved = $discovery->discoverAll($base, $shopCe, ['o3-shop/shop-demodata-ce']);

        $this->assertSame(
            $shopCe . '/shop-demodata-ce',
            $resolved['o3-shop/shop-demodata-ce']
        );
    }

    public function testNestedScanReverseMapsCaseRenamedPackages(): void
    {
        // o3-Theme is a PackageRepoSlug rename: composer name is
        // "o3-shop/o3-theme" but GitHub slug is "o3-shop/o3-Theme".
        // The scanner must reverse-map back to the composer name.
        $base = $this->mkdir();
        $shopCe = $this->mkdir();
        $this->mkNestedGitWorkingTree(
            $shopCe . '/source/Application/views/o3-theme',
            'https://github.com/o3-shop/o3-Theme.git'
        );

        $exec = new FakeProcessExecutor();
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $resolved = $discovery->discoverAll($base, $shopCe, ['o3-shop/o3-theme']);

        $this->assertArrayHasKey('o3-shop/o3-theme', $resolved);
        $this->assertSame(
            $shopCe . '/source/Application/views/o3-theme',
            $resolved['o3-shop/o3-theme']
        );
    }

    public function testNestedDirWithoutGitFallsThroughToSibling(): void
    {
        // Path that LOOKS like a nested clone target exists, but it's
        // a composer-plugin install artifact (no .git/). Scanner must
        // skip it; resolution falls through to sibling layout.
        $base = $this->mkdir();
        $shopCe = $this->mkdir();
        $strayPath = $shopCe . '/source/Application/views/o3-theme';
        mkdir($strayPath, 0755, true);
        $this->tmpDirs[] = $strayPath;
        $this->tmpDirs[] = dirname($strayPath);
        $this->tmpDirs[] = dirname(dirname($strayPath));
        $this->mkGitWorkingTree($base . '/o3-Theme'); // sibling fallback

        $exec = new FakeProcessExecutor();
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $resolved = $discovery->discoverAll($base, $shopCe, ['o3-shop/o3-theme']);

        // Falls back to sibling path
        $this->assertSame($base . '/o3-Theme', $resolved['o3-shop/o3-theme']);
    }

    public function testNestedScanIgnoresNonO3ShopOrigins(): void
    {
        // Maintainer has some unrelated repo checked out inside
        // shop-ce. Scanner ignores it (origin not under o3-shop).
        $base = $this->mkdir();
        $shopCe = $this->mkdir();
        $this->mkNestedGitWorkingTree(
            $shopCe . '/some-private-thing',
            'git@github.com:other-org/other-repo.git'
        );
        // Sibling for o3-theme exists and should be picked
        $this->mkGitWorkingTree($base . '/o3-Theme');

        $exec = new FakeProcessExecutor();
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $resolved = $discovery->discoverAll($base, $shopCe, ['o3-shop/o3-theme']);

        $this->assertSame($base . '/o3-Theme', $resolved['o3-shop/o3-theme']);
    }

    public function testNestedScanSkipsVendorAndNodeModules(): void
    {
        // composer's vendor/ tree may contain .git references for
        // some install configs; node_modules is similar. The scan
        // must not descend into either, even if we drop a fake
        // git-tree there to assert the skip.
        $base = $this->mkdir();
        $shopCe = $this->mkdir();
        $this->mkNestedGitWorkingTree(
            $shopCe . '/vendor/o3-shop/o3-theme',
            'https://github.com/o3-shop/o3-Theme.git'
        );
        $this->mkNestedGitWorkingTree(
            $shopCe . '/node_modules/foo',
            'https://github.com/o3-shop/o3-Theme.git'
        );

        $exec = new FakeProcessExecutor([], new ProcessOutcome(0, '', ''));
        $discovery = new RepoPathDiscovery(
            $exec,
            new RepoCloneUrlResolver(RepoCloneUrlResolver::SCHEME_HTTPS),
            new DefaultBranchResolver()
        );
        $resolved = $discovery->discoverAll($base, $shopCe, ['o3-shop/o3-theme']);

        // Should NOT pick up the vendor or node_modules nested tree;
        // falls through to auto-clone into <base>/o3-Theme
        $this->assertSame($base . '/o3-Theme', $resolved['o3-shop/o3-theme']);
        // And a clone command was invoked
        $cloneCalls = array_filter(
            $exec->commands(),
            static fn (array $cmd): bool => isset($cmd[1]) && $cmd[1] === 'clone'
        );
        $this->assertCount(1, $cloneCalls);
    }

    private function mkdir(): string
    {
        $dir = sys_get_temp_dir() . '/repo-discovery-test-' . bin2hex(random_bytes(4));
        mkdir($dir);
        $this->tmpDirs[] = $dir;
        return $dir;
    }

    private function mkGitWorkingTree(string $path): void
    {
        mkdir($path);
        mkdir($path . '/.git');
        $this->tmpDirs[] = $path;
    }

    /**
     * Create a nested git working tree at $path (recursively making
     * parent dirs if needed) with a `.git/config` whose origin URL
     * points at $originUrl. Used by the nested-scan tests.
     */
    private function mkNestedGitWorkingTree(string $path, string $originUrl): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        if (!is_dir($path . '/.git')) {
            mkdir($path . '/.git');
        }
        file_put_contents($path . '/.git/config', sprintf(
            "[core]\n\trepositoryformatversion = 0\n[remote \"origin\"]\n\turl = %s\n\tfetch = +refs/heads/*:refs/remotes/origin/*\n",
            $originUrl
        ));
        $this->tmpDirs[] = $path;
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
            $sub = $dir . '/' . $entry;
            is_dir($sub) ? $this->rmrf($sub) : @unlink($sub);
        }
        @rmdir($dir);
    }
}
