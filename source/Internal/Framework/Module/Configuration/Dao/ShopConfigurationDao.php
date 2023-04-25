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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Storage\ArrayStorageInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Storage\FileStorageFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Cache\ShopConfigurationCacheInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper\ShopConfigurationDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ShopConfigurationNotFoundException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class ShopConfigurationDao implements ShopConfigurationDaoInterface
{
    /**
     * @var ShopConfigurationDataMapperInterface
     */
    private $shopConfigurationMapper;

    /**
     * @var FileStorageFactoryInterface
     */
    private $fileStorageFactory;

    /**
     * @var BasicContextInterface
     */
    private $context;

    /**
     * @var ShopConfigurationCacheInterface
     */
    private $cache;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var ShopConfigurationExtenderInterface
     */
    private $shopConfigurationExtender;

    public function __construct(
        ShopConfigurationDataMapperInterface $shopConfigurationMapper,
        FileStorageFactoryInterface $fileStorageFactory,
        BasicContextInterface $context,
        ShopConfigurationCacheInterface $cache,
        Filesystem $fileSystem,
        NodeInterface $node,
        ShopConfigurationExtenderInterface $shopConfigurationExtender
    ) {
        $this->shopConfigurationMapper = $shopConfigurationMapper;
        $this->fileStorageFactory = $fileStorageFactory;
        $this->context = $context;
        $this->cache = $cache;
        $this->fileSystem = $fileSystem;
        $this->node = $node;
        $this->shopConfigurationExtender = $shopConfigurationExtender;
    }

    /**
     * @param int $shopId
     *
     * @return ShopConfiguration
     * @throws ShopConfigurationNotFoundException
     */
    public function get(int $shopId): ShopConfiguration
    {
        if (!$this->isShopIdExists($shopId)) {
            throw new ShopConfigurationNotFoundException(
                'ShopId ' . $shopId . ' does not exist'
            );
        }

        if ($this->cache->exists($shopId)) {
            $shopConfiguration = $this->cache->get($shopId);
        } else {
            $shopConfiguration = $this->getConfigurationFromStorage($shopId);
            $this->cache->put($shopId, $shopConfiguration);
        }

        return $shopConfiguration;
    }

    /**
     * @param ShopConfiguration $shopConfiguration
     * @param int               $shopId
     */
    public function save(ShopConfiguration $shopConfiguration, int $shopId): void
    {
        $this->cache->evict($shopId);

        $this->getStorage($shopId)->save(
            $this->shopConfigurationMapper->toData($shopConfiguration)
        );
    }

    /**
     * @return ShopConfiguration[]
     * @throws ShopConfigurationNotFoundException
     */
    public function getAll(): array
    {
        $configurations = [];

        foreach ($this->getShopIds() as $shopId) {
            $configurations[$shopId] = $this->get($shopId);
        }

        return $configurations;
    }

    /**
     * delete all shops configuration
     */
    public function deleteAll(): void
    {
        try {
            if ($this->fileSystem->exists($this->getShopsConfigurationDirectory())) {
                $this->fileSystem->remove(
                    $this->getShopsConfigurationDirectory()
                );
            }
        } catch (IOException $e) {
            unset($e);
        }
    }

    /**
     * @return int[]
     */
    private function getShopIds(): array
    {
        $shopIds = [];

        if (file_exists($this->getShopsConfigurationDirectory())) {
            $dir = new \DirectoryIterator($this->getShopsConfigurationDirectory());

            foreach ($dir as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $shopIds[] = (int)$fileInfo->getFilename();
                }
            }
        }

        return $shopIds;
    }

    /**
     * @param int $shopId
     *
     * @return ArrayStorageInterface
     */
    private function getStorage(int $shopId): ArrayStorageInterface
    {
        return $this->fileStorageFactory->create(
            $this->getShopConfigurationFilePath($shopId)
        );
    }

    /**
     * @param int $shopId
     *
     * @return string
     */
    private function getShopConfigurationFilePath(int $shopId): string
    {
        return $this->getShopsConfigurationDirectory() . $shopId . '.yaml';
    }


    /**
     * @return string
     */
    private function getShopsConfigurationDirectory(): string
    {
        return $this->context->getProjectConfigurationDirectory() . 'shops/';
    }

    /**
     * @param int $shopId
     *
     * @return ShopConfiguration
     * @throws \Exception
     */
    private function getConfigurationFromStorage(int $shopId): ShopConfiguration
    {
        $extendedShopConfiguration = $this->shopConfigurationExtender->getExtendedConfiguration(
            $shopId,
            $this->getShopConfigurationData($shopId)
        );
        return $this->shopConfigurationMapper->fromData($extendedShopConfiguration);
    }

    /**
     * @param int $shopId
     *
     * @return bool
     */
    private function isShopIdExists(int $shopId): bool
    {
        return \in_array($shopId, $this->getShopIds(), true);
    }

    /**
     * @param int $shopId
     *
     * @return array
     * @throws \Exception
     */
    private function getShopConfigurationData(int $shopId): array
    {
        try {
            $data = $this->node->normalize($this->getStorage($shopId)->get());
        } catch (InvalidConfigurationException $exception) {
            throw new InvalidConfigurationException(
                'File ' . $this->getShopConfigurationFilePath($shopId) . ' is broken: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
        return $data;
    }
}
