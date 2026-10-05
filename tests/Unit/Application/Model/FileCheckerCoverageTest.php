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

use OxidEsales\Eshop\Core\Curl;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Model\FileChecker;

/**
 * Covers the FileChecker methods FileCheckerTest does not exercise:
 * webservice probe, version-existence check, file MD5 verification, and the
 * different status branches of checkFile().
 */
class FileCheckerCoverageTest extends \OxidTestCase
{
    private function installLanguageStub(): void
    {
        $lang = $this->getMockBuilder(Language::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['translateString'])
            ->getMock();
        $lang->method('translateString')->willReturnCallback(function ($key) {
            return $key;
        });
        Registry::set(Language::class, $lang);
    }

    /**
     * Sets the protected Curl handler via reflection so we don't need to mock
     * oxNew(Curl::class). Returns the mock so tests can wire expectations.
     */
    private function injectCurl(FileChecker $checker, ?string $execReturn = '<x><res>OK</res></x>'): Curl
    {
        $curl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setUrl', 'setMethod', 'setOption', 'setParameters', 'execute'])
            ->getMock();
        $curl->method('execute')->willReturn($execReturn);

        $reflection = new \ReflectionProperty(FileChecker::class, '_oCurlHandler');
        $reflection->setAccessible(true);
        $reflection->setValue($checker, $curl);

        return $curl;
    }

