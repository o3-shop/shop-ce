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

use OxidEsales\EshopCommunity\Application\Model\Diagnostics;

/**
 * Covers the Diagnostics methods that DiagnosticsTest doesn't exercise:
 * setter empty-value guards, php/server config getters, and the protected
 * info accessors.
 */
class DiagnosticsCoverageTest extends \OxidTestCase
{
    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::setFileCheckerPathList
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::getFileCheckerPathList
     */
    public function testFileCheckerPathListRoundTrip(): void
    {
        $diag = new Diagnostics();
        $diag->setFileCheckerPathList(['source/index.php', 'source/admin/']);
        $this->assertSame(['source/index.php', 'source/admin/'], $diag->getFileCheckerPathList());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::setFileCheckerExtensionList
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::getFileCheckerExtensionList
     */
    public function testFileCheckerExtensionListRoundTrip(): void
    {
        $diag = new Diagnostics();
        $diag->setFileCheckerExtensionList(['php']);
        $this->assertSame(['php'], $diag->getFileCheckerExtensionList());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::setVersion
     */
    public function testSetVersionIgnoresEmptyValue(): void
    {
        $diag = new Diagnostics();
        $diag->setVersion('1.6.0');
        $diag->setVersion('');
        $this->assertSame('1.6.0', $diag->getVersion());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::setEdition
     */
    public function testSetEditionIgnoresEmptyValue(): void
    {
        $diag = new Diagnostics();
        $diag->setEdition('CE');
        $diag->setEdition('');
        $this->assertSame('CE', $diag->getEdition());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::setRevision
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::getRevision
     */
    public function testRevisionRoundTrip(): void
    {
        $diag = new Diagnostics();
        $diag->setRevision('rev-1');
        $this->assertSame('rev-1', $diag->getRevision());
        $diag->setRevision('');
        $this->assertSame('rev-1', $diag->getRevision());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::setShopLink
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::getShopLink
     */
    public function testSetShopLinkIgnoresEmptyValue(): void
    {
        $diag = new Diagnostics();
        $diag->setShopLink('https://shop.example/');
        $diag->setShopLink('');
        $this->assertSame('https://shop.example/', $diag->getShopLink());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::getPhpSelection
     */
    public function testGetPhpSelectionExposesIniValuesForKnownKeys(): void
    {
        $diag = new Diagnostics();
        $selection = $diag->getPhpSelection();

        foreach ([
            'allow_url_fopen',
            'display_errors',
            'file_uploads',
            'max_execution_time',
            'memory_limit',
            'post_max_size',
            'register_globals',
            'upload_max_filesize',
        ] as $key) {
            $this->assertArrayHasKey($key, $selection);
        }
        $this->assertSame(ini_get('memory_limit'), $selection['memory_limit']);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::getPhpDecoder
     */
    public function testGetPhpDecoderAlwaysStartsWithZend(): void
    {
        $this->assertStringStartsWith('Zend', (new Diagnostics())->getPhpDecoder());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::isExecAllowed
     */
    public function testIsExecAllowedReportsBool(): void
    {
        $this->assertIsBool((new Diagnostics())->isExecAllowed());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::_getPhpVersion
     */
    public function testGetPhpVersionMatchesPhpversion(): void
    {
        $diag = new Diagnostics();
        $reflection = new \ReflectionMethod($diag, '_getPhpVersion');
        $reflection->setAccessible(true);
        $this->assertSame(phpversion(), $reflection->invoke($diag));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::_getDiskTotalSpace
     */
    public function testGetDiskTotalSpaceFormatsAsGiB(): void
    {
        $diag = new Diagnostics();
        $reflection = new \ReflectionMethod($diag, '_getDiskTotalSpace');
        $reflection->setAccessible(true);
        $this->assertStringEndsWith(' GiB', $reflection->invoke($diag));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::_getDiskFreeSpace
     */
    public function testGetDiskFreeSpaceFormatsAsGiB(): void
    {
        $diag = new Diagnostics();
        $reflection = new \ReflectionMethod($diag, '_getDiskFreeSpace');
        $reflection->setAccessible(true);
        $this->assertStringEndsWith(' GiB', $reflection->invoke($diag));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::_getApacheVersion
     */
    public function testGetApacheVersionReturnsServerSoftwareWhenApacheFunctionMissing(): void
    {
        $previous = $_SERVER['SERVER_SOFTWARE'] ?? null;
        $_SERVER['SERVER_SOFTWARE'] = 'TestServer/1.0';

        try {
            $diag = new Diagnostics();
            $reflection = new \ReflectionMethod($diag, '_getApacheVersion');
            $reflection->setAccessible(true);
            $result = $reflection->invoke($diag);

            // In CLI / non-Apache environments apache_get_version() does not exist,
            // so the fallback to $_SERVER['SERVER_SOFTWARE'] is what the test runs
            // against.
            if (!function_exists('apache_get_version')) {
                $this->assertSame('TestServer/1.0', $result);
            } else {
                $this->assertNotSame('', $result);
            }
        } finally {
            if ($previous === null) {
                unset($_SERVER['SERVER_SOFTWARE']);
            } else {
                $_SERVER['SERVER_SOFTWARE'] = $previous;
            }
        }
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::_getVirtualizationSystem
     */
    public function testGetVirtualizationSystemReturnsEmptyWhenNoVirtualHardwareDetected(): void
    {
        // Subclass overrides exec-driven helpers so we don't depend on lspci.
        $diag = new class () extends Diagnostics {
            public function isExecAllowed()
            {
                return true;
            }
            protected function _getDeviceList($sSystemType) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
            {
                return '';
            }
        };

        $reflection = new \ReflectionMethod($diag, '_getVirtualizationSystem');
        $reflection->setAccessible(true);
        $this->assertSame('', $reflection->invoke($diag));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::_getVirtualizationSystem
     */
    public function testGetVirtualizationSystemDetectsVMWare(): void
    {
        $diag = new class () extends Diagnostics {
            public function isExecAllowed()
            {
                return true;
            }
            protected function _getDeviceList($sSystemType) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
            {
                return $sSystemType === 'vmware' ? 'VMWare device' : '';
            }
        };

        $reflection = new \ReflectionMethod($diag, '_getVirtualizationSystem');
        $reflection->setAccessible(true);
        $this->assertSame('VMWare', $reflection->invoke($diag));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::_getVirtualizationSystem
     */
    public function testGetVirtualizationSystemDetectsVirtualBox(): void
    {
        $diag = new class () extends Diagnostics {
            public function isExecAllowed()
            {
                return true;
            }
            protected function _getDeviceList($sSystemType) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
            {
                return $sSystemType === 'VirtualBox' ? 'VirtualBox Graphics Adapter' : '';
            }
        };

        $reflection = new \ReflectionMethod($diag, '_getVirtualizationSystem');
        $reflection->setAccessible(true);
        $this->assertSame('VirtualBox', $reflection->invoke($diag));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\Diagnostics::_getVirtualizationSystem
     */
    public function testGetVirtualizationSystemReturnsEmptyWhenExecForbidden(): void
    {
        $diag = new class () extends Diagnostics {
            public function isExecAllowed()
            {
                return false;
            }
        };

        $reflection = new \ReflectionMethod($diag, '_getVirtualizationSystem');
        $reflection->setAccessible(true);
        $this->assertSame('', $reflection->invoke($diag));
    }
}
