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
 * Modified for O3-Shop (2026): ported from twig-component v2.0.1 to PHP 7.4
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\Resolver\TemplateChain;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Resolver\ModulesTemplateDirectoryResolver;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TwigContextInterface;
use Webmozart\PathUtil\Path;

/**
 * Builds the inheritance chain of a theme template: the module extensions
 * "@<moduleId>/extensions/themes/<themeId|default>/<template>" of the active modules in activation order
 * (per module the first match of: active child theme, its parent theme, "default"),
 * followed by the theme template itself. The first entry is rendered; each entry extends the next one.
 */
class TemplateChainResolver
{
    private const DEFAULT_THEME_ID = 'default';
    private const EXTENSION_TEMPLATE_PATTERN = '%^@[^\s/]+/extensions/themes/[^\s/]+/(.+)$%';

    /** @var ModulesTemplateDirectoryResolver */
    private $modulesTemplateDirectoryResolver;

    /** @var TwigContextInterface */
    private $twigContext;

    public function __construct(
        ModulesTemplateDirectoryResolver $modulesTemplateDirectoryResolver,
        TwigContextInterface $twigContext
    ) {
        $this->modulesTemplateDirectoryResolver = $modulesTemplateDirectoryResolver;
        $this->twigContext = $twigContext;
    }

    /**
     * The template to render for $templateName: the outermost module extension, or the name itself.
     */
    public function getLastChild(string $templateName): string
    {
        $chain = $this->getChain($templateName);

        return count($chain) > 1 ? $chain[0] : $templateName;
    }

    /**
     * The template that $templateName extends in its chain, or null if it is the end of the chain.
     */
    public function getParent(string $templateName): ?string
    {
        $chain = $this->getChain($templateName);
        $position = array_search($templateName, $chain, true);

        return $position !== false && isset($chain[$position + 1]) ? $chain[$position + 1] : null;
    }

    /**
     * @return string[]
     */
    private function getChain(string $templateName): array
    {
        $themeTemplate = $this->getThemeTemplateName($templateName);
        if ($themeTemplate === null) {
            return [$templateName];
        }

        $chain = [];
        $themeIds = array_merge($this->twigContext->getActiveThemeIds(), [self::DEFAULT_THEME_ID]);
        foreach ($this->modulesTemplateDirectoryResolver->getTemplateDirectories() as $moduleId => $directory) {
            foreach ($themeIds as $themeId) {
                $relativePath = "extensions/themes/$themeId/$themeTemplate";
                if (is_file(Path::join($directory, $relativePath))) {
                    $chain[] = "@$moduleId/$relativePath";
                    break;
                }
            }
        }
        $chain[] = $themeTemplate;

        return $chain;
    }

    /**
     * Maps a theme template or a module extension of it to the theme template name. A legacy ".tpl"
     * name is mapped to its ".html.twig" sibling. Other namespaced templates have no chain (null).
     */
    private function getThemeTemplateName(string $templateName): ?string
    {
        if (preg_match(self::EXTENSION_TEMPLATE_PATTERN, $templateName, $matches)) {
            return $matches[1];
        }
        if (strpos($templateName, '@') === 0) {
            return null;
        }

        return preg_replace('/\.tpl$/', '.html.twig', $templateName);
    }
}
