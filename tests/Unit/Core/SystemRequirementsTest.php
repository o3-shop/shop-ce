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

use OxidEsales\Eshop\Core\SystemRequirements;
use PHPUnit\Framework\MockObject\MockObject as Mock;
use Psr\Container\ContainerInterface;

class SystemRequirementsTest extends \OxidTestCase
{
    public function testGetBytes()
    {
        $systemRequirements = new SystemRequirements();

        $this->assertEquals(33554432, $systemRequirements->UNITgetBytes('32M'));
        $this->assertEquals(32768, $systemRequirements->UNITgetBytes('32K'));
        $this->assertEquals(34359738368, $systemRequirements->UNITgetBytes('32G'));
    }

    public function testGetRequiredModules()
    {
        $systemRequirements = new SystemRequirements();

        $requiredModules = $systemRequirements->getRequiredModules();
        $this->assertTrue(is_array($requiredModules));
        $requirementGroups = array_unique(array_values($requiredModules));

        $this->assertCount(3, $requirementGroups);
    }

    public function testGetModuleInfo()
    {
        /** @var SystemRequirements|Mock $systemRequirementsMock */
        $systemRequirementsMock = $this->getMock(SystemRequirements::class, ['checkMbString', 'checkModRewrite']);

        $systemRequirementsMock->expects($this->once())->method('checkMbString');
        $systemRequirementsMock->expects($this->never())->method('checkModRewrite');

        $systemRequirementsMock->getModuleInfo('mb_string');
    }

    /**
     * Probe reached oxseo.php and the RewriteRule fired: mod_rewrite is confirmed working.
     */
    public function testCheckModRewriteReturnsOkWhenRewriteFired()
    {
        $systemRequirements = $this->getMockBuilder(SystemRequirements::class)
            ->onlyMethods(['_getModRewriteResponse'])
            ->getMock();
        $systemRequirements->method('_getModRewriteResponse')
            ->willReturn("HTTP/1.1 200 OK\r\nConnection: close\r\n\r\nmod_rewrite_on");

        $this->assertSame(
            SystemRequirements::MODULE_STATUS_OK,
            $systemRequirements->UNITcheckModRewrite($this->getModRewriteHostInfoStub())
        );
    }

    /**
     * Probe reached oxseo.php but the RewriteRule did NOT fire: mod_rewrite is genuinely off.
     */
    public function testCheckModRewriteBlocksSetupWhenRewriteDidNotFire()
    {
        $systemRequirements = $this->getMockBuilder(SystemRequirements::class)
            ->onlyMethods(['_getModRewriteResponse'])
            ->getMock();
        $systemRequirements->method('_getModRewriteResponse')
            ->willReturn("HTTP/1.1 200 OK\r\nConnection: close\r\n\r\nmod_rewrite_off");

        $this->assertSame(
            SystemRequirements::MODULE_STATUS_BLOCKS_SETUP,
            $systemRequirements->UNITcheckModRewrite($this->getModRewriteHostInfoStub())
        );
    }

    /**
     * Probe got a response that contains neither marker (e.g. a reverse-proxy / DDEV 301 redirect):
     * the result is undeterminable and must NOT block setup.
     */
    public function testCheckModRewriteIsUndeterminableWhenNeitherMarkerIsPresent()
    {
        $systemRequirements = $this->getMockBuilder(SystemRequirements::class)
            ->onlyMethods(['_getModRewriteResponse'])
            ->getMock();
        $systemRequirements->method('_getModRewriteResponse')
            ->willReturn("HTTP/1.1 301 Moved Permanently\r\nLocation: https://example.ddev.site/oxseo.php\r\nConnection: close\r\n\r\n");

        $this->assertSame(
            SystemRequirements::MODULE_STATUS_UNABLE_TO_DETECT,
            $systemRequirements->UNITcheckModRewrite($this->getModRewriteHostInfoStub())
        );
    }

    private function getModRewriteHostInfoStub(): array
    {
        return ['host' => '127.0.0.1', 'port' => 80, 'dir' => '/', 'ssl' => false];
    }

