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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use OxidEsales\EshopCommunity\Core\ShopVersion;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for the 3-step version-resolution chain in ShopVersion.
 * See spec: openspec/.../shop-version-resolution/spec.md
 *
 * Plain PHPUnit\TestCase — ShopVersion has zero shop-framework deps,
 * so the OxidTestCase bootstrap (db, registry, mock factory) is not
 * needed and would just slow the run down.
 */
class ShopVersionTest extends TestCase
{
    /**
     * Snapshot the canonical generated-file path so tearDown can clean
     * up after tests that exercised Step 1 against the real location.
     */
    private $generatedFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generatedFilePath = ShopVersion::GENERATED_VERSION_FILE;
        $this->removeGeneratedFile();
    }

    protected function tearDown(): void
    {
        $this->removeGeneratedFile();
        parent::tearDown();
    }

    private function removeGeneratedFile(): void
    {
        if (is_file($this->generatedFilePath)) {
            unlink($this->generatedFilePath);
        }
    }

    private function writeGeneratedFile(string $version): void
    {
        $contents = "<?php\n\nreturn " . var_export($version, true) . ";\n";
        file_put_contents($this->generatedFilePath, $contents);
    }

    public function testGetVersionReturnsNonEmptyString(): void
    {
        $version = ShopVersion::getVersion();
        $this->assertIsString($version);
        $this->assertNotSame('', $version);
    }

    public function testGetVersionPrefersGeneratedFile(): void
    {
        $this->writeGeneratedFile('v0.0.0-fixture');
        $this->assertSame('v0.0.0-fixture', ShopVersion::getVersion());
    }

    public function testGetVersionFallsThroughToComposerWhenFileAbsent(): void
    {
        // No generated file (setUp removed it). Composer\InstalledVersions
        // is loaded in the test runtime and knows o3-shop/shop-ce.
        $version = ShopVersion::getVersion();
        $this->assertNotSame('dev', $version);
        $this->assertNotSame('', $version);
    }

    public function testGetVersionFallsBackToDevWhenBothStepsYieldNull(): void
    {
        $stub = new class () extends ShopVersion {
            public static function tryGeneratedFile($path = null)
            {
                return null;
            }

            public static function tryComposerRuntime($packageName = self::PACKAGE_NAME)
            {
                return null;
            }
        };
        $this->assertSame('dev', $stub::getVersion());
    }

    public function testTryGeneratedFileReturnsNullForMissingPath(): void
    {
        $this->assertNull(ShopVersion::tryGeneratedFile('/dev/null/no/such/file.php'));
    }

    public function testTryGeneratedFileReturnsContentForValidFile(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'shopversion_');
        file_put_contents($tmp, "<?php\nreturn 'v7.7.7';\n");
        try {
            $this->assertSame('v7.7.7', ShopVersion::tryGeneratedFile($tmp));
        } finally {
            unlink($tmp);
        }
    }

    public function testTryGeneratedFileReturnsNullWhenFileReturnsEmpty(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'shopversion_');
        file_put_contents($tmp, "<?php\nreturn '';\n");
        try {
            $this->assertNull(ShopVersion::tryGeneratedFile($tmp));
        } finally {
            unlink($tmp);
        }
    }

    public function testTryComposerRuntimeReturnsNullForUnknownPackage(): void
    {
        $this->assertNull(ShopVersion::tryComposerRuntime('vendor/never-installed-pkg-9999'));
    }

    public function testTryComposerRuntimeReturnsVersionForInstalledShopCe(): void
    {
        $version = ShopVersion::tryComposerRuntime();
        $this->assertNotNull($version);
        $this->assertNotSame('', $version);
    }

    public function testImplementationContainsNoProcessForks(): void
    {
        $sourcePath = (new ReflectionClass(ShopVersion::class))->getFileName();
        $source = file_get_contents($sourcePath);
        // Strip class-level docblock and comments so the wording of the
        // contract docblock (which mentions these names) does not pollute
        // the assertion. We only care about callable references.
        $stripped = preg_replace('#/\*.*?\*/#s', '', $source) ?? $source;
        $stripped = preg_replace('#//.*$#m', '', $stripped) ?? $stripped;
        foreach (['shell_exec', 'proc_open', 'passthru(', 'exec(', 'system('] as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $stripped,
                "ShopVersion must not call $needle"
            );
        }
        // No backtick operator (PHP shell-exec shorthand).
        $this->assertDoesNotMatchRegularExpression('/`[^`]+`/', $stripped, 'ShopVersion must not use backticks');
        // No literal "git " invocation string.
        $this->assertStringNotContainsString('git ', $stripped, 'ShopVersion must not invoke git');
    }
}
