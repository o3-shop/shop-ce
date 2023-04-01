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

use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\Migrations\AbstractMigration;
use OxidEsales\EshopCommunity\Core\Config;

final class Version20230322213324 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf($this->connection->getDatabasePlatform() instanceof MySQL80Platform, 'Config values can\'t decoded on MySQL 8');

        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $table = $schema->getTable('oxconfig');
        $column = $table->getColumn('oxvarvalue');

        $this->skipIf(!$column->getType() instanceof BlobType, 'Config values are already decoded');

        $this->addSql('ALTER TABLE oxconfig ADD COLUMN `OXVARVALUE_UNENC` text;');
        $this->addSql("UPDATE oxconfig SET `OXVARVALUE_UNENC` = DECODE(OXVARVALUE, '".Config::DEFAULT_CONFIG_KEY."') WHERE 1;");
        $this->addSql('ALTER TABLE oxconfig MODIFY COLUMN `OXVARVALUE` text;');
        $this->addSql("UPDATE oxconfig SET `OXVARVALUE` = `OXVARVALUE_UNENC` WHERE 1;");
        $this->addSql('ALTER TABLE oxconfig DROP COLUMN `OXVARVALUE_UNENC`;');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf($this->connection->getDatabasePlatform() instanceof MySQL80Platform, 'Config values can\'t encoded on MySQL 8');

        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $table = $schema->getTable('oxconfig');
        $column = $table->getColumn('oxvarvalue');

        $this->skipIf($column->getType() instanceof BlobType, 'Config values are already encoded');

        $this->addSql( 'ALTER TABLE oxconfig ADD COLUMN `OXVARVALUE_ENC` text;' );
        $this->addSql( "UPDATE oxconfig SET `OXVARVALUE_ENC` = ENCODE(OXVARVALUE, '" . Config::DEFAULT_CONFIG_KEY . "') WHERE 1;" );
        $this->addSql( 'ALTER TABLE oxconfig MODIFY COLUMN `OXVARVALUE` mediumblob;' );
        $this->addSql( "UPDATE oxconfig SET `OXVARVALUE` = `OXVARVALUE_ENC` WHERE 1;" );
        $this->addSql( 'ALTER TABLE oxconfig DROP COLUMN `OXVARVALUE_ENC`;' );
    }

    public function isTransactional(): bool
    {
        return true;
    }
}
