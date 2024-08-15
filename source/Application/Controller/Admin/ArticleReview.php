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
use OxidEsales\Eshop\Application\Model\Rating;
use OxidEsales\Eshop\Application\Model\Review;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

/**
 * Admin article review manager.
 * Collects customer review about article data. There ir possibility to update
 * review text or delete it.
 * Admin Menu: Manage Products -> Articles -> Review.
 */
class ArticleReview extends AdminDetailsController
{
    /**
     * Loads selected article review information, returns name of template
     * file "article_review.tpl".
     *
     * @return string
     */
    public function render()
    {
        $config = Registry::getConfig();

        parent::render();

        $article = oxNew(Article::class);
        $this->_aViewData["edit"] = $article;

        $articleId = $this->getEditObjectId();
        $reviewId = Registry::getRequest()->getRequestEscapedParameter('rev_oxid');
        if (isset($articleId) && $articleId != "-1") {
            // load object
            $article->load($articleId);

            if ($article->isDerived()) {
                $this->_aViewData['readonly'] = true;
            }

            $reviewList = $this->_getReviewList($article);

            foreach ($reviewList as $review) {
                if ($review->oxreviews__oxid->value == $reviewId) {
                    $review->selected = 1;
                    break;
                }
            }
            $this->_aViewData["allreviews"] = $reviewList;
            $this->_aViewData['editlanguage'] = $this->_iEditLang;

            if (isset($reviewId)) {
                $reviewForEditing = oxNew(Review::class);
                $reviewForEditing->load($reviewId);
                $this->_aViewData["editreview"] = $reviewForEditing;

                $user = oxNew(User::class);
                $user->load($reviewForEditing->oxreviews__oxuserid->value);
                $this->_aViewData["user"] = $user;
            }
            //show "active" checkbox if moderating is active
            $this->_aViewData["blShowActBox"] = $config->getConfigParam('blGBModerate');
        }

        return "article_review.tpl";
    }

    /**
     * returns reviews list for article
     *
     * @param Article $article Article object
     *
     * @return ListModel
     * @deprecated underscore prefix violates PSR12, will be renamed to "getReviewList" in next major
     */
    protected function _getReviewList($article) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $database = DatabaseProvider::getDb();
        $query = "select oxreviews.* from oxreviews
                     where oxreviews.OXOBJECTID = " . $database->quote($article->oxarticles__oxid->value) . "
                     and oxreviews.oxtype = 'oxarticle'";

        $variantList = $article->getVariants();

        if (Registry::getConfig()->getConfigParam('blShowVariantReviews') && count($variantList)) {
            // verifying rights
            foreach ($variantList as $variant) {
                $query .= "or oxreviews.oxobjectid = " . $database->quote($variant->oxarticles__oxid->value) . " ";
            }
        }

        //$sSelect .= "and oxreviews.oxtext".Registry::getLang()->getLanguageTag($this->_iEditLang)." != ''";
        $query .= "and oxreviews.oxlang = '" . $this->_iEditLang . "'";
        $query .= "and oxreviews.oxtext != '' ";

        // all reviews
        $reviewList = oxNew(ListModel::class);
        $reviewList->init("oxreview");
        $reviewList->selectString($query);

        return $reviewList;
    }

    /**
     * Saves article review information changes.
     */
    public function save()
    {
        parent::save();

        $parameters = Registry::getRequest()->getRequestEscapedParameter('editval');
        // checkbox handling
        if (Registry::getConfig()->getConfigParam('blGBModerate') && !isset($parameters['oxreviews__oxactive'])) {
            $parameters['oxreviews__oxactive'] = 0;
        }

        $review = oxNew(Review::class);
        $review->load(Registry::getRequest()->getRequestEscapedParameter('rev_oxid'));
        $review->assign($parameters);
        $review->save();
    }

    /**
     * Deletes selected article review information.
     */
    public function delete()
    {
        $this->resetContentCache();

        $reviewId = Registry::getRequest()->getRequestEscapedParameter('rev_oxid');
        $review = oxNew(Review::class);
        $review->load($reviewId);
        $review->delete();

        // recalculating article average rating
        $rating = oxNew(Rating::class);
        $articleId = $this->getEditObjectId();

        $article = oxNew(Article::class);
        $article->load($articleId);

        //switch database connection to master for the following read/write access.
        DatabaseProvider::getMaster();
        $article->setRatingAverage($rating->getRatingAverage($articleId, 'oxarticle'));
        $article->setRatingCount($rating->getRatingCount($articleId, 'oxarticle'));
        $article->save();
    }
}
