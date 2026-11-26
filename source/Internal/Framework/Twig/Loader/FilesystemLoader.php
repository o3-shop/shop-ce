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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): adopted from OXID twig-component v1.1.0, ported to Twig 3.
 * Modified for O3-Shop (2026): module namespaces ported from twig-component v2.0.1 to PHP 7.4
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\Loader;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Exception\TemplateFileNotFoundException;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Loader\TemplateLoaderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Resolver\ModulesTemplateDirectoryResolver;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigContextInterface;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader as TwigLoader;

/**
 * Twig filesystem loader that falls back to the shop's internal template loaders
 */
class FilesystemLoader extends TwigLoader
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var TemplateLoaderInterface
     */
    private $loader;

    /**
     * @var TemplateLoaderInterface
     */
    private $adminLoader;

    /**
     * @var ModulesTemplateDirectoryResolver|null
     */
    private $modulesTemplateDirectoryResolver;

    /**
     * @var TwigContextInterface|null
     */
    private $twigContext;

    /**
     * FilesystemLoader constructor.
     *
     * @param array                   $paths
     * @param string|null             $rootPath
     * @param TemplateLoaderInterface $loader
     * @param TemplateLoaderInterface $adminLoader
     */
    public function __construct(
        $paths = [],
        ?string $rootPath = null,
        ?TemplateLoaderInterface $loader = null,
        ?TemplateLoaderInterface $adminLoader = null,
        ?ModulesTemplateDirectoryResolver $modulesTemplateDirectoryResolver = null,
        ?TwigContextInterface $twigContext = null
    ) {
        parent::__construct($paths, $rootPath);

        $this->config = Registry::getConfig();
        $this->loader = $loader;
        $this->adminLoader = $adminLoader;
        $this->modulesTemplateDirectoryResolver = $modulesTemplateDirectoryResolver;
        $this->twigContext = $twigContext;
    }

    /**
     * Module extensions compile with their template chain parent baked in, and the chain depends on the
     * active theme and modules. Keying the compiled template by them keeps shops and theme switches apart.
     * Not covered: an extension file added to an already active module in place; that needs a template
     * cache clear (as on deploy), since only a template's own file is checked for freshness.
     */
    public function getCacheKey(string $name): string
    {
        $key = parent::getCacheKey($name);
        if ($this->modulesTemplateDirectoryResolver !== null && $this->twigContext !== null) {
            $key .= '|' . $this->twigContext->getActiveThemeId()
                . '|' . implode(',', array_keys($this->modulesTemplateDirectoryResolver->getTemplateDirectories()));
        }

        return $key;
    }

    /**
     * Admin templates come from the admin loader. Storefront templates are looked up in the
     * configured Twig paths first and then through the internal template loader.
     *
     * @param string $name
     * @param bool   $throw
     *
     * @return string|false|null
     */
    public function findTemplate(string $name, bool $throw = true)
    {
        $this->addModuleNamespaces();

        if ($this->config->isAdmin()) {
            $template = $this->findByInternalLoader($this->adminLoader, $name);
        } else {
            $template = parent::findTemplate($name, false) ?: $this->findByInternalLoader($this->loader, $name);
        }

        if (!$template) {
            if ($throw) {
                throw new LoaderError(sprintf('Unable to find template "%s".', $name));
            }

            return false;
        }

        return $template;
    }

    /**
     * Active modules' "views/twig" directories become the "@<moduleId>" namespaces. Resolved per call,
     * since the active modules depend on the current shop.
     */
    private function addModuleNamespaces(): void
    {
        if ($this->modulesTemplateDirectoryResolver === null) {
            return;
        }
        foreach ($this->modulesTemplateDirectoryResolver->getTemplateDirectories() as $moduleId => $directory) {
            if (!in_array($directory, $this->getPaths($moduleId), true)) {
                $this->addPath($directory, $moduleId);
            }
        }
    }

    private function findByInternalLoader(?TemplateLoaderInterface $loader, string $name): string
    {
        if (null === $loader) {
            return '';
        }

        try {
            return $loader->getPath($name);
        } catch (TemplateFileNotFoundException $e) {
            return '';
        }
    }
}
