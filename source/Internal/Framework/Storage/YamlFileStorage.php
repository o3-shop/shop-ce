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

use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Yaml\Yaml;

class YamlFileStorage implements ArrayStorageInterface
{
    /**
     * @var FileLocatorInterface
     */
    private $fileLocator;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var Factory
     */
    private $lockFactory;

    /**
     * @var Filesystem
     */
    private $filesystemService;

    /**
     * YamlFileStorage constructor.
     * @param FileLocatorInterface $fileLocator
     * @param string               $filePath
     * @param Factory              $lockFactory
     * @param Filesystem           $filesystemService
     */
    public function __construct(
        FileLocatorInterface $fileLocator,
        string $filePath,
        Factory $lockFactory,
        Filesystem $filesystemService
    ) {
        $this->fileLocator = $fileLocator;
        $this->filePath = $filePath;
        $this->lockFactory = $lockFactory;
        $this->filesystemService = $filesystemService;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        $fileContent = file_get_contents($this->getLocatedFilePath());

        $yaml = Yaml::parse(
            $fileContent
        );

        return $yaml ?? [];
    }

    /**
     * @param array $data
     */
    public function save(array $data): void
    {
        $lock = $this->lockFactory->createLock($this->getLockId());

        if ($lock->acquire(true)) {
            try {
                file_put_contents(
                    $this->getLocatedFilePath(),
                    Yaml::dump($data, 10, 2)
                );
            } finally {
                $lock->release();
            }
        }
    }

    /**
     * @return string
     */
    private function getLocatedFilePath(): string
    {
        try {
            $filePath = $this->fileLocator->locate($this->filePath);
        } catch (FileLocatorFileNotFoundException $exception) {
            $this->createFileDirectory();
            $this->createFile();
            $filePath = $this->fileLocator->locate($this->filePath);
        }

        return $filePath;
    }

    /**
     * Creates file directory if it doesn't exist.
     */
    private function createFileDirectory(): void
    {
        if (!$this->filesystemService->exists(\dirname($this->filePath))) {
            $this->filesystemService->mkdir(\dirname($this->filePath));
        }
    }

    /**
     * Creates file.
     */
    private function createFile(): void
    {
        $this->filesystemService->touch($this->filePath);
    }

    /**
     * @return string
     */
    private function getLockId(): string
    {
        return md5($this->filePath);
    }
}
