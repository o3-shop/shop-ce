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
namespace OxidEsales\EshopCommunity\Tests\Unit\core;

use \oxField;
use oxStr;

class EmailUtf8Test extends \OxidTestCase
{

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Testing email charset
     *
     * @return null
     */
    public function testGetCharset()
    {
        $oEmail = oxNew('oxEmail');
        $this->assertEquals("UTF-8", $oEmail->getCharset());
    }

    /**
     * Testing email charset
     *
     * @return null
     */
    public function testGetCurrency()
    {
        $oEmail = oxNew('oxEmail');
        $this->assertEquals("€", $oEmail->getCurrency()->sign);
    }

    /**
     * Test sending ordering mail to user
     */
    public function testSendOrderEmailToUser()
    {
        $oPrice = $this->getMock('oxprice');
        $oPrice->expects($this->any())->method('getPrice')->will($this->returnValue(256));
        $oPrice->expects($this->any())->method('getBruttoPrice')->will($this->returnValue(8));

        $oBasketItem = $this->getMock(\OxidEsales\Eshop\Application\Model\BasketItem::class, array('getPrice', 'getUnitPrice', 'getRegularUnitPrice', 'getTitle'));
        $oBasketItem->expects($this->any())->method('getPrice')->will($this->returnValue($oPrice));
        $oBasketItem->expects($this->any())->method('getUnitPrice')->will($this->returnValue($oPrice));
        $oBasketItem->expects($this->any())->method('getRegularUnitPrice')->will($this->returnValue($oPrice));
        $oBasketItem->expects($this->any())->method('getTitle')->will($this->returnValue("testarticle"));

        // insert test article
        $oArticle = oxNew("oxArticle");
        $oArticle->setId('_testArticleId');
        $oArticle->setId('_testArticleId');
        $oArticle->oxarticles__oxtitle = new oxField();

        $aBasketContents[] = $oBasketItem;
        $aBasketArticles[] = $oArticle;

        $oPrice->setPrice(0);

        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, array("getBasketArticles", "getContents", "getCosts", "getBruttoSum",));
        $oBasket->expects($this->any())->method('getBasketArticles')->will($this->returnValue($aBasketArticles));
        $oBasket->expects($this->any())->method('getContents')->will($this->returnValue($aBasketContents));
        $oBasket->expects($this->any())->method('getCosts')->will($this->returnValue($oPrice));
        $oBasket->expects($this->any())->method('getBruttoSum')->will($this->returnValue(7));

        $oPayment = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
        $oPayment->oxpayments__oxdesc = new oxField("testPaymentDesc");

        $oUser = oxNew("oxuser");
        $oUser->setId('_testUserId');
        $oUser->oxuser__oxusername = new oxField('username@useremail.nl', oxField::T_RAW);
        $oUser->oxuser__oxfname = new oxField('testUserFName', oxField::T_RAW);
        $oUser->oxuser__oxlname = new oxField('testUserLName', oxField::T_RAW);

        $oOrder = $this->getMock(\OxidEsales\Eshop\Application\Model\Order::class, array("getOrderUser", "getBasket", "getPayment"));
        $oOrder->expects($this->any())->method('getOrderUser')->will($this->returnValue($oUser));
        $oOrder->expects($this->any())->method('getBasket')->will($this->returnValue($oBasket));
        $oOrder->expects($this->any())->method('getPayment')->will($this->returnValue($oPayment));

        $oOrder->oxorder__oxordernr = new oxField('987654321', oxField::T_RAW);
        $oOrder->oxorder__oxbillfname = new oxField('');
        $oOrder->oxorder__oxbilllname = new oxField('');
        $oOrder->oxorder__oxbilladdinfo = new oxField('');
        $oOrder->oxorder__oxbillstreet = new oxField('');
        $oOrder->oxorder__oxbillcity = new oxField('');
        $oOrder->oxorder__oxbillcountry = new oxField('');
        $oOrder->oxorder__oxbillcompany = new oxField('');
        $oOrder->oxorder__oxdeltype = new oxField("oxidstandard");

        $oShop = oxNew("oxshop");
        $oShop->load($this->getConfig()->getShopId());

        $oEmail = $this->getMock(\OxidEsales\Eshop\Core\Email::class, array("_sendMail", "_getShop", "getOrderFileList"));
        $oEmail->expects($this->once())->method('_sendMail')->will($this->returnValue(true));
        $oEmail->expects($this->any())->method('_getShop')->will($this->returnValue($oShop));
        $oEmail->expects($this->any())->method('getOrderFileList')->will($this->returnValue(false));

        $blRet = $oEmail->sendOrderEmailToUser($oOrder);
        $this->assertTrue($blRet);

        $sBody = $oEmail->getBody();
        //uncoment line to generate template for checking mail body
        //file_put_contents (__DIR__ .'/../testData/email_templates/'.__FUNCTION__.'_.html', $oEmail->getBody() );

        $oStr = oxStr::getStr();

        // checking if there are some utf-8 strings

        // translation check
        $this->assertTrue($oStr->strpos($sBody, "Grußkarte:") > 0);

        // euro sign check
        $this->assertTrue($oStr->strpos($sBody, "256,00 €") > 0);

        // strings, that comes from oxcontent
        $this->assertTrue($oStr->strpos($sBody, "Vielen Dank für Ihre Bestellung!") > 0);
        $this->assertTrue($oStr->strpos($sBody, "Bitte fügen Sie hier Ihre vollständige Anbieterkennzeichnung ein.") > 0);
    }

    public function testSendForgotPwdEmailIsCaseInsensitive()
    {
        $realEmailAddress = 'admin';
        $userProvidedEmailAddress = 'ADMIN';

        $oEmailMock = $this->getMock(\OxidEsales\Eshop\Core\Email::class, array("send", "setRecipient"));
        $oEmailMock->expects($this->once())->method("setRecipient")->with($realEmailAddress, 'Erika Mustermann');
        $oEmailMock->expects($this->once())->method("send")->will($this->returnValue(true));

        $oEmailMock->sendForgotPwdEmail($userProvidedEmailAddress);
    }

    /**
     * Test for bug #0008618
     *
     * @dataProvider dataProviderTestSendForgotPwdEmailSendsToEmailAddressStoredInDatabase
     */
    public function testSendForgotPwdEmailSendsToEmailAddressStoredInDatabase($bogusEmailAddress)
    {
        $realEmailAddress = 'admin';

        $oEmailMock = $this->getMock(\OxidEsales\Eshop\Core\Email::class, ["send", "setRecipient"]);
        $oEmailMock->expects($this->atLeastOnce())->method("setRecipient")->with($realEmailAddress, 'Erika Mustermann');
        $oEmailMock->expects($this->atLeastOnce())->method("send")->will($this->returnValue(true));

        $oEmailMock->sendForgotPwdEmail($bogusEmailAddress);
    }

    public function dataProviderTestSendForgotPwdEmailSendsToEmailAddressStoredInDatabase()
    {
        return [
            ['Admin'],
            ['Àdmin'],
            ['Ádmin'],
            ['Âdmin'],
            ['Ãdmin'],
            ['Ädmin'],
            ['Ådmin'],
            ['àdmin'],
            ['ádmin'],
            ['âdmin'],
            ['ãdmin'],
            ['ädmin'],
            ['ådmin'],
            ['Ādmin'],
            ['Ądmin'],
            ['ądmin']
        ];
    }
}
