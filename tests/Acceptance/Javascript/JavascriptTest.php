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

namespace OxidEsales\EshopCommunity\Tests\Acceptance\Javascript;

use OxidEsales\EshopCommunity\Tests\Acceptance\JavascriptTestCase;

class JavascriptTest extends JavascriptTestCase
{
    public function setUpTestsSuite($sTestSuitePath)
    {
        if ($this->getTestConfig()->isSubShop()) {
            $this->markTestSkipped('No need to run javascript tests on subshop.');
        }
        parent::setUpTestsSuite($sTestSuitePath);
    }

    /**
     * Selenium test for all javascript qunit test
     *
     * @group javascript
     */
    public function testJavascript()
    {
        $this->open(shopURL . '/jstests/index.php?shopUrl=' . shopURL);

        $this->waitForItemAppear("//p[@id='qunit-testresult']");
        $result = $this->getText("//p[@id='qunit-testresult']/span[3]");

        $this->assertEquals($result, '0');
    }
}
