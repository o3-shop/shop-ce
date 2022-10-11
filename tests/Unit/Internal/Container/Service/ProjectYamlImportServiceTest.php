<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Container\Service;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\DataObject\DIConfigWrapper;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Exception\NoServiceYamlException;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service\ProjectYamlImportService;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ProjectYamlImportServiceTest extends TestCase
{

    /**
     * @var ProjectYamlDaoInterface|MockObject
     */
    private $dao;

    /**
     * @var ProjectYamlImportService
     */
    private $service;

    private $savedArray;

    public function setup(): void
    {
        $this->dao = $this->getMockBuilder(ProjectYamlDaoInterface::class)
            ->setMethods(['loadProjectConfigFile', 'saveProjectConfigFile', 'loadDIConfigFile'])->getMock();
        $this->savedArray = [];
        $this->dao->method('saveProjectConfigFile')->willReturnCallback([$this, 'getConfigWrapper']);

        $context = $this->getMockBuilder(BasicContextInterface::class)->getMock();
        $context->method('getGeneratedServicesFilePath')->willReturn(__DIR__);
        $this->service = new ProjectYamlImportService($this->dao, $context);
    }

    public function getConfigWrapper(DIConfigWrapper $config)
    {
        $this->savedArray = $config->getConfigAsArray();
    }

    public function testAddImport()
    {
        $this->dao->method('loadProjectConfigFile')->willReturn(new DIConfigWrapper([]));
        $this->service->addImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule1');
        $resource = $this->savedArray['imports'][0]['resource'];
        $this->assertStringEndsWith(
            'TestModule1/services.yaml',
            $resource
        );
    }

    public function testAddImportSeveralTimes()
    {
        $this->dao->method('loadProjectConfigFile')->willReturn(new DIConfigWrapper([]));
        $this->service->addImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule1');
        $this->service->addImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule2');
        $this->service->addImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule1');
        $this->assertEquals(2, count($this->savedArray['imports']));
    }

    public function testRemoveImport()
    {
        $this->dao->method('loadProjectConfigFile')->willReturn(new DIConfigWrapper([]));
        $this->service->addImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule1');
        $this->service->addImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule2');
        $this->service->removeImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule1');
        $resource = $this->savedArray['imports'][0]['resource'];
        $this->assertEquals(1, count($this->savedArray['imports']));
        $this->assertStringEndsWith(
            'TestModule2/services.yaml',
            $resource
        );
    }

    public function testRemoveAllImports()
    {
        $this->dao->method('loadProjectConfigFile')->willReturn(new DIConfigWrapper([]));
        $this->service->addImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule1');
        $this->service->addImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule2');
        $this->service->removeImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule1');
        $this->service->removeImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule2');
        $this->assertEquals([], $this->savedArray);
    }

    public function testAddNonExistingDirectory()
    {
        $this->dao->method('loadProjectConfigFile')->willReturn(new DIConfigWrapper([]));
        $this->expectException(NoServiceYamlException::class);
        $this->service->addImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule3');
    }

    public function testAddNonExistingServiceYaml()
    {
        $this->dao->method('loadProjectConfigFile')->willReturn(new DIConfigWrapper([]));
        $this->expectException(NoServiceYamlException::class);
        $this->service->addImport(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Dao');
    }

    public function testRemovingNonExistingImports()
    {
        $existingImport = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestModule1' .
                          DIRECTORY_SEPARATOR . 'services.yaml');
        $nonexistingImport = 'some' . DIRECTORY_SEPARATOR . 'not' . DIRECTORY_SEPARATOR . 'existing' .
                             DIRECTORY_SEPARATOR . 'directory' . DIRECTORY_SEPARATOR . 'services.yaml';
        $this->dao->method('loadProjectConfigFile')->willReturn(new DIConfigWrapper(
            ['imports' =>[
                ['resource' => $nonexistingImport],
                ['resource' => $existingImport]]]
        ));
        $this->service->removeNonExistingImports();

        $this->assertArrayHasKey('imports', $this->savedArray);
        $this->assertEquals(1, count($this->savedArray['imports']));
        $this->assertEquals($existingImport, $this->savedArray['imports'][0]['resource']);
    }
}
