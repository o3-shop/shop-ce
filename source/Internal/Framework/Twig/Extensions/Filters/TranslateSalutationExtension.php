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

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\Filters;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\TranslateSalutationLogic;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class TranslateSalutationExtension
 */
class TranslateSalutationExtension extends AbstractExtension
{
    /**
     * @var TranslateSalutationLogic
     */
    private $translateSalutationLogic;

    /**
     * TranslateSalutationExtension constructor.
     *
     * @param TranslateSalutationLogic $translateSalutationLogic
     */
    public function __construct(TranslateSalutationLogic $translateSalutationLogic)
    {
        $this->translateSalutationLogic = $translateSalutationLogic;
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('translate_salutation', [$this, 'translateSalutation']),
        ];
    }

    /**
     * @param string $ident
     *
     * @return string
     */
    public function translateSalutation(?string $ident = null): string
    {
        return $this->translateSalutationLogic->translateSalutation($ident);
    }
}
