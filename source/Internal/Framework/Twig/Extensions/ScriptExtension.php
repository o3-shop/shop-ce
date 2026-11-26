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

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\ScriptLogic;
use Twig\Error\Error;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class ScriptExtension
 */
class ScriptExtension extends AbstractExtension
{
    /**
     * @var ScriptLogic
     */
    private $scriptLogic;

    /**
     * ScriptExtension constructor.
     *
     * @param ScriptLogic $scriptLogic
     */
    public function __construct(ScriptLogic $scriptLogic)
    {
        $this->scriptLogic = $scriptLogic;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [new TwigFunction('script', [$this, 'script'], ['needs_context' => true, 'is_safe' => ['html']])];
    }

    /**
     * @param array $context
     * @param array $parameters
     *
     * @return string
     * @throws Error
     */
    public function script(array $context = [], array $parameters = []): string
    {
        $isDynamic = isset($context['__oxid_include_dynamic']) ? (bool) $context['__oxid_include_dynamic'] : false;
        $priority = !empty($parameters['priority']) ? $parameters['priority'] : 3;
        $widget = !empty($parameters['widget']) ? $parameters['widget'] : '';
        $isInWidget = !empty($parameters['inWidget']) ? $parameters['inWidget'] : false;
        $output = '';

        if (isset($parameters['add'])) {
            if (empty($parameters['add'])) {
                throw new Error("{{ script }} parameter 'add' can not be empty!");
            }

            $this->scriptLogic->add($parameters['add'], $isDynamic);
        } elseif (isset($parameters['include'])) {
            if (empty($parameters['include'])) {
                throw new Error("{{ script }} parameter 'include' can not be empty!");
            }

            $this->scriptLogic->include($parameters['include'], $priority, $isDynamic);
        } else {
            $output = $this->scriptLogic->render($widget, $isInWidget, $isDynamic);
        }

        return $output;
    }
}
