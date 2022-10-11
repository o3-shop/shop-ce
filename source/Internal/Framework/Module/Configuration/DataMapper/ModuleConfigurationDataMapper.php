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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataMapper;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;

class ModuleConfigurationDataMapper implements ModuleConfigurationDataMapperInterface
{
    /** @var ModuleConfigurationDataMapperInterface[] */
    private $dataMappers = [];

    public function __construct(ModuleConfigurationDataMapperInterface ...$dataMappers)
    {
        $this->dataMappers = $dataMappers;
    }

    /**
     * @param ModuleConfiguration $configuration
     *
     * @return array
     */
    public function toData(ModuleConfiguration $configuration): array
    {
        $data = [
            'id' => $configuration->getId(),
            'path' => $configuration->getPath(),
            'version' => $configuration->getVersion(),
            'configured' => $configuration->isConfigured(),
            'title' => $configuration->getTitle(),
            'description' => $configuration->getDescription(),
            'lang' => $configuration->getLang(),
            'thumbnail' => $configuration->getThumbnail(),
            'author' => $configuration->getAuthor(),
            'url' => $configuration->getUrl(),
            'email' => $configuration->getEmail()
        ];

        foreach ($this->dataMappers as $dataMapper) {
            $data = array_merge($data, $dataMapper->toData($configuration));
        }

        return $data;
    }

    /**
     * @param ModuleConfiguration $moduleConfiguration
     * @param array               $data
     *
     * @return ModuleConfiguration
     */
    public function fromData(ModuleConfiguration $moduleConfiguration, array $data): ModuleConfiguration
    {
        $moduleConfiguration
            ->setId($data['id'])
            ->setPath($data['path'])
            ->setVersion($data['version'])
            ->setConfigured($data['configured'])
            ->setTitle($data['title']);

        if (isset($data['description'])) {
            $moduleConfiguration->setDescription($data['description']);
        }

        if (isset($data['lang'])) {
            $moduleConfiguration->setLang($data['lang']);
        }

        if (isset($data['thumbnail'])) {
            $moduleConfiguration->setThumbnail($data['thumbnail']);
        }

        if (isset($data['author'])) {
            $moduleConfiguration->setAuthor($data['author']);
        }

        if (isset($data['url'])) {
            $moduleConfiguration->setUrl($data['url']);
        }

        if (isset($data['email'])) {
            $moduleConfiguration->setEmail($data['email']);
        }

        foreach ($this->dataMappers as $dataMapper) {
            $moduleConfiguration = $dataMapper->fromData($moduleConfiguration, $data);
        }

        return $moduleConfiguration;
    }
}
