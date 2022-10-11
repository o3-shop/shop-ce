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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\ToolsList;

/**
 * Tests for Tools_List class
 */
class ToolsListTest extends \OxidEsales\TestingLibrary\UnitTestCase
{
    /**
     * Temporary file name with path
     *
     * @var string
     */
    private $tempFile;

    /**
     * Clean up test case
     *
     * @return null
     */
    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    /**
     * Tools_List::Performsql() test case
     *
     * @return null
     */
    public function testPerformsql()
    {
        // testing..
        $this->getSession()->setVariable('auth', "oxdefaultadmin");
        $this->setRequestParameter("updatesql", 'select * from oxvoucher');

        $oView = oxNew('Tools_List');
        $oView->performsql();
        $this->assertTrue(isset($oView->aSQLs));
    }

    /**
     * ToolsList::performsql() test case
     */
    public function testDoNotProcessEmptySqlFile()
    {
        $this->getSession()->setVariable('auth', 'oxdefaultadmin');
        $tempFile = tempnam(sys_get_temp_dir() . '/test_temp_sql_file', 'test_');
        $this->tempFile = $tempFile;
        $_FILES['myfile']['name'] = [basename($tempFile)];
        $_FILES['myfile']['tmp_name'] = [$tempFile];

        $toolsList = oxNew(ToolsList::class);
        $toolsList->performsql();

        $this->assertFalse(isset($toolsList->aSQLs));
    }

    /**
     * Tools_List::PrepareSQL() test case
     *
     * @return null
     */
    public function testPrepareSQL()
    {
        // defining parameters
        $sSQL = 'select * from oxvoucher';
        $iSQLlen = '';

        // testing..
        $oView = oxNew('Tools_List');
        $this->assertTrue($oView->UNITprepareSQL($sSQL, $iSQLlen));
        $this->assertTrue(isset($oView->aSQLs));
    }

    /**
     * Tools_List::Render() test case
     *
     * @return null
     */
    public function testRender()
    {
        // testing..
        $oView = oxNew('Tools_List');
        $this->assertEquals('tools_list.tpl', $oView->render());
    }

    /**
     * Tools_List::updateViews() test case
     *
     * @return null
     */
    public function testUpdateViews()
    {
        $this->getSession()->setVariable('malladmin', true);

        $oView = oxNew('Tools_List');
        $oView->updateViews();

        // assert that updating was successful
        $aViewData = $oView->getViewData();
        $this->assertTrue($aViewData['blViewSuccess']);
    }
}
