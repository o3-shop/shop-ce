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

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\TranslateFilterLogic;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class TranslateExtension
 */
class TranslateExtension extends AbstractExtension
{
    /**
     * @var TranslateFilterLogic
     */
    private $multiLangFilterLogic;

    /**
     * TranslateExtension constructor.
     *
     * @param TranslateFilterLogic $multiLangFilterLogic
     */
    public function __construct(TranslateFilterLogic $multiLangFilterLogic)
    {
        $this->multiLangFilterLogic = $multiLangFilterLogic;
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('translate', [$this, 'translate']),
        ];
    }

    /**
     * @param string $ident
     * @param mixed  $arguments
     *
     * @return string
     */
    public function translate($ident, $arguments = null): string
    {
        return $this->multiLangFilterLogic->multiLang($ident, $arguments);
    }
}
