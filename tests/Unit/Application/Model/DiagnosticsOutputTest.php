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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput;

class DiagnosticsOutputTest extends \OxidTestCase
{
    private function installUtilsStub(): Utils
    {
        $utils = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['toFileCache', 'fromFileCache', 'getCacheFilePath', 'setHeader'])
            ->getMock();
        Registry::set(Utils::class, $utils);
        return $utils;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::__construct
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::getOutputKey
     */
    public function testDefaultOutputKey(): void
    {
        $this->installUtilsStub();
        $output = new DiagnosticsOutput();
        $this->assertSame('diagnostic_tool_result', $output->getOutputKey());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::getOutputFileName
     */
    public function testDefaultOutputFileName(): void
    {
        $this->installUtilsStub();
        $output = new DiagnosticsOutput();
        $this->assertSame('diagnostic_tool_result.html', $output->getOutputFileName());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::setOutputKey
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::getOutputKey
     */
    public function testSetOutputKeyOverridesDefault(): void
    {
        $this->installUtilsStub();
        $output = new DiagnosticsOutput();
        $output->setOutputKey('custom_key');
        $this->assertSame('custom_key', $output->getOutputKey());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::setOutputKey
     */
    public function testSetOutputKeyKeepsDefaultWhenEmpty(): void
    {
        $this->installUtilsStub();
        $output = new DiagnosticsOutput();
        $output->setOutputKey('');
        $this->assertSame('diagnostic_tool_result', $output->getOutputKey());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::setOutputFileName
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::getOutputFileName
     */
    public function testSetOutputFileNameOverridesDefault(): void
    {
        $this->installUtilsStub();
        $output = new DiagnosticsOutput();
        $output->setOutputFileName('report.html');
        $this->assertSame('report.html', $output->getOutputFileName());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::setOutputFileName
     */
    public function testSetOutputFileNameKeepsDefaultWhenEmpty(): void
    {
        $this->installUtilsStub();
        $output = new DiagnosticsOutput();
        $output->setOutputFileName('');
        $this->assertSame('diagnostic_tool_result.html', $output->getOutputFileName());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::storeResult
     */
    public function testStoreResultDelegatesToUtilsFileCache(): void
    {
        $utils = $this->installUtilsStub();
        $utils->expects($this->once())
            ->method('toFileCache')
            ->with('diagnostic_tool_result', '<html>report</html>');

        $output = new DiagnosticsOutput();
        $output->storeResult('<html>report</html>');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::readResultFile
     */
    public function testReadResultFileUsesCurrentKeyByDefault(): void
    {
        $utils = $this->installUtilsStub();
        $utils->expects($this->once())
            ->method('fromFileCache')
            ->with('diagnostic_tool_result')
            ->willReturn('<html>cached</html>');

        $output = new DiagnosticsOutput();
        $this->assertSame('<html>cached</html>', $output->readResultFile());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::readResultFile
     */
    public function testReadResultFileWithExplicitKey(): void
    {
        $utils = $this->installUtilsStub();
        $utils->expects($this->once())
            ->method('fromFileCache')
            ->with('explicit_key')
            ->willReturn('payload');

        $output = new DiagnosticsOutput();
        $this->assertSame('payload', $output->readResultFile('explicit_key'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::downloadResultFile
     */
    public function testDownloadResultFileWritesHeadersAndEchoesCachedContent(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'diag_');
        file_put_contents($tmp, '<html>report content</html>');

        $utils = $this->installUtilsStub();
        $utils->method('getCacheFilePath')->with('diagnostic_tool_result')->willReturn($tmp);

        $expectedHeaders = [
            'Pragma: public',
            'Expires: 0',
            'Cache-Control: must-revalidate, post-check=0, pre-check=0, private',
            'Content-Disposition: attachment;filename=diagnostic_tool_result.html',
            'Content-Type:text/html;charset=utf-8',
            'Content-Length: ' . filesize($tmp),
        ];
        $sentHeaders = [];
        $utils->method('setHeader')->willReturnCallback(function ($header) use (&$sentHeaders) {
            $sentHeaders[] = $header;
        });
        $utils->method('fromFileCache')->with('diagnostic_tool_result')->willReturn('<html>report content</html>');

        $output = new DiagnosticsOutput();

        ob_start();
        $output->downloadResultFile();
        $body = ob_get_clean();

        $this->assertSame($expectedHeaders, $sentHeaders);
        $this->assertSame('<html>report content</html>', $body);

        unlink($tmp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\DiagnosticsOutput::downloadResultFile
     */
    public function testDownloadResultFileSkipsContentLengthHeaderForEmptyFile(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'diag_');
        // empty file → filesize() returns 0 → no Content-Length header

        $utils = $this->installUtilsStub();
        $utils->method('getCacheFilePath')->willReturn($tmp);
        $sentHeaders = [];
        $utils->method('setHeader')->willReturnCallback(function ($header) use (&$sentHeaders) {
            $sentHeaders[] = $header;
        });
        $utils->method('fromFileCache')->willReturn('');

        $output = new DiagnosticsOutput();
        ob_start();
        $output->downloadResultFile('any_key');
        ob_end_clean();

        $contentLengthHeaders = array_filter($sentHeaders, function ($h) {
            return strpos($h, 'Content-Length') === 0;
        });
        $this->assertSame([], $contentLengthHeaders);

        unlink($tmp);
    }
}
