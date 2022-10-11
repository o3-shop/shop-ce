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

namespace OxidEsales\EshopCommunity\Core\Dao;

/**
 * Data access object interface.
 *
 * @internal Do not make a module extension for this class.
 */
interface BaseDaoInterface
{
    /**
     * Finds all entities.
     *
     * @return array
     */
    public function findAll();

    /**
     * Deletes the entity with the given id.
     *
     * @param string $id An id of the entity to delete.
     */
    public function delete($id);

    /**
     * Updates or insert the given entity.
     *
     * @param object $object
     */
    public function save($object);

    /**
     * Start a database transaction.
     */
    public function startTransaction();

    /**
     * Commit a database transaction.
     */
    public function commitTransaction();

    /**
     * RollBack a database transaction.
     */
    public function rollbackTransaction();
}
