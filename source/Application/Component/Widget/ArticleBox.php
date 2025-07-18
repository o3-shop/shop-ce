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

namespace OxidEsales\EshopCommunity\Application\Component\Widget;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;

/**
 * Article box widget
 */
class ArticleBox extends \OxidEsales\Eshop\Application\Component\Widget\WidgetController
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * User component used in template.
     *
     * @var array
     */
    protected $_aComponentNames = ['oxcmp_user' => 1, 'oxcmp_basket' => 1, 'oxcmp_cur' => 1];

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sTemplate = 'widget/product/boxproduct.tpl';

    /**
     * Current article
     *
     * @var Article|null
     */
    protected $_oArticle = null;

    /**
     * Returns active category
     *
     * @return null|Category
     */
    public function getActiveCategory()
    {
        $oCategory = Registry::getConfig()->getTopActiveView()->getActiveCategory();
        if ($oCategory) {
            $this->setActiveCategory($oCategory);
        }

        return $this->_oActCategory;
    }

    /**
     * Renders template based on widget type or just use directly passed path of template
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $sWidgetType = $this->getViewParameter('sWidgetType');
        $sListType = $this->getViewParameter('sListType');

        if ($sWidgetType && $sListType) {
            $this->_sTemplate = "widget/" . $sWidgetType . "/" . $sListType . ".tpl";
        }

        $sForceTemplate = $this->getViewParameter('oxwtemplate');
        if ($sForceTemplate) {
            $this->_sTemplate = $sForceTemplate;
        }

        return $this->_sTemplate;
    }

    /**
     * Sets box product
     *
     * @param Article $oArticle Box product
     */
    public function setProduct($oArticle)
    {
        $this->_oArticle = $oArticle;
    }

    /**
     * Get product article
     *
     * @return Article
     * @throws DatabaseConnectionException
     */
    public function getProduct()
    {
        if (is_null($this->_oArticle)) {
            if ($this->getViewParameter('_object')) {
                $oArticle = $this->getViewParameter('_object');
            } else {
                $sAddDynParams = Registry::getConfig()->getTopActiveView()->getAddUrlParams();

                $sAddDynParams = $this->updateDynamicParameters($sAddDynParams);

                $oArticle = $this->getArticleById($this->getViewParameter('anid'));
                $this->addDynParamsToLink($sAddDynParams, $oArticle);
            }

            $this->setProduct($oArticle);
        }

        return $this->_oArticle;
    }

    /**
     * get link of current top view
     *
     * @param int $languageId requested language
     *
     * @return string
     */
    public function getLink($languageId = null)
    {
        return Registry::getConfig()->getTopActiveView()->getLink($languageId);
    }

    /**
     * Returns if VAT is included in price
     *
     * @return bool
     */
    public function isVatIncluded()
    {
        return (bool) $this->getViewParameter("isVatIncluded");
    }

    /**
     * Returns wish list id
     *
     * @return string
     */
    public function getWishId()
    {
        return $this->getViewParameter('owishid');
    }

    /**
     * Returns remove function
     *
     * @return string
     */
    public function getRemoveFunction()
    {
        return $this->getViewParameter('removeFunction');
    }

    /**
     * Returns toBasket function
     *
     * @return string
     */
    public function getToBasketFunction()
    {
        return $this->getViewParameter('toBasketFunction');
    }

    /**
     * Returns if toCart must be disabled
     *
     * @return bool
     */
    public function getDisableToCart()
    {
        return (bool) $this->getViewParameter('blDisableToCart');
    }

    /**
     * Returns list item id with identifier
     *
     * @return string
     */
    public function getIndex()
    {
        return $this->getViewParameter('iIndex');
    }

    /**
     * Returns recommendation id
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return string
     */
    public function getRecommId()
    {
        return $this->getViewParameter('recommid');
    }

    /**
     * Returns iteration number
     *
     * @return string
     */
    public function getIteration()
    {
        return $this->getViewParameter('iIteration');
    }

    /**
     * Returns RSS links
     *
     * @return array|null
     */
    public function getRSSLinks()
    {
        $aRSS = $this->getViewParameter('rsslinks');
        if (!is_array($aRSS)) {
            $aRSS = null;
        }

        return $aRSS;
    }

    /**
     * Returns the answer if main link must be showed
     *
     * @return bool
     */
    public function getShowMainLink()
    {
        return (bool) $this->getViewParameter('showMainLink');
    }

    /**
     * Returns if alternate product exists
     *
     * @return bool
     */
    public function getAltProduct()
    {
        return (bool) $this->getViewParameter('altproduct');
    }

    /**
     * Appends dyn params to url.
     *
     * @param string                                      $sAddDynParams Dyn params
     * @param Article $oArticle      Article
     *
     * @return bool
     * @deprecated underscore prefix violates PSR12, will be renamed to "addDynParamsToLink" in next major
     */
    protected function _addDynParamsToLink($sAddDynParams, $oArticle) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->addDynParamsToLink($sAddDynParams, $oArticle);
    }
    
    /**
     * Appends dyn params to url.
     *
     * @param string                                      $sAddDynParams Dyn params
     * @param Article $oArticle      Article
     *
     * @return bool
     */
    protected function addDynParamsToLink($sAddDynParams, $oArticle)
    {
        $blAddedParams = false;
        if ($sAddDynParams) {
            $blSeo = Registry::getUtils()->seoIsActive();
            if (!$blSeo) {
                // only if seo is off...
                $oArticle->appendStdLink($sAddDynParams);
            }
            $oArticle->appendLink($sAddDynParams);
            $blAddedParams = true;
        }

        return $blAddedParams;
    }

    /**
     * Returns prepared article by id.
     *
     * @param string $sArticleId Article id
     *
     * @return Article
     * @throws DatabaseConnectionException
     * @deprecated underscore prefix violates PSR12, will be renamed to "getArticleById" in next major
     */
    protected function _getArticleById($sArticleId) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getArticleById($sArticleId);
    }

    /**
     * Returns prepared article by id.
     *
     * @param string $sArticleId Article id
     *
     * @return Article
     * @throws DatabaseConnectionException
     */
    protected function getArticleById($sArticleId)
    {
        /** @var Article $oArticle */
        $oArticle = oxNew(Article::class);
        $oArticle->load($sArticleId);
        $iLinkType = $this->getViewParameter('iLinkType');

        if ($this->getViewParameter('inlist')) {
            $oArticle->setInList();
        }
        if ($iLinkType) {
            $oArticle->setLinkType($iLinkType);
        }
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        if ($oRecommList = $this->getActiveRecommList()) {
            $oArticle->text = $oRecommList->getArtDescription($oArticle->getId());
        }
        // END deprecated

        return $oArticle;
    }

    /**
     * @param string $dynamicParameters
     *
     * @return string
     */
    protected function updateDynamicParameters($dynamicParameters)
    {
        return $dynamicParameters;
    }
}
