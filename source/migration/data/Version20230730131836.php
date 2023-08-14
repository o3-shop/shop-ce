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

namespace OxidEsales\EshopCommunity\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\Migrations\AbstractMigration;

final class Version20230730131836 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add admin navigation roles table';
    }

    public function up(Schema $schema) : void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $this->adminNaviTable($schema);
    }

    public function down( Schema $schema ): void {}

    public function adminNaviTable(Schema $schema): void
    {
        $adminNaviTable = $schema->hasTable('o3rightsroles') ?
            $schema->getTable('o3rightsroles') :
            $schema->createTable('o3rightsroles');

        if (!$adminNaviTable->hasColumn('OXID')) {
            $adminNaviTable->addColumn('OXID', (new StringType())->getName())
                ->setLength(32)
                ->setFixed(true)
                ->setNotnull(true);
        }
        if (!$adminNaviTable->hasColumn('SHOPID')) {
            $adminNaviTable->addColumn('SHOPID', (new IntegerType())->getName())
                ->setLength(11)
                ->setNotnull(true);
        }
        if (!$adminNaviTable->hasColumn('TITLE')) {
            $adminNaviTable->addColumn('TITLE', (new StringType())->getName())
                ->setLength(255)
                ->setFixed(false)
                ->setNotnull(true);
        }
        if (!$adminNaviTable->hasColumn('NAVIGATIONID')) {
            $adminNaviTable->addColumn('NAVIGATIONID', (new StringType())->getName())
                ->setLength(255)
                ->setFixed(false)
                ->setNotnull(true);
        }
        if (!$adminNaviTable->hasColumn('OXTIMESTAMP')) {
            $adminNaviTable->addColumn('OXTIMESTAMP', (new DateTimeType())->getName())
                ->setNotnull(true)
                ->setDefault('CURRENT_TIMESTAMP');
        }

        $adminNaviTable->hasPrimaryKey() ?: $adminNaviTable->setPrimaryKey(['OXID', 'OXID']);

        if ($adminNaviTable->hasIndex('SHOPNAVIGATION') === false) {
            $adminNaviTable->addIndex(['SHOPID', 'NAVIGATIONID'], 'SHOPNAVIGATION');
        }
    }
}
