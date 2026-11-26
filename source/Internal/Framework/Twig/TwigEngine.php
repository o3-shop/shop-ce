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

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Escaper\EscaperInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Resolver\TemplateChain\TemplateChainResolver;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extension\EscaperExtension;

/**
 * Class TwigEngine
 */
class TwigEngine implements TemplateEngineInterface
{
    /**
     * @var Environment
     */
    private $engine;

    /**
     * @var TemplateChainResolver|null
     */
    private $templateChainResolver;

    /**
     * TwigEngine constructor.
     *
     * @param Environment $engine
     * @param iterable    $twigExtensions
     * @param iterable    $twigEscaper
     */
    public function __construct(
        Environment $engine,
        iterable $twigExtensions = [],
        iterable $twigEscaper = [],
        ?TemplateChainResolver $templateChainResolver = null
    ) {
        $this->engine = $engine;
        $this->templateChainResolver = $templateChainResolver;

        foreach ($twigExtensions as $extension) {
            $this->engine->addExtension($extension);
        }
        foreach ($twigEscaper as $escaper) {
            $this->addEscaper($escaper);
        }
        if ($this->engine->isDebug()) {
            $this->engine->addExtension(new DebugExtension());
        }
    }

    /**
     * Returns the template file extension.
     * @return string
     */
    public function getDefaultFileExtension(): string
    {
        return 'html.twig';
    }

    /**
     * Renders a template.
     *
     * @param string $name    A template name
     * @param array  $context An array of parameters to pass to the template
     *
     * @return string The evaluated template as a string
     *
     * @throws \RuntimeException if the template cannot be rendered
     */
    public function render(string $name, array $context = []): string
    {
        if ($this->templateChainResolver !== null) {
            $name = $this->templateChainResolver->getLastChild($name);
        }

        return $this->engine->render($name, $context);
    }

    /**
     * Returns true if the template exists.
     *
     * @param string $name A template name
     *
     * @return bool true if the template exists, false otherwise
     *
     * @throws \RuntimeException if the engine cannot handle the template name
     */
    public function exists(string $name): bool
    {
        return $this->engine->getLoader()->exists($name);
    }

    /**
     * Renders a fragment of the template.
     *
     * @param string $fragment   The template fragment to render
     * @param string $fragmentId The Id of the fragment
     * @param array  $context    An array of parameters to pass to the template
     *
     * @return string
     */
    public function renderFragment(string $fragment, string $fragmentId, array $context = []): string
    {
        $template = $this->engine->createTemplate($fragment, $fragmentId);

        return $template->render($context);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function addGlobal(string $name, $value)
    {
        $this->engine->addGlobal($name, $value);
    }

    /**
     * Returns assigned globals.
     *
     * @return array
     */
    public function getGlobals(): array
    {
        return $this->engine->getGlobals();
    }

    /**
     * @param EscaperInterface $escaper
     */
    public function addEscaper(EscaperInterface $escaper)
    {
        /** @var EscaperExtension $escaperExtension */
        $escaperExtension = $this->engine->getExtension(EscaperExtension::class);
        $escaperExtension->setEscaper($escaper->getStrategy(), [$escaper, 'escape']);
    }
}
