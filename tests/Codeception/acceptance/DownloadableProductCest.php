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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Codeception;

use DateTime;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Home;
use OxidEsales\Codeception\Step\Basket;

final class DownloadableProductCest
{
    /**
     * @var string
     */
    private $orderId;

    /** @param AcceptanceTester $I */
    public function _before(AcceptanceTester $I)
    {
        $I->updateConfigInDatabase('iMaxDownloadsCount', "2", 'str');
        $I->updateConfigInDatabase('iLinkExpirationTime', "240", 'str');
        $I->updateInDatabase('oxarticles', ['oxisdownloadable' => 1], ['oxartnum' => '1002-1']);
    }

    /** @param AcceptanceTester $I */
    public function _after(AcceptanceTester $I)
    {
        $I->updateConfigInDatabase('iMaxDownloadsCount', "0", 'str');
        $I->updateConfigInDatabase('iLinkExpirationTime', "168", 'str');
        $I->updateInDatabase('oxarticles', ['oxisdownloadable' => 0], ['oxartnum' => '1002-1']);
        $I->deleteFromDatabase('oxorder', ['OXID' => $this->orderId]);
        $I->deleteFromDatabase('oxorderarticles', ['OXORDERID' => $this->orderId]);
    }

    /** @param AcceptanceTester $I */
    public function downloadableFiles(AcceptanceTester $I): void
    {
        $I->wantToTest('Product downloadable files');

        $I->clearShopCache();
        $startPage = $I->loginShopWithExistingUser();
        $this->makePurchaseComplete($I);

        $this->orderId = $I->grabFromDatabase('oxorder', 'OXID', ['oxuserid' => 'testuser']);
        $articleId = $I->grabFromDatabase('oxorderarticles', 'OXID', ['OXORDERID' => $this->orderId]);

        $I->haveInDatabase(
            'oxorderfiles',
            [
                'OXID' => "testdownloadProductCest",
                'OXORDERID' => $this->orderId,
                'OXFILENAME' => 'testFile3',
                'OXFILEID' => '1000l',
                'OXSHOPID' => 1,
                'OXORDERARTICLEID' => $articleId,
                'OXDOWNLOADCOUNT' => '0',
                'OXMAXDOWNLOADCOUNT' => 2,
                'OXDOWNLOADEXPIRATIONTIME' => 24,
                'OXLINKEXPIRATIONTIME' => 240,
                'OXRESETCOUNT' => 0,
                'OXVALIDUNTIL' => (new DateTime())->modify('+1 week')->format('Y-m-d 00:00:00'),
                'OXTIMESTAMP' => (new DateTime())->format('Y-m-d 00:00:00')
            ]
        );

        $I->haveInDatabase(
            'oxfiles',
            [
                'OXID' => '1000l',
                'OXARTID' => '1002-1',
                'OXFILENAME' => 'testFile3',
                'OXPURCHASEDONLY' => 1,
                'OXSTOREHASH' => 'e48a1b571bd2d2e60fb2d9b1b76b35d5',
            ]
        );

        $this->checkMyDownloads($I, $startPage);
        $this->makeOrderComplete($I);
        $this->checkFileInMyDownloads($I, $startPage);
    }

    /**
     * @param AcceptanceTester $I
     */
    private function makePurchaseComplete(AcceptanceTester $I): void
    {
        $basket = new Basket($I);
        $userCheckoutPage = $basket->addProductToBasketAndOpenUserCheckout('1002-1', 1);
        $userCheckoutPage->goToNextStep();
        $I->click(['name' => 'userform']);
        $I->checkOption('oxdownloadableproductsagreement');
        $I->click("//form[@id='orderConfirmAgbBottom']//button");
        $userCheckoutPage->seeOnBreadCrumb(Translator::translate('ORDER_COMPLETED'));
    }

    /**
     * @param AcceptanceTester $I
     * @param Home             $startPage
     */
    private function checkMyDownloads(AcceptanceTester $I, Home $startPage): void
    {
        $accountPage = $startPage->openAccountPage();
        $accountPage->openMyDownloadsPage();
        $I->see(Translator::translate('DOWNLOADS_PAYMENT_PENDING'));
    }

    /**
     * @param AcceptanceTester $I
     */
    private function makeOrderComplete(AcceptanceTester $I): void
    {
        $currentTime = date('Y-m-d H:i:s');
        $I->updateInDatabase('oxorder', ['oxpaid' => $currentTime], ['OXID' => $this->orderId]);
    }

    /**
     * @param AcceptanceTester $I
     * @param Home             $startPage
     */
    private function checkFileInMyDownloads(AcceptanceTester $I, Home $startPage): void
    {
        $accountPage = $startPage->openAccountPage();
        $accountPage->openMyDownloadsPage();
        $I->dontSee(Translator::translate('DOWNLOADS_PAYMENT_PENDING'));
        $I->click(".downloadList a");
    }
}
