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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Model;

/**
 * Class StateTest
 *
 * @package OxidEsales\EshopCommunity\Tests\Integration\Application\Model
 */
class StateTest extends \OxidEsales\TestingLibrary\UnitTestCase
{

    /**
     * Entries in the table oxstates are unique by a composite key of the columns `OXID` and `OXCOUNTRYID`.
     *
     * See https://bugs.oxid-esales.com/view.php?id=5029
     */
    public function testInsertingDuplicateStatesIsNotPossible()
    {
        $this->expectException(\OxidEsales\Eshop\Core\Exception\DatabaseErrorException::class);
        $database = $this->getDb();
        $sql = "INSERT INTO `oxstates` (`OXID`, `OXCOUNTRYID`) VALUES (?, ?)";
        $database->execute($sql, ['duplicateOxid', 'duplicateCountryId']);
        $database->execute($sql, ['duplicateOxid', 'duplicateCountryId']);
    }

    /**
     * Entries in the table oxstates are unique by a composite key of the columns `OXID` and `OXCOUNTRYID`.
     *
     * See https://bugs.oxid-esales.com/view.php?id=5029
     */
    public function testInsertingDuplicateOxidButDifferentCountryIdIsPossible()
    {
        $database = $this->getDb();
        $sql = "INSERT INTO `oxstates` (`OXID`, `OXCOUNTRYID`) VALUES (?, ?)";
        try {
            $database->execute($sql, ['duplicateOxid', 'CountryId-1']);
            $database->execute($sql, ['duplicateOxid', 'CountryId-2']);
        } catch (\OxidEsales\Eshop\Core\Exception\DatabaseErrorException $exception) {
            $this->fail("Inserting two states with duplicate OXIDs but different countryIds is not possible");
        }
    }
}
