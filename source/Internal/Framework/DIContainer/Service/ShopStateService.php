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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Service;

use OxidEsales\EshopCommunity\Core\DynamicPropertiesTrait;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;

/**
 * @internal
 */
class ShopStateService implements ShopStateServiceInterface
{
    use DynamicPropertiesTrait;

    /**
     * @var BasicContextInterface
     */
    private $basicContext;

    /**
     * @var string
     */
    private $anyUnifiedNamespace;

    private $dbHost;
    private $dbPort;
    private $dbName;
    private $dbUser;
    private $dbPwd;

    /**
     * ShopStateService constructor.
     * @param BasicContextInterface $basicContext
     * @param string                $anyUnifiedNamespace
     */
    public function __construct(BasicContextInterface $basicContext, string $anyUnifiedNamespace)
    {
        $this->basicContext = $basicContext;
        $this->anyUnifiedNamespace = $anyUnifiedNamespace;
    }

    /**
     * @return bool
     */
    public function isLaunched(): bool
    {
        return $this->areUnifiedNamespacesGenerated()
            && $this->doesConfigFileExist()
            && $this->doesConfigTableExist();
    }

    /**
     * @return bool
     */
    private function areUnifiedNamespacesGenerated(): bool
    {
        return class_exists($this->anyUnifiedNamespace);
    }

    /**
     * @return bool
     */
    private function doesConfigFileExist(): bool
    {
        return file_exists($this->basicContext->getConfigFilePath());
    }

    /**
     * @return bool
     */
    private function doesConfigTableExist(): bool
    {
        try {
            $connection = $this->getConnection();
            $connection->exec(
                'SELECT 1 FROM ' . $this->basicContext->getConfigTableName() . ' LIMIT 1'
            );
        } catch (\PDOException $exception) {
            return false;
        }

        return true;
    }

    /**
     * @return \PDO
     */
    private function getConnection(): \PDO
    {
        include $this->basicContext->getConfigFilePath();

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $this->dbHost,
            $this->dbPort,
            $this->dbName
        );

        return new \PDO(
            $dsn,
            $this->dbUser,
            $this->dbPwd,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]
        );
    }
}
