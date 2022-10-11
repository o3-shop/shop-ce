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

namespace OxidEsales\EshopCommunity\Internal\Framework\Smarty;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;

class SmartyContext implements SmartyContextInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var UtilsView
     */
    private $utilsView;

    /**
     * @var BasicContextInterface
     */
    private $basicContext;

    /**
     * Context constructor.
     *
     * @param BasicContextInterface $basicContext
     * @param Config                $config
     * @param UtilsView             $utilsView
     */
    public function __construct(BasicContextInterface $basicContext, Config $config, UtilsView $utilsView)
    {
        $this->config = $config;
        $this->utilsView = $utilsView;
        $this->basicContext = $basicContext;
    }

    /**
     * @return bool
     */
    public function getTemplateEngineDebugMode(): bool
    {
        $debugMode = $this->getConfigParameter('iDebug');
        return ($debugMode === 1 || $debugMode === 3 || $debugMode === 4);
    }

    /**
     * @return bool
     */
    public function showTemplateNames(): bool
    {
        $debugMode = $this->getConfigParameter('iDebug');
        return ($debugMode === 8 && !$this->getBackendMode());
    }

    /**
     * @return bool
     */
    public function getTemplateSecurityMode(): bool
    {
        return $this->getDemoShopMode();
    }

    /**
     * @return string
     */
    public function getTemplateCompileDirectory(): string
    {
        return $this->utilsView->getSmartyDir();
    }

    /**
     * @return array
     */
    public function getTemplateDirectories(): array
    {
        return $this->utilsView->getTemplateDirs();
    }

    /**
     * @return string
     */
    public function getTemplateCompileId(): string
    {
        return $this->utilsView->getTemplateCompileId();
    }

    /**
     * @return bool
     */
    public function getTemplateCompileCheckMode(): bool
    {
        $compileCheck = (bool) $this->getConfigParameter('blCheckTemplates');
        if ($this->config->isProductiveMode()) {
            // override in any case
            $compileCheck = false;
        }
        return $compileCheck;
    }

    /**
     * @return array
     */
    public function getSmartyPluginDirectories(): array
    {
        return $this->utilsView->getSmartyPluginDirectories();
    }

    /**
     * @return int
     */
    public function getTemplatePhpHandlingMode(): int
    {
        return (int) $this->getConfigParameter('iSmartyPhpHandling');
    }

    /**
     * @param string $templateName
     *
     * @return string
     */
    public function getTemplatePath($templateName): string
    {
        return $this->config->getTemplatePath($templateName, $this->getBackendMode());
    }

    /**
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->basicContext->getSourcePath();
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    private function getConfigParameter($name)
    {
        return $this->config->getConfigParam($name);
    }

    /**
     * @return bool
     */
    private function getBackendMode(): bool
    {
        return $this->config->isAdmin();
    }

    /**
     * @return bool
     */
    private function getDemoShopMode(): bool
    {
        return (bool) $this->config->isDemoShop();
    }
}
