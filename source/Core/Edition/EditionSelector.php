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
 * Class is responsible for returning edition.
 *
 * @internal Do not make a module extension for this class.
 *
 * @deprecated since v6.0.0-rc.2 (2017-08-24); Use \OxidEsales\Facts\Facts instead.
 */
class EditionSelector
{
    const COMMUNITY = 'CE';

    /** @var string Edition abbreviation  */
    private $edition = null;

    /**
     * EditionSelector constructor.
     *
     * @param string|null $edition to force edition.
     */
    public function __construct($edition = null)
    {
        $this->edition = $edition ?: $this->findEdition();
    }

    /**
     * Method returns edition.
     *
     * @return string
     */
    public function getEdition()
    {
        return $this->edition;
    }

    /**
     * @return bool
     */
    public function isCommunity()
    {
        return $this->getEdition() === static::COMMUNITY;
    }

    /**
     * Check for forced edition in config file. If edition is not specified,
     * determine it by ClassMap existence.
     *
     * @return string
     */
    protected function findEdition()
    {
        if (!class_exists('OxidEsales\EshopCommunity\Core\Registry') || !Registry::instanceExists('oxConfigFile')) {
            $configFile = new ConfigFile(OX_BASE_PATH . DIRECTORY_SEPARATOR . "config.inc.php");
        }
        $configFile = isset($configFile) ? $configFile : Registry::get(\OxidEsales\Eshop\Core\ConfigFile::class);
        $edition = $configFile->getVar('edition') ?: $this->getEditionByExistingClasses();
        $configFile->setVar('edition', $edition);

        return strtoupper($edition);
    }

    /**
     * Determine shop edition by existence of edition specific classes.
     *
     * @return string
     */
    protected function getEditionByExistingClasses()
    {
        return static::COMMUNITY;
    }
}
