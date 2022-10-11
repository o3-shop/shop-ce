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

use OxidEsales\Eshop\Core\OnlineServerEmailBuilder;
use \oxRegistry;

/**
 * Class Unit_Core_OnlineServerEmailBuilderTest
 */
class OnlineServerEmailBuilderTest extends \OxidTestCase
{
    public function testBuildIfParametersWereSetCorrectly()
    {
        $sBody = '_testXML';
        $oExpirationEmailBuilder = oxNew(OnlineServerEmailBuilder::class);
        $oExpirationEmail = $oExpirationEmailBuilder->build($sBody);
        $aRecipient = $oExpirationEmail->getRecipient();

        $this->assertSame($sBody, $oExpirationEmail->getBody(), 'Email content is not as it should be.');
        $this->assertSame('olc@oxid-esales.com', $aRecipient[0][0], 'Recipient email address is wrong.');
        $this->assertSame(oxRegistry::getLang()->translateString('SUBJECT_UNABLE_TO_SEND_VIA_CURL', null, true), $oExpirationEmail->getSubject(), 'Subject is wrong.');
    }
}
