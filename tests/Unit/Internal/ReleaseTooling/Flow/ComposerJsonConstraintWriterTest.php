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
use OxidEsales\EshopCommunity\Internal\ReleaseTooling\Planning\ConstraintEditPlan;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ComposerJsonConstraintWriterTest extends TestCase
{
    private string $tmpFile = '';

    protected function tearDown(): void
    {
        if ($this->tmpFile !== '' && is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testReplacesExactPinnedConstraint(): void
    {
        $this->tmpFile = $this->writeTempJson(<<<'JSON'
{
  "name": "o3-shop/o3-shop",
  "require": {
    "o3-shop/shop-ce": "v1.6.0",
    "o3-shop/shop-facts": "v1.0.4"
  }
}
JSON);
        $writer = new ComposerJsonConstraintWriter();
        $staged = $writer->apply($this->tmpFile, [
            $this->edit('o3-shop/o3-shop', 'require', 'o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
        ]);

        $this->assertSame(['composer.json'], $staged);
        $contents = file_get_contents($this->tmpFile);
        $this->assertStringContainsString('"o3-shop/shop-ce": "v1.6.1-RC1"', $contents);
        $this->assertStringContainsString('"o3-shop/shop-facts": "v1.0.4"', $contents);
        $this->assertStringNotContainsString('"o3-shop/shop-ce": "v1.6.0"', $contents);
    }

    public function testReplacesMultipleConstraintsInOnePass(): void
    {
        $this->tmpFile = $this->writeTempJson(<<<'JSON'
{
  "require": {
    "o3-shop/shop-ce": "v1.6.0",
    "o3-shop/shop-facts": "v1.0.4",
    "o3-shop/usercentrics": "v1.0.0"
  }
}
JSON);
        $writer = new ComposerJsonConstraintWriter();
        $writer->apply($this->tmpFile, [
            $this->edit('o3-shop/o3-shop', 'require', 'o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
            $this->edit('o3-shop/o3-shop', 'require', 'o3-shop/shop-facts', 'v1.0.4', 'v1.0.5'),
            $this->edit('o3-shop/o3-shop', 'require', 'o3-shop/usercentrics', 'v1.0.0', 'v1.2.2'),
        ]);

        $contents = file_get_contents($this->tmpFile);
        $this->assertStringContainsString('"o3-shop/shop-ce": "v1.6.1-RC1"', $contents);
        $this->assertStringContainsString('"o3-shop/shop-facts": "v1.0.5"', $contents);
        $this->assertStringContainsString('"o3-shop/usercentrics": "v1.2.2"', $contents);
    }

    public function testReplacesConstraintStartingWithDigitWithoutEatingBackreference(): void
    {
        // Regression: with an unbraced `$3` backreference, a new constraint
        // starting with a digit (version passed without `v` prefix) formed
        // the nonexistent group `$31`, which PCRE expanded to an empty
        // string — producing `"o3-shop/shop-ce": .6.1-RC11"` and invalid
        // JSON (Packagist skipped tag 1.6.1-RC11 because of this).
        $this->tmpFile = $this->writeTempJson(<<<'JSON'
{
  "require": {
    "o3-shop/shop-ce": "v1.6.1-RC10"
  }
}
JSON);
        $writer = new ComposerJsonConstraintWriter();
        $writer->apply($this->tmpFile, [
            $this->edit('o3-shop/o3-shop', 'require', 'o3-shop/shop-ce', 'v1.6.1-RC10', '1.6.1-RC11'),
        ]);

        $contents = file_get_contents($this->tmpFile);
        $this->assertStringContainsString('"o3-shop/shop-ce": "1.6.1-RC11"', $contents);
        $this->assertNotNull(json_decode($contents), 'edited composer.json must remain valid JSON');
    }

    public function testThrowsWhenExpectedConstraintNotFound(): void
    {
        $this->tmpFile = $this->writeTempJson(<<<'JSON'
{
  "require": {
    "o3-shop/shop-ce": "v1.6.0"
  }
}
JSON);
        $writer = new ComposerJsonConstraintWriter();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expected pattern not found');
        $writer->apply($this->tmpFile, [
            $this->edit('o3-shop/o3-shop', 'require', 'o3-shop/shop-ce', 'v1.5.0', 'v1.6.1-RC1'),
        ]);
    }

    public function testEmptyEditListIsNoOp(): void
    {
        $this->tmpFile = $this->writeTempJson('{"require":{}}');
        $writer = new ComposerJsonConstraintWriter();
        $staged = $writer->apply($this->tmpFile, []);
        $this->assertSame([], $staged);
    }

    public function testPreservesOriginalFormattingOutsideEditedLine(): void
    {
        $original = <<<'JSON'
{
    "name": "o3-shop/o3-shop",
    "minimum-stability": "RC",
    "require": {
        "composer/semver": "3.3.2",
        "o3-shop/shop-ce": "v1.6.0",
        "monolog/monolog": "^v2.10"
    },
    "scripts": {
        "post-install-cmd": ["echo hi"]
    }
}
JSON;
        $this->tmpFile = $this->writeTempJson($original);
        $writer = new ComposerJsonConstraintWriter();
        $writer->apply($this->tmpFile, [
            $this->edit('o3-shop/o3-shop', 'require', 'o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
        ]);

        $contents = file_get_contents($this->tmpFile);
        // Surrounding lines preserved verbatim.
        $this->assertStringContainsString('"composer/semver": "3.3.2"', $contents);
        $this->assertStringContainsString('"monolog/monolog": "^v2.10"', $contents);
        $this->assertStringContainsString('"minimum-stability": "RC"', $contents);
        $this->assertStringContainsString('"post-install-cmd": ["echo hi"]', $contents);
        // 4-space indentation kept.
        $this->assertStringContainsString('    "require": {', $contents);
    }

    public function testMissingFileThrows(): void
    {
        $writer = new ComposerJsonConstraintWriter();
        $this->expectException(RuntimeException::class);
        $writer->apply('/nonexistent/composer.json', [
            $this->edit('o3-shop/o3-shop', 'require', 'o3-shop/shop-ce', 'v1.6.0', 'v1.6.1-RC1'),
        ]);
    }

    private function edit(
        string $parent,
        string $key,
        string $dep,
        string $oldConstraint,
        string $newConstraint
    ): ConstraintEditPlan {
        $update = new ConstraintUpdate(
            $oldConstraint,
            $newConstraint,
            ConstraintUpdate::SHAPE_EXACT_REPLACED
        );
        return new ConstraintEditPlan($parent, $key, $dep, $update);
    }

    private function writeTempJson(string $contents): string
    {
        $path = sys_get_temp_dir() . '/composer-writer-test-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($path, $contents);
        return $path;
    }
}
