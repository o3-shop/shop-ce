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

namespace OxidEsales\EshopCommunity\Application\Controller;

use oxException;
use oxExceptionToDisplay;
use OxidEsales\Eshop\Core\Registry;
use oxRegistry;

/**
 * Article file download page.
 *
 */
class DownloadController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Prevents from loading any component as this controller
     * only returns file content if token is valid
     */
    public function init()
    {
        // empty for performance reasons
    }

    /**
     * Checks if given token is valid, formats HTTP headers,
     * and outputs file to buffer.
     *
     * If token is not valid, redirects to start page.
     */
    public function render()
    {
        $sFileOrderId = Registry::getConfig()->getRequestParameter('sorderfileid');

        if ($sFileOrderId) {
            $oArticleFile = oxNew(\OxidEsales\Eshop\Application\Model\File::class);
            try {
                /** @var \OxidEsales\Eshop\Application\Model\OrderFile $oOrderFile */
                $oOrderFile = oxNew(\OxidEsales\Eshop\Application\Model\OrderFile::class);
                if ($oOrderFile->load($sFileOrderId)) {
                    $sFileId = $oOrderFile->getFileId();
                    $blLoadedAndExists = $oArticleFile->load($sFileId) && $oArticleFile->exist();
                    if ($sFileId && $blLoadedAndExists && $oOrderFile->processOrderFile()) {
                        $oArticleFile->download();
                    } else {
                        $sError = "ERROR_MESSAGE_FILE_DOESNOT_EXIST";
                    }
                }
            } catch (\OxidEsales\Eshop\Core\Exception\StandardException $oEx) {
                $sError = "ERROR_MESSAGE_FILE_DOWNLOAD_FAILED";
            }
        } else {
            $sError = "ERROR_MESSAGE_WRONG_DOWNLOAD_LINK";
        }
        if ($sError) {
            $oEx = new \OxidEsales\Eshop\Core\Exception\ExceptionToDisplay();
            $oEx->setMessage($sError);
            Registry::getUtilsView()->addErrorToDisplay($oEx, false);
            Registry::getUtils()->redirect(Registry::getConfig()->getShopUrl() . 'index.php?cl=account_downloads');
        }
    }
}
