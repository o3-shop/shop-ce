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
use Doctrine\Migrations\AbstractMigration;

final class Version20231219085936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'change admin navigation roles tables collation';
    }

    public function up(Schema $schema): void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $this->rightsRolesTable($schema);
        $this->rightsRolesElementsTable($schema);
        $this->object2roleTable($schema);
    }

    public function down(Schema $schema): void
    {
    }

    public function rightsRolesTable(Schema $schema): void
    {
        $this->addSql('ALTER TABLE o3rightsroles CONVERT TO CHARACTER SET LATIN1 COLLATE latin1_general_ci');
        $this->addSql('ALTER TABLE o3rightsroles MODIFY OXID CHAR(32) CHARACTER SET LATIN1 COLLATE latin1_general_ci');
    }

    public function rightsRolesElementsTable(Schema $schema): void
    {
        $this->addSql('ALTER TABLE o3rightsroleselements CONVERT TO CHARACTER SET LATIN1 COLLATE latin1_general_ci');
        $this->addSql('ALTER TABLE o3rightsroleselements MODIFY OXID CHAR(32) CHARACTER SET LATIN1 COLLATE latin1_general_ci');
    }

    public function object2roleTable(Schema $schema)
    {
        $this->addSql('ALTER TABLE o3object2role CONVERT TO CHARACTER SET LATIN1 COLLATE latin1_general_ci');
        $this->addSql('ALTER TABLE o3object2role MODIFY OXID CHAR(32) CHARACTER SET LATIN1 COLLATE latin1_general_ci');
    }
}
