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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Core\Smarty\Plugin;

use OxidEsales\EshopCommunity\Core\Smarty\Plugin\StringInputParser;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\Context;
use PHPUnit\Framework\TestCase;
use Webmozart\PathUtil\Path;

final class StringInputParserTest extends TestCase
{
    private string $logFile;
    private string $testFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = (new Context())->getLogFilePath();
        $this->testFile = Path::join(sys_get_temp_dir(), uniqid('test_file', true));
        $this->checkTestingEnvironment();
    }

    protected function tearDown(): void
    {
        $this->removeTestFile();
        $this->truncateLogFile();
        parent::tearDown();
    }

    public function testParseArrayWithInvalidInputWillWriteToLog(): void
    {
        $invalidInput = 'array(^^^)';
        $logSizeBefore = $this->getLogFileSize();

        $result = (new StringInputParser())->parseArray($invalidInput);

        $this->assertGreaterThan($logSizeBefore, $this->getLogFileSize());
        $this->assertSame([], $result);
    }

    public function testParseArrayWithCodeInjectionWillWriteToLog(): void
    {
        $invalidInput = 'array().some_function_call()';
        $logSizeBefore = $this->getLogFileSize();

        (new StringInputParser())->parseArray($invalidInput);

        $this->assertGreaterThan($logSizeBefore, $this->getLogFileSize());
    }

    public function testParseArrayWithCodeInjectionWillNotExecuteCode(): void
    {
        $invalidInput = $this->getEvalExpressionWithInjection('array()');

        try {
            (new StringInputParser())->parseArray($invalidInput);
        } catch (\Throwable $exception) {
            /** Regardless of execution status, continue to assertion */
        }

        $this->assertFileNotExists($this->testFile);
    }

    public function testParseRangeWithInvalidInputWillWriteToLog(): void
    {
        $invalidInput = 'range("a")';
        $logSizeBefore = $this->getLogFileSize();

        $result = (new StringInputParser())->parseRange($invalidInput);

        $this->assertGreaterThan($logSizeBefore, $this->getLogFileSize());
        $this->assertSame([], $result);
    }

    public function testParseRangeWithInjectedCodeWillNotExecuteCode(): void
    {
        $invalidInput = $this->getEvalExpressionWithInjection('range(1,2)');

        try {
            (new StringInputParser())->parseRange($invalidInput);
        } catch (\Throwable $exception) {
            /** Regardless of execution status, continue to assertion */
        }

        $this->assertFileNotExists($this->testFile);
    }

    private function checkTestingEnvironment(): void
    {
        $this->assertFileNotExists($this->testFile);
        eval($this->getEvalExpressionWithInjection('array()'));
        $this->assertFileExists($this->testFile);
        $this->removeTestFile();
    }

    protected function removeTestFile(): void
    {
        if (is_file($this->testFile)) {
            unlink($this->testFile);
        }
    }

    private function getLogFileSize()
    {
        clearstatcache();
        return filesize($this->logFile);
    }

    private function truncateLogFile(): void
    {
        file_put_contents($this->logFile, '');
    }

    private function getEvalExpressionWithInjection(string $prefix): string
    {
        return "$prefix; file_put_contents('$this->testFile', '123');";
    }
}
