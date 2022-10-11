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

use \oxField;
use \oxDb;

class LinksTest extends \OxidTestCase
{
    private $_oxLinks;

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->_oxLinks = oxNew("oxlinks", getViewName('oxlinks'));
        $this->_oxLinks->setId('testlink');
        $this->_oxLinks->oxlinks__oxurl = new oxField('http://www.oxid-esales.com', oxField::T_RAW);
        $this->_oxLinks->Save();
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $sDelete = "delete from oxlinks where oxid='" . $this->_oxLinks->getId() . "'";
        oxDb::getDb()->Execute($sDelete);
        parent::tearDown();
    }

    /**
     * tests save and load function
     */
    public function testLoad()
    {
        $oLink = oxNew("oxlinks", getViewName('oxlinks'));
        $oLink->load($this->_oxLinks->getId());
        $this->assertEquals('http://www.oxid-esales.com', $oLink->oxlinks__oxurl->value);
    }

    /**
     * tests save function with special chars
     */
    public function testDescWithHtmlEntity()
    {
        $oLink = oxNew("oxlinks", getViewName('oxlinks'));
        $oLink->load($this->_oxLinks->getId());
        $oLink->oxlinks__oxurldesc = new oxField('Link&, &amp;, !@#$%^&*%$$&@\'.,;p"äüßö', oxField::T_RAW);
        $this->_oxLinks->Save();
        $this->assertEquals('Link&, &amp;, !@#$%^&*%$$&@\'.,;p"äüßö', $oLink->oxlinks__oxurldesc->value);
    }
}
