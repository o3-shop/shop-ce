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

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;

class TemplateBlock
{
    /**
     * @var string
     */
    private $shopTemplatePath;

    /**
     * @var string
     */
    private $blockName;

    /**
     * @var string
     */
    private $moduleTemplatePath;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var string
     */
    private $theme = '';

    /**
     * @param string $shopTemplatePath
     * @param string $blockName
     * @param string $moduleTemplatePath
     */
    public function __construct(string $shopTemplatePath, string $blockName, string $moduleTemplatePath)
    {
        $this->shopTemplatePath = $shopTemplatePath;
        $this->blockName = $blockName;
        $this->moduleTemplatePath = $moduleTemplatePath;
    }

    /**
     * @return string
     */
    public function getShopTemplatePath(): string
    {
        return $this->shopTemplatePath;
    }

    /**
     * @return string
     */
    public function getBlockName(): string
    {
        return $this->blockName;
    }

    /**
     * @return string
     */
    public function getModuleTemplatePath(): string
    {
        return $this->moduleTemplatePath;
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
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * @param string $theme
     */
    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }
}
