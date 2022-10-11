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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Validator;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\ContainerBuilder;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Exception\NoServiceYamlException;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use Webmozart\PathUtil\Path;

class ServicesYamlValidator implements ModuleConfigurationValidatorInterface
{
    /** @var BasicContextInterface  */
    private $basicContext;

    /** @var ProjectYamlDaoInterface  */
    private $projectYamlDao;

    public function __construct(
        BasicContextInterface $basicContext,
        ProjectYamlDaoInterface $projectYamlDao
    ) {
        $this->basicContext = $basicContext;
        $this->projectYamlDao = $projectYamlDao;
    }

    /**
     * @param ModuleConfiguration $configuration
     * @param int $shopId
     * @throws \Throwable
     */
    public function validate(ModuleConfiguration $configuration, int $shopId)
    {
        $projectYaml = $this->projectYamlDao->loadProjectConfigFile();
        $originalProjectYaml = clone $projectYaml;

        try {
            $projectYaml->addImport(
                Path::join($this->basicContext->getModulesPath(), $configuration->getPath(), 'services.yaml')
            );
            $this->projectYamlDao->saveProjectConfigFile($projectYaml);

            $container = $this->buildContainer();
            $this->checkContainer($container);
        } catch (NoServiceYamlException $e) {
            return;
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            // Restore the old settings
            $this->projectYamlDao->saveProjectConfigFile($originalProjectYaml);
        }
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     * @throws \Exception
     */
    private function buildContainer(): \Symfony\Component\DependencyInjection\ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder($this->basicContext);
        $container = $containerBuilder->getContainer();
        foreach ($container->getDefinitions() as $definitionKey => $definition) {
            $definition->setPublic(true);
        }
        $container->compile();
        return $container;
    }

    /**
     * Try to fetch all services defined
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @throws \Exception
     */
    private function checkContainer(\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $definitionKey => $definition) {
            $container->get($definitionKey);
        }
    }
}
