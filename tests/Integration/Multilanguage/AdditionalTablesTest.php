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
namespace OxidEsales\EshopCommunity\Tests\Integration\Multilanguage;

use oxDb;

/**
 * Class AdditionalTablesTest
 *
 * @group slow-tests
 *
 * @package OxidEsales\EshopCommunity\Tests\Integration\Multilanguage
 */
class AdditionalTablesTest extends MultilanguageTestCase
{
    /**
     * Additional multilanguage tables.
     *
     * @var array
     */
    protected $additionalTables = array();

    /**
     * Fixture setUp.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Fixture tearDown.
     */
    protected function tearDown(): void
    {
        $this->setConfigParam('aMultiLangTables', array());
        $this->updateViews();

        foreach ($this->additionalTables as $name) {
            $this->removeAdditionalTables($name);
        }
        $this->removeAdditionalTables('set1');

        parent::tearDown();
    }

    /**
     * Assert that set tables are automatically created for additional multilanguage table
     * in case we add first the table and then create the languages.
     */
    public function testCreateLanguagesAfterAdditionalTable()
    {
        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        //add nine more languages
        $this->prepare(9);

        $tableSchemaQuery = "SELECT TABLE_NAME, TABLE_COLLATION  FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_NAME LIKE 'addtest_set1'";
        $result = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getRow($tableSchemaQuery);

        self::assertEquals(
            [
                'TABLE_NAME'      => "addtest_set1",
                'TABLE_COLLATION' => "latin1_general_ci"
            ],
            $result
        );

        $charset_query = "SELECT character_set_name FROM information_schema.`COLUMNS` 
                            WHERE table_name = 'addtest_set1'
                              AND column_name = 'TITLE_8';";

        $result = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getOne($charset_query);

        self::assertEquals('latin1', $result);
    }

    /**
     * Assert that set tables are automatically created for additional multilanguage table
     * in case first create the languages, then set the table in config.inc.php variable 'aMultiLangTables'
     * and call updateViews. Without *_set1 tables, view creating throws and exception.
     */
    public function testCreateAdditionalTableAfterCreatingLanguages()
    {
        //add nine more languages
        $this->prepare(9);

        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        $this->updateViews();

        $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_NAME LIKE 'addtest_set1'";
        $result = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getOne($sql);
        $this->assertEquals('addtest_set1', $result);
    }

    /**
     * Verify that the expected data turned up in the language views
     */
    public function testViewContentsCreateLanguagesAfterAdditionalTable()
    {
        $oxid = '_test101';

        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        //add nine more languages
        $languageId = $this->prepare(9);

        //insert testdata for language id 0
        $sql = "INSERT INTO addtest (OXID, TITLE) VALUES ('" . $oxid . "', 'some default title')";
        oxDb::getDb()->execute($sql);

        //insert testdata for last added language id in set1 table
        $sql = "INSERT INTO addtest_set1 (OXID, TITLE_" . $languageId . ") VALUES ('" . $oxid . "', 'some additional title')";
        oxDb::getDb()->execute($sql);

        $sql = "SELECT TITLE FROM " . getViewName('addtest', $languageId) . " WHERE OXID = '" . $oxid . "'";
        $this->assertSame('some additional title', oxDb::getDb()->getOne($sql));

        $sql = "SELECT TITLE FROM " . getViewName('addtest', 0) . " WHERE OXID = '" . $oxid . "'";
        $this->assertSame('some default title', oxDb::getDb()->getOne($sql));
    }

    /**
     * Verify that the expected data turned up in the language views
     */
    public function testViewContentsCreateAdditionalTableAfterCreatingLanguages()
    {
        //add nine more languages
        $languageId = $this->prepare(9);
        $oxid = '_test101';

        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        $this->updateViews();

        //insert testdata for language id 0
        $sql = "INSERT INTO addtest (OXID, TITLE) VALUES ('" . $oxid . "', 'some default title')";
        oxDb::getDb()->execute($sql);

        //insert testdata for last added language id in set1 table
        $sql = "INSERT INTO addtest_set1 (OXID, TITLE_" . $languageId . ") VALUES ('" . $oxid . "', 'some additional title')";
        oxDb::getDb()->execute($sql);

        $sql = "SELECT TITLE FROM " . getViewName('addtest', $languageId) . " WHERE OXID = '" . $oxid . "'";
        $this->assertSame('some additional title', oxDb::getDb()->getOne($sql));

        $sql = "SELECT TITLE FROM " . getViewName('addtest', 0) . " WHERE OXID = '" . $oxid . "'";
        $this->assertSame('some default title', oxDb::getDb()->getOne($sql));
    }

    /**
     * Create additional multilanguage table.
     *
     * @param string $name
     */
    protected function createTable($name = 'addtest')
    {
        $sql = "CREATE TABLE `" . $name . "` (" .
                "`OXID` char(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Item id'," .
                "`TITLE` varchar(128) NOT NULL DEFAULT '' COMMENT 'Title (multilanguage)'," .
                "`TITLE_1` varchar(128) NOT NULL DEFAULT ''," .
                "`TITLE_2` varchar(128) NOT NULL DEFAULT ''," .
                "`TITLE_3` varchar(128) NOT NULL DEFAULT ''," .
                "`TITLE_4` varchar(128) NOT NULL DEFAULT ''," .
                "`TITLE_5` varchar(128) NOT NULL DEFAULT ''," .
                "`TITLE_6` varchar(128) NOT NULL DEFAULT ''," .
                "`TITLE_7` varchar(128) NOT NULL DEFAULT ''," .
                "PRIMARY KEY (`OXID`)" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='for testing'";

        oxDb::getDb()->execute($sql);
        oxDb::getInstance()->getTableDescription($name); //throws exception if table does not exist
        $this->additionalTables[] = $name;
    }

    /**
     * Remove additional multilanguage tables and related.
     *
     * @return null
     */
    protected function removeAdditionalTables($name)
    {
        $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_NAME LIKE '%" . $name . "%'";
        $result = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sql);
        foreach ($result as $sub) {
            oxDb::getDb()->execute("DROP TABLE IF EXISTS `" . $sub['TABLE_NAME'] . "`");
        }
    }
}
