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

namespace OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service;

use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Dao\ProjectYamlDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Exception\NoServiceYamlException;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Webmozart\PathUtil\Path;

/**
 * @internal
 */
class ProjectYamlImportService implements ProjectYamlImportServiceInterface
{
    const SERVICE_FILE_NAME = 'services.yaml';

    /**
     * @var ProjectYamlDaoInterface
     */
    private $projectYamlDao;

    /**
     * @var BasicContextInterface
     */
    private $context;

    public function __construct(ProjectYamlDaoInterface $projectYamlDao, BasicContextInterface $context)
    {
        $this->projectYamlDao = $projectYamlDao;
        $this->context = $context;
    }

    /**
     * @param string $serviceDir
     */
    public function addImport(string $serviceDir)
    {
        if (!realpath($serviceDir)) {
            throw new NoServiceYamlException();
        }
        $projectConfig = $this->projectYamlDao->loadProjectConfigFile();
        $projectConfig->addImport($this->getServiceRelativeFilePath($serviceDir));

        $this->projectYamlDao->saveProjectConfigFile($projectConfig);
    }

    /**
     * @param string $serviceDir
     */
    public function removeImport(string $serviceDir)
    {
        $projectConfig = $this->projectYamlDao->loadProjectConfigFile();

        $projectConfig->removeImport($this->getServiceRelativeFilePath($serviceDir));

        $this->projectYamlDao->saveProjectConfigFile($projectConfig);
    }

    /**
     * Checks if the import files exist and if not removes them
     */
    public function removeNonExistingImports()
    {
        $projectConfig = $this->projectYamlDao->loadProjectConfigFile();

        $configChanged = false;
        foreach ($projectConfig->getImportFileNames() as $fileName) {
            if (file_exists($this->getAbsolutePath($fileName))) {
                continue;
            }
            $projectConfig->removeImport($fileName);
            $configChanged = true;
        }

        if ($configChanged) {
            $this->projectYamlDao->saveProjectConfigFile($projectConfig);
        }
    }

    /**
     * @param $fileName
     * @return string
     */
    private function getAbsolutePath($fileName): string
    {
        $fileAbsolutePath = Path::makeAbsolute(
            $fileName,
            Path::getDirectory($this->context->getGeneratedServicesFilePath())
        );
        return $fileAbsolutePath;
    }

    /**
     * @param string $serviceDir
     * @return string
     */
    private function getServiceRelativeFilePath(string $serviceDir): string
    {
        return Path::makeRelative(
            $serviceDir . DIRECTORY_SEPARATOR . static::SERVICE_FILE_NAME,
            Path::getDirectory($this->context->getGeneratedServicesFilePath())
        );
    }
}
