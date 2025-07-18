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
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin article picture manager.
 * Collects information about article's used pictures, there is possibility to
 * upload any other picture, etc.
 * Admin Menu: Manage Products -> Articles -> Pictures.
 */
class ArticlePictures extends AdminDetailsController
{
    /**
     * Loads article information - pictures, passes data to Smarty
     * engine, returns name of template file "article_pictures.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render()
    {
        parent::render();

        $this->_aViewData["edit"] = $oArticle = oxNew(Article::class);

        $soxId = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oArticle->load($soxId);
            $oArticle = $this->updateArticle($oArticle);

            // variant handling
            if ($oArticle->oxarticles__oxparentid->value) {
                $oParentArticle = oxNew(Article::class);
                $oParentArticle->load($oArticle->oxarticles__oxparentid->value);
                $this->_aViewData["parentarticle"] = $oParentArticle;
                $this->_aViewData["oxparentid"] = $oArticle->oxarticles__oxparentid->value;
            }
        }

        $this->_aViewData["iPicCount"] = Registry::getConfig()->getConfigParam('iPicCount');

        return "article_pictures.tpl";
    }

    /**
     * Saves (uploads) pictures to server.
     *
     * @return void
     * @throws DatabaseConnectionException
     */
    public function save()
    {
        $myConfig = Registry::getConfig();

        if ($myConfig->isDemoShop()) {
            // disabling uploading pictures if this is demo shop
            $oEx = oxNew(ExceptionToDisplay::class);
            $oEx->setMessage('ARTICLE_PICTURES_UPLOADISDISABLED');
            Registry::getUtilsView()->addErrorToDisplay($oEx, false);

            return;
        }

        parent::save();

        $oArticle = oxNew(Article::class);
        if ($oArticle->load($this->getEditObjectId())) {
            $oArticle->assign(Registry::getRequest()->getRequestEscapedParameter('editval'));
            Registry::getUtilsFile()->processFiles($oArticle);

            // Show that no new image added
            if (Registry::getUtilsFile()->getNewFilesCounter() == 0) {
                $oEx = oxNew(ExceptionToDisplay::class);
                $oEx->setMessage('NO_PICTURES_CHANGES');
                Registry::getUtilsView()->addErrorToDisplay($oEx, false);
            }

            $oArticle->save();
        }
    }

    /**
     * Deletes selected master picture and all other master pictures
     * where master picture index is higher than currently deleted index.
     * Also deletes custom icon and thumbnail.
     *
     * @return void
     * @throws DatabaseConnectionException
     */
    public function deletePicture()
    {
        $myConfig = Registry::getConfig();

        if ($myConfig->isDemoShop()) {
            // disabling uploading pictures if this is demo shop
            $oEx = oxNew(ExceptionToDisplay::class);
            $oEx->setMessage('ARTICLE_PICTURES_UPLOADISDISABLED');
            Registry::getUtilsView()->addErrorToDisplay($oEx, false);

            return;
        }

        $sOxId = $this->getEditObjectId();
        $iIndex = Registry::getRequest()->getRequestEscapedParameter('masterPicIndex');

        $oArticle = oxNew(Article::class);
        $oArticle->load($sOxId);

        if ($iIndex == "ICO") {
            // deleting main icon
            $this->deleteMainIcon($oArticle);
        } elseif ($iIndex == "TH") {
            // deleting thumbnail
            $this->deleteThumbnail($oArticle);
        } else {
            $iIndex = (int) $iIndex;
            if ($iIndex > 0) {
                // deleting master picture
                $this->resetMasterPicture($oArticle, $iIndex, true);
            }
        }

        $oArticle->save();
    }

    /**
     * Deletes selected master picture and all pictures generated
     * from master picture
     *
     * @param Article $oArticle       article object
     * @param int                                         $iIndex         master picture index
     * @param bool                                        $blDeleteMaster if TRUE - deletes and unsets master image file
     * @deprecated underscore prefix violates PSR12, will be renamed to "resetMasterPicture" in next major
     */
    protected function _resetMasterPicture($oArticle, $iIndex, $blDeleteMaster = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->resetMasterPicture($oArticle, $iIndex, $blDeleteMaster);
    }

