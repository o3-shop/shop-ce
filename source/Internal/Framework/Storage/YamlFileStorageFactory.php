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

namespace OxidEsales\EshopCommunity\Internal\Framework\Storage;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Factory;

class YamlFileStorageFactory implements FileStorageFactoryInterface
{
    /**
     * @var FileLocatorInterface
     */
    private $fileLocator;

    /**
     * @var Factory
     */
    private $lockFactory;

    /**
     * @var Filesystem
     */
    private $filesystemService;

    /**
     * YamlFileStorageFactory constructor.
     * @param FileLocatorInterface $fileLocator
     * @param Factory $lockFactory
     * @param Filesystem $filesystemService
     */
    public function __construct(FileLocatorInterface $fileLocator, Factory $lockFactory, Filesystem $filesystemService)
    {
        $this->fileLocator = $fileLocator;
        $this->lockFactory = $lockFactory;
        $this->filesystemService = $filesystemService;
    }


    public function create(string $filePath): ArrayStorageInterface
    {
        return new YamlFileStorage(
            $this->fileLocator,
            $filePath,
            $this->lockFactory,
            $this->filesystemService
        );
    }
}
