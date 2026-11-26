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

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\UtilsView;

/**
 * Class TwigContext
 *
 * @package OxidEsales\EshopCommunity\Internal\Framework\Twig
 */
class TwigContext implements TwigContextInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var UtilsView
     */
    private $utilsView;

    /**
     * Context constructor.
     *
     * @param Config    $config
     * @param UtilsView $utilsView
     */
    public function __construct(Config $config, UtilsView $utilsView)
    {
        $this->config = $config;
        $this->utilsView = $utilsView;
    }

    /**
     * @return array
     */
    public function getTemplateDirectories(): array
    {
        $templateDirectory = $this->utilsView->getTemplateDirs();
        $directories = array_filter(
            $templateDirectory,
            function ($directory) {
                return is_dir($directory);
            }
        );

        return $directories;
    }

    /**
     * @return bool
     */
    public function getIsDebug(): bool
    {
        return (bool) $this->config->getConfigParam('iDebug', false);
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->config->getConfigParam('sCompileDir') . '/twig';
    }
}
