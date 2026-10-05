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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Setup;

use OxidEsales\EshopCommunity\Setup\Core;
use OxidEsales\EshopCommunity\Setup\Database;
use OxidEsales\EshopCommunity\Setup\Language;

require_once getShopBasePath() . '/Setup/functions.php';

/**
 * Covers Setup/Database paths the existing DatabaseTest doesn't reach:
 * parseQuery's comment/quote/multiple-statement branches, connectDb error
 * conversion, testCreateView happy + each failure branch.
 */
class DatabaseCoverageTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, []);
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, []);
        parent::tearDown();
    }

    private function injectLanguageMock(): void
    {
        $language = $this->getMockBuilder(Language::class)
            ->onlyMethods(['getText'])
            ->getMock();
        $language->method('getText')->willReturnArgument(0);

        $reflection = new \ReflectionProperty(Core::class, '_aInstances');
        $reflection->setAccessible(true);
        $reflection->setValue(null, [Language::class => $language]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Database::parseQuery
     */
    public function testParseQuerySplitsOnSemicolonOutsideQuotes(): void
    {
        $database = new Database();
        $sql = "SELECT 1; SELECT 'a;b' FROM t;";
        $statements = $database->parseQuery($sql);

        $this->assertCount(2, $statements);
        $this->assertSame('SELECT 1;', $statements[0]);
        $this->assertSame("SELECT 'a;b' FROM t;", $statements[1]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Database::parseQuery
     */
    public function testParseQueryStripsHashCommentsToEndOfLine(): void
    {
        $database = new Database();
        $sql = "SELECT 1; # this is a comment that should be stripped\nSELECT 2;";
        $statements = $database->parseQuery($sql);

        $this->assertCount(2, $statements);
        $this->assertSame('SELECT 1;', $statements[0]);
        $this->assertSame('SELECT 2;', $statements[1]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Database::parseQuery
     */
    public function testParseQueryStripsDoubleDashCommentLines(): void
    {
        $database = new Database();
        $sql = "-- this whole line is a comment\nSELECT 3;";
        $statements = $database->parseQuery($sql);

        $this->assertSame(['SELECT 3;'], $statements);
    }

    /**
     * Regression test for issue #136. parseQuery used to recognise `--`
     * comments only at column 0; an indented `--` comment inside an
     * INSERT VALUES list survived the line-join and, on a single-line
     * MySQL parse, swallowed the rest of the statement. Result: the
     * whole o3-theme settings block in initial_data.sql was silently
     * dropped at install time.
     *
     * @covers \OxidEsales\EshopCommunity\Setup\Database::parseQuery
     */
    public function testParseQueryStripsDoubleDashCommentEvenWithLeadingWhitespace(): void
    {
        $database = new Database();
        $sql = "INSERT INTO t VALUES (1),\n"
            . "       -- skip me, leading-whitespace comment\n"
            . '       (2);';
        $statements = $database->parseQuery($sql);

        $this->assertCount(1, $statements);
        $this->assertStringNotContainsString('skip me', $statements[0]);
        // The two values rows must still concatenate cleanly into one INSERT.
        $this->assertStringContainsString('VALUES (1),', $statements[0]);
        $this->assertStringContainsString('(2);', $statements[0]);
    }

    /**
     * Per the MySQL spec, `--` only opens a comment when followed by
     * whitespace (or EOL). A bare `--` between values must be left intact
     * — if a future row ever needed `'--something'` as a literal value,
     * we don't want parseQuery eating it.
     *
     * @covers \OxidEsales\EshopCommunity\Setup\Database::parseQuery
     */
    public function testParseQueryDoesNotTreatDoubleDashWithoutWhitespaceAsComment(): void
    {
        $database = new Database();
        $sql = "SELECT '--keep' FROM t;";
        $statements = $database->parseQuery($sql);

        $this->assertSame(["SELECT '--keep' FROM t;"], $statements);
    }

    /**
     * `#` and `--` inside quoted strings must not trigger comment mode.
     *
     * @covers \OxidEsales\EshopCommunity\Setup\Database::parseQuery
     */
    public function testParseQueryKeepsHashAndDoubleDashInsideQuotes(): void
    {
        $database = new Database();
        $sql = "INSERT INTO t VALUES ('#ABC123', '-- not a comment');";
        $statements = $database->parseQuery($sql);

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('#ABC123', $statements[0]);
        $this->assertStringContainsString('-- not a comment', $statements[0]);
    }

    /**
     * A `;` inside a comment must NOT terminate the surrounding statement.
     * Companion to issue #136 — the same fix discovered that the
     * end-of-statement check used to ignore comment mode entirely, so a
     * `;` inside a `-- …` comment line silently chopped the next INSERT
     * row off the previous one.
     *
     * @covers \OxidEsales\EshopCommunity\Setup\Database::parseQuery
     */
    public function testParseQueryIgnoresSemicolonInsideComment(): void
    {
        $database = new Database();
        $sql = "INSERT INTO t VALUES (1),\n"
            . "-- explanatory note; with embedded semicolon\n"
            . "       (2);\n"
            . 'INSERT INTO t VALUES (3);';
        $statements = $database->parseQuery($sql);

        $this->assertCount(2, $statements);
        $this->assertStringContainsString('VALUES (1),', $statements[0]);
        $this->assertStringContainsString('(2);', $statements[0]);
        $this->assertStringContainsString('VALUES (3);', $statements[1]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Database::parseQuery
     */
    public function testParseQueryReturnsEmptyArrayForEmptyOrCommentOnlyInput(): void
    {
        $database = new Database();
        $this->assertSame([], $database->parseQuery(''));
        $this->assertSame([], $database->parseQuery("# only a comment\n"));
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Database::parseQuery
     */
    public function testParseQueryHandlesEscapedSingleQuotes(): void
    {
        $database = new Database();
        // The escaped quote shouldn't toggle quote-mode off, so the semicolon
        // inside the string stays inside its statement.
        $sql = "INSERT INTO t VALUES ('it\\'s here; also ;');";
        $statements = $database->parseQuery($sql);

        $this->assertCount(1, $statements);
        $this->assertSame("INSERT INTO t VALUES ('it\\'s here; also ;');", $statements[0]);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Database::connectDb
     */
    public function testConnectDbWrapsPdoExceptionWithLanguageText(): void
    {
        $this->injectLanguageMock();

        $database = $this->getMockBuilder(Database::class)
            ->onlyMethods([])
            ->getMock();

        // Inject a PDO mock that throws on exec.
        $pdo = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['exec'])
            ->getMock();
        $pdo->method('exec')->willThrowException(new \PDOException('connection refused'));

        $reflection = new \ReflectionProperty(Database::class, '_oConn');
        $reflection->setAccessible(true);
        $reflection->setValue($database, $pdo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ERROR_COULD_NOT_CREATE_DB');
        $this->expectExceptionCode(Database::ERROR_COULD_NOT_CREATE_DB);
        $database->connectDb('o3shop');
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Database::connectDb
     */
    public function testConnectDbSucceedsWhenExecRuns(): void
    {
        $database = new Database();

        $pdo = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['exec'])
            ->getMock();
        $pdo->expects($this->once())->method('exec')->with('USE `myshop`');

        $reflection = new \ReflectionProperty(Database::class, '_oConn');
        $reflection->setAccessible(true);
        $reflection->setValue($database, $pdo);

        $database->connectDb('myshop');
        $this->assertTrue(true);
    }

    /**
     * @covers \OxidEsales\EshopCommunity\Setup\Database::testCreateView
     */
    public function testTestCreateViewWrapsExceptionFromCreateAttempt(): void
    {
        $this->injectLanguageMock();

        $database = new Database();

        $pdo = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['exec', 'query'])
            ->getMock();
        $pdo->method('exec')->willThrowException(new \PDOException('CREATE VIEW denied'));

        $reflection = new \ReflectionProperty(Database::class, '_oConn');
        $reflection->setAccessible(true);
        $reflection->setValue($database, $pdo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ERROR_VIEWS_CANT_CREATE');
        $database->testCreateView();
    }
}