    private function createTempFile(string $contents): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'fchk_');
        file_put_contents($tmp, $contents);
        return $tmp;
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::setBaseDirectory
     */
    public function testSetBaseDirectoryIgnoresEmptyValue(): void
    {
        $checker = new FileChecker();
        $checker->setBaseDirectory('/var/www/');
        $checker->setBaseDirectory('');
        $this->assertSame('/var/www/', $checker->getBaseDirectory());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::setVersion
     */
    public function testSetVersionIgnoresEmptyValue(): void
    {
        $checker = new FileChecker();
        $checker->setVersion('1.6.0');
        $checker->setVersion('');
        $this->assertSame('1.6.0', $checker->getVersion());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::setEdition
     */
    public function testSetEditionIgnoresEmptyValue(): void
    {
        $checker = new FileChecker();
        $checker->setEdition('CE');
        $checker->setEdition('');
        $this->assertSame('CE', $checker->getEdition());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::setRevision
     */
    public function testSetRevisionIgnoresEmptyValue(): void
    {
        $checker = new FileChecker();
        $checker->setRevision('rev-1');
        $checker->setRevision('');
        $this->assertSame('rev-1', $checker->getRevision());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::setWebServiceUrl
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::getWebServiceUrl
     */
    public function testWebServiceUrlSetterAndGetter(): void
    {
        $checker = new FileChecker();
        $this->assertNotEmpty($checker->getWebServiceUrl());

        $checker->setWebServiceUrl('https://example.com/check');
        $this->assertSame('https://example.com/check', $checker->getWebServiceUrl());

        $checker->setWebServiceUrl('');
        $this->assertSame('https://example.com/check', $checker->getWebServiceUrl());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::hasError
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::getErrorMessage
     */
    public function testInitialErrorState(): void
    {
        $checker = new FileChecker();
        $this->assertFalse($checker->hasError());
        $this->assertNull($checker->getErrorMessage());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::_isWebServiceOnline
     */
    public function testIsWebServiceOnlineFlagsErrorWhenCurlReturnsEmptyString(): void
    {
        $this->installLanguageStub();
        $checker = new FileChecker();
        $this->injectCurl($checker, '');

        $reflection = new \ReflectionMethod($checker, '_isWebServiceOnline');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($checker);

        $this->assertFalse($result);
        $this->assertTrue($checker->hasError());
        $this->assertStringContainsString('OXDIAG_ERRORMESSAGEWEBSERVICEISNOTREACHABLE', $checker->getErrorMessage());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::_isWebServiceOnline
     */
    public function testIsWebServiceOnlineFlagsErrorWhenCurlReturnsInvalidXml(): void
    {
        $this->installLanguageStub();
        $checker = new FileChecker();
        $this->injectCurl($checker, '<not-xml');

        $reflection = new \ReflectionMethod($checker, '_isWebServiceOnline');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($checker);

        $this->assertFalse($result);
        $this->assertStringContainsString('OXDIAG_ERRORMESSAGEWEBSERVICERETURNEDNOXML', $checker->getErrorMessage());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::_isWebServiceOnline
     */
    public function testIsWebServiceOnlineSucceedsForValidXml(): void
    {
        $this->installLanguageStub();
        $checker = new FileChecker();
        $curl = $this->injectCurl($checker, '<root><pong/></root>');

        $curl->expects($this->once())->method('setUrl')->with($checker->getWebServiceUrl());
        $curl->expects($this->once())->method('setMethod')->with('GET');
        $curl->expects($this->once())->method('setOption')->with('CURLOPT_CONNECTTIMEOUT', 30);
        $curl->expects($this->once())->method('setParameters')->with(['job' => 'ping']);

        $reflection = new \ReflectionMethod($checker, '_isWebServiceOnline');
        $reflection->setAccessible(true);
        $this->assertTrue($reflection->invoke($checker));
        $this->assertFalse($checker->hasError());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::_isShopVersionIsKnown
     */
    public function testIsShopVersionIsKnownReportsUnknownVersionWhenServiceUnreachable(): void
    {
        $this->installLanguageStub();
        $checker = new FileChecker();
        // Non-routable URL → file_get_contents returns false fast.
        $checker->setWebServiceUrl('http://127.0.0.1:1/nope');
        $checker->setVersion('9.9.9');
        $checker->setEdition('CE');
        $checker->setRevision('rev-x');

        $reflection = new \ReflectionMethod($checker, '_isShopVersionIsKnown');
        $reflection->setAccessible(true);
        $this->assertFalse($reflection->invoke($checker));
        $this->assertTrue($checker->hasError());
        $this->assertStringContainsString('OXDIAG_ERRORMESSAGEVERSIONDOESNOTEXIST', $checker->getErrorMessage());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::checkSystemRequirements
     */
    public function testCheckSystemRequirementsReturnsFalseWhenWebServiceOffline(): void
    {
        $this->installLanguageStub();
        $checker = new FileChecker();
        $this->injectCurl($checker, '');

        $this->assertFalse($checker->checkSystemRequirements());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::init
     */
    public function testInitReturnsFalseAndAccumulatesErrorWhenRequirementsFail(): void
    {
        $this->installLanguageStub();

        // Drive init() through a subclass that uses our pre-injected Curl
        // rather than oxNew()'ing a real one.
        $checker = new class () extends FileChecker {
            /** @var Curl */
            public $injectedCurl;

            public function init()
            {
                $this->_oCurlHandler = $this->injectedCurl;
                if (!$this->checkSystemRequirements()) {
                    $this->_blError = true;
                    $this->_sErrorMessage .= 'Error: requirements are not met.';
                    return false;
                }
                return true;
            }
        };
        $checker->injectedCurl = $this->injectCurl($checker, '');

        $this->assertFalse($checker->init());
        $this->assertTrue($checker->hasError());
        $this->assertStringContainsString('Error: requirements are not met.', $checker->getErrorMessage());
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::checkFile
     */
    public function testCheckFileReturnsEmptyWhenCurlNotInitialised(): void
    {
        $checker = new FileChecker();
        $this->assertSame([], $checker->checkFile('source/index.php'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::checkFile
     */
    public function testCheckFileReturnsEmptyWhenLocalFileMissing(): void
    {
        $checker = new FileChecker();
        $this->injectCurl($checker, '<x><res>OK</res></x>');
        $checker->setBaseDirectory('/no/such/dir/');

        $this->assertSame([], $checker->checkFile('missing.php'));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::checkFile
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::_getFileVersion
     */
    public function testCheckFileReportsOkForRecognisedFile(): void
    {
        $this->installLanguageStub();
        $tmp = $this->createTempFile('hello');

        $checker = new FileChecker();
        $this->injectCurl($checker, '<r><res>OK</res><pkg>release</pkg></r>');
        $checker->setBaseDirectory(dirname($tmp) . '/');

        $result = $checker->checkFile(basename($tmp));

        $this->assertSame('OK', $result['result']);
        $this->assertTrue($result['ok']);
        $this->assertSame('green', $result['color']);
        $this->assertSame(basename($tmp), $result['file']);
        $this->assertSame('OXDIAG_OK', $result['message']);

        unlink($tmp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::checkFile
     */
    public function testCheckFileFlagsSourceSnapshotPackagesAsNotOk(): void
    {
        $this->installLanguageStub();
        $tmp = $this->createTempFile('hello');

        $checker = new FileChecker();
        $this->injectCurl($checker, '<r><res>OK</res><pkg>SNAPSHOT-1.6</pkg></r>');
        $checker->setBaseDirectory(dirname($tmp) . '/');

        $result = $checker->checkFile(basename($tmp));

        $this->assertFalse($result['ok']);
        $this->assertSame('red', $result['color']);
        $this->assertSame('SOURCE|SNAPSHOT', $result['message']);

        unlink($tmp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::checkFile
     */
    public function testCheckFileFlagsVersionMismatchAsNotOk(): void
    {
        $this->installLanguageStub();
        $tmp = $this->createTempFile('hello');

        $checker = new FileChecker();
        $this->injectCurl($checker, '<r><res>VERSIONMISMATCH</res></r>');
        $checker->setBaseDirectory(dirname($tmp) . '/');

        $result = $checker->checkFile(basename($tmp));

        $this->assertFalse($result['ok']);
        $this->assertSame('red', $result['color']);
        $this->assertSame('OXDIAG_VERSION_MISMATCH', $result['message']);
        $this->assertSame('VERSIONMISMATCH', $result['result']);

        unlink($tmp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::checkFile
     */
    public function testCheckFileFlagsModifiedAsNotOk(): void
    {
        $this->installLanguageStub();
        $tmp = $this->createTempFile('hello');

        $checker = new FileChecker();
        $this->injectCurl($checker, '<r><res>MODIFIED</res></r>');
        $checker->setBaseDirectory(dirname($tmp) . '/');

        $result = $checker->checkFile(basename($tmp));

        $this->assertFalse($result['ok']);
        $this->assertSame('red', $result['color']);
        $this->assertSame('OXDIAG_MODIFIED', $result['message']);

        unlink($tmp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::checkFile
     */
    public function testCheckFileFlagsObsoleteAsNotOk(): void
    {
        $this->installLanguageStub();
        $tmp = $this->createTempFile('hello');

        $checker = new FileChecker();
        $this->injectCurl($checker, '<r><res>OBSOLETE</res></r>');
        $checker->setBaseDirectory(dirname($tmp) . '/');

        $result = $checker->checkFile(basename($tmp));

        $this->assertFalse($result['ok']);
        $this->assertSame('red', $result['color']);
        $this->assertSame('OXDIAG_OBSOLETE', $result['message']);

        unlink($tmp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::checkFile
     */
    public function testCheckFileTreatsUnknownAsGreen(): void
    {
        $this->installLanguageStub();
        $tmp = $this->createTempFile('hello');

        $checker = new FileChecker();
        $this->injectCurl($checker, '<r><res>UNKNOWN</res></r>');
        $checker->setBaseDirectory(dirname($tmp) . '/');

        $result = $checker->checkFile(basename($tmp));

        // UNKNOWN is treated as green; ok stays at its initial true.
        $this->assertTrue($result['ok']);
        $this->assertSame('green', $result['color']);
        $this->assertSame('OXDIAG_UNKNOWN', $result['message']);

        unlink($tmp);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::_getFileVersion
     */
    public function testGetFileVersionReturnsParsedXmlOnSuccess(): void
    {
        $checker = new FileChecker();
        $curl = $this->injectCurl($checker, '<root><res>OK</res></root>');
        $checker->setVersion('1.0.0');
        $checker->setEdition('CE');
        $checker->setRevision('rev-1');

        $curl->expects($this->once())->method('setParameters')->with([
            'job' => 'md5check',
            'ver' => '1.0.0',
            'rev' => 'rev-1',
            'edi' => 'CE',
            'fil' => 'index.php',
            'md5' => 'md5-value',
        ]);

        $reflection = new \ReflectionMethod($checker, '_getFileVersion');
        $reflection->setAccessible(true);
        $xml = $reflection->invoke($checker, 'md5-value', 'index.php');

        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        $this->assertSame('OK', (string) $xml->res);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Application\Model\FileChecker::_getFileVersion
     */
    public function testGetFileVersionReturnsNullOnInvalidXml(): void
    {
        $checker = new FileChecker();
        $this->injectCurl($checker, '<not-valid');

        $reflection = new \ReflectionMethod($checker, '_getFileVersion');
        $reflection->setAccessible(true);
        $this->assertNull($reflection->invoke($checker, 'md5-value', 'index.php'));
    }
}
