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

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\StyleLogic;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class StyleExtension
 *
 * @package OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions
 */
class StyleExtension extends AbstractExtension
{
    /**
     * @var StyleLogic
     */
    private $styleLogic;

    /**
     * StyleExtension constructor.
     *
     * @param StyleLogic $styleLogic
     */
    public function __construct(StyleLogic $styleLogic)
    {
        $this->styleLogic = $styleLogic;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [new TwigFunction('style', [$this, 'style'], ['needs_environment' => true, 'is_safe' => ['html']])];
    }

    /**
     * @param Environment $env
     * @param array       $params
     *
     * @return string
     */
    public function style(Environment $env, $params = [])
    {
        $globals = $env->getGlobals();
        $isDynamic = false;
        if (isset($globals['__oxid_include_dynamic'])) {
            $isDynamic = $globals['__oxid_include_dynamic'];
        }

        $output = $this->styleLogic->collectStyleSheets($params, $isDynamic);

        return $output;
    }
}
