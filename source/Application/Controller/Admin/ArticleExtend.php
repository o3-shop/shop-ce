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
use OxidEsales\Eshop\Application\Controller\Admin\ArticleBundleAjax;
use OxidEsales\Eshop\Application\Controller\Admin\ArticleExtendAjax;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\MediaUrl;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ExceptionToDisplay;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use stdClass;
use Exception;

/**
 * Admin article extended parameters manager.
 * Collects and updates (on user submit) extended article properties ( such as
 * weight, dimensions, purchase Price and etc.). There is ability to assign article
 * to any chosen article group.
 * Admin Menu: Manage Products -> Articles -> Extended.
 */
class ArticleExtend extends AdminDetailsController
{
    /**
     * Unit array
     *
     * @var array
     */
    protected $_aUnitsArray = null;

    /**
     * Collects available article extended parameters, passes them to
     * Smarty engine and returns template file name "article_extend.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $this->_aViewData['edit'] = $article = oxNew(Article::class);

        $oxId = $this->getEditObjectId();

        $this->_createCategoryTree("artcattree");

        // all categories
        if (isset($oxId) && $oxId != "-1") {
            // load object
            $article->loadInLang($this->_iEditLang, $oxId);

            $article = $this->updateArticle($article);

            // load object in other languages
            $otherLang = $article->getAvailableInLangs();
            if (!isset($otherLang[$this->_iEditLang])) {
                $article->loadInLang(key($otherLang), $oxId);
            }

            foreach ($otherLang as $id => $language) {
                $lang = new stdClass();
                $lang->sLangDesc = $language;
                $lang->selected = ($id == $this->_iEditLang);
                $this->_aViewData["otherlang"][$id] = clone $lang;
            }

            // variant handling
            if ($article->oxarticles__oxparentid->value) {
                $parentArticle = oxNew(Article::class);
                $parentArticle->load($article->oxarticles__oxparentid->value);
                $this->_aViewData["parentarticle"] = $parentArticle;
                $this->_aViewData["oxparentid"] = $article->oxarticles__oxparentid->value;
            }
        }

        $this->prepareBundledArticlesDataForView($article);

        $iAoc = Registry::getRequest()->getRequestEscapedParameter('aoc');
        if ($iAoc == 1) {
            $oArticleExtendAjax = oxNew(ArticleExtendAjax::class);
            $this->_aViewData['oxajax'] = $oArticleExtendAjax->getColumns();

            return "popups/article_extend.tpl";
        } elseif ($iAoc == 2) {
            $oArticleBundleAjax = oxNew(ArticleBundleAjax::class);
            $this->_aViewData['oxajax'] = $oArticleBundleAjax->getColumns();

            return "popups/article_bundle.tpl";
        }

        //load media files
        $this->_aViewData['aMediaUrls'] = $article->getMediaUrls();

        return "article_extend.tpl";
    }

    /**
     * Saves modified extended article parameters.
     *
     * @return mixed
     */
    public function save()
    {
        parent::save();

        $aMyFile = Registry::getConfig()->getUploadedFile("myfile");
        $aMediaFile = Registry::getConfig()->getUploadedFile("mediaFile");
        if (is_array($aMyFile['name']) && reset($aMyFile['name']) || $aMediaFile['name']) {
            $myConfig = Registry::getConfig();
            if ($myConfig->isDemoShop()) {
                $oEx = oxNew(ExceptionToDisplay::class);
                $oEx->setMessage('ARTICLE_EXTEND_UPLOADISDISABLED');
                Registry::getUtilsView()->addErrorToDisplay($oEx, false);

                return;
            }
        }

        $soxId = $this->getEditObjectId();
        $aParams = Registry::getRequest()->getRequestEscapedParameter('editval');
        // checkbox handling
        if (!isset($aParams['oxarticles__oxissearch'])) {
            $aParams['oxarticles__oxissearch'] = 0;
        }
        if (!isset($aParams['oxarticles__oxblfixedprice'])) {
            $aParams['oxarticles__oxblfixedprice'] = 0;
        }

        // new way of handling bundled articles
        //#1517C - remove possibility to add Bundled Product
        //$this->setBundleId($aParams, $soxId);

        // default values
        $aParams = $this->addDefaultValues($aParams);

        $oArticle = oxNew(Article::class);
        $oArticle->loadInLang($this->_iEditLang, $soxId);
        $sTPriceField = 'oxarticles__oxtprice';
        $sPriceField = 'oxarticles__oxprice';
        $dTPrice = $aParams['oxarticles__oxtprice'];
        if ($dTPrice && $dTPrice != $oArticle->$sTPriceField->value && $dTPrice <= $oArticle->$sPriceField->value) {
            $this->_aViewData["errorsavingtprice"] = 1;
        }

        $oArticle->setLanguage(0);
        $oArticle->assign($aParams);
        $oArticle->setLanguage($this->_iEditLang);
        $oArticle = Registry::getUtilsFile()->processFiles($oArticle);
        $oArticle->save();

        //saving media file
        $sMediaUrl = Registry::getRequest()->getRequestEscapedParameter('mediaUrl');
        $sMediaDesc = Registry::getRequest()->getRequestEscapedParameter('mediaDesc');

        if (($sMediaUrl && $sMediaUrl != 'http://') || $aMediaFile['name'] || $sMediaDesc) {
            if (!$sMediaDesc) {
                return Registry::getUtilsView()->addErrorToDisplay('EXCEPTION_NODESCRIPTIONADDED');
            }

            if ((!$sMediaUrl || $sMediaUrl == 'http://') && !$aMediaFile['name']) {
                return Registry::getUtilsView()->addErrorToDisplay('EXCEPTION_NOMEDIAADDED');
            }

            $oMediaUrl = oxNew(MediaUrl::class);
            $oMediaUrl->setLanguage($this->_iEditLang);
            $oMediaUrl->oxmediaurls__oxisuploaded = new Field(0, Field::T_RAW);

            //handle uploaded file
            if ($aMediaFile['name']) {
                try {
                    $sMediaUrl = Registry::getUtilsFile()->processFile('mediaFile', 'out/media/');
                    $oMediaUrl->oxmediaurls__oxisuploaded = new Field(1, Field::T_RAW);
                } catch (Exception $e) {
                    return Registry::getUtilsView()->addErrorToDisplay($e->getMessage());
                }
            }

            //save media url
            $oMediaUrl->oxmediaurls__oxobjectid = new Field($soxId, Field::T_RAW);
            $oMediaUrl->oxmediaurls__oxurl = new Field($sMediaUrl, Field::T_RAW);
            $oMediaUrl->oxmediaurls__oxdesc = new Field($sMediaDesc, Field::T_RAW);
            $oMediaUrl->save();
        }

        // renew price update time
        oxNew(\OxidEsales\Eshop\Application\Model\ArticleList::class)->renewPriceUpdateTime();
    }

