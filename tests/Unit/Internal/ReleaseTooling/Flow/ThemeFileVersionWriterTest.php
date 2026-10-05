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

use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Flow\ThemeFileVersionWriter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ThemeFileVersionWriterTest extends TestCase
{
    /** @var array<int,string> */
    private array $tmpDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpDirs as $dir) {
            $this->rmrf($dir);
        }
        $this->tmpDirs = [];
    }

    public function testRewritesVersionLineAndStagesThemeFile(): void
    {
        $repo = $this->mkRepoWithTheme(<<<'PHP'
<?php
$aTheme = [
    'id'      => 'o3-theme',
    'title'   => 'O3-Theme',
    'version' => '1.0.0',
    'author'  => '<a>O3-Shop</a>',
];
PHP);
        $writer = new ThemeFileVersionWriter();
        $staged = $writer->apply($repo, 'v1.3.1');

        $this->assertSame(['theme.php'], $staged);
        $contents = file_get_contents($repo . '/theme.php');
        $this->assertStringContainsString("'version' => '1.3.1',", $contents);
        $this->assertStringNotContainsString("'version' => '1.0.0',", $contents);
    }

    public function testStripsLeadingVFromTag(): void
    {
        $repo = $this->mkRepoWithTheme("<?php\n\$aTheme = ['version' => '1.0.0'];");
        (new ThemeFileVersionWriter())->apply($repo, 'V1.2.3');

        $this->assertStringContainsString("'version' => '1.2.3'", file_get_contents($repo . '/theme.php'));
    }

    public function testHandlesPreReleaseTagWithRcSuffix(): void
    {
        $repo = $this->mkRepoWithTheme("<?php\n\$aTheme = ['version' => '1.0.0'];");
        (new ThemeFileVersionWriter())->apply($repo, 'v1.6.1-RC1');

        $this->assertStringContainsString("'version' => '1.6.1-RC1'", file_get_contents($repo . '/theme.php'));
    }

    public function testReturnsEmptyWhenNoThemeFileExists(): void
    {
        $repo = $this->mkRepoWithoutTheme();
        $staged = (new ThemeFileVersionWriter())->apply($repo, 'v1.6.1');

        $this->assertSame([], $staged);
        $this->assertFalse(is_file($repo . '/theme.php'));
    }

    public function testReturnsEmptyWhenVersionAlreadyCurrent(): void
    {
        $repo = $this->mkRepoWithTheme("<?php\n\$aTheme = ['version' => '1.3.1'];");
        $staged = (new ThemeFileVersionWriter())->apply($repo, 'v1.3.1');

        $this->assertSame([], $staged);
        // File contents unchanged (mtime check would be flaky; content equality is enough)
        $this->assertSame(
            "<?php\n\$aTheme = ['version' => '1.3.1'];",
            file_get_contents($repo . '/theme.php')
        );
    }

    public function testThrowsInsteadOfDowngradingNewerThemeFileVersion(): void
    {
        // Regression: the v1.3.5 o3-theme release silently downgraded a
        // hand-bumped theme.php (1.4.0) because .next-bump was not set
        // and the default patch bump computed a lower tag. The file being
        // ahead of the tag must abort the release instead.
        $repo = $this->mkRepoWithTheme("<?php\n\$aTheme = ['version' => '1.4.0'];");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to downgrade');
        (new ThemeFileVersionWriter())->apply($repo, 'v1.3.5');
    }

    public function testDowngradeGuardSkipsUnparseableCurrentVersion(): void
    {
        $repo = $this->mkRepoWithTheme("<?php\n\$aTheme = ['version' => 'dev'];");
        $staged = (new ThemeFileVersionWriter())->apply($repo, 'v1.3.5');

        $this->assertSame(['theme.php'], $staged);
        $this->assertStringContainsString("'version' => '1.3.5'", file_get_contents($repo . '/theme.php'));
    }

    public function testThrowsWhenThemeFileLacksVersionLine(): void
    {
        $repo = $this->mkRepoWithTheme(<<<'PHP'
<?php
$aTheme = [
    'id'    => 'broken-theme',
    'title' => 'Broken',
];
PHP);
        $writer = new ThemeFileVersionWriter();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("missing expected `'version' => '…'` line");
        $writer->apply($repo, 'v1.3.1');
    }

    public function testAcceptsDoubleQuotedVersionLine(): void
    {
        $repo = $this->mkRepoWithTheme("<?php\n\$aTheme = [\"version\" => \"1.0.0\"];");
        (new ThemeFileVersionWriter())->apply($repo, 'v1.4.0');

        $this->assertStringContainsString('"version" => "1.4.0"', file_get_contents($repo . '/theme.php'));
    }

    public function testPreservesSurroundingFormattingAndKeyOrder(): void
    {
        $original = <<<'PHP'
<?php
$aTheme = [
    'id'          => 'o3-theme',
    'title'       => 'O3-Theme',
    'description' => 'Demo',
    'thumbnail'   => 'theme.png',
    'version'     => '1.0.0',
    'author'      => '<a>O3-Shop</a>',
    'settings'    => [
        ['group' => 'mode', 'name' => 'sShowMode'],
    ],
];
PHP;
        $repo = $this->mkRepoWithTheme($original);
        (new ThemeFileVersionWriter())->apply($repo, 'v1.3.1');

        $expected = str_replace("'version'     => '1.0.0',", "'version'     => '1.3.1',", $original);
        $this->assertSame($expected, file_get_contents($repo . '/theme.php'));
    }

    private function mkRepoWithTheme(string $themeFileContents): string
    {
        $repo = $this->mkRepoWithoutTheme();
        file_put_contents($repo . '/theme.php', $themeFileContents);
        return $repo;
    }

    private function mkRepoWithoutTheme(): string
    {
        $repo = sys_get_temp_dir() . '/theme-writer-test-' . bin2hex(random_bytes(4));
        mkdir($repo);
        $this->tmpDirs[] = $repo;
        return $repo;
    }

    private function rmrf(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path) && !is_link($path)) {
                $this->rmrf($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
