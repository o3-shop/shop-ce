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

namespace OxidEsales\EshopCommunity\Internal\Framework\Templating\Locator;

use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\AdminThemeBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class EditionMenuFileLocator
 * @package OxidEsales\EshopCommunity\Internal\Framework\Templating\Locator
 */
class EditionMenuFileLocator implements NavigationFileLocatorInterface
{
    /**
     * @var BasicContextInterface
     */
    private $context;

    /**
     * @var string
     */
    private $themeName;

    /**
     * @var string
     */
    private $fileName = 'menu.xml';

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * EditionMenuFileLocator constructor.
     *
     * @param AdminThemeBridgeInterface $adminThemeBridge
     * @param BasicContextInterface     $context
     * @param Filesystem                $fileSystem
     */
    public function __construct(
        AdminThemeBridgeInterface $adminThemeBridge,
        BasicContextInterface $context,
        Filesystem $fileSystem
    ) {
        $this->themeName = $adminThemeBridge->getActiveTheme();
        $this->context = $context;
        $this->fileSystem = $fileSystem;
    }

    /**
     * Returns a full path for a given file name.
     *
     * @return array An array of file paths
     *
     * @throws \Exception
     */
    public function locate(): array
    {
        $filePath = $this->getMenuFileDirectory() . DIRECTORY_SEPARATOR . $this->fileName;
        return $this->validateFile($filePath);
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    private function getMenuFileDirectory(): string
    {
        return $this->getEditionsRootPaths() . DIRECTORY_SEPARATOR .
            'Application' . DIRECTORY_SEPARATOR .
            'views' . DIRECTORY_SEPARATOR .
            $this->themeName;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    private function getEditionsRootPaths(): string
    {
        $editionPath = $this->context->getSourcePath();
        return $editionPath;
    }

    /**
     * @param string $file
     *
     * @return array
     */
    private function validateFile(string $file): array
    {
        $existingFiles = [];
        if ($this->fileSystem->exists($file)) {
            $existingFiles[] = $file;
        }
        return $existingFiles;
    }
}
