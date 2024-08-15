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

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\File;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use Exception;

/**
 * Admin article files parameters manager.
 * Collects and updates (on user submit) files.
 * Admin Menu: Manage Products -> Articles -> Files.
 */
class ArticleFiles extends AdminDetailsController
{
    /**
     * Template name
     *
     * @var string
     */
    protected $_sThisTemplate = 'article_files.tpl';

    /**
     * Stores editing article
     *
     * @var Article
     */
    protected $_oArticle = null;

    /**
     * Collects available article axtended parameters, passes them to
     * Smarty engine and returns tamplate file name "article_extend.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        if (!Registry::getConfig()->getConfigParam('blEnableDownloads')) {
            Registry::getUtilsView()->addErrorToDisplay('EXCEPTION_DISABLED_DOWNLOADABLE_PRODUCTS');
        }
        $oArticle = $this->getArticle();
        // variant handling
        if ($oArticle->oxarticles__oxparentid->value) {
            $oParentArticle = oxNew(Article::class);
            $oParentArticle->load($oArticle->oxarticles__oxparentid->value);
            $oArticle->oxarticles__oxisdownloadable = new Field($oParentArticle->oxarticles__oxisdownloadable->value);
            $this->_aViewData["oxparentid"] = $oArticle->oxarticles__oxparentid->value;
        }

        return $this->_sThisTemplate;
    }

    /**
     * Saves editing article changes (oxisdownloadable)
     * and updates oxFile object which are associated with editing object
     */
    public function save()
    {
        // save article changes
        $aArticleChanges = Registry::getRequest()->getRequestEscapedParameter('editval');
        $oArticle = $this->getArticle();
        $oArticle->assign($aArticleChanges);
        $oArticle->save();

        //update article files
        $aArticleFiles = Registry::getRequest()->getRequestEscapedParameter('article_files');
        if (is_array($aArticleFiles)) {
            foreach ($aArticleFiles as $sArticleFileId => $aArticleFileUpdate) {
                $oArticleFile = oxNew(File::class);
                $oArticleFile->load($sArticleFileId);
                $aArticleFileUpdate = $this->_processOptions($aArticleFileUpdate);
                $oArticleFile->assign($aArticleFileUpdate);

                if ($oArticleFile->isUnderDownloadFolder()) {
                    $oArticleFile->save();
                } else {
                    Registry::getUtilsView()->addErrorToDisplay('EXCEPTION_NOFILE');
                }
            }
        }
    }

    /**
     * Returns current oxarticle object
     *
     * @param bool $blReset Load article again
     *
     * @return File
     */
    public function getArticle($blReset = false)
    {
        if ($this->_oArticle !== null && !$blReset) {
            return $this->_oArticle;
        }
        $sProductId = $this->getEditObjectId();

        $oProduct = oxNew(Article::class);
        $oProduct->load($sProductId);

        return $this->_oArticle = $oProduct;
    }

    /**
     * Creates new oxFile object and stores newly uploaded file
     *
     * @return null
     */
    public function upload()
    {
        $myConfig = Registry::getConfig();

        if ($myConfig->isDemoShop()) {
            $oEx = oxNew(ExceptionToDisplay::class);
            $oEx->setMessage('ARTICLE_EXTEND_UPLOADISDISABLED');
            Registry::getUtilsView()->addErrorToDisplay($oEx, false);

            return;
        }

        $soxId = $this->getEditObjectId();

        $aParams = Registry::getRequest()->getRequestEscapedParameter('newfile');
        $aParams = $this->_processOptions($aParams);
        $aNewFile = Registry::getConfig()->getUploadedFile("newArticleFile");

        //uploading and processing supplied file
        $oArticleFile = oxNew(File::class);
        $oArticleFile->assign($aParams);

        if (!$aNewFile['name'] && !$oArticleFile->oxfiles__oxfilename->value) {
            return Registry::getUtilsView()->addErrorToDisplay('EXCEPTION_NOFILE');
        }

        if ($aNewFile['name']) {
            $oArticleFile->oxfiles__oxfilename = new Field($aNewFile['name'], Field::T_RAW);
            try {
                $oArticleFile->processFile('newArticleFile');
            } catch (Exception $e) {
                return Registry::getUtilsView()->addErrorToDisplay($e->getMessage());
            }
        }

        if (!$oArticleFile->isUnderDownloadFolder()) {
            return Registry::getUtilsView()->addErrorToDisplay('EXCEPTION_NOFILE');
        }

        //save media url
        $oArticleFile->oxfiles__oxartid = new Field($soxId, Field::T_RAW);
        $oArticleFile->save();
    }

    /**
     * Deletes article file from fileid parameter and checks if this file belongs to current article.
     *
     * @return void
     */
    public function deletefile()
    {
        $myConfig = Registry::getConfig();

        if ($myConfig->isDemoShop()) {
            $oEx = oxNew(ExceptionToDisplay::class);
            $oEx->setMessage('ARTICLE_EXTEND_UPLOADISDISABLED');
            Registry::getUtilsView()->addErrorToDisplay($oEx, false);

            return;
        }

        $sArticleId = $this->getEditObjectId();
        $sArticleFileId = Registry::getRequest()->getRequestEscapedParameter('fileid');
        $oArticleFile = oxNew(File::class);
        $oArticleFile->load($sArticleFileId);
        if ($oArticleFile->hasValidDownloads()) {
            return Registry::getUtilsView()->addErrorToDisplay('EXCEPTION_DELETING_VALID_FILE');
        }
        if ($oArticleFile->oxfiles__oxartid->value == $sArticleId) {
            $oArticleFile->delete();
        }
    }

    /**
     * Returns real config option value
     *
     * @param int $iOption option value
     *
     * @return int
     */
    public function getConfigOptionValue($iOption)
    {
        return ($iOption < 0) ? "" : $iOption;
    }

    /**
     * Process config options. If value is not set, save as "-1" to database
     *
     * @param array $aParams params
     *
     * @return array
     * @deprecated underscore prefix violates PSR12, will be renamed to "processOptions" in next major
     */
    protected function _processOptions($aParams) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!is_array($aParams)) {
            $aParams = [];
        }

        if (!isset($aParams["oxfiles__oxdownloadexptime"]) || $aParams["oxfiles__oxdownloadexptime"] == "") {
            $aParams["oxfiles__oxdownloadexptime"] = -1;
        }
        if (!isset($aParams["oxfiles__oxlinkexptime"]) || $aParams["oxfiles__oxlinkexptime"] == "") {
            $aParams["oxfiles__oxlinkexptime"] = -1;
        }
        if (!isset($aParams["oxfiles__oxmaxunregdownloads"]) || $aParams["oxfiles__oxmaxunregdownloads"] == "") {
            $aParams["oxfiles__oxmaxunregdownloads"] = -1;
        }
        if (!isset($aParams["oxfiles__oxmaxdownloads"]) || $aParams["oxfiles__oxmaxdownloads"] == "") {
            $aParams["oxfiles__oxmaxdownloads"] = -1;
        }

        return $aParams;
    }
}