    /**
     * Deletes media url (with possible linked files)
     */
    public function deletemedia()
    {
        $soxId = $this->getEditObjectId();
        $sMediaId = Registry::getRequest()->getRequestEscapedParameter('mediaid');
        if ($sMediaId && $soxId) {
            $oMediaUrl = oxNew(MediaUrl::class);
            $oMediaUrl->load($sMediaId);
            $oMediaUrl->delete();
        }
    }

    /**
     * Adds default values for extended article parameters. Returns modified
     * parameters array.
     *
     * @param array $aParams Article parameters array
     *
     * @return array
     */
    public function addDefaultValues($aParams)
    {
        return $aParams;
    }

    /**
     * Updates existing media descriptions
     */
    public function updateMedia()
    {
        $aMediaUrls = Registry::getRequest()->getRequestEscapedParameter('aMediaUrls');
        if (is_array($aMediaUrls)) {
            foreach ($aMediaUrls as $sMediaId => $aMediaParams) {
                $oMedia = oxNew(MediaUrl::class);
                if ($oMedia->load($sMediaId)) {
                    $oMedia->setLanguage(0);
                    $oMedia->assign($aMediaParams);
                    $oMedia->setLanguage($this->_iEditLang);
                    $oMedia->save();
                }
            }
        }
    }

    /**
     * Returns array of possible unit combination and its translation for edit language
     *
     * @return array
     */
    public function getUnitsArray()
    {
        if ($this->_aUnitsArray === null) {
            $this->_aUnitsArray = Registry::getLang()->getSimilarByKey("_UNIT_", $this->_iEditLang, false);
        }

        return $this->_aUnitsArray;
    }

    /**
     * Method used to overload and update article.
     *
     * @param Article $article
     *
     * @return Article
     */
    protected function updateArticle($article)
    {
        return $article;
    }

    /**
     * Adds data to _aViewData for later use in templates.
     *
     * @param Article $article
     */
    protected function prepareBundledArticlesDataForView($article)
    {
        $database = DatabaseProvider::getDB();
        $config = Registry::getConfig();

        $articleTable = getViewName('oxarticles', $this->_iEditLang);
        $query = "select {$articleTable}.oxtitle, {$articleTable}.oxartnum, {$articleTable}.oxvarselect " .
            "from {$articleTable} where 1 ";
        // #546
        $isVariantSelectionEnabled = $config->getConfigParam('blVariantsSelection');
        $bundleIdField = 'oxarticles__oxbundleid';
        $query .= $isVariantSelectionEnabled ? '' : " and {$articleTable}.oxparentid = '' ";
        $query .= " and {$articleTable}.oxid = :oxid";

        $resultFromDatabase = $database->select($query, [
            ':oxid' => $article->$bundleIdField->value
        ]);
        if ($resultFromDatabase != false && $resultFromDatabase->count() > 0) {
            while (!$resultFromDatabase->EOF) {
                $articleNumber = new Field($resultFromDatabase->fields[1]);
                $articleTitle = new Field($resultFromDatabase->fields[0] . " " . $resultFromDatabase->fields[2]);
                $resultFromDatabase->fetchRow();
            }
        }
        $this->_aViewData['bundle_artnum'] = $articleNumber;
        $this->_aViewData['bundle_title'] = $articleTitle;
    }
}
