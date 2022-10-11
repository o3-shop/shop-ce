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

namespace OxidEsales\EshopCommunity\Core\Database\Adapter;

/**
 * Interface ResultSetInterface
 *
 * @deprecated since v6.5.0 (2019-09-24); Use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface
 */
interface ResultSetInterface extends \Traversable, \Countable
{

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return boolean Returns true on success or false on failure.
     */
    public function close();

    /**
     * Returns an array containing all of the result set rows
     *
     * @return array
     */
    public function fetchAll();

    /**
     * Returns the next row from a result set.
     *
     * @return mixed The return value of this function on success depends on the fetch type.
     *               In all cases, FALSE is returned on failure.
     */
    public function fetchRow();

    /**
     * Returns the number of columns in the result set
     *
     * @return integer Returns the number of columns in the result set represented by the PDOStatement object.
     */
    public function fieldCount();
}
