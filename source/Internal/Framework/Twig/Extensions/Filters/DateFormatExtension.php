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

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\DateFormatHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class DateFormatExtension
 *
 * @package OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\Filters
 */
class DateFormatExtension extends AbstractExtension
{
    /**
     * @var DateFormatHelper
     */
    private $dateFormatHelper;

    /**
     * DateFormatExtension constructor.
     *
     * @param DateFormatHelper $dateFormatHelper
     */
    public function __construct(DateFormatHelper $dateFormatHelper)
    {
        $this->dateFormatHelper = $dateFormatHelper;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [new TwigFilter('date_format', [$this, 'dateFormat'])];
    }

    /**
     * Smarty date_format modifier plugin
     *
     * @param string $string
     * @param string $format
     * @param string $defaultDate
     *
     * @return string|null
     */
    public function dateFormat($string, $format = '%b %e, %Y', $defaultDate = '')
    {
        if ($string != '') {
            $timestamp = $this->getTimestamp($string);
        } elseif ($defaultDate != '') {
            $timestamp = $this->getTimestamp($defaultDate);
        } else {
            return null;
        }

        if (DIRECTORY_SEPARATOR == '\\') {
            $format = $this->dateFormatHelper->fixWindowsTimeFormat($format, $timestamp);
        }

        return strftime($format, $timestamp);
    }

    /**
     * smarty_make_timestamp
     *
     * @param string $string
     *
     * @return false|int
     */
    private function getTimestamp($string)
    {
        if (empty($string)) {
            // use "now":
            $time = time();
        } elseif (is_numeric($string) && strlen((string)$string) === 14) {
            // it is mysql timestamp format of YYYYMMDDHHMMSS?
            $string = (string)$string;
            $time = mktime(
                (int)substr($string, 8, 2),
                (int)substr($string, 10, 2),
                (int)substr($string, 12, 2),
                (int)substr($string, 4, 2),
                (int)substr($string, 6, 2),
                (int)substr($string, 0, 4)
            );
        } elseif (is_numeric($string)) {
            // it is a numeric string, we handle it as timestamp
            $time = (int) $string;
        } else {
            // strtotime should handle it
            $time = strtotime($string);
            if ($time == -1 || $time === false) {
                // strtotime() was not able to parse $string, use "now":
                $time = time();
            }
        }

        return $time;
    }
}
