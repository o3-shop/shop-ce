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

namespace OxidEsales\EshopCommunity\Internal\Framework\Twig;

/**
 * Class TwigEngineConfiguration
 *
 * @package OxidEsales\EshopCommunity\Internal\Framework\Twig
 */
class TwigEngineConfiguration implements TwigEngineConfigurationInterface
{
    /**
     * @var TwigContextInterface
     */
    private $context;

    /**
     * TemplateEngineConfiguration constructor.
     *
     * @param TwigContextInterface $context
     */
    public function __construct(TwigContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * Return an array of twig parameters to configure.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return [
            'debug' => $this->context->getIsDebug(),
            'cache' => $this->context->getCacheDir(),
            'autoescape' => [self::class, 'getAutoescapeStrategy'],
        ];
    }

    /**
     * Autoescape html everywhere except plain-text mail templates. Matches the path segment so that logical names
     * ("email/plain/x.html.twig") and module chain names ("@module/email/plain/x.html.twig") are both covered.
     * A static callable (not a closure) keeps the options array dumpable by the container.
     *
     * @param string $templateName
     *
     * @return string|false
     */
    public static function getAutoescapeStrategy(string $templateName)
    {
        return preg_match('#(^|/)email/plain/#', $templateName) === 1 ? false : 'html';
    }
}
