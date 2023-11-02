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
use OxidEsales\EshopCommunity\Application\Model\RightsRolesElement;

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
        $rightsRolesTable = $schema->hasTable('o3rightsroles') ?
            $schema->getTable('o3rightsroles') :
            $schema->createTable('o3rightsroles');

        if (!$rightsRolesTable->hasColumn('OXID')) {
            $rightsRolesTable->addColumn('OXID', (new StringType())->getName())
                ->setLength(32)
                ->setFixed(true)
                ->setNotnull(true);
        }
        if (!$rightsRolesTable->hasColumn('OXSHOPID')) {
            $rightsRolesTable->addColumn('OXSHOPID', (new IntegerType())->getName())
                ->setLength(11)
                ->setNotnull(true);
        }
        if (!$rightsRolesTable->hasColumn('ACTIVE')) {
            $rightsRolesTable->addColumn('ACTIVE', (new IntegerType())->getName())
                ->setLength(1)
                ->setFixed(true)
                ->setDefault(1)
                ->setNotnull(true);
        }
        if (!$rightsRolesTable->hasColumn('TITLE')) {
            $rightsRolesTable->addColumn('TITLE', (new StringType())->getName())
                ->setLength(255)
                ->setFixed(false)
                ->setNotnull(true);
        }
        for ($lang = 1; $lang <= 3; $lang++) {
            if ( ! $rightsRolesTable->hasColumn( 'TITLE_'.$lang ) ) {
                $rightsRolesTable->addColumn( 'TITLE_'.$lang, ( new StringType() )->getName() )
                    ->setLength( 255 )
                    ->setFixed( false )
                    ->setNotnull( true );
            }
        }
        if (!$rightsRolesTable->hasColumn('OXTIMESTAMP')) {
            $rightsRolesTable->addColumn('OXTIMESTAMP', (new DateTimeType())->getName())
                ->setNotnull(true)
                ->setDefault('CURRENT_TIMESTAMP');
        }

        $rightsRolesTable->hasPrimaryKey() ?: $rightsRolesTable->setPrimaryKey(['OXID', 'OXID']);
    }

    public function rightsRolesElementsTable(Schema $schema): void
    {
        $rightsRolesElementsTable = $schema->hasTable('o3rightsroleselements') ?
            $schema->getTable('o3rightsroleselements') :
            $schema->createTable('o3rightsroleselements');

        if (!$rightsRolesElementsTable->hasColumn('OXID')) {
            $rightsRolesElementsTable->addColumn('OXID', (new StringType())->getName())
                ->setLength(32)
                ->setFixed(true)
                ->setNotnull(true);
        }

        if (!$rightsRolesElementsTable->hasColumn('ELEMENTID')) {
            $rightsRolesElementsTable->addColumn('ELEMENTID', (new StringType())->getName())
                ->setLength(32)
                ->setFixed(true)
                ->setNotnull(true);
        }
        if (!$rightsRolesElementsTable->hasColumn('OBJECTID')) {
            $rightsRolesElementsTable->addColumn('OBJECTID', (new StringType())->getName())
                ->setLength(32)
                ->setFixed(true)
                ->setNotnull(true)
                ->setComment('role or user id');
        }
        if (!$rightsRolesElementsTable->hasColumn('TYPE')) {
            $rightsRolesElementsTable->addColumn('TYPE', (new IntegerType())->getName())
                ->setLength(1)
                ->setFixed(true)
                ->setNotnull(true)
                ->setDefault(RightsRolesElement::TYPE_EDITABLE)
                ->setComment('right type: 0 = hidden, 1 = readonly, 2 = editable');
        }
        if (!$rightsRolesElementsTable->hasColumn('OXTIMESTAMP')) {
            $rightsRolesElementsTable->addColumn('OXTIMESTAMP', (new DateTimeType())->getName())
                ->setNotnull(true)
                ->setDefault('CURRENT_TIMESTAMP');
        }

        $rightsRolesElementsTable->hasPrimaryKey() ?: $rightsRolesElementsTable->setPrimaryKey(['OXID', 'OXID']);

        $rightsRolesElementsTable->hasIndex('OBJECT_IDX') ?:
            $rightsRolesElementsTable->addIndex(['OBJECTID'], 'OBJECT_IDX');

        $rightsRolesElementsTable->hasIndex('ROLETYPE_IDX') ?:
            $rightsRolesElementsTable->addIndex(['OBJECTID', 'TYPE'], 'ROLETYPE_IDX');
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

        $object2RoleTable->hasIndex('ROLEOBJECT_IDX') ?:
            $object2RoleTable->addUniqueIndex( [ 'ROLEID', 'OBJECTID' ], 'ROLEOBJECT_IDX' );
    }
}
