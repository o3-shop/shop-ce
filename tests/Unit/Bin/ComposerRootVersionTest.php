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

namespace OxidEsales\EshopCommunity\Tests\Unit\Bin;

use PHPUnit\Framework\TestCase;

/**
 * End-to-end CLI test for bin/composer-root-version.php, the value that
 * docker/entrypoint.sh and the test matrix and E2E CI jobs pass as
 * COMPOSER_ROOT_VERSION.
 *
 * Refs: o3-shop/o3-shop#282
 */
final class ComposerRootVersionTest extends TestCase
{
    /** @var array<int,string> */
    private array $tmpFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $f) {
            @unlink($f);
        }
        $this->tmpFiles = [];
    }

    public function testPrintsTheAliasOfTheNewestReleaseLine(): void
    {
        $json = $this->writeComposerJson(['dev-b-1.6' => '1.6-dev', 'dev-b-1.7' => '1.7-dev']);
        $result = $this->runScript([$json]);

        $this->assertSame(0, $result['exit']);
        $this->assertSame("dev-b-1.7\n", $result['stdout']);
    }

    public function testComparesVersionsNotKeyOrderOrStrings(): void
    {
        // 1.10 > 1.9 numerically, but not as strings; listed out of order.
        $json = $this->writeComposerJson([
            'dev-b-1.10' => '1.10-dev',
            'dev-b-1.9' => '1.9-dev',
            'dev-b-1.2' => '1.2-dev',
        ]);
        $result = $this->runScript([$json]);

        $this->assertSame(0, $result['exit']);
        $this->assertSame("dev-b-1.10\n", $result['stdout']);
    }

    public function testFailsWhenThereIsNoBranchAlias(): void
    {
        $json = $this->writeComposerJson([]);
        $result = $this->runScript([$json]);

        $this->assertSame(1, $result['exit']);
        $this->assertSame('', $result['stdout']);
        $this->assertStringContainsString('branch-alias', $result['stderr']);
    }

    public function testFailsWhenTheBranchAliasMapIsEmpty(): void
    {
        $path = $this->tmpFile('{"name":"o3-shop/shop-ce","extra":{"branch-alias":{}}}');
        $result = $this->runScript([$path]);

        $this->assertSame(1, $result['exit']);
        $this->assertSame('', $result['stdout']);
    }

    public function testFailsWhenTheBranchAliasIsNotAMap(): void
    {
        $path = $this->tmpFile('{"extra":{"branch-alias":"dev-b-1.7"}}');
        $result = $this->runScript([$path]);

        $this->assertSame(1, $result['exit']);
        $this->assertSame('', $result['stdout']);
        $this->assertStringContainsString('[ERROR]', $result['stderr']);
    }

    public function testIgnoresAliasesThatAreNotStrings(): void
    {
        $path = $this->tmpFile('{"extra":{"branch-alias":{"dev-b-1.7":"1.7-dev","dev-broken":null,"dev-odd":["9.9-dev"]}}}');
        $result = $this->runScript([$path]);

        $this->assertSame(0, $result['exit']);
        $this->assertSame("dev-b-1.7\n", $result['stdout']);
    }

    public function testFailsWhenComposerJsonIsMissing(): void
    {
        $result = $this->runScript([sys_get_temp_dir() . '/does-not-exist-' . bin2hex(random_bytes(4)) . '.json']);

        $this->assertSame(2, $result['exit']);
        $this->assertSame('', $result['stdout']);
        $this->assertStringContainsString('[ERROR] Cannot read', $result['stderr']);
    }

    public function testFailsWhenComposerJsonIsMalformed(): void
    {
        $path = $this->tmpFile('{not json');
        $result = $this->runScript([$path]);

        $this->assertSame(2, $result['exit']);
        $this->assertSame('', $result['stdout']);
        $this->assertStringContainsString('is not valid JSON', $result['stderr']);
    }

    public function testDefaultsToTheRepositoryComposerJson(): void
    {
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 3) . '/composer.json'), true);
        $aliases = $composer['extra']['branch-alias'];

        $result = $this->runScript([]);
        $printed = rtrim($result['stdout'], "\n");

        $this->assertSame(0, $result['exit']);
        $this->assertArrayHasKey($printed, $aliases, 'Prints one of the repository\'s branch-alias keys.');
        foreach ($aliases as $key => $alias) {
            $this->assertFalse(
                version_compare($alias, $aliases[$printed], '>'),
                "Alias '$key' => '$alias' is newer than the printed '$printed'."
            );
        }
    }

    /**
     * @param array<string,string> $aliases
     */
    private function writeComposerJson(array $aliases): string
    {
        $data = ['name' => 'o3-shop/shop-ce'];
        if ($aliases !== []) {
            $data['extra'] = ['branch-alias' => $aliases];
        }

        return $this->tmpFile((string) json_encode($data));
    }

    private function tmpFile(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'crv');
        file_put_contents($path, $contents);
        $this->tmpFiles[] = $path;

        return $path;
    }

    /**
     * @param array<int,string> $args
     * @return array{exit:int,stdout:string,stderr:string}
     */
    private function runScript(array $args): array
    {
        $cmd = array_merge([PHP_BINARY, dirname(__DIR__, 3) . '/bin/composer-root-version.php'], $args);
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($proc);
        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['exit' => proc_close($proc), 'stdout' => $stdout, 'stderr' => $stderr];
    }
}
