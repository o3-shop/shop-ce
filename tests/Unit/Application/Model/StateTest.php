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

class StateTest extends \OxidTestCase
{
    public function testInit()
    {
        $oState = oxNew('oxState');
        $oState->load('AB');
        $this->assertEquals('Alberta', $oState->oxstates__oxtitle->value);
    }

    /**
     * Tests state ID getter by provided code
     */
    public function testGetIdByCode()
    {
        $oState = oxNew('oxState');
        $this->assertEquals('MB', $oState->getIdByCode('MB', '8f241f11095649d18.02676059'));
    }

    /**
     * Data provider for testGetTitleById
     *
     * @return array
     */
    public function providerStateIDs()
    {
        $sMsgCorrect = 'State title is correct';
        $sMsgEmptyString = 'Empty string is returned';

        $iStateId = 'CA';
        $sStateId = 'AK';

        $sStateTitle = 'Kalifornien';
        $sAltStateTitle = 'Alaska';

        $sWrongId1 = null;
        $sWrongId2 = '';
        $sWrongId3 = 's4';

        $sEmptyString = '';

        return array(
            /*     ID          expected         message         */
            array($iStateId, $sStateTitle, $sMsgCorrect),
            array($sStateId, $sAltStateTitle, $sMsgCorrect),
            array($sWrongId1, $sEmptyString, $sMsgEmptyString),
            array($sWrongId2, $sEmptyString, $sMsgEmptyString),
            array($sWrongId3, $sEmptyString, $sMsgEmptyString)
        );
    }

    /**
     * Testing getTitleById with various IDs passed
     *
     * @dataProvider providerStateIDs
     */
    public function testGetTitleById($sId, $sExpected, $sMsg)
    {
        $oState = oxNew('oxState');
        $this->assertEquals($sExpected, $oState->getTitleById($sId), $sMsg);
    }
}
