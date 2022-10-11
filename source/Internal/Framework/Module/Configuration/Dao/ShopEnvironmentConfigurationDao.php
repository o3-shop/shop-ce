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
use OxidEsales\EshopCommunity\Internal\Framework\Storage\FileStorageFactoryInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Filesystem\Filesystem;

class ShopEnvironmentConfigurationDao implements ShopEnvironmentConfigurationDaoInterface
{
    /**
     * @var FileStorageFactoryInterface
     */
    private $fileStorageFactory;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var BasicContextInterface
     */
    private $context;


    /**
     * ShopConfigurationDao constructor.
     *
     * @param FileStorageFactoryInterface $fileStorageFactory
     * @param Filesystem                  $fileSystem
     * @param NodeInterface               $node
     * @param BasicContextInterface       $context
     */
    public function __construct(
        FileStorageFactoryInterface $fileStorageFactory,
        Filesystem $fileSystem,
        NodeInterface $node,
        BasicContextInterface $context
    ) {
        $this->fileStorageFactory = $fileStorageFactory;
        $this->fileSystem = $fileSystem;
        $this->node = $node;
        $this->context = $context;
    }

    /**
     * @param int $shopId
     *
     * @return array
     */
    public function get(int $shopId): array
    {
        $data = [];

        $configurationFilePath = $this->getEnvironmentConfigurationFilePath($shopId);

        if ($this->fileSystem->exists($configurationFilePath)) {
            $storage = $this->fileStorageFactory->create(
                $this->getEnvironmentConfigurationFilePath($shopId)
            );

            try {
                $data = $this->node->normalize($storage->get());
            } catch (InvalidConfigurationException $exception) {
                throw new InvalidConfigurationException(
                    'File ' .
                    $this->getEnvironmentConfigurationFilePath($shopId) .
                    ' is broken: ' . $exception->getMessage()
                );
            }
        }

        return $data;
    }

    /**
     * backup environment configuration file
     *
     * @param int $shopId
     */
    public function remove(int $shopId): void
    {
        $path = $this->getEnvironmentConfigurationFilePath($shopId);

        if ($this->fileSystem->exists($path)) {
            $this->fileSystem->rename($path, $path . '.bak', true);
        }
    }

    /**
     * @param int $shopId
     *
     * @return string
     */
    private function getEnvironmentConfigurationFilePath(int $shopId): string
    {
        return $this->context->getProjectConfigurationDirectory() . 'environment/' . $shopId . '.yaml';
    }
}
