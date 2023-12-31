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

namespace OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\DataObject\DIConfigWrapper;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

class ProjectYamlDao implements ProjectYamlDaoInterface
{
    /**
     * @var BasicContextInterface $context
     */
    private $context;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * ProjectYamlDao constructor.
     * @param BasicContextInterface $context
     * @param Filesystem            $filesystem
     */
    public function __construct(BasicContextInterface $context, Filesystem $filesystem)
    {
        $this->context = $context;
        $this->filesystem = $filesystem;
    }

    /**
     * @return DIConfigWrapper
     */
    public function loadProjectConfigFile(): DIConfigWrapper
    {
        return $this->loadDIConfigFile($this->context->getGeneratedServicesFilePath());
    }

    /**
     * @param DIConfigWrapper $config
     */
    public function saveProjectConfigFile(DIConfigWrapper $config)
    {
        $config = $this->convertAbsolutePathsToRelative($config);

        if (!$this->filesystem->exists($this->getGeneratedServicesFileDirectory())) {
            $this->filesystem->mkdir($this->getGeneratedServicesFileDirectory());
        }

        file_put_contents(
            $this->context->getGeneratedServicesFilePath(),
            Yaml::dump($config->getConfigAsArray(), 3, 2)
        );
    }

    /**
     * @param string $path
     *
     * @return DIConfigWrapper
     */
    public function loadDIConfigFile(string $path): DIConfigWrapper
    {
        $yamlArray = [];

        if (file_exists($path)) {
            $yamlArray = Yaml::parse(file_get_contents($path), Yaml::PARSE_CUSTOM_TAGS) ?? [];
        }

        return new DIConfigWrapper($yamlArray);
    }

    /**
     * @return string
     */
    private function getGeneratedServicesFileDirectory(): string
    {
        return \dirname($this->context->getGeneratedServicesFilePath());
    }

    /**
     * @param DIConfigWrapper $configWrapper
     * @return DIConfigWrapper
     */
    private function convertAbsolutePathsToRelative(DIConfigWrapper $configWrapper): DIConfigWrapper
    {
        foreach ($configWrapper->getImportFileNames() as $fileName) {
            if (Path::isAbsolute($fileName)) {
                $relativePath = Path::makeRelative(
                    $fileName,
                    Path::getDirectory($this->context->getGeneratedServicesFilePath())
                );
                $configWrapper->addImport($relativePath);
                $configWrapper->removeImport($fileName);
            }
        }

        return $configWrapper;
    }
}
