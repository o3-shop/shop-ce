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
        return 'add admin navigation roles tables';
    }

    public function up(Schema $schema) : void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $this->rightsRolesTable($schema);
        $this->rightsRolesElementsTable($schema);
        $this->object2roleTable($schema);
    }

    public function down( Schema $schema ): void {}

    public function rightsRolesTable(Schema $schema): void
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
        if (!$adminNaviTable->hasColumn('ACTIVE')) {
            $adminNaviTable->addColumn('ACTIVE', (new IntegerType())->getName())
                ->setLength(1)
                ->setFixed(true)
                ->setNotnull(true);
        }
        if (!$adminNaviTable->hasColumn('TITLE')) {
            $adminNaviTable->addColumn('TITLE', (new StringType())->getName())
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

    public function rightsRolesElementsTable(Schema $schema): void
    {
        $adminNaviTable = $schema->hasTable('o3rightsroleselements') ?
            $schema->getTable('o3rightsroleselements') :
            $schema->createTable('o3rightsroleselements');

        if (!$adminNaviTable->hasColumn('OXID')) {
            $adminNaviTable->addColumn('OXID', (new StringType())->getName())
                ->setLength(32)
                ->setFixed(true)
                ->setNotnull(true);
        }

        if (!$adminNaviTable->hasColumn('ELEMENTID')) {
            $adminNaviTable->addColumn('ELEMENTID', (new StringType())->getName())
                ->setLength(32)
                ->setFixed(true)
                ->setNotnull(true);
        }
        if (!$adminNaviTable->hasColumn('ROLEID')) {
            $adminNaviTable->addColumn('ROLEID', (new StringType())->getName())
                ->setLength(32)
                ->setFixed(true)
                ->setNotnull(true);
        }
        if (!$adminNaviTable->hasColumn('OXTIMESTAMP')) {
            $adminNaviTable->addColumn('OXTIMESTAMP', (new DateTimeType())->getName())
                ->setNotnull(true)
                ->setDefault('CURRENT_TIMESTAMP');
        }

        $adminNaviTable->hasPrimaryKey() ?: $adminNaviTable->setPrimaryKey(['OXID', 'OXID']);

        //        if ($adminNaviTable->hasIndex('SHOPNAVIGATION') === false) {
        //            $adminNaviTable->addIndex(['SHOPID', 'NAVIGATIONID'], 'SHOPNAVIGATION');
        //        }
    }
    
    public function object2roleTable(Schema $schema)
    {
        $object2RoleTable = $schema->hasTable('o3object2role') ?
            $schema->getTable('o3object2role') :
            $schema->createTable('o3object2role');

        if (!$object2RoleTable->hasColumn('OXID')) {
            $object2RoleTable->addColumn('OXID', (new StringType())->getName())
                           ->setLength(32)
                           ->setFixed(true)
                           ->setNotnull(true);
        }

        if (!$object2RoleTable->hasColumn('OXSHOPID')) {
            $object2RoleTable->addColumn('OXSHOPID', (new StringType())->getName())
                             ->setLength(32)
                             ->setFixed(true)
                             ->setNotnull(true);
        }
        if (!$object2RoleTable->hasColumn('OBJECTID')) {
            $object2RoleTable->addColumn('OBJECTID', (new StringType())->getName())
                           ->setLength(32)
                           ->setFixed(true)
                           ->setNotnull(true);
        }
        if (!$object2RoleTable->hasColumn('ROLEID')) {
            $object2RoleTable->addColumn('ROLEID', (new StringType())->getName())
                           ->setLength(32)
                           ->setFixed(true)
                           ->setNotnull(true);
        }
        if (!$object2RoleTable->hasColumn('OXTIMESTAMP')) {
            $object2RoleTable->addColumn('OXTIMESTAMP', (new DateTimeType())->getName())
                           ->setNotnull(true)
                           ->setDefault('CURRENT_TIMESTAMP');
        }

        $object2RoleTable->hasPrimaryKey() ?: $object2RoleTable->setPrimaryKey(['OXID', 'OXID']);

        //        if ($adminNaviTable->hasIndex('SHOPNAVIGATION') === false) {
        //            $adminNaviTable->addIndex(['SHOPID', 'NAVIGATIONID'], 'SHOPNAVIGATION');
        //        }
    }
}
