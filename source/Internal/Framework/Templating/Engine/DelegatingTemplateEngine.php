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

/**
 * Forwards every call to the engine chosen for this call.
 *
 * A global goes to the engine resolved now and is remembered; another engine gets the remembered globals the
 * next time it is resolved. So a Smarty storefront never builds the Twig environment just to pass a global.
 */
class DelegatingTemplateEngine implements TemplateEngineInterface
{
    /** @var TemplateEngineResolverInterface */
    private $resolver;

    /** @var array */
    private $globals = [];

    /** @var array<int, true> object ids of the engines that hold every remembered global */
    private $engineIdsInSync = [];

    public function __construct(TemplateEngineResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    public function addGlobal(string $name, $value)
    {
        $engine = $this->engine();
        $engine->addGlobal($name, $value);
        $this->globals[$name] = $value;
        $this->engineIdsInSync = [spl_object_id($engine) => true];
    }

    public function getGlobals(): array
    {
        return $this->engine()->getGlobals();
    }

    public function getDefaultFileExtension(): string
    {
        return $this->engine()->getDefaultFileExtension();
    }

    public function render(string $name, array $context = []): string
    {
        return $this->engine()->render($name, $context);
    }

    public function renderFragment(string $fragment, string $fragmentId, array $context = []): string
    {
        return $this->engine()->renderFragment($fragment, $fragmentId, $context);
    }

    public function exists(string $name): bool
    {
        return $this->engine()->exists($name);
    }

    private function engine(): TemplateEngineInterface
    {
        $engine = $this->resolver->getEngine();
        $id = spl_object_id($engine);
        if (!isset($this->engineIdsInSync[$id])) {
            foreach ($this->globals as $name => $value) {
                $engine->addGlobal($name, $value);
            }
            $this->engineIdsInSync[$id] = true;
        }

        return $engine;
    }
}
