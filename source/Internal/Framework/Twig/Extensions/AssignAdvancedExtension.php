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

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\AssignAdvancedLogic;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AssignAdvancedExtension
 *
 * @package OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions
 */
class AssignAdvancedExtension extends AbstractExtension
{
    /**
     * @var AssignAdvancedLogic
     */
    private $assignAdvancedLogic;

    /**
     * AssignAdvancedExtension constructor.
     *
     * @param AssignAdvancedLogic $assignAdvancedLogic
     */
    public function __construct(AssignAdvancedLogic $assignAdvancedLogic)
    {
        $this->assignAdvancedLogic = $assignAdvancedLogic;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'assign_advanced',
                [$this, 'assignAdvanced'],
                ['deprecated' => true, 'alternative' => 'Twig array syntax']
            ),
        ];
    }

    /**
     * Calles formatValue function to format arrays and range()
     *
     * @param string $value
     *
     * @return mixed
     */
    public function assignAdvanced(string $value): string
    {
        $formattedValue = $this->assignAdvancedLogic->formatValue($value);

        return $formattedValue;
    }
}
