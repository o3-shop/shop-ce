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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924111451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add OXNODELETE column to oxactions and oxcontents tables for deletion protection';
    }

    public function up(Schema $schema): void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $this->addSql("ALTER TABLE `oxactions` ADD COLUMN `OXNODELETE` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = must not be deleted, 0 = default' AFTER `OXSORT`");

        $this->addSql("ALTER TABLE `oxcontents` ADD COLUMN `OXNODELETE` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = must not be deleted, 0 = default' AFTER `OXISPLAIN`");
    }

    public function down(Schema $schema): void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $this->addSql('ALTER TABLE `oxactions` DROP COLUMN `OXNODELETE`');

        $this->addSql('ALTER TABLE `oxcontents` DROP COLUMN `OXNODELETE`');
    }
}
