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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Core\Edition;

use OxidEsales\Eshop\Core\ConfigFile;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class is responsible for returning edition directory path.
 *
 * @internal Do not make a module extension for this class.
 *
 * @deprecated since v6.0.0-rc.2 (2017-08-24); Use \OxidEsales\Facts\Facts instead.
 */
class EditionRootPathProvider
{
    const EDITIONS_DIRECTORY = 'o3-shop';

    /** @var EditionSelector */
    private $editionSelector;

    /**
     * @param EditionSelector $editionSelector
     */
    public function __construct($editionSelector)
    {
        $this->editionSelector = $editionSelector;
    }

    /**
     * Returns path to edition directory. If no additional editions are found, returns base path.
     *
     * @return string
     */
    public function getDirectoryPath()
    {
        $editionsPath = VENDOR_PATH . static::EDITIONS_DIRECTORY;
        $path = getShopBasePath();

        return realpath($path) . DIRECTORY_SEPARATOR;
    }

    /**
     * @return EditionSelector
     */
    protected function getEditionSelector()
    {
        return $this->editionSelector;
    }
}