    /**
     * Deletes selected master picture and all pictures generated
     * from master picture
     *
     * @param Article $oArticle       article object
     * @param int                                         $iIndex         master picture index
     * @param bool                                        $blDeleteMaster if TRUE - deletes and unsets master image file
     */
    protected function resetMasterPicture($oArticle, $iIndex, $blDeleteMaster = false)
    {
        if ($this->canResetMasterPicture($oArticle, $iIndex)) {
            if (!$oArticle->isDerived()) {
                $oPicHandler = Registry::getPictureHandler();
                $oPicHandler->deleteArticleMasterPicture($oArticle, $iIndex, $blDeleteMaster);
            }

            if ($blDeleteMaster) {
                //reseting master picture field
                $oArticle->{"oxarticles__oxpic" . $iIndex} = new Field();
            }

            // cleaning oxzoom fields
            if (isset($oArticle->{"oxarticles__oxzoom" . $iIndex})) {
                $oArticle->{"oxarticles__oxzoom" . $iIndex} = new Field();
            }

            if ($iIndex == 1) {
                $this->cleanupCustomFields($oArticle);
            }
        }
    }

    /**
     * Deletes main icon file
     *
     * @param Article $oArticle article object
     * @deprecated underscore prefix violates PSR12, will be renamed to "deleteMainIcon" in next major
     */
    protected function _deleteMainIcon($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->deleteMainIcon($oArticle);
    }

    /**
     * Deletes main icon file
     *
     * @param Article $oArticle article object
     */
    protected function deleteMainIcon($oArticle)
    {
        if ($this->canDeleteMainIcon($oArticle)) {
            if (!$oArticle->isDerived()) {
                $oPicHandler = Registry::getPictureHandler();
                $oPicHandler->deleteMainIcon($oArticle);
            }

            //reseting field
            $oArticle->oxarticles__oxicon = new Field();
        }
    }

    /**
     * Deletes thumbnail file
     *
     * @param Article $oArticle article object
     * @deprecated underscore prefix violates PSR12, will be renamed to "deleteThumbnail" in next major
     */
    protected function _deleteThumbnail($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->deleteThumbnail($oArticle);
    }

    /**
     * Deletes thumbnail file
     *
     * @param Article $oArticle article object
     */
    protected function deleteThumbnail($oArticle)
    {
        if ($this->canDeleteThumbnail($oArticle)) {
            if (!$oArticle->isDerived()) {
                $oPicHandler = Registry::getPictureHandler();
                $oPicHandler->deleteThumbnail($oArticle);
            }

            //reseting field
            $oArticle->oxarticles__oxthumb = new Field();
        }
    }

    /**
     * Cleans up article custom fields oxicon and oxthumb. If there is custom
     * icon or thumb picture, leaves records untouched.
     *
     * @param Article $oArticle article object
     * @deprecated underscore prefix violates PSR12, will be renamed to "cleanupCustomFields" in next major
     */
    protected function _cleanupCustomFields($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->cleanupCustomFields($oArticle);
    }

    /**
     * Cleans up article custom fields oxicon and oxthumb. If there is custom
     * icon or thumb picture, leaves records untouched.
     *
     * @param Article $oArticle article object
     */
    protected function cleanupCustomFields($oArticle)
    {
        $sIcon = $oArticle->oxarticles__oxicon->value;
        $sThumb = $oArticle->oxarticles__oxthumb->value;

        if ($sIcon == "nopic.jpg") {
            $oArticle->oxarticles__oxicon = new Field();
        }

        if ($sThumb == "nopic.jpg") {
            $oArticle->oxarticles__oxthumb = new Field();
        }
    }

    /**
     * Method is used for overloading to update article object.
     *
     * @param Article $oArticle
     *
     * @return Article
     */
    protected function updateArticle($oArticle)
    {
        return $oArticle;
    }

    /**
     * Checks if possible to reset master picture.
     *
     * @param Article $oArticle
     * @param int                                         $masterPictureIndex
     *
     * @return bool
     */
    protected function canResetMasterPicture($oArticle, $masterPictureIndex)
    {
        return (bool) $oArticle->{"oxarticles__oxpic" . $masterPictureIndex}->value;
    }

    /**
     * Checks if possible to delete main icon of article.
     *
     * @param Article $oArticle
     *
     * @return bool
     */
    protected function canDeleteMainIcon($oArticle)
    {
        return (bool) $oArticle->oxarticles__oxicon->value;
    }

    /**
     * Checks if possible to delete thumbnail of article.
     *
     * @param Article $oArticle
     *
     * @return bool
     */
    protected function canDeleteThumbnail($oArticle)
    {
        return (bool) $oArticle->oxarticles__oxthumb->value;
    }
}
