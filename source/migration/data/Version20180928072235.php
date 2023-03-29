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

namespace OxidEsales\EshopCommunity\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\ConfigFile;
use OxidEsales\Facts\Facts;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180928072235 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $configSettingName = 'includeProductReviewLinksInEmail';
        $configSettingType = 'bool';
        $configSettingValue = '1';

        $query = "INSERT INTO `oxconfig` 
                  (
                      `OXID`, 
                      `OXSHOPID`, 
                      `OXVARNAME`, 
                      `OXVARTYPE`, 
                      `OXVARVALUE`
                  )
                  SELECT  
                      REPLACE(UUID() , '-', '' ), 
                      `OXID`,
                      ?, 
                      ?, 
                      ?
                  FROM `oxshops`
                  WHERE NOT EXISTS (
                      SELECT `OXVARNAME` 
                      FROM `oxconfig`
                      WHERE `OXVARNAME` = ? 
                      AND `oxconfig`.OXSHOPID = `oxshops`.OXID 
                  )";

        $this->addSql(
            $query,
            [
                $configSettingName,
                $configSettingType,
                $configSettingValue,
                $configSettingName,
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }

    /**
     * @return string
     */
    private function getConfigEncryptionKey(): string
    {
        $facts = new Facts();
        $configFile = new ConfigFile($facts->getSourcePath() . '/config.inc.php');

        return $configFile->getVar('sConfigKey') ?? Config::DEFAULT_CONFIG_KEY;
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
