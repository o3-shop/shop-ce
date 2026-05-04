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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Controller\Admin\ToolsList;

/**
 * DbMetaDataHandler stub: updateViews() returns a steered value so the
 * controller's public success flag can be inspected without touching
 * actual table metadata.
 */
class ToolsListTest_StubMetaData
{
    public static bool $updateReturns = true;
    public static int $callCount = 0;

    public function updateViews()
    {
        self::$callCount++;
        return self::$updateReturns;
    }
}

class ToolsListTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ToolsListTest_StubMetaData::$updateReturns = true;
        ToolsListTest_StubMetaData::$callCount = 0;
    }

    public function testTemplateNameIsToolsList(): void
    {
        $controller = $this->getProxyClass(ToolsList::class);
        $this->assertSame('tools_list.tpl', $controller->getNonPublicVar('_sThisTemplate'));
    }

    public function testUpdateViewsRunsAndStoresResultForMalladmin(): void
    {
        \oxTestModules::addModuleObject(DbMetaDataHandler::class, new ToolsListTest_StubMetaData());
        Registry::getSession()->setVariable('malladmin', true);

        $controller = $this->getProxyClass(ToolsList::class);
        $controller->updateViews();

        $this->assertSame(1, ToolsListTest_StubMetaData::$callCount);
        $viewData = $controller->getNonPublicVar('_aViewData');
        $this->assertTrue($viewData['blViewSuccess'] ?? null);
    }

    public function testUpdateViewsIsNoopForNonMalladmin(): void
    {
        \oxTestModules::addModuleObject(DbMetaDataHandler::class, new ToolsListTest_StubMetaData());
        Registry::getSession()->setVariable('malladmin', false);

        $controller = $this->getProxyClass(ToolsList::class);
        $controller->updateViews();

        $this->assertSame(0, ToolsListTest_StubMetaData::$callCount);
        $viewData = $controller->getNonPublicVar('_aViewData');
        $this->assertArrayNotHasKey('blViewSuccess', $viewData);
    }

    public function testPrepareSqlSplitsOnSemicolon(): void
    {
        $controller = $this->getProxyClass(ToolsList::class);
        $controller->aSQLs = [];
        $sql = 'select 1; select 2; select 3';
        $this->assertTrue($controller->UNITprepareSQL($sql, strlen($sql)));
        $this->assertCount(3, $controller->aSQLs);
        $this->assertSame('select 1', $controller->aSQLs[0]);
        $this->assertSame('select 2', $controller->aSQLs[1]);
        $this->assertSame('select 3', $controller->aSQLs[2]);
    }

    public function testPrepareSqlPreservesQuotedSemicolons(): void
    {
        $controller = $this->getProxyClass(ToolsList::class);
        $controller->aSQLs = [];
        $sql = "select 'one;two'";
        $this->assertTrue($controller->UNITprepareSQL($sql, strlen($sql)));
        $this->assertCount(1, $controller->aSQLs);
        $this->assertSame("select 'one;two'", $controller->aSQLs[0]);
    }

    public function testPrepareSqlStripsMysqldumpStyleComments(): void
    {
        $controller = $this->getProxyClass(ToolsList::class);
        $controller->aSQLs = [];
        $sql = "-- a comment\nselect 1";
        $this->assertTrue($controller->UNITprepareSQL($sql, strlen($sql)));
        $this->assertCount(1, $controller->aSQLs);
        $this->assertStringContainsString('select 1', $controller->aSQLs[0]);
    }

    public function testPrepareSqlStripsHashStyleInlineComments(): void
    {
        $controller = $this->getProxyClass(ToolsList::class);
        $controller->aSQLs = [];
        $sql = "select 1 # tail comment\nselect 2";
        $controller->UNITprepareSQL($sql, strlen($sql));
        // Should produce some queries; the exact split is parser-defined.
        $this->assertNotEmpty($controller->aSQLs);
        $joined = implode('|', $controller->aSQLs);
        $this->assertStringNotContainsString('tail comment', $joined);
    }

    public function testPrepareSqlPreservesBacktickIdentifierWithSemicolon(): void
    {
        $controller = $this->getProxyClass(ToolsList::class);
        $controller->aSQLs = [];
        $sql = 'select `foo;bar` from t';
        $this->assertTrue($controller->UNITprepareSQL($sql, strlen($sql)));
        // The backtick literal must keep its embedded semicolon intact.
        $joined = implode('|', $controller->aSQLs);
        $this->assertStringContainsString('foo;bar', $joined);
    }

    public function testPerformsqlIsBlockedForNonMalladmin(): void
    {
        // A non-malladmin user must not have any of the work happen —
        // _aViewData should remain untouched (no aQueries key set).
        \oxTestModules::addFunction(
            'oxuser',
            'loadAdminUser',
            '{ $this->oxuser__oxrights = new \\OxidEsales\\Eshop\\Core\\Field( "justadmin" ); }'
        );

        $this->setRequestParameter('updatesql', 'SELECT 1');

        $controller = $this->getProxyClass(ToolsList::class);
        $controller->performsql();

        $viewData = $controller->getNonPublicVar('_aViewData');
        $this->assertArrayNotHasKey('aQueries', $viewData);
    }

    public function testPerformsqlExecutesValidSqlForMalladmin(): void
    {
        \oxTestModules::addFunction(
            'oxuser',
            'loadAdminUser',
            '{ $this->oxuser__oxrights = new \\OxidEsales\\Eshop\\Core\\Field( "malladmin" ); }'
        );

        // DO 1 is a no-op; it doesn't open a result-set so the test won't
        // leave dangling cursors that confuse DatabaseRestorer in tearDown.
        $this->setRequestParameter('updatesql', 'DO 1');

        $controller = $this->getProxyClass(ToolsList::class);
        $controller->performsql();

        $viewData = $controller->getNonPublicVar('_aViewData');
        $this->assertArrayHasKey('aQueries', $viewData);
        $this->assertCount(1, $viewData['aQueries']);
        $this->assertStringContainsString('DO 1', $viewData['aQueries'][0]);
        $this->assertArrayHasKey('aErrorMessages', $viewData);
        // No error → the corresponding slot must be null.
        $this->assertArrayHasKey(0, $viewData['aErrorMessages']);
        $this->assertNull($viewData['aErrorMessages'][0]);
    }

    public function testPerformsqlSplitsAndStopsOnFirstError(): void
    {
        \oxTestModules::addFunction(
            'oxuser',
            'loadAdminUser',
            '{ $this->oxuser__oxrights = new \\OxidEsales\\Eshop\\Core\\Field( "malladmin" ); }'
        );

        // First query OK, second one references a table that doesn't exist
        // → execute() throws, controller must record the error and stop
        // before reaching the third query.
        $this->setRequestParameter(
            'updatesql',
            'DO 1; UPDATE _no_such_table_priv_test_1234 SET x=1; DO 2'
        );

        $controller = $this->getProxyClass(ToolsList::class);
        $controller->performsql();

        $viewData = $controller->getNonPublicVar('_aViewData');
        $this->assertArrayHasKey('aQueries', $viewData);
        $this->assertCount(2, $viewData['aQueries']);
        $this->assertNotNull($viewData['aErrorMessages'][1]);
        $this->assertStringContainsString('_no_such_table_priv_test_1234', $viewData['aErrorMessages'][1]);
    }

    public function testPerformsqlTruncatesEchoedQueryAtTwoHundredChars(): void
    {
        \oxTestModules::addFunction(
            'oxuser',
            'loadAdminUser',
            '{ $this->oxuser__oxrights = new \\OxidEsales\\Eshop\\Core\\Field( "malladmin" ); }'
        );

        // Long arithmetic expression pushes the echoed (htmlentities-encoded)
        // query past the 200-char display limit. Avoids string literals so
        // getRequestEscapedParameter() doesn't escape the input out from
        // under us before performsql() sees it.
        $long = 'DO ' . implode('+', array_fill(0, 200, '1'));
        $this->setRequestParameter('updatesql', $long);

        $controller = $this->getProxyClass(ToolsList::class);
        $controller->performsql();

        $viewData = $controller->getNonPublicVar('_aViewData');
        $this->assertArrayHasKey('aQueries', $viewData);
        $this->assertNotEmpty($viewData['aQueries']);
        $this->assertStringEndsWith('...', $viewData['aQueries'][0]);
    }
}
