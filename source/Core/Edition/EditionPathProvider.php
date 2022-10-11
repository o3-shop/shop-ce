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

/**
 * Class is responsible for returning directories paths according edition.
 *
 * @internal Do not make a module extension for this class.
 *
 * @deprecated since v6.0.0-rc.2 (2017-08-24); Use \OxidEsales\Facts\Facts instead.
 */
class EditionPathProvider
{
    const SETUP_DIRECTORY = 'Setup';

    const DATABASE_SQL_DIRECTORY = 'Sql';

    /** @var EditionRootPathProvider */
    private $editionRootPathProvider;

    /**
     * @param EditionRootPathProvider $editionRootPathProvider
     */
    public function __construct($editionRootPathProvider)
    {
        $this->editionRootPathProvider = $editionRootPathProvider;
    }

    /**
     * Method forms path to corresponding edition setup directory.
     *
     * @return string
     */
    public function getSetupDirectory()
    {
        return $this->getEditionRootPathProvider()->getDirectoryPath()
        . static::SETUP_DIRECTORY . DIRECTORY_SEPARATOR;
    }

    /**
     * Method forms path to corresponding edition database sql files directory.
     *
     * @return string
     */
    public function getDatabaseSqlDirectory()
    {
        return $this->getSetupDirectory() . static::DATABASE_SQL_DIRECTORY . DIRECTORY_SEPARATOR;
    }

    /**
     * Method forms path to corresponding edition views directory.
     *
     * @return string
     */
    public function getViewsDirectory()
    {
        return $this->getEditionRootPathProvider()->getDirectoryPath()
        . 'Application' . DIRECTORY_SEPARATOR
        . 'views' . DIRECTORY_SEPARATOR;
    }

    /**
     * Method forms path to corresponding smarty plugins directory.
     *
     * @return string
     */
    public function getSmartyPluginsDirectory()
    {
        return $this->getEditionRootPathProvider()->getDirectoryPath() . 'Core/Smarty/Plugin/';
    }

    /**
     * @return EditionRootPathProvider
     */
    protected function getEditionRootPathProvider()
    {
        return $this->editionRootPathProvider;
    }
}
