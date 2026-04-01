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

namespace OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine;

use Doctrine\DBAL\Result;
use OxidEsales\Eshop\Core\Database\Adapter\ResultSetInterface;
use PDO;

/**
 * The doctrine result wrapper, to support the old adodblite interface.
 *
 * @package OxidEsales\EshopCommunity\Core\Database\Adapter
 *
 * @deprecated since v6.5.0 (2019-09-24); Use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface
 */
class ResultSet implements \IteratorAggregate, ResultSetInterface
{
    /**
     * @var array Holds the retrieved fields of the resultSet row on the current cursor position.
     */
    public $fields;

    /**
     * @var bool Did we reach the end of the results?
     */
    public $EOF;

    /**
     * @var array All rows buffered from the Result on construction.
     */
    private $bufferedRows = [];

    /**
     * @var int Current position in the buffered rows array.
     */
    private $currentRow = 0;

    /**
     * DoctrineResultSet constructor.
     *
     * @param Result $result    The DBAL Result to wrap.
     * @param int    $fetchMode PDO fetch mode constant (PDO::FETCH_NUM, PDO::FETCH_ASSOC, PDO::FETCH_BOTH).
     *                         Defaults to PDO::FETCH_NUM to match the Database adapter default.
     */
    public function __construct(Result $result, int $fetchMode = PDO::FETCH_NUM)
    {
        $this->bufferedRows = $this->fetchAllWithMode($result, $fetchMode);
        $result->free();

        $this->fields = [];
        $this->EOF = false;
        $this->currentRow = 0;

        if ($this->count() == 0) {
            $this->setToEmptyState();
        }

        $this->fetchRow();
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
        $this->bufferedRows = [];
        $this->currentRow = 0;
        $this->fields = [];
        $this->EOF = true;
    }

    /**
     * Fetches the next row from the buffered rows and fills the fields array.
     *
     * @return mixed The next row as an array, or false if no more rows.
     */
    public function fetchRow()
    {
        if ($this->currentRow < count($this->bufferedRows)) {
            $this->fields = $this->bufferedRows[$this->currentRow];
            $this->currentRow++;
        } else {
            $this->fields = false;
            $this->EOF = true;
        }

        return $this->fields;
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->bufferedRows;
    }

    /**
     * Returns the number of columns in the result set.
     *
     * @return int The number of columns.
     */
    public function fieldCount()
    {
        return !empty($this->bufferedRows) ? count(reset($this->bufferedRows)) : 0;
    }

    /**
     * Returns an external iterator over all buffered rows.
     *
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->bufferedRows);
    }

    /**
     * Returns fields array
     *
     * @return array containing the retrieved fields of the resultSet row
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set the state of this wrapper to 'empty'.
     */
    protected function setToEmptyState()
    {
        $this->EOF = true;
    }

    /**
     * Count elements of an object.
     *
     * @return int The number of rows in the result set.
     */
    public function count(): int
    {
        return count($this->bufferedRows);
    }

    /**
     * Buffer all rows from the Result using the requested PDO fetch mode.
     *
     * DBAL 3 removed the unified fetchAll()-with-mode API.  We emulate the
     * legacy PDO fetch-mode behaviour so that callers relying on numeric or
     * BOTH-keyed rows (the historical default) continue to work.
     *
     * @param Result $result
     * @param int    $fetchMode
     * @return array
     */
    private function fetchAllWithMode(Result $result, int $fetchMode): array
    {
        switch ($fetchMode) {
            case PDO::FETCH_ASSOC:
                return $result->fetchAllAssociative();
            case PDO::FETCH_BOTH:
                return array_map(
                    static function (array $row): array {
                        return array_merge(array_values($row), $row);
                    },
                    $result->fetchAllAssociative()
                );
            case PDO::FETCH_NUM:
            default:
                return $result->fetchAllNumeric();
        }
    }
}
