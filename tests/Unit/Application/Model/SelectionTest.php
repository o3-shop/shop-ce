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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

class SelectionTest extends \OxidTestCase
{
    /**
     * Testing constructor and setters
     *
     * @return null
     */
    public function testConstructorAndSetters()
    {
        $oSelection = oxNew('oxSelection', "test", "test", true, true);

        $this->assertEquals("test", $oSelection->getValue());
        $this->assertEquals("test", $oSelection->getName());
        $this->assertEquals("#", $oSelection->getLink());
        $this->assertTrue($oSelection->isActive());
        $this->assertTrue($oSelection->isDisabled());

        $oSelection->setActiveState(false);
        $oSelection->setDisabled(false);
        $this->assertFalse($oSelection->isActive());
        $this->assertFalse($oSelection->isDisabled());
    }
}
