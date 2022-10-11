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

namespace OxidEsales\EshopCommunity\Tests\CodeceptionAdmin;

use DateTime;
use OxidEsales\Codeception\Admin\AdminPanel;
use OxidEsales\EshopCommunity\Tests\Codeception\AcceptanceAdminTester;

final class AdminDownloadableProductCest
{
    /** @param AcceptanceAdminTester $I */
    public function _before(AcceptanceAdminTester $I)
    {
        $I->updateInDatabase('oxarticles', ['oxisdownloadable' => 1], ['oxartnum' => '1208']);
        $userId = $I->grabFromDatabase('oxuser', 'OXID', ['OXUSERNAME' => 'user@oxid-esales.com']);
        $orderId = $I->grabFromDatabase('oxorder', 'OXID', ['oxuserid' => $userId]);
        $articleId = $I->grabFromDatabase('oxorderarticles', 'OXID', ['OXORDERID' => $orderId]);

        $I->haveInDatabase(
            'oxorderfiles',
            [
                'OXID' => "testdownloadProductCest",
                'OXORDERID' => $orderId,
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
                'OXARTID' => '1208',
                'OXFILENAME' => 'testFile3',
                'OXPURCHASEDONLY' => 1,
                'OXSTOREHASH' => 'e48a1b571bd2d2e60fb2d9b1b76b35d5',
            ]
        );
    }

    /** @param AcceptanceAdminTester $I */
    public function downloadableFiles(AcceptanceAdminTester $I): void
    {
        $I->wantToTest('Product downloadable files');

        $adminPanel = $I->loginAdmin();

        $this->enableDownloadableFiles($I, $adminPanel);
        $this->setDownloadableFileForAProduct($I, $adminPanel);
        $this->makeOrderComplete($I, $adminPanel);
    }

    /**
     * @param AcceptanceAdminTester $I
     * @param AdminPanel            $adminPanel
     */
    private function enableDownloadableFiles(AcceptanceAdminTester $I, AdminPanel $adminPanel): void
    {
        $coreSettings = $adminPanel->openCoreSettings();
        $settingsTab = $coreSettings->openSettingsTab();
        $settingsTab->openDownloadableProducts();
        $I->checkOption('confbools[blEnableDownloads]');
        $I->fillField("confstrs[iMaxDownloadsCount]", "2");
        $I->fillField("confstrs[iLinkExpirationTime]", "240");
        $I->fillField("confstrs[iDownloadExpirationTime]", "24");
        $I->fillField("confstrs[iMaxDownloadsCountUnregistered]", "1");
        $I->click(['name' => 'save']);
    }

    /**
     * @param AcceptanceAdminTester $I
     * @param AdminPanel            $adminPanel
     */
    private function setDownloadableFileForAProduct(AcceptanceAdminTester $I, AdminPanel $adminPanel): void
    {
        $products = $adminPanel->openProducts();
        $products->find("where[oxarticles][oxartnum]", "1002");
        $products->openDownloadsTab();
        $I->checkOption('editval[oxarticles__oxisdownloadable]');
        $I->click(['name' => 'save']);
    }

    /**
     * @param AcceptanceAdminTester $I
     * @param AdminPanel            $adminPanel
     */
    private function makeOrderComplete(AcceptanceAdminTester $I, AdminPanel $adminPanel): void
    {
        $orders = $adminPanel->openOrders();
        $order = $orders->find("where[oxorder][oxordernr]", "1");
        $orderDownloadsTab = $order->openDownloadsTab();

        $I->assertEquals("1208", $I->grabTextFrom($orderDownloadsTab->productNumberInDownloadsTab));
        $I->assertEquals("Kite CORE GTS", $I->grabTextFrom($orderDownloadsTab->titleInDownloadsTab));
        $I->assertEquals("testFile3", $I->grabTextFrom($orderDownloadsTab->downloadableFileInDownloadsTab));
        $I->assertEquals(
            "0000-00-00 00:00:00",
            $I->grabTextFrom($orderDownloadsTab->firstDownloadInDownloadsTab)
        );
        $I->assertEquals(
            "0000-00-00 00:00:00",
            $I->grabTextFrom($orderDownloadsTab->lastDownloadInDownloadsTab)
        );
        $I->assertEquals("0", $I->grabTextFrom($orderDownloadsTab->countInDownloadsTab));
        $I->assertEquals("2", $I->grabTextFrom($orderDownloadsTab->maxCountInDownloadsTab));
    }
}
