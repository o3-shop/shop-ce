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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Core;

use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\PriceAlarm;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\UserPayment;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsObject;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Twig\FixtureThemeTrait;
use PHPUnit\Framework\TestCase;

/**
 * E-mails render with the storefront theme's engine, also when sent from the admin.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class EmailTwigTest extends TestCase
{
    use FixtureThemeTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installFixtureTheme();
    }

    protected function tearDown(): void
    {
        Registry::getConfig()->setAdminMode(false);
        $this->uninstallFixtureTheme();
        parent::tearDown();
    }

    public function testOrderMailRendersTwigUnderTwigTheme(): void
    {
        $email = $this->sendOrderMail();

        $this->assertStringContainsString('TWIG-FIXTURE-ORDER-CUST-HTML', $email->getBody());
        $this->assertStringContainsString('TWIG-FIXTURE-ORDER-CUST-PLAIN', $email->getAltBody());
    }

    public function testMailSentFromAdminUsesStorefrontEngine(): void
    {
        Registry::getConfig()->setAdminMode(true);

        $email = $this->sendOrderMail();

        $this->assertStringContainsString('TWIG-FIXTURE-ORDER-CUST-HTML', $email->getBody());
        $this->assertTrue(Registry::getConfig()->isAdmin(), 'Admin mode is restored after the mail was rendered.');
    }

    public function testSmartyThemeStillRendersTpl(): void
    {
        // wave is a Smarty theme (O3-Theme 2.x is Twig).
        $this->activateTheme('wave', '');

        $email = $this->sendOrderMail();

        $this->assertStringNotContainsString('TWIG-FIXTURE', $email->getBody());
        $this->assertStringContainsString('987654321', $email->getBody());
    }

    public function testStockReminderRendersTwigTemplate(): void
    {
        $articleList = $this->getMockBuilder(ArticleList::class)
            ->setMethods(['loadStockRemindProducts', 'count'])
            ->getMock();
        $articleList->method('count')->willReturn(1);
        UtilsObject::setClassInstance(ArticleList::class, $articleList);

        $email = $this->getMailMock();
        $this->assertTrue($email->sendStockReminder([]));

        $this->assertStringContainsString('TWIG-FIXTURE-OWNER-REMINDER', $email->getBody());
    }

    public function testShippedMailSentFromAdminRendersTwigAndRestoresAdminMode(): void
    {
        Registry::getConfig()->setAdminMode(true);

        $email = $this->getMailMock();
        $email->sendSendedNowMail($this->getOrderStub());

        $this->assertStringContainsString('TWIG-FIXTURE-ORDERSHIPPED-HTML 987654321', $email->getBody());
        $this->assertStringContainsString('TWIG-FIXTURE-ORDERSHIPPED-PLAIN', $email->getAltBody());
        $this->assertTrue(Registry::getConfig()->isAdmin());
    }

    public function testAdminModeIsRestoredWhenRenderingFails(): void
    {
        Registry::getConfig()->setAdminMode(true);
        $email = $this->getMailMock();
        \Closure::bind(function (): void {
            $this->_sSenedNowTemplate = 'email/html/does_not_exist.tpl';
        }, $email, Email::class)();

        try {
            $email->sendSendedNowMail($this->getOrderStub());
            $this->fail('Rendering a missing template throws.');
        } catch (\Throwable $exception) {
            $this->assertTrue(Registry::getConfig()->isAdmin(), 'Admin mode is restored after a failed render.');
        }
    }

    /**
     * The customer price-alarm mail template exists only in views/admin, so it stays Smarty and admin mode.
     */
    public function testCustomerPriceAlarmFromAdminRendersAdminSmartyTemplate(): void
    {
        Registry::getConfig()->setAdminMode(true);
        $article = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        $article->oxarticles__oxid = new Field('_testArticleId');
        $currency = new \stdClass();
        $currency->sign = 'EUR';
        $alarm = $this->getMockBuilder(PriceAlarm::class)
            ->setMethods(['getArticle', 'getFProposedPrice', 'getPriceAlarmCurrency', 'getFPrice', 'getTitle'])
            ->getMock();
        $alarm->method('getArticle')->willReturn($article);
        $alarm->method('getFProposedPrice')->willReturn('1,00');
        $alarm->method('getPriceAlarmCurrency')->willReturn($currency);
        $alarm->method('getFPrice')->willReturn('2,00');
        $alarm->method('getTitle')->willReturn('PRICEALARM-TITLE');
        $alarm->oxpricealarm__oxshopid = new Field(Registry::getConfig()->getShopId());

        $body = $this->getMailMock()->sendPricealarmToCustomer('customer@example.com', $alarm, null, true);

        $this->assertStringContainsString('PRICEALARM-TITLE', $body);
        $this->assertStringContainsString('_testArticleId', $body);
        $this->assertTrue(Registry::getConfig()->isAdmin());
    }

    private function getMailMock(): Email
    {
        $shop = oxNew(Shop::class);
        $shop->load(Registry::getConfig()->getShopId());

        $email = $this->getMockBuilder(Email::class)
            ->setMethods(['_sendMail', '_getShop'])
            ->getMock();
        $email->method('_sendMail')->willReturn(true);
        $email->method('_getShop')->willReturn($shop);

        return $email;
    }

    private function sendOrderMail(): Email
    {
        $shop = oxNew(Shop::class);
        $shop->load(Registry::getConfig()->getShopId());

        $email = $this->getMockBuilder(Email::class)
            ->setMethods(['_sendMail', '_getShop', 'getOrderFileList'])
            ->getMock();
        $email->method('_sendMail')->willReturn(true);
        $email->method('_getShop')->willReturn($shop);
        $email->method('getOrderFileList')->willReturn(false);

        $email->sendOrderEmailToUser($this->getOrderStub());

        return $email;
    }

    private function getOrderStub(): Order
    {
        $basket = $this->getMockBuilder(Basket::class)
            ->setMethods(['getBasketArticles', 'getContents'])
            ->getMock();
        $basket->method('getBasketArticles')->willReturn([]);
        $basket->method('getContents')->willReturn([]);

        $user = oxNew(User::class);
        $user->setId('_testUserId');
        $user->oxuser__oxusername = new Field('username@useremail.nl', Field::T_RAW);
        $user->oxuser__oxfname = new Field('testUserFName', Field::T_RAW);
        $user->oxuser__oxlname = new Field('testUserLName', Field::T_RAW);

        $order = $this->getMockBuilder(Order::class)
            ->setMethods(['getOrderUser', 'getBasket', 'getPayment'])
            ->getMock();
        $order->method('getOrderUser')->willReturn($user);
        $order->method('getBasket')->willReturn($basket);
        $payment = oxNew(UserPayment::class);
        $payment->oxpayments__oxdesc = new Field('testPaymentDesc');
        $order->method('getPayment')->willReturn($payment);

        $order->oxorder__oxordernr = new Field('987654321', Field::T_RAW);
        $order->oxorder__oxremark = new Field('');
        $order->oxorder__oxdeltype = new Field('oxidstandard');
        $order->oxorder__oxlang = new Field(0);
        $order->oxorder__oxuserid = new Field('_testUserId');
        $order->oxorder__oxbillemail = new Field('username@useremail.nl');
        foreach (['fname', 'lname', 'addinfo', 'street', 'streetnr', 'zip', 'city', 'country', 'company'] as $field) {
            $order->{'oxorder__oxbill' . $field} = new Field('');
        }

        return $order;
    }
}
