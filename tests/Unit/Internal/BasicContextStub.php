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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal;

use OxidEsales\EshopCommunity\Internal\Container\BootstrapContainerFactory;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;

/**
 * @internal
 */
class BasicContextStub implements BasicContextInterface
{
    private $communityEditionSourcePath;
    private $containerCacheFilePath;
    private $edition;
    private $generatedServicesFilePath;
    private $configurableServicesFilePath;
    private $sourcePath;
    private $modulesPath;
    private $shopRootPath;
    private $configFilePath;
    private $projectConfigurationDirectory;
    private $backwardsCompatibilityClassMap;
    private $cacheDirectory;

    public function __construct()
    {
        /** @var BasicContextInterface $basicContext */
        $basicContext = BootstrapContainerFactory::getBootstrapContainer()->get(BasicContextInterface::class);

        $this->communityEditionSourcePath = $basicContext->getCommunityEditionSourcePath();
        $this->containerCacheFilePath = $basicContext->getContainerCacheFilePath();
        $this->edition = $basicContext->getEdition();
        $this->generatedServicesFilePath = $basicContext->getGeneratedServicesFilePath();
        $this->configurableServicesFilePath = $basicContext->getConfigurableServicesFilePath();
        $this->sourcePath = $basicContext->getSourcePath();
        $this->modulesPath = $basicContext->getModulesPath();
        $this->configFilePath = $basicContext->getConfigFilePath();
        $this->shopRootPath = $basicContext->getShopRootPath();
        $this->backwardsCompatibilityClassMap = $basicContext->getBackwardsCompatibilityClassMap();
        $this->cacheDirectory = $basicContext->getCacheDirectory();
    }

    /**
     * @return string
     */
    public function getCommunityEditionSourcePath(): string
    {
        return $this->communityEditionSourcePath;
    }

    /**
     * @param string $communityEditionSourcePath
     */
    public function setCommunityEditionSourcePath(string $communityEditionSourcePath)
    {
        $this->communityEditionSourcePath = $communityEditionSourcePath;
    }

    /**
     * @return string
     */
    public function getContainerCacheFilePath(): string
    {
        return $this->containerCacheFilePath;
    }

    /**
     * @return string
     */
    public function getEdition(): string
    {
        return $this->edition;
    }

    /**
     * @param string $edition
     */
    public function setEdition(string $edition)
    {
        $this->edition = $edition;
    }

    /**
     * @return string
     */
    public function getGeneratedServicesFilePath(): string
    {
        return $this->generatedServicesFilePath;
    }

    /**
     * @param string $generatedServicesFilePath
     */
    public function setGeneratedServicesFilePath(string $generatedServicesFilePath)
    {
        $this->generatedServicesFilePath = $generatedServicesFilePath;
    }

    /**
     * @return string
     */
    public function getConfigurableServicesFilePath(): string
    {
        return $this->configurableServicesFilePath;
    }

    /**
     * @param string $configurableServicesFilePath
     */
    public function setConfigurableServicesFilePath(string $configurableServicesFilePath)
    {
        $this->configurableServicesFilePath = $configurableServicesFilePath;
    }

    /**
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * @param string $sourcePath
     */
    public function setSourcePath(string $sourcePath)
    {
        $this->sourcePath = $sourcePath;
    }

    /**
     * @return int
     */
    public function getDefaultShopId(): int
    {
        return 1;
    }

    /**
     * @return array
     */
    public function getAllShopIds(): array
    {
        return [$this->getDefaultShopId()];
    }

    /**
     * @return string
     */
    public function getModulesPath(): string
    {
        return $this->modulesPath;
    }

    /**
     * @param string $path
     */
    public function setModulesPath(string $path)
    {
        $this->modulesPath = $path;
    }
    /**
     * @return array
     */
    public function getBackwardsCompatibilityClassMap(): array
    {
        return $this->backwardsCompatibilityClassMap;
    }

    /**
     * @return string
     */
    public function getProjectConfigurationDirectory(): string
    {
        return $this->projectConfigurationDirectory;
    }

    /**
     * @param string $projectConfigurationDirectory
     */
    public function setProjectConfigurationDirectory(string $projectConfigurationDirectory): void
    {
        $this->projectConfigurationDirectory = $projectConfigurationDirectory;
    }

    /**
     * @return string
     */
    public function getConfigFilePath(): string
    {
        return $this->configFilePath;
    }

    /**
     * @return string
     */
    public function getConfigTableName(): string
    {
        return 'oxconfig';
    }

    /**
     * @return string
     */
    public function getConfigurationDirectoryPath(): string
    {
        return $this->getSourcePath() . '/tmp/';
    }

    /**
     * @return string
     */
    public function getShopRootPath(): string
    {
        return $this->shopRootPath;
    }

    /**
     * @return string
     */
    public function getCacheDirectory(): string
    {
        return $this->cacheDirectory;
    }
}
