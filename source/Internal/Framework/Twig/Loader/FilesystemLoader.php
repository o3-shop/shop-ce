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
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\Loader;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Exception\TemplateFileNotFoundException;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Loader\TemplateLoaderInterface;
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
        ?TemplateLoaderInterface $adminLoader = null
    ) {
        parent::__construct($paths, $rootPath);

        $this->config = Registry::getConfig();
        $this->loader = $loader;
        $this->adminLoader = $adminLoader;
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
