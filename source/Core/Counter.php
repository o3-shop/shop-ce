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

namespace OxidEsales\EshopCommunity\Core;

use Exception;

/**
 * Counter class
 *
 */
class Counter
{
    /**
     * Return the next counter value for a given type of counter
     *
     * @param string $ident Identifies the type of counter. E.g. 'oxOrder'
     *
     * @throws Exception
     *
     * @return int Next counter value
     */
    public function getNext($ident)
    {
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        /** Current counter retrieval needs to be encapsulated in transaction */
        $database->startTransaction();
        try {
            /** Block row for reading until the counter is updated */
            $query = "SELECT `oxcount` FROM `oxcounters` WHERE `oxident` = :oxident FOR UPDATE";
            $currentCounter = (int) $database->getOne($query, [
                ':oxident' => $ident
            ]);
            $nextCounter = $currentCounter + 1;

            /** Insert or increment the the counter */
            $query = "INSERT INTO `oxcounters` (`oxident`, `oxcount`) VALUES (:oxident, 1) ON DUPLICATE KEY UPDATE `oxcount` = `oxcount` + 1";
            $database->execute($query, [':oxident' => $ident]);

            $database->commitTransaction();
        } catch (Exception $exception) {
            $database->rollbackTransaction();

            throw $exception;
        }

        return $nextCounter;
    }

    /**
     * Update the counter value for a given type of counter, but only when it is greater than the current value
     *
     * @param string  $ident Identifies the type of counter. E.g. 'oxOrder'
     * @param integer $count New counter value
     *
     * @throws Exception
     *
     * @return int Number of affected rows
     */
    public function update($ident, $count)
    {
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        /** Current counter retrieval needs to be encapsulated in transaction */
        $database->startTransaction();
        try {
            /** Block row for reading until the counter is updated */
            $query = "SELECT `oxcount` FROM `oxcounters` WHERE `oxident` = :oxident FOR UPDATE";
            $database->getOne($query, [
                ':oxident' => $ident
            ]);

            /** Insert or update the counter, if the value to be updated is greater, than the current value */
            $query = "INSERT INTO `oxcounters` (`oxident`, `oxcount`) VALUES (:oxident, :oxcount) ON DUPLICATE KEY UPDATE `oxcount` = IF(:oxcount > oxcount, :oxcount, oxcount)";
            $result = $database->execute($query, [':oxident' => $ident, ':oxcount' => $count]);

            $database->commitTransaction();
        } catch (Exception $exception) {
            $database->rollbackTransaction();

            throw $exception;
        }

        return $result;
    }
}
