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
 * End-to-end CLI test for bin/check-coverage-threshold.php — invokes the
 * actual binary that CI runs and asserts exit codes + diagnostic output.
 * Keeps the local quality gate (./docker.sh test-all-coverage) and the
 * matrix `test` workflow in sync with the same exit-code contract.
 */
final class CheckCoverageThresholdTest extends TestCase
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

    public function testExitsZeroWhenCoverageMeetsThreshold(): void
    {
        $clover = $this->writeClover(9000, 10000); // 90.00%
        $result = $this->runScript(['--clover', $clover, '--threshold', '90']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('[OK]', $result['stdout']);
        $this->assertStringContainsString('90%', $result['stdout']);
    }

    public function testExitsZeroWhenCoverageExceedsThreshold(): void
    {
        $clover = $this->writeClover(9500, 10000); // 95.00%
        $result = $this->runScript(['--clover', $clover, '--threshold', '90']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('95%', $result['stdout']);
    }

    public function testExitsOneWhenCoverageBelowThreshold(): void
    {
        $clover = $this->writeClover(8999, 10000); // 89.99% — one statement short
        $result = $this->runScript(['--clover', $clover, '--threshold', '90']);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('[FAIL]', $result['stderr']);
        $this->assertStringContainsString('89.99%', $result['stderr']);
        $this->assertStringContainsString('threshold 90%', $result['stderr']);
    }

    public function testExitsZeroWhenProjectHasNoStatements(): void
    {
        $clover = $this->writeClover(0, 0); // empty project
        $result = $this->runScript(['--clover', $clover, '--threshold', '90']);

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('no statements in report', $result['stdout']);
    }

    public function testExitsTwoWhenCloverFileMissing(): void
    {
        $result = $this->runScript(['--clover', '/nonexistent/clover.xml']);

        $this->assertSame(2, $result['exit']);
        $this->assertStringContainsString('not readable', $result['stderr']);
    }

    public function testExitsTwoWhenCloverFileMalformed(): void
    {
        $path = sys_get_temp_dir() . '/coverage-test-' . bin2hex(random_bytes(4)) . '.xml';
        file_put_contents($path, '<<not xml>>');
        $this->tmpFiles[] = $path;

        $result = $this->runScript(['--clover', $path]);

        $this->assertSame(2, $result['exit']);
        $this->assertStringContainsString('could not parse', $result['stderr']);
    }

    public function testExitsThreeWhenCloverArgMissing(): void
    {
        $result = $this->runScript([]);

        $this->assertSame(3, $result['exit']);
        $this->assertStringContainsString('--clover', $result['stderr']);
    }

    public function testExitsThreeWhenThresholdOutOfRange(): void
    {
        $clover = $this->writeClover(9000, 10000);
        $result = $this->runScript(['--clover', $clover, '--threshold', '150']);

        $this->assertSame(3, $result['exit']);
        $this->assertStringContainsString('threshold must be in', $result['stderr']);
    }

    public function testAcceptsEqualsSyntax(): void
    {
        $clover = $this->writeClover(9000, 10000);
        $result = $this->runScript(['--clover=' . $clover, '--threshold=90']);

        $this->assertSame(0, $result['exit']);
    }

    public function testDefaultThresholdIsNinety(): void
    {
        $clover = $this->writeClover(8500, 10000); // 85% — below default 90
        $result = $this->runScript(['--clover', $clover]);

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('threshold 90%', $result['stderr']);
    }

    /**
     * @param array<int,string> $args
     * @return array{exit:int,stdout:string,stderr:string}
     */
    private function runScript(array $args): array
    {
        $script = dirname(__DIR__, 3) . '/bin/check-coverage-threshold.php';
        $cmd = array_merge([PHP_BINARY, $script], $args);

        $proc = proc_open(
            $cmd,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (!is_resource($proc)) {
            $this->fail('failed to spawn check-coverage-threshold.php');
        }
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    private function writeClover(int $coveredStatements, int $statements): string
    {
        $xml = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<coverage><project>'
            . '<metrics statements="%d" coveredstatements="%d"/>'
            . '</project></coverage>',
            $statements,
            $coveredStatements
        );
        $path = sys_get_temp_dir() . '/coverage-test-' . bin2hex(random_bytes(4)) . '.xml';
        file_put_contents($path, $xml);
        $this->tmpFiles[] = $path;
        return $path;
    }
}
