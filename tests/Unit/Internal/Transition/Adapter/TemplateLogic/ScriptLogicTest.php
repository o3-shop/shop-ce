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

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\ScriptLogic;
use PHPUnit\Framework\TestCase;

/**
 * Class ScriptLogicTest
 */
class ScriptLogicTest extends TestCase
{

    /** @var Config */
    private $config;

    /** @var int */
    private $oldIDebug;

    /** @var ScriptLogic */
    private $scriptLogic;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        $this->config = Registry::getConfig();
        $this->oldIDebug = $this->config->getConfigParam("iDebug");
        $this->config->setConfigParam("iDebug", -1);

        $this->scriptLogic = new ScriptLogic();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        $this->config->setConfigParam("iDebug", $this->oldIDebug);
    }

    public function testIncludeFileNotExists(): void
    {
        $this->expectWarning();
        $this->scriptLogic->include('somescript.js');
    }

    public function testIncludeFileExists(): void
    {
        $includes = $this->config->getGlobalParameter('includes');

        $this->scriptLogic->include('http://someurl/src/js/libs/jquery.min.js', 3);
        $this->assertArrayHasKey(3, $this->config->getGlobalParameter('includes'));
        $this->assertTrue(in_array('http://someurl/src/js/libs/jquery.min.js', $this->config->getGlobalParameter('includes')[3]));

        $this->config->setGlobalParameter('includes', $includes);
    }

    public function testAddNotDynamic(): void
    {
        $scripts = $this->config->getGlobalParameter('scripts');

        $this->scriptLogic->add('oxidadd');
        $this->assertTrue(in_array('oxidadd', $this->config->getGlobalParameter('scripts')));

        $this->config->setGlobalParameter('scripts', $scripts);
    }

    public function testAddDynamic(): void
    {
        $scripts = $this->config->getGlobalParameter('scripts_dynamic');

        $this->scriptLogic->add('oxidadddynamic', true);
        $this->assertTrue(in_array('oxidadddynamic', $this->config->getGlobalParameter('scripts_dynamic')));

        $this->config->setGlobalParameter('scripts_dynamic', $scripts);
    }

    /**
     * @param string $script
     * @param string $output
     *
     * @dataProvider addWidgetProvider
     */
    public function testRenderAddWidget(string $script, string $output): void
    {
        $scripts = $this->config->getGlobalParameter('scripts');

        $output = "<script type='text/javascript'>window.addEventListener('load', function() { WidgetsHandler.registerFunction('$output', 'somewidget'); }, false )</script>";

        $this->scriptLogic->add($script);
        $this->assertEquals($output, $this->scriptLogic->render('somewidget', true));

        $this->config->setGlobalParameter('scripts', $scripts);
    }

    /**
     * @return array
     */
    public function addWidgetProvider(): array
    {
        return [
            ['oxidadd', 'oxidadd'],
            ['"oxidadd"', '"oxidadd"'],
            ["'oxidadd'", "\\'oxidadd\\'"],
            ["oxid\r\nadd", 'oxid\nadd'],
            ["oxid\nadd", 'oxid\nadd'],
        ];
    }

    public function testRenderIncludeWidget(): void
    {
        $includes = $this->config->getGlobalParameter('includes');

        $this->scriptLogic->include('http://someurl/src/js/libs/jquery.min.js');

        $output = <<<HTML
<script type='text/javascript'>
    window.addEventListener('load', function() {
        WidgetsHandler.registerFile('http://someurl/src/js/libs/jquery.min.js', 'somewidget');
    }, false)
</script>
HTML;

        $this->assertEquals($output, $this->scriptLogic->render('somewidget', true));

        $this->config->setGlobalParameter('includes', $includes);
    }
}
