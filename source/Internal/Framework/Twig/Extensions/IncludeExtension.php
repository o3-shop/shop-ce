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
 * Modified for O3-Shop (2026): template chain include ported from twig-component v2.0.1 to PHP 7.4
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Resolver\TemplateChain\TemplateChainResolver;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\IncludeChainTokenParser;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\TokenParser\IncludeDynamicTokenParser;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\IncludeDynamicLogic;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFunction;

/**
 * Class IncludeExtension
 */
class IncludeExtension extends AbstractExtension
{
    /**
     * @var IncludeDynamicLogic
     */
    private $includeDynamicLogic;

    /**
     * @var TemplateChainResolver|null
     */
    private $templateChainResolver;

    public function __construct(
        IncludeDynamicLogic $includeDynamicLogic,
        ?TemplateChainResolver $templateChainResolver = null
    ) {
        $this->includeDynamicLogic = $includeDynamicLogic;
        $this->templateChainResolver = $templateChainResolver;
    }

    /**
     * The "include" and "include_dynamic" tags and the "include()" function render the outermost
     * template of the included template's chain (module extensions).
     *
     * @return TokenParserInterface[]
     */
    public function getTokenParsers(): array
    {
        if ($this->templateChainResolver === null) {
            return [new IncludeDynamicTokenParser()];
        }

        return [
            new IncludeChainTokenParser($this->templateChainResolver),
            new IncludeDynamicTokenParser($this->templateChainResolver),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        if ($this->templateChainResolver === null) {
            return [];
        }

        return [
            new TwigFunction(
                'include',
                [$this, 'include'],
                ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]
            ),
        ];
    }

    /**
     * Twig's include() with the template names resolved through their chains.
     *
     * @see CoreExtension::include
     *
     * @param mixed $template a name, a template object or a list of them
     * @param mixed $variables
     */
    public function include(
        Environment $env,
        $context,
        $template,
        $variables = [],
        bool $withContext = true,
        bool $ignoreMissing = false,
        bool $sandboxed = false
    ): string {
        $resolve = function ($name) {
            return is_string($name) ? $this->templateChainResolver->getLastChild($name) : $name;
        };
        $template = is_array($template) ? array_map($resolve, $template) : $resolve($template);

        return CoreExtension::include($env, $context, $template, $variables, $withContext, $ignoreMissing, $sandboxed);
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function renderForCache(array $parameters): string
    {
        return $this->includeDynamicLogic->renderForCache($parameters);
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    public function includeDynamicPrefix(array $parameters): array
    {
        return $this->includeDynamicLogic->includeDynamicPrefix($parameters);
    }
}
