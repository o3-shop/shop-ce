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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\ComposerPlugin\Utilities\CopyFileManager\GlobMatcher\GlobMatcher;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Packaging gate: OX_BASE_PATH must never be used to reach a file that the
 * composer installer refuses to copy.
 *
 * OX_BASE_PATH is the *shop's* source directory. When the shop is installed
 * via composer, `ShopPackageInstaller` copies the package's `source/` into it
 * while applying `extra.oxideshop.blacklist-filter` — so `Core/`, `Internal/`
 * and the blacklisted `Application/` subtrees never arrive there; their
 * classes are autoloaded straight out of `vendor/` instead.
 *
 * A git checkout hides this: package root and shop root are the same
 * directory, so `OX_BASE_PATH . 'Core/…'` happens to resolve. The breakage
 * only shows up on a composer-installed shop, where the file is simply absent.
 * Assets that live next to their class must therefore be addressed relative to
 * the class file (`__DIR__`), never through OX_BASE_PATH.
 *
 * Real incident: `GuaranteeLabelGenerator` resolved its label templates and
 * fonts through OX_BASE_PATH and logged
 * "Label template '…/source/Core/GuaranteeLabel/assets/nested-template.png' is
 * missing" on every composer install.
 */
class ComposerBlacklistedAssetPathTest extends TestCase
{
    private const SOURCE_ROOT = __DIR__ . '/../../../source';
    private const COMPOSER_JSON = __DIR__ . '/../../../composer.json';

    /** Literal appended to OX_BASE_PATH, e.g. `OX_BASE_PATH . 'Core/x/y.png'`. */
    private const CONCAT_PATTERN = '/OX_BASE_PATH\s*\.\s*[\'"]([^\'"]+)[\'"]/';

    public function testNoOxBasePathReachesIntoAComposerBlacklistedSubtree(): void
    {
        $blacklist = $this->getBlacklistFilter();

        $violations = [];
        foreach ($this->getShippedPhpFiles() as $file) {
            $source = file_get_contents($file);
            if (!preg_match_all(self::CONCAT_PATTERN, $source, $matches)) {
                continue;
            }
            foreach ($matches[1] as $literal) {
                if (GlobMatcher::matchAny(ltrim($literal, '/'), $blacklist)) {
                    $violations[] = sprintf(
                        "%s\n    OX_BASE_PATH . '%s' — stripped by the composer source filter",
                        $this->getRelativePath($file),
                        $literal
                    );
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "These paths do not exist on a composer-installed shop.\n"
            . "Address assets relative to their class file (__DIR__) instead:\n\n"
            . implode("\n", $violations) . "\n"
        );
    }

    /**
     * @return array<int, string> glob expressions from extra.oxideshop.blacklist-filter
     */
    private function getBlacklistFilter(): array
    {
        if (!is_file(self::COMPOSER_JSON)) {
            $this->markTestSkipped('composer.json not reachable — not running from a source checkout.');
        }

        $composer = json_decode((string) file_get_contents(self::COMPOSER_JSON), true);
        $blacklist = $composer['extra']['oxideshop']['blacklist-filter'] ?? null;

        if (!is_array($blacklist) || $blacklist === []) {
            $this->markTestSkipped('No extra.oxideshop.blacklist-filter — nothing to guard.');
        }

        return $blacklist;
    }

    /**
     * @return \Generator<string> absolute paths of shipped PHP files under source/
     */
    private function getShippedPhpFiles(): \Generator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::SOURCE_ROOT, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }
            // Modules are installed separately and are not subject to this filter.
            if (strpos($file->getPathname(), DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR) !== false) {
                continue;
            }
            yield $file->getPathname();
        }
    }

    private function getRelativePath(string $file): string
    {
        $root = realpath(self::SOURCE_ROOT . '/..');
        $file = realpath($file) ?: $file;

        return $root ? ltrim(str_replace($root, '', $file), DIRECTORY_SEPARATOR) : $file;
    }
}
