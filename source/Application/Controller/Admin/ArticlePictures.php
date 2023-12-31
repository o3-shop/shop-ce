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

use oxRegistry;
use oxField;

/**
 * Admin article picture manager.
 * Collects information about article's used pictures, there is posibility to
 * upload any other picture, etc.
 * Admin Menu: Manage Products -> Articles -> Pictures.
 */
class ArticlePictures extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    /**
     * Loads article information - pictures, passes data to Smarty
     * engine, returns name of template file "article_pictures.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $this->_aViewData["edit"] = $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);

        $soxId = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oArticle->load($soxId);
            $oArticle = $this->updateArticle($oArticle);

            // variant handling
            if ($oArticle->oxarticles__oxparentid->value) {
                $oParentArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
                $oParentArticle->load($oArticle->oxarticles__oxparentid->value);
                $this->_aViewData["parentarticle"] = $oParentArticle;
                $this->_aViewData["oxparentid"] = $oArticle->oxarticles__oxparentid->value;
            }
        }

        $this->_aViewData["iPicCount"] = $this->getConfig()->getConfigParam('iPicCount');

        return "article_pictures.tpl";
    }

    /**
     * Saves (uploads) pictures to server.
     *
     * @return mixed
     */
    public function save()
    {
        $myConfig = $this->getConfig();

        if ($myConfig->isDemoShop()) {
            // disabling uploading pictures if this is demo shop
            $oEx = oxNew(\OxidEsales\Eshop\Core\Exception\ExceptionToDisplay::class);
            $oEx->setMessage('ARTICLE_PICTURES_UPLOADISDISABLED');
            \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay($oEx, false);

            return;
        }

        parent::save();

        $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        if ($oArticle->load($this->getEditObjectId())) {
            $oArticle->assign(\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("editval"));
            \OxidEsales\Eshop\Core\Registry::getUtilsFile()->processFiles($oArticle);

            // Show that no new image added
            if (\OxidEsales\Eshop\Core\Registry::getUtilsFile()->getNewFilesCounter() == 0) {
                $oEx = oxNew(\OxidEsales\Eshop\Core\Exception\ExceptionToDisplay::class);
                $oEx->setMessage('NO_PICTURES_CHANGES');
                \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay($oEx, false);
            }

            $oArticle->save();
        }
    }

    /**
     * Deletes selected master picture and all other master pictures
     * where master picture index is higher than currently deleted index.
     * Also deletes custom icon and thumbnail.
     *
     * @return null
     */
    public function deletePicture()
    {
        $myConfig = $this->getConfig();

        if ($myConfig->isDemoShop()) {
            // disabling uploading pictures if this is demo shop
            $oEx = oxNew(\OxidEsales\Eshop\Core\Exception\ExceptionToDisplay::class);
            $oEx->setMessage('ARTICLE_PICTURES_UPLOADISDISABLED');
            \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay($oEx, false);

            return;
        }

        $sOxId = $this->getEditObjectId();
        $iIndex = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("masterPicIndex");

        $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        $oArticle->load($sOxId);

        if ($iIndex == "ICO") {
            // deleting main icon
            $this->_deleteMainIcon($oArticle);
        } elseif ($iIndex == "TH") {
            // deleting thumbnail
            $this->_deleteThumbnail($oArticle);
        } else {
            $iIndex = (int) $iIndex;
            if ($iIndex > 0) {
                // deleting master picture
                $this->_resetMasterPicture($oArticle, $iIndex, true);
            }
        }

        $oArticle->save();
    }

    /**
     * Deletes selected master picture and all pictures generated
     * from master picture
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle       article object
     * @param int                                         $iIndex         master picture index
     * @param bool                                        $blDeleteMaster if TRUE - deletes and unsets master image file
     * @deprecated underscore prefix violates PSR12, will be renamed to "resetMasterPicture" in next major
     */
    protected function _resetMasterPicture($oArticle, $iIndex, $blDeleteMaster = false) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->canResetMasterPicture($oArticle, $iIndex)) {
            if (!$oArticle->isDerived()) {
                $oPicHandler = \OxidEsales\Eshop\Core\Registry::getPictureHandler();
                $oPicHandler->deleteArticleMasterPicture($oArticle, $iIndex, $blDeleteMaster);
            }

            if ($blDeleteMaster) {
                //reseting master picture field
                $oArticle->{"oxarticles__oxpic" . $iIndex} = new \OxidEsales\Eshop\Core\Field();
            }

            // cleaning oxzoom fields
            if (isset($oArticle->{"oxarticles__oxzoom" . $iIndex})) {
                $oArticle->{"oxarticles__oxzoom" . $iIndex} = new \OxidEsales\Eshop\Core\Field();
            }

            if ($iIndex == 1) {
                $this->_cleanupCustomFields($oArticle);
            }
        }
    }

    /**
     * Deletes main icon file
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     * @deprecated underscore prefix violates PSR12, will be renamed to "deleteMainIcon" in next major
     */
    protected function _deleteMainIcon($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->canDeleteMainIcon($oArticle)) {
            if (!$oArticle->isDerived()) {
                $oPicHandler = \OxidEsales\Eshop\Core\Registry::getPictureHandler();
                $oPicHandler->deleteMainIcon($oArticle);
            }

            //reseting field
            $oArticle->oxarticles__oxicon = new \OxidEsales\Eshop\Core\Field();
        }
    }

    /**
     * Deletes thumbnail file
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     * @deprecated underscore prefix violates PSR12, will be renamed to "deleteThumbnail" in next major
     */
    protected function _deleteThumbnail($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->canDeleteThumbnail($oArticle)) {
            if (!$oArticle->isDerived()) {
                $oPicHandler = \OxidEsales\Eshop\Core\Registry::getPictureHandler();
                $oPicHandler->deleteThumbnail($oArticle);
            }

            //reseting field
            $oArticle->oxarticles__oxthumb = new \OxidEsales\Eshop\Core\Field();
        }
    }

    /**
     * Cleans up article custom fields oxicon and oxthumb. If there is custom
     * icon or thumb picture, leaves records untouched.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     * @deprecated underscore prefix violates PSR12, will be renamed to "cleanupCustomFields" in next major
     */
    protected function _cleanupCustomFields($oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sIcon = $oArticle->oxarticles__oxicon->value;
        $sThumb = $oArticle->oxarticles__oxthumb->value;

        if ($sIcon == "nopic.jpg") {
            $oArticle->oxarticles__oxicon = new \OxidEsales\Eshop\Core\Field();
        }

        if ($sThumb == "nopic.jpg") {
            $oArticle->oxarticles__oxthumb = new \OxidEsales\Eshop\Core\Field();
        }
    }

    /**
     * Method is used for overloading to update article object.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function updateArticle($oArticle)
    {
        return $oArticle;
    }

    /**
     * Checks if possible to reset master picture.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle
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
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle
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
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle
     *
     * @return bool
     */
    protected function canDeleteThumbnail($oArticle)
    {
        return (bool) $oArticle->oxarticles__oxthumb->value;
    }
}
