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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Module\MetaData\Dao;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDao;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\DataMapper\MetaDataToModuleConfigurationDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\MetaDataProviderInterface;
use PHPUnit\Framework\TestCase;

class ModuleConfigurationDaoTest extends TestCase
{
    /**
     * @var string
     */
    private $metadataFileName = 'metadata.php';

    public function testGet(): void
    {
        $path = 'packagePath';

        $metaDataProvider = $this->prophesize(MetaDataProviderInterface::class);
        $metaDataProvider->getData($this->getMetadataFilePath($path))->willReturn([]);

        $metaDataToModuleConfigurationDataMapper = $this
            ->prophesize(MetaDataToModuleConfigurationDataMapperInterface::class);
        $metaDataToModuleConfigurationDataMapper->fromData([])
            ->willReturn($this->prophesize(ModuleConfiguration::class));


        $moduleConfigurationDao = new ModuleConfigurationDao(
            $metaDataProvider->reveal(),
            $metaDataToModuleConfigurationDataMapper->reveal()
        );

        $this->assertInstanceOf(
            ModuleConfiguration::class,
            $moduleConfigurationDao->get($path)
        );
    }

    /**
     * @param string $moduleFullPath
     *
     * @return string
     */
    private function getMetadataFilePath(string $moduleFullPath): string
    {
        return $moduleFullPath . DIRECTORY_SEPARATOR . $this->metadataFileName;
    }

}
