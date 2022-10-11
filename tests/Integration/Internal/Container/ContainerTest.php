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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Container;

use Monolog\Logger;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDao;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Event\ShopAwareEventDispatcher;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\TestContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ContainerTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    private $testServicesYml = __DIR__ . '/Fixtures/Project/services.yaml';

    public function setup(): void
    {
        ContainerFactory::resetContainer();

        $this->container = ContainerFactory::getInstance()->getContainer();
    }

    public function tearDown(): void
    {
        ContainerFactory::resetContainer();

        $this->cleanUpGeneratedServices();
    }

    public function testGetInstance(): void
    {
        $this->assertInstanceOf(
            ContainerInterface::class,
            $this->container
        );
    }

    public function testAllServicesAreCorrectlyConfigured(): void
    {
        $testContainer = (new TestContainerFactory())->create();
        $testContainer->compile();
        foreach ($testContainer->getDefinitions() as $key => $definition) {
            $testContainer->get($key);
        };
        $this->assertTrue(true);
    }

    /**
     * Checks that a private service may not be accessed
     */
    public function testPrivateServices(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $this->container->get(Logger::class);
    }

    public function testCacheIsUsed(): void
    {
        $projectYamlDao = $this->getProjectYmlDao();

        $projectConfigurationFile = $projectYamlDao->loadProjectConfigFile();
        $projectConfigurationFile->addImport($this->testServicesYml);

        $projectYamlDao->saveProjectConfigFile($projectConfigurationFile);

        $this->expectException(ServiceNotFoundException::class);
        $this->container->get('test_service');
    }

    public function testResetCacheWorks(): void
    {
        $projectYamlDao = $this->getProjectYmlDao();

        $projectConfigurationFile = $projectYamlDao->loadProjectConfigFile();
        $projectConfigurationFile->addImport($this->testServicesYml);

        $projectYamlDao->saveProjectConfigFile($projectConfigurationFile);

        ContainerFactory::resetContainer();

        $this->assertTrue(
            is_object(
                ContainerFactory::getInstance()->getContainer()->get('test_service')
            )
        );
    }

    /**
     * Checks that the cachefile has been created
     */
    public function testCacheIsCreated(): void
    {
        $this->assertFileExists($this->getCacheFilePath());
    }

    /**
     * Tests that an event dispatcher is available and implements
     * the correct interface.
     *
     */
    public function testEventDispatcher(): void
    {
        $this->assertInstanceOf(
            ShopAwareEventDispatcher::class,
            $this->container->get(EventDispatcherInterface::class)
        );
    }

    private function getCacheFilePath(): string
    {
        return $this
            ->container
            ->get(BasicContextInterface::class)
            ->getContainerCacheFilePath();
    }

    private function cleanUpGeneratedServices(): void
    {
        $projectYamlDao = $this->getProjectYmlDao();

        $projectConfigurationFile = $projectYamlDao->loadProjectConfigFile();
        $projectConfigurationFile->removeImport($this->testServicesYml);
        $projectYamlDao->saveProjectConfigFile($projectConfigurationFile);
    }

    private function getProjectYmlDao(): ProjectYamlDaoInterface
    {
        return new ProjectYamlDao(
            $this->container->get(ContextInterface::class),
            $this->container->get('oxid_esales.symfony.file_system')
        );
    }
}
