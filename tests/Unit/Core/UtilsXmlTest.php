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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use \DOMDocument;

class UtilsXmlTest extends \OxidTestCase
{
    public function xmlProviderNoDomDocument()
    {
        return array(
            array('<?xml version="1.0" encoding="utf-8"?><message>ACK</message>', true),
            array('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><message>ACK</message>', false),
        );
    }

    public function xmlProviderWithDomDocument()
    {
        $oDom = new DOMDocument();

        return array(
            array('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html>ACK</html>', $oDom, false),
            array('<?xml version="1.0" encoding="utf-8"?><message>ACK</message>', $oDom, true),
        );
    }

    /**
     * Check if loadXml returns valid XML or response
     *
     * @dataProvider xmlProviderNoDomDocument
     */
    public function testLoadXmlNoDocument($sXml, $blResult)
    {
        $oUtilsXml = oxNew('oxUtilsXml');
        $this->assertEquals($blResult, $oUtilsXml->loadXml($sXml) != false);
    }

    /**
     * Check for valid response when passing normal DOM document
     */
    public function testLoadXmlWithDomDocumentInvalidXml()
    {
        $oUtilsXml = oxNew('oxUtilsXml');
        $oDom = new DOMDocument();
        $sInValidXml = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><message>ACK</message>';
        $this->assertEquals(false, $oUtilsXml->loadXml($sInValidXml, $oDom) != false);
    }

    /**
     * Check for invalid response when passing normal DOM document
     */
    public function testLoadXmlWithDomDocumentValidXml()
    {
        $oUtilsXml = oxNew('oxUtilsXml');
        $oDom = new DOMDocument();
        $sValidXml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><ocl><message>ACK</message></ocl>";
        $this->assertEquals(true, $oUtilsXml->loadXml($sValidXml, $oDom) != false);
    }
}
