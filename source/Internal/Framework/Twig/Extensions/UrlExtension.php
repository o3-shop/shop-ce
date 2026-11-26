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

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\AddUrlParametersLogic;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\SeoUrlLogic;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class UrlExtension
 */
class UrlExtension extends AbstractExtension
{
    /**
     * @var SeoUrlLogic
     */
    private $seoUrlLogic;

    /**
     * @var AddUrlParametersLogic
     */
    private $addUrlParametersLogic;

    /**
     * OxidExtension constructor.
     *
     * @param SeoUrlLogic           $seoUrlLogic
     * @param AddUrlParametersLogic $addUrlParametersLogic
     */
    public function __construct(SeoUrlLogic $seoUrlLogic, AddUrlParametersLogic $addUrlParametersLogic)
    {
        $this->seoUrlLogic = $seoUrlLogic;
        $this->addUrlParametersLogic = $addUrlParametersLogic;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('seo_url', [$this, 'getSeoUrl']),
        ];
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('add_url_parameters', [$this, 'addUrlParameters']),
        ];
    }

    /**
     * Output SEO style url
     *
     * @param array $parameters
     *
     * @return null|string
     */
    public function getSeoUrl(array $parameters): ?string
    {
        $url = $this->seoUrlLogic->seoUrl($parameters);

        $dynamicParameters = isset($parameters['params']) ? $parameters['params'] : false;
        if ($dynamicParameters) {
            $url = $this->addUrlParameters($url, $dynamicParameters);
        }

        return $this->toPlainUrl($url);
    }

    /**
     * Add additional parameters to url
     *
     * @param string $url               Url
     * @param string $dynamicParameters Dynamic URL parameters
     *
     * @return string
     */
    public function addUrlParameters(string $url, string $dynamicParameters): string
    {
        return $this->toPlainUrl($this->addUrlParametersLogic->addUrlParameters($url, $dynamicParameters));
    }

    /**
     * The URL logic returns HTML-encoded ampersands. A URL is not markup, so it is returned plain and
     * left to the autoescaper, which encodes it exactly once.
     */
    private function toPlainUrl(string $url): string
    {
        return str_replace('&amp;', '&', $url);
    }
}
