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

namespace OxidEsales\EshopCommunity\Internal\Transition\Utility;

/**
 * Contains necessary methods to provide basic information about the application.
 */
interface BasicContextInterface
{
    /**
     * @return string
     */
    public function getContainerCacheFilePath(): string;

    /**
     * @return string
     */
    public function getGeneratedServicesFilePath(): string;

    /**
     * @return string
     */
    public function getConfigurableServicesFilePath(): string;

    /**
     * @return string
     */
    public function getSourcePath(): string;

    /**
     * @return string
     */
    public function getModulesPath(): string;

    /**
     * @return string
     */
    public function getEdition(): string;

    /**
     * @return string
     */
    public function getCommunityEditionSourcePath(): string;

    /**
     * @return int
     */
    public function getDefaultShopId(): int;

    /**
     * @return array
     */
    public function getAllShopIds(): array;

    /**
     * @return array
     */
    public function getBackwardsCompatibilityClassMap(): array;

    /**
     * @return string
     */
    public function getProjectConfigurationDirectory(): string;

    /**
     * @return string
     */
    public function getConfigurationDirectoryPath(): string;

    /**
     * @return string
     */
    public function getShopRootPath(): string;

    /**
     * @return string
     */
    public function getConfigFilePath(): string;

    /**
     * @return string
     */
    public function getConfigTableName(): string;

    /**
     * @return string
     */
    public function getCacheDirectory(): string;
}
