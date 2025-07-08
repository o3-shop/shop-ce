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

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Model\MultiLanguageModel;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * State handler
 */
class State extends MultiLanguageModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxstate';

    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init("oxstates");
    }

    /**
     * Returns country id by code
     *
     * @param string $sCode country code
     * @param string $sCountryId country id
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getIdByCode($sCode, $sCountryId)
    {
        $oDb = DatabaseProvider::getDb();
        $params = [
            ':oxisoalpha2' => $sCode,
            ':oxcountryid' => $sCountryId
        ];

        return $oDb->getOne("SELECT oxid FROM oxstates 
            WHERE oxisoalpha2 = :oxisoalpha2 
              AND oxcountryid = :oxcountryid", $params);
    }

    /**
     * Get state title by id
     *
     * @param integer|string $iStateId
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function getTitleById($iStateId)
    {
        $oDb = DatabaseProvider::getDb();
        $sQ = "SELECT oxtitle FROM " . Registry::get(TableViewNameGenerator::class)->getViewName("oxstates") . " 
            WHERE oxid = :oxid";

        $sStateTitle = $oDb->getOne($sQ, [
            ':oxid' => $iStateId
        ]);

        return (string) $sStateTitle;
    }
}
