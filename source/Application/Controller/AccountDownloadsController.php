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

use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Application\Model\OrderFileList;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\SeoEncoder;

/**
 * Account article file download page.
 */
class AccountDownloadsController extends AccountController
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'page/account/downloads.tpl';

    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_iViewIndexState = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;

    /**
     * @var OrderFileList
     */
    protected $_oOrderFilesList = null;

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths = [];
        $aPath = [];

        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        /** @var SeoEncoder $oSeoEncoder */
        $oSeoEncoder = Registry::getSeoEncoder();
        $aPath['title'] = Registry::getLang()->translateString('MY_ACCOUNT', $iBaseLanguage, false);
        $aPath['link'] = $oSeoEncoder->getStaticUrl($this->getViewConfig()->getSelfLink() . 'cl=account');
        $aPaths[] = $aPath;

        $aPath['title'] = Registry::getLang()->translateString('MY_DOWNLOADS', $iBaseLanguage, false);
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * Returns article list which was ordered and has downloadable files
     *
     * @return null|OrderFileList
     */
    public function getOrderFilesList()
    {
        if ($this->_oOrderFilesList !== null) {
            return $this->_oOrderFilesList;
        }

        $oOrderFileList = oxNew(OrderFileList::class);
        $oOrderFileList->loadUserFiles($this->getUser()->getId());

        $this->_oOrderFilesList = $this->_prepareForTemplate($oOrderFileList);

        return $this->_oOrderFilesList;
    }

    /**
     * Returns prepared orders files list
     *
     * @param OrderFileList $oOrderFileList - list or orderfiles
     *
     * @return array
     * @deprecated Use prepareForTemplate() instead. This underscore-prefixed name is
     *             retained only for backward compatibility with module subclasses that
     *             already override it; new code, including new modules, MUST NOT call
     *             or override _prepareForTemplate().
     */
    protected function _prepareForTemplate($oOrderFileList) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $oOrderArticles = [];

        foreach ($oOrderFileList as $oOrderFile) {
            $sOrderArticleIdField = 'oxorderfiles__oxorderarticleid';
            $sOrderNumberField = 'oxorderfiles__oxordernr';
            $sOrderDateField = 'oxorderfiles__oxorderdate';
            $sOrderTitleField = 'oxorderfiles__oxarticletitle';
            $sOrderArticleId = $oOrderFile->$sOrderArticleIdField->value;
            $oOrderArticles[$sOrderArticleId]['oxordernr'] = $oOrderFile->$sOrderNumberField->value;
            $oOrderArticles[$sOrderArticleId]['oxorderdate'] = substr($oOrderFile->$sOrderDateField->value, 0, 16);
            $oOrderArticles[$sOrderArticleId]['oxarticletitle'] = $oOrderFile->$sOrderTitleField->value;
            $oOrderArticles[$sOrderArticleId]['oxorderfiles'][] = $oOrderFile;
        }

        return $oOrderArticles;
    }

    /**
     * Returns prepared orders files list
     *
     * @param OrderFileList $oOrderFileList - list or orderfiles
     *
     * @return array
     *
     * @internal If your override does not fully replace the behavior, call
     *           parent::prepareForTemplate() (not the deprecated _prepareForTemplate())
     *           so downstream overrides in the class chain are preserved. Template-method
     *           refactor tracked in o3-shop/o3-shop#108.
     */
    protected function prepareForTemplate($oOrderFileList)
    {
        return $this->_prepareForTemplate($oOrderFileList);
    }

    /**
     * Returns error code.
     *
     * @return int
     */
    public function getDownloadError()
    {
        return Registry::getRequest()->getRequestEscapedParameter('download_error');
    }
}