    /**
     * With a self-signed certificate the TLS handshake of the mod_rewrite self-probe fails, so on
     * https shop URLs setup could not detect mod_rewrite (issue #27). When the config.inc.php flag
     * blAllowSelfSignedCertificates is enabled (development setups), the probe must accept the
     * certificate and return the server response.
     */
    public function testModRewriteProbeAcceptsSelfSignedCertificateWhenAllowed()
    {
        [$process, $pipes, $port, $certFile] = $this->startSelfSignedTlsServer();

        $configFile = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Core\ConfigFile::class);
        $originalFlag = $configFile->getVar('blAllowSelfSignedCertificates');
        $configFile->setVar('blAllowSelfSignedCertificates', true);

        try {
            $systemRequirements = new SystemRequirements();
            $response = $systemRequirements->UNITgetModRewriteResponse(
                ['host' => '127.0.0.1', 'port' => $port, 'dir' => '/', 'ssl' => true]
            );

            $this->assertIsString($response, 'Probe must complete the TLS handshake when self-signed certificates are allowed');
            $this->assertStringContainsString('mod_rewrite_on', $response);
        } finally {
            $configFile->setVar('blAllowSelfSignedCertificates', $originalFlag);
            $this->stopSelfSignedTlsServer($process, $pipes, $certFile);
        }
    }

    /**
     * Secure default: without blAllowSelfSignedCertificates the probe must keep verifying
     * certificates and refuse the handshake with a self-signed one.
     */
    public function testModRewriteProbeRejectsSelfSignedCertificateByDefault()
    {
        [$process, $pipes, $port, $certFile] = $this->startSelfSignedTlsServer();

        $configFile = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Core\ConfigFile::class);
        $originalFlag = $configFile->getVar('blAllowSelfSignedCertificates');
        $configFile->setVar('blAllowSelfSignedCertificates', false);

        try {
            $systemRequirements = new SystemRequirements();
            $response = $systemRequirements->UNITgetModRewriteResponse(
                ['host' => '127.0.0.1', 'port' => $port, 'dir' => '/', 'ssl' => true]
            );

            $this->assertFalse($response, 'Probe must reject self-signed certificates unless explicitly allowed');
        } finally {
            $configFile->setVar('blAllowSelfSignedCertificates', $originalFlag);
            $this->stopSelfSignedTlsServer($process, $pipes, $certFile);
        }
    }

    /**
     * Starts a one-shot TLS server on a random loopback port, using a freshly generated
     * self-signed certificate. It answers any successfully handshaked HTTP request with the
     * 'mod_rewrite_on' marker the probe looks for.
     *
     * @return array [proc resource, pipes, port, certFile]
     */
    private function startSelfSignedTlsServer(): array
    {
        $certFile = tempnam(sys_get_temp_dir(), 'o3tstcrt');
        $this->createSelfSignedCertificate($certFile);

        $serverCode = <<<'SRV'
$ctx = stream_context_create(['ssl' => ['local_cert' => $argv[1]]]);
$srv = stream_socket_server('ssl://127.0.0.1:0', $errNo, $errStr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $ctx);
if (!$srv) { fwrite(STDERR, $errNo . ' ' . $errStr); exit(1); }
echo stream_socket_get_name($srv, false), "\n";
$end = microtime(true) + 15;
while (microtime(true) < $end) {
    $client = @stream_socket_accept($srv, 1);
    if (!$client) { continue; }
    stream_set_timeout($client, 2);
    fread($client, 4096);
    fwrite($client, "HTTP/1.1 200 OK\r\nConnection: close\r\n\r\nmod_rewrite_on");
    fclose($client);
}
SRV;

        $process = proc_open(
            [PHP_BINARY, '-d', 'error_reporting=0', '-r', $serverCode, $certFile],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        $this->assertIsResource($process, 'Could not start TLS test server process');

        stream_set_timeout($pipes[1], 5);
        $listenAddress = (string) fgets($pipes[1]);
        $port = (int) substr($listenAddress, strrpos($listenAddress, ':') + 1);
        $this->assertGreaterThan(0, $port, 'TLS test server did not report a listen port');

        return [$process, $pipes, $port, $certFile];
    }

    private function stopSelfSignedTlsServer($process, array $pipes, string $certFile): void
    {
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        if (is_resource($process)) {
            proc_terminate($process);
            proc_close($process);
        }
        if (file_exists($certFile)) {
            unlink($certFile);
        }
    }

    private function createSelfSignedCertificate(string $certFile): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => '127.0.0.1'], $key, ['digest_alg' => 'sha256']);
        $cert = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);

        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($key, $keyPem);
        file_put_contents($certFile, $certPem . $keyPem);
    }

    /**
     * Testing SystemRequirements::checkServerPermissions()
     */
    public function testCheckServerPermissions()
    {
        $systemRequirementsMock = $this
            ->getMockBuilder(SystemRequirements::class)
            ->setMethods(['isAdmin'])
            ->getMock();

        $systemRequirementsMock->method('isAdmin')->willReturn(false);

        $this->assertEquals(2, $systemRequirementsMock->checkServerPermissions());
    }

    public function testCheckServerPermissionsReturnsSetupBlockedStatusIfDirectoriesDoNotExist()
    {
        $systemRequirementsMock = $this
            ->getMockBuilder(SystemRequirements::class)
            ->setMethods(['isAdmin'])
            ->getMock();

        $systemRequirementsMock->method('isAdmin')->willReturn(false);

        $this->assertEquals(0, $systemRequirementsMock->checkServerPermissions('nonExistentSourcePath'));
    }

    public function testCheckCollation()
    {
        $systemRequirements = new SystemRequirements();

        $collations = $systemRequirements->checkCollation();

        $this->assertEquals(0, count($collations));
    }

    public function testGetSysReqStatus()
    {
        /** @var SystemRequirements|Mock $systemRequirementsMock */
        $systemRequirementsMock = $this->getMock(SystemRequirements::class, ['getSystemInfo']);
        $systemRequirementsMock->expects($this->once())->method('getSystemInfo');

        $this->assertTrue($systemRequirementsMock->getSysReqStatus());
    }

    public function testGetReqInfoUrlWillReturnStringParsableAsUrl(): void
    {
        $url = (new SystemRequirements())->getReqInfoUrl('');

        $this->assertTrue(
            \array_key_exists('scheme', \parse_url($url))
        );
    }

    public function testGetReqInfoUrlWithKnownParameterWillAddAnchorToUrl(): void
    {
        $parameter = 'php_version';
        $anchor = '#php';

        $url = (new SystemRequirements())->getReqInfoUrl($parameter);

        $this->assertStringContainsString($anchor, $url);
    }

    public function testGetReqInfoUrlWithServerPermissionsParameterWillAddAnchorToUrl(): void
    {
        $parameter = 'server_permissions';
        $anchor = '#adjusting-file-and-directory-permissions';

        $url = (new SystemRequirements())->getReqInfoUrl($parameter);

        $this->assertStringContainsString($anchor, $url);
        // server_permissions uses the preparation info URL, not the regular one
        $this->assertStringContainsString('PrepareInstallation.html', $url);
    }

    public function testGetReqInfoUrlWithUnknownParameterWillReturnUnchangedUrl(): void
    {
        $unknownParameter = uniqid('parameter-', true);

        $url1 = (new SystemRequirements())->getReqInfoUrl('');
        $url2 = (new SystemRequirements())->getReqInfoUrl($unknownParameter);

        $this->assertEquals($url1, $url2);
    }

    /**
     * Testing SystemRequirements::_getShopHostInfoFromConfig()
     *
     * @return null
     */
    public function testGetShopHostInfoFromConfig()
    {
        $this->getConfig()->setConfigParam('sShopURL', 'http://www.testshopurl.lt/testsubdir1/insideit2/');
        $systemRequirements = new SystemRequirements();
        $this->assertEquals(
            [
                'host' => 'www.testshopurl.lt',
                'port' => 80,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => false,
            ],
            $systemRequirements->UNITgetShopHostInfoFromConfig()
        );
        $this->getConfig()->setConfigParam('sShopURL', 'https://www.testshopurl.lt/testsubdir1/insideit2/');
        $this->assertEquals(
            [
                'host' => 'www.testshopurl.lt',
                'port' => 443,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => true,
            ],
            $systemRequirements->UNITgetShopHostInfoFromConfig()
        );
        $this->getConfig()->setConfigParam('sShopURL', 'https://51.1586.51.15:21/testsubdir1/insideit2/');
        $this->assertEquals(
            [
                'host' => '51.1586.51.15',
                'port' => 21,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => true,
            ],
            $systemRequirements->UNITgetShopHostInfoFromConfig()
        );
        $this->getConfig()->setConfigParam('sShopURL', '51.1586.51.15:21/testsubdir1/insideit2/');
        $this->assertEquals(
            [
                'host' => '51.1586.51.15',
                'port' => 21,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => false,
            ],
            $systemRequirements->UNITgetShopHostInfoFromConfig()
        );
    }

    /**
     * Testing SystemRequirements::_getShopSSLHostInfoFromConfig()
     *
     * @return null
     */
    public function testGetShopSSLHostInfoFromConfig()
    {
        $this->getConfig()->setConfigParam('sSSLShopURL', 'http://www.testshopurl.lt/testsubdir1/insideit2/');
        $systemRequirements = new SystemRequirements();
        $this->assertEquals(
            [
                'host' => 'www.testshopurl.lt',
                'port' => 80,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => false,
            ],
            $systemRequirements->UNITgetShopSSLHostInfoFromConfig()
        );
        $this->getConfig()->setConfigParam('sSSLShopURL', 'https://www.testshopurl.lt/testsubdir1/insideit2/');
        $this->assertEquals(
            [
                'host' => 'www.testshopurl.lt',
                'port' => 443,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => true,
            ],
            $systemRequirements->UNITgetShopSSLHostInfoFromConfig()
        );
        $this->getConfig()->setConfigParam('sSSLShopURL', 'https://51.1586.51.15:21/testsubdir1/insideit2/');
        $this->assertEquals(
            [
                'host' => '51.1586.51.15',
                'port' => 21,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => true,
            ],
            $systemRequirements->UNITgetShopSSLHostInfoFromConfig()
        );
        $this->getConfig()->setConfigParam('sSSLShopURL', '51.1586.51.15:21/testsubdir1/insideit2/');
        $this->assertEquals(
            [
                'host' => '51.1586.51.15',
                'port' => 21,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => false,
            ],
            $systemRequirements->UNITgetShopSSLHostInfoFromConfig()
        );
    }

    /**
     * Testing SystemRequirements::_getShopHostInfoFromServerVars()
     *
     * @return null
     */
    public function testGetShopHostInfoFromServerVars()
    {
        $_SERVER['SCRIPT_NAME'] = '/testsubdir1/insideit2/setup/index.php';
        $_SERVER['HTTPS'] = null;
        $_SERVER['SERVER_PORT'] = null;
        $_SERVER['HTTP_HOST'] = 'www.testshopurl.lt';

        $systemRequirements = new SystemRequirements();
        $this->assertEquals(
            [
                'host' => 'www.testshopurl.lt',
                'port' => 80,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => false,
            ],
            $systemRequirements->UNITgetShopHostInfoFromServerVars()
        );

        $_SERVER['SCRIPT_NAME'] = '/testsubdir1/insideit2/setup/index.php';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = null;
        $_SERVER['HTTP_HOST'] = 'www.testshopurl.lt';
        $this->assertEquals(
            [
                'host' => 'www.testshopurl.lt',
                'port' => 443,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => true,
            ],
            $systemRequirements->UNITgetShopHostInfoFromServerVars()
        );

        $_SERVER['SCRIPT_NAME'] = '/testsubdir1/insideit2/setup/index.php';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = 21;
        $_SERVER['HTTP_HOST'] = '51.1586.51.15';
        $this->assertEquals(
            [
                'host' => '51.1586.51.15',
                'port' => 21,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => true,
            ],
            $systemRequirements->UNITgetShopHostInfoFromServerVars()
        );

        $_SERVER['SCRIPT_NAME'] = '/testsubdir1/insideit2/setup/index.php';
        $_SERVER['HTTPS'] = null;
        $_SERVER['SERVER_PORT'] = '21';
        $_SERVER['HTTP_HOST'] = '51.1586.51.15';
        $this->assertEquals(
            [
                'host' => '51.1586.51.15',
                'port' => 21,
                'dir'  => '/testsubdir1/insideit2/',
                'ssl'  => false,
            ],
            $systemRequirements->UNITgetShopHostInfoFromServerVars()
        );
    }

    /**
     * Behind a reverse proxy / TLS terminator (DDEV, Traefik, nginx ingress, load balancer) the PHP
     * process sees the internal scheme/port while the client spoke HTTPS to the proxy. The forwarded
     * headers must take precedence so the mod_rewrite self-probe dials the public scheme/port.
     *
     * @dataProvider providerGetShopHostInfoFromServerVarsBehindProxy
     */
    public function testGetShopHostInfoFromServerVarsBehindProxy(array $server, array $expected)
    {
        $backup = $_SERVER;

        $_SERVER['SCRIPT_NAME'] = '/setup/index.php';
        $_SERVER['HTTP_HOST'] = 'shop.ddev.site';
        $_SERVER['HTTPS'] = null;
        $_SERVER['SERVER_PORT'] = 80;
        unset(
            $_SERVER['HTTP_X_FORWARDED_PROTO'],
            $_SERVER['HTTP_X_FORWARDED_PORT'],
            $_SERVER['HTTP_X_FORWARDED_SSL']
        );
        foreach ($server as $key => $value) {
            $_SERVER[$key] = $value;
        }

        $systemRequirements = new SystemRequirements();
        try {
            $this->assertEquals(
                $expected + ['host' => 'shop.ddev.site', 'dir' => '/'],
                $systemRequirements->UNITgetShopHostInfoFromServerVars()
            );
        } finally {
            $_SERVER = $backup;
        }
    }

    public function providerGetShopHostInfoFromServerVarsBehindProxy(): array
    {
        return [
            'X-Forwarded-Proto https, default https port' => [
                ['HTTP_X_FORWARDED_PROTO' => 'https'],
                ['port' => 443, 'ssl' => true],
            ],
            'X-Forwarded-Proto https with explicit forwarded port' => [
                ['HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => '8443'],
                ['port' => 8443, 'ssl' => true],
            ],
            'X-Forwarded-Ssl on' => [
                ['HTTP_X_FORWARDED_SSL' => 'on'],
                ['port' => 443, 'ssl' => true],
            ],
            'X-Forwarded-Proto http stays plain on internal port' => [
                ['HTTP_X_FORWARDED_PROTO' => 'http'],
                ['port' => 80, 'ssl' => false],
            ],
        ];
    }

    public function testCheckTemplateBlockIfTemplateDoNotExists()
    {
        $systemRequirements = new SystemRequirements();

        $this->assertFalse($systemRequirements->UNITcheckTemplateBlock('test.tpl', 'nonimportanthere'));
    }

    /**
     * base functionality test
     *
     * @dataProvider dataProviderCheckTemplateBlock
     */
    public function testCheckTemplateBlock($templateContent, $blockName, $result)
    {
        $templateLoader = $this->getMockBuilder(\OxidEsales\EshopCommunity\Internal\Framework\Templating\Loader\TemplateLoader::class)
            ->disableOriginalConstructor()
            ->setMethods(['exists', 'getContext'])
            ->getMock();
        $templateLoader->expects($this->any())
            ->method('exists')
            ->will($this->returnValue(true));
        $templateLoader->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($templateContent));

        $container = $this->getMockBuilder(ContainerInterface::class)
            ->setMethods(['get', 'has'])
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->with($this->equalTo('oxid_esales.templating.template.loader'))
            ->will($this->returnValue($templateLoader));
        $systemRequirements = $this->getMockBuilder(SystemRequirements::class)
            ->setMethods(['getContainer'])
            ->getMock();
        $systemRequirements->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($container));

        $this->assertSame($result, $systemRequirements->UNITcheckTemplateBlock('tests.tpl', $blockName));
    }

    /**
     * @return array
     */
    public function dataProviderCheckTemplateBlock()
    {
        $templateContent = '[{block name="block1"}][{/block}][{block name="block2"}][{/block}]';

        return [
            [$templateContent, 'block1', true],
            [$templateContent, 'block2', true],
            [$templateContent, 'block3', false],
        ];
    }

    /**
     * base functionality test
     */
    public function testGetMissingTemplateBlocksIfNotFound()
    {
        $resultSetMock = $this->getMock('stdclass', ['fetchRow', 'count']);
        $resultSetMock->expects($this->exactly(1))->method('fetchRow')
            ->will($this->evalFunction('{$_this->EOF = true;}'));
        $resultSetMock->expects($this->exactly(1))->method('count')
            ->will($this->returnValue(1));
        $resultSetMock->fields = [
            'OXTEMPLATE'  => '_OXTEMPLATE_',
            'OXBLOCKNAME' => '_OXBLOCKNAME_',
            'OXMODULE'    => '_OXMODULE_',
        ];

        /** @var SystemRequirements|Mock $systemRequirementsMock */
        $systemRequirementsMock = $this->getMock(\OxidEsales\Eshop\Core\SystemRequirements::class, ['_checkTemplateBlock', 'fetchBlockRecords']);
        $systemRequirementsMock->expects($this->exactly(1))->method('_checkTemplateBlock')
            ->with($this->equalTo('_OXTEMPLATE_'), $this->equalTo('_OXBLOCKNAME_'))
            ->will($this->returnValue(false));
        $systemRequirementsMock->expects($this->exactly(1))->method('fetchBlockRecords')
            ->willReturn($resultSetMock);

        $this->assertEquals(
            [
                [
                    'module'   => '_OXMODULE_',
                    'block'    => '_OXBLOCKNAME_',
                    'template' => '_OXTEMPLATE_',
                ],
            ],
            $systemRequirementsMock->getMissingTemplateBlocks()
        );
    }

    /**
     * base functionality test
     */
    public function testGetMissingTemplateBlocksIfFound()
    {
        $resultSetMock = $this->getMock('stdclass', ['fetchRow', 'count']);
        $resultSetMock->expects($this->exactly(1))->method('fetchRow')
            ->will($this->evalFunction('{$_this->EOF = true;}'));
        $resultSetMock->expects($this->exactly(1))->method('count')
            ->will($this->returnValue(1));
        $resultSetMock->fields = [
            'OXTEMPLATE'  => '_OXTEMPLATE_',
            'OXBLOCKNAME' => '_OXBLOCKNAME_',
            'OXMODULE'    => '_OXMODULE_',
        ];

        /** @var SystemRequirements|Mock $systemRequirementsMock */
        $systemRequirementsMock = $this->getMock(\OxidEsales\Eshop\Core\SystemRequirements::class, ['_checkTemplateBlock', 'fetchBlockRecords']);
        $systemRequirementsMock->expects($this->exactly(1))->method('_checkTemplateBlock')
            ->with($this->equalTo('_OXTEMPLATE_'), $this->equalTo('_OXBLOCKNAME_'))
            ->will($this->returnValue(true));
        $systemRequirementsMock->expects($this->exactly(1))->method('fetchBlockRecords')
            ->willReturn($resultSetMock);

        $this->assertEquals(
            [],
            $systemRequirementsMock->getMissingTemplateBlocks()
        );
    }

    /**
     * Provides different server configuration to check memory limit.
     *
     * @return array
     */
    public function providerCheckMemoryLimit()
    {
        $memoryLimitsWithExpectedSystemHealth = [
            ['8M', 0],
            ['31M', 0],
            ['32M', 1],
            ['59M', 1],
            ['60M', 2],
            ['61M', 2],
            ['-1', 2],
        ];

        return $memoryLimitsWithExpectedSystemHealth;
    }

    /**
     * Testing SystemRequirements::checkMemoryLimit()
     * contains assertion for bug #5083
     *
     * @param string $memoryLimit    how much memory allocated.
     * @param int    $expectedResult if fits system requirements.
     *
     * @dataProvider providerCheckMemoryLimit
     *
     * @return null
     */
    public function testCheckMemoryLimit($memoryLimit, $expectedResult)
    {
        $systemRequirements = new SystemRequirements();

        $this->assertEquals($expectedResult, $systemRequirements->checkMemoryLimit($memoryLimit));
    }

    public function testFilterSystemRequirementsInfo()
    {
        $systemRequirementsInfoInput = [
            'group_a' => [
                'module_a' => SystemRequirements::MODULE_STATUS_BLOCKS_SETUP,
                'module_b' => SystemRequirements::MODULE_STATUS_OK,
            ],
            'group_b' => [
                'module_c' => SystemRequirements::MODULE_STATUS_FITS_MINIMUM_REQUIREMENTS,
            ],
        ];

        $expectedSystemRequirementsInfo = [
            'group_a' => [
                'module_a' => SystemRequirements::MODULE_STATUS_OK,
                'module_b' => SystemRequirements::MODULE_STATUS_FITS_MINIMUM_REQUIREMENTS,
            ],
            'group_b' => [
                'module_c' => SystemRequirements::MODULE_STATUS_BLOCKS_SETUP,
            ],
        ];

        $filterFunction = function ($groupId, $moduleId, $status) {
            if (($groupId === 'group_a') && ($moduleId === 'module_a')) {
                $status = SystemRequirements::MODULE_STATUS_OK;
            }
            if (($groupId === 'group_a') && ($moduleId === 'module_b')) {
                $status = SystemRequirements::MODULE_STATUS_FITS_MINIMUM_REQUIREMENTS;
            }
            if (($groupId === 'group_b') && ($moduleId === 'module_c')) {
                $status = SystemRequirements::MODULE_STATUS_BLOCKS_SETUP;
            }

            return $status;
        };

        $actualSystemRequirementsInfo = SystemRequirements::filter($systemRequirementsInfoInput, $filterFunction);

        $this->assertSame($expectedSystemRequirementsInfo, $actualSystemRequirementsInfo);
    }

    /**
     * @dataProvider canSetupContinuePositiveValuesProvider
     *
     * @param array $systemRequirementsInfo
     */
    public function testCanSetupContinueWithPositiveValues($systemRequirementsInfo)
    {
        $expectedValue = true;
        $actualValue = SystemRequirements::canSetupContinue($systemRequirementsInfo);

        $this->assertSame($expectedValue, $actualValue);
    }

    public function canSetupContinuePositiveValuesProvider()
    {
        $testCase1 = [
            'group_a' => [
                'module_a' => SystemRequirements::MODULE_STATUS_OK,
            ],
        ];

        $testCase2 = [
            'group_a' => [
                'module_a' => SystemRequirements::MODULE_STATUS_FITS_MINIMUM_REQUIREMENTS,
                'module_b' => SystemRequirements::MODULE_STATUS_OK,
            ],
            'group_b' => [
                'module_c' => SystemRequirements::MODULE_STATUS_UNABLE_TO_DETECT,
            ],
        ];

        return [
            [$testCase1],
            [$testCase2],
        ];
    }

    /**
     * @dataProvider canSetupContinueNegativeValuesProvider
     *
     * @param array $systemRequirementsInfo
     */
    public function testSetupCantContinueWithNegativeValue($systemRequirementsInfo)
    {
        $expectedValue = false;
        $actualValue = SystemRequirements::canSetupContinue($systemRequirementsInfo);

        $this->assertSame($expectedValue, $actualValue);
    }

    public function canSetupContinueNegativeValuesProvider()
    {
        $testCase1 = [
            'group_a' => [
                'module_a' => SystemRequirements::MODULE_STATUS_BLOCKS_SETUP,
            ],
        ];

        $testCase2 = [
            'group_a' => [
                'module_a' => SystemRequirements::MODULE_STATUS_UNABLE_TO_DETECT,
                'module_b' => SystemRequirements::MODULE_STATUS_FITS_MINIMUM_REQUIREMENTS,
            ],
            'group_b' => [
                'module_c' => SystemRequirements::MODULE_STATUS_BLOCKS_SETUP,
            ],
        ];

        return [
            [$testCase1],
            [$testCase2],
        ];
    }

    public function testIterateThroughSystemRequirementsInfo()
    {
        $systemRequirementsInfo = [
            'group_a' => [
                'module_a' => 0,
                'module_b' => 1,
            ],
            'group_b' => [
                'module_c' => 2,
                'module_d' => -1,
            ],
        ];

        $expectedOutput = [
            ['group_a', 'module_a', 0],
            ['group_a', 'module_b', 1],
            ['group_b', 'module_c', 2],
            ['group_b', 'module_d', -1],
        ];

        $actualOutput = [];
        $iteration = SystemRequirements::iterateThroughSystemRequirementsInfo($systemRequirementsInfo);
        foreach ($iteration as list($groupId, $moduleId, $moduleState)) {
            $actualOutput[] = [$groupId, $moduleId, $moduleState];
        }

        $this->assertSame($expectedOutput, $actualOutput);
    }
}
