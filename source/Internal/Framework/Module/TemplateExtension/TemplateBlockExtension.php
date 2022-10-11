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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\TemplateExtension;

class TemplateBlockExtension
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $extendedBlockTemplatePath;

    /**
     * @var int
     */
    private $position = 1;

    /**
     * @var string
     */
    private $moduleId;

    /**
     * @var int
     */
    private $shopId;

    /**
     * @var string
     */
    private $themeId = '';

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return TemplateBlockExtension
     */
    public function setName(string $name): TemplateBlockExtension
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     * @return TemplateBlockExtension
     */
    public function setFilePath(string $filePath): TemplateBlockExtension
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtendedBlockTemplatePath(): string
    {
        return $this->extendedBlockTemplatePath;
    }

    /**
     * @param string $extendedBlockTemplatePath
     * @return TemplateBlockExtension
     */
    public function setExtendedBlockTemplatePath(string $extendedBlockTemplatePath): TemplateBlockExtension
    {
        $this->extendedBlockTemplatePath = $extendedBlockTemplatePath;
        return $this;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     * @return TemplateBlockExtension
     */
    public function setPosition(int $position): TemplateBlockExtension
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return string
     */
    public function getModuleId(): string
    {
        return $this->moduleId;
    }

    /**
     * @param string $moduleId
     * @return TemplateBlockExtension
     */
    public function setModuleId(string $moduleId): TemplateBlockExtension
    {
        $this->moduleId = $moduleId;
        return $this;
    }

    /**
     * @return int
     */
    public function getShopId(): int
    {
        return $this->shopId;
    }

    /**
     * @param int $shopId
     * @return TemplateBlockExtension
     */
    public function setShopId(int $shopId): TemplateBlockExtension
    {
        $this->shopId = $shopId;
        return $this;
    }

    /**
     * @return string
     */
    public function getThemeId(): string
    {
        return $this->themeId;
    }

    /**
     * @param string $themeId
     * @return TemplateBlockExtension
     */
    public function setThemeId(string $themeId): TemplateBlockExtension
    {
        $this->themeId = $themeId;
        return $this;
    }
}
