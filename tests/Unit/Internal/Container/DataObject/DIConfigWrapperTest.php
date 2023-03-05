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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Container\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\DataObject\DIConfigWrapper;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Exception\SystemServiceOverwriteException;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\ProjectDIConfig\TestModule\TestEventSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerStub implements ContainerInterface
{
    public function get($key)
    {
        return null;
    }
    public function has($key)
    {
        return $key == 'existing.service';
    }
}

class DIConfigWrapperTest extends TestCase
{
    private $servicePath1;
    private $servicePath2;

    public function setup(): void
    {
        $this->servicePath1 = __DIR__ . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'TestModule1' . DIRECTORY_SEPARATOR .
            'services.yaml';
        $this->servicePath2 = __DIR__ . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'TestModule2' . DIRECTORY_SEPARATOR .
            'services.yaml';
    }

    public function testCleaningSections()
    {
        $projectYaml = new DIConfigWrapper(['imports' => [], 'services' => []]);
        // These empty sections should be cleaned away
        $this->assertCount(0, $projectYaml->getConfigAsArray());
    }

    public function testGetAllImportFileNames()
    {
        $configArray = ['imports' => [
            ['resource' => $this->servicePath1],
            ['resource' => $this->servicePath2]
        ]];

        $wrapper = new DIConfigWrapper($configArray);
        $names = $wrapper->getImportFileNames();

        $this->assertEquals($this->servicePath1, $names[0]);
        $this->assertEquals($this->servicePath2, $names[1]);
    }

    public function testAddImport()
    {
        $configArray = ['imports' => [['resource' => $this->servicePath1]]];

        $wrapper = new DIConfigWrapper($configArray);
        $wrapper->addImport($this->servicePath2);

        $this->assertEquals(2, count($wrapper->getConfigAsArray()['imports']));
    }

    public function testAddFirstImport()
    {
        $configArray = [];

        $wrapper = new DIConfigWrapper($configArray);
        $wrapper->addImport($this->servicePath1);

        $expected = ['imports' => [['resource' => $this->servicePath1]]];
        $this->assertEquals($expected, $wrapper->getConfigAsArray());
    }

    public function testRemoveImport()
    {
        $configArray = ['imports' => [
            ['resource' => $this->servicePath1],
            ['resource' => $this->servicePath2]
        ]];

        $wrapper = new DIConfigWrapper($configArray);
        $wrapper->removeImport($this->servicePath1);

        $this->assertEquals(1, count($wrapper->getConfigAsArray()['imports']));
    }

    public function testRemoveLastImport()
    {
        $configArray = ['imports' => [['resource' => $this->servicePath1]]];

        $wrapper = new DIConfigWrapper($configArray);
        $wrapper->removeImport($this->servicePath1);

        $this->assertEquals([], $wrapper->getConfigAsArray());
    }

    public function testActivateServicesForShop()
    {
        $projectYaml = new DIConfigWrapper(
            [
                'services' =>
                [
                    'testmodulesubscriber' =>
                    [
                        'class' => TestEventSubscriber::class
                    ]
                ]
            ]
        );
        $service = $projectYaml->getService('testmodulesubscriber');
        $activeShops = $service->addActiveShops([1, 5]);
        $projectYaml->addOrUpdateService($service);

        $yamlArray = $projectYaml->getConfigAsArray();

        $this->assertEquals([1, 5], $yamlArray['services']['testmodulesubscriber']['calls'][0]['arguments'][0]);
        $this->assertEquals([1, 5], $activeShops);
    }

    public function testRemovingActiveShops()
    {
        $projectYaml = new DIConfigWrapper(
            [
                'services' =>
                [
                    'testmodulesubscriber' =>
                    [
                        'class' => TestEventSubscriber::class,
                        'calls' => [
                            [
                                'method' => 'setActiveShops',
                                'arguments' => [
                                    [1, 5, 7]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $service = $projectYaml->getService('testmodulesubscriber');
        $activeShops = $service->removeActiveShops([1, 5]);
        $projectYaml->addOrUpdateService($service);

        $yamlArray = $projectYaml->getConfigAsArray();
        $this->assertEquals([7], $yamlArray['services']['testmodulesubscriber']['calls'][0]['arguments'][0]);
        $this->assertEquals([7], $activeShops);
    }

    public function testGetServices()
    {
        $projectYaml = new DIConfigWrapper(
            [
                'services' =>
                [
                    'testmodulesubscriber' =>
                    [
                        'class' => TestEventSubscriber::class,
                        'calls' => [
                            [
                                'method' => 'setActiveShops',
                                'arguments' => [[1, 5, 7]]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $services = $projectYaml->getServices();
        $this->assertCount(1, $services);
        $this->assertEquals('testmodulesubscriber', $services[0]->getKey());
    }

    public function testGetServicesWithNullArguments()
    {
        $projectYaml = new DIConfigWrapper(
            [
                'services' =>
                [
                    TestEventSubscriber::class => null
                ]
            ]
        );

        $services = $projectYaml->getServices();
        $this->assertCount(1, $services);
        $this->assertEquals(
            TestEventSubscriber::class,
            $services[0]->getKey()
        );
        $this->assertTrue($projectYaml->checkServiceClassesCanBeLoaded());
    }

    public function testGetServicesWithNoClassArguments()
    {
        $projectYaml = new DIConfigWrapper(
            [
                'services' =>
                [
                    TestEventSubscriber::class => []
                ]
            ]
        );

        $services = $projectYaml->getServices();
        $this->assertEquals(
            TestEventSubscriber::class,
            $services[0]->getKey()
        );
        $this->assertTrue($projectYaml->checkServiceClassesCanBeLoaded());
    }

    public function testCleaningUncalledServices()
    {
        $projectYaml = new DIConfigWrapper(['services' =>
        ['testmodulesubscriber' =>
        [
            'class' => TestEventSubscriber::class,
            'calls' => [['method' => 'setActiveShops', 'arguments' => [[1]]]]
        ]]]);
        $service = $projectYaml->getService('testmodulesubscriber');
        $service->removeActiveShops([1]);
        $projectYaml->addOrUpdateService($service);
        // services section should be cleaned away after removeal of service
        $this->assertCount(0, $projectYaml->getConfigAsArray());
    }

    public function testSystemServiceCheckSucceeding()
    {
        $config = new DIConfigWrapper(['services' => ['nonexisting.service' => []]]);
        try {
            $config->checkServices(new ContainerStub());
        } catch (SystemServiceOverwriteException $e) {
            $this->fail('There should no exception been raised!');
        }
        // This is for php unit that is too stupid to recognize the above construct as test
        $this->assertTrue(true);
    }

    public function testSystemServiceCheckFailing()
    {
        $this->expectException(SystemServiceOverwriteException::class);
        $config = new DIConfigWrapper(['services' => ['existing.service' => []]]);
        $config->checkServices(new ContainerStub());
    }

    public function testServiceClassCheckWorking()
    {
        $servicesYaml = new DIConfigWrapper(['services' =>
        ['testmodulesubscriber' =>
        ['class' => TestEventSubscriber::class]]]);


        $this->assertTrue($servicesYaml->checkServiceClassesCanBeLoaded());
    }

    public function testServiceClassCheckFailing()
    {
        $servicesYaml = new DIConfigWrapper(['services' =>
        ['testmodulesubscriber' =>
        ['class' => 'OxidEsales\EshopCommunity\Tests\SomeNotExistingClass']]]);


        $this->assertFalse($servicesYaml->checkServiceClassesCanBeLoaded());
    }
}
