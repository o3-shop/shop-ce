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
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Psr\Container\ContainerInterface;

class TemplateEngineResolver implements TemplateEngineResolverInterface
{
    /** @var ContainerInterface engines keyed by TemplateEngineName, lazy (breaks the Twig loader cycle) */
    private $engines;

    /** @var ThemeTemplateEngineProviderInterface */
    private $themeEngineProvider;

    /** @var ContextInterface */
    private $context;

    public function __construct(
        ContainerInterface $engines,
        ThemeTemplateEngineProviderInterface $themeEngineProvider,
        ContextInterface $context
    ) {
        $this->engines = $engines;
        $this->themeEngineProvider = $themeEngineProvider;
        $this->context = $context;
    }

    public function getEngine(): TemplateEngineInterface
    {
        $name = $this->context->isAdmin()
            ? TemplateEngineName::SMARTY
            : $this->themeEngineProvider->getEngineName();

        return $this->engines->get($name);
    }
}
