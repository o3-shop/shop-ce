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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Templating\Engine;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateEngineInterface;
use Psr\Container\ContainerInterface;

/**
 * Forwards every call to the engine chosen for this call; globals go to all engines.
 */
class DelegatingTemplateEngine implements TemplateEngineInterface
{
    /** @var TemplateEngineResolverInterface */
    private $resolver;

    /** @var ContainerInterface engines keyed by TemplateEngineName */
    private $engines;

    public function __construct(TemplateEngineResolverInterface $resolver, ContainerInterface $engines)
    {
        $this->resolver = $resolver;
        $this->engines = $engines;
    }

    public function addGlobal(string $name, $value)
    {
        foreach ([TemplateEngineName::SMARTY, TemplateEngineName::TWIG] as $engineName) {
            $this->engines->get($engineName)->addGlobal($name, $value);
        }
    }

    public function getGlobals(): array
    {
        return $this->resolver->getEngine()->getGlobals();
    }

    public function getDefaultFileExtension(): string
    {
        return $this->resolver->getEngine()->getDefaultFileExtension();
    }

    public function render(string $name, array $context = []): string
    {
        return $this->resolver->getEngine()->render($name, $context);
    }

    public function renderFragment(string $fragment, string $fragmentId, array $context = []): string
    {
        return $this->resolver->getEngine()->renderFragment($fragment, $fragmentId, $context);
    }

    public function exists(string $name): bool
    {
        return $this->resolver->getEngine()->exists($name);
    }
}
