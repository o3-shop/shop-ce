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

use OxidEsales\Eshop\Application\Model\RssFeed;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererInterface;

/**
 * Shop RSS page.
 */
class RssController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * current rss object
     *
     * @var RssFeed
     */
    protected $_oRss = null;

    /**
     * Current rss channel
     *
     * @var object
     */
    protected $_oChannel = null;

    /**
     * Xml start and end definition
     *
     * @var array
     */
    protected $_aXmlDef = null;

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'widget/rss.tpl';

    /**
     * get RssFeed
     *
     * @return RssFeed
     * @deprecated underscore prefix violates PSR12, will be renamed to "getRssFeed" in next major
     */
    protected function _getRssFeed() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (!$this->_oRss) {
            $this->_oRss = oxNew(RssFeed::class);
        }

        return $this->_oRss;
    }

    /**
     * Renders requested RSS feed
     *
     * Template variables:
     * <b>rss</b>
     */
    public function render()
    {
        parent::render();

        $renderer = $this->getRenderer();
        // TODO: can we move it?
        // #2873: In demoshop for RSS we set php_handling to SMARTY_PHP_PASSTHRU
        // as SMARTY_PHP_REMOVE removes not only php tags, but also xml
        if ($this->getConfig()->isDemoShop()) {
            $renderer->php_handling = SMARTY_PHP_PASSTHRU;
        }

        $this->_aViewData['oxEngineTemplateId'] = $this->getViewId();
        // return rss xml, no further processing
        $sCharset = \OxidEsales\Eshop\Core\Registry::getLang()->translateString("charset");
        \OxidEsales\Eshop\Core\Registry::getUtils()->setHeader("Content-Type: text/xml; charset=" . $sCharset);
        \OxidEsales\Eshop\Core\Registry::getUtils()->showMessageAndExit(
            $this->_processOutput(
                $renderer->renderTemplate($this->_sThisTemplate, $this->_aViewData)
            )
        );
    }

    /**
     * @internal
     *
     * @return TemplateRendererInterface
     */
    private function getRenderer()
    {
        return $this->getContainer()
            ->get(TemplateRendererBridgeInterface::class)
            ->getTemplateRenderer();
    }

    /**
     * Processes xml before outputting to user
     *
     * @param string $sInput input to process
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "processOutput" in next major
     */
    protected function _processOutput($sInput) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return getStr()->recodeEntities($sInput);
    }

    /**
     * getTopShop loads top shop articles to rss
     *
     * @access public
     */
    public function topshop()
    {
        if ($this->getConfig()->getConfigParam('bl_rssTopShop')) {
            $this->_getRssFeed()->loadTopInShop();
        } else {
            error_404_handler();
        }
    }

    /**
     * loads newest shop articles
     *
     * @access public
     */
    public function newarts()
    {
        if ($this->getConfig()->getConfigParam('bl_rssNewest')) {
            $this->_getRssFeed()->loadNewestArticles();
        } else {
            error_404_handler();
        }
    }

    /**
     * loads category articles
     *
     * @access public
     */
    public function catarts()
    {
        if ($this->getConfig()->getConfigParam('bl_rssCategories')) {
            $oCat = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
            if ($oCat->load(\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('cat'))) {
                $this->_getRssFeed()->loadCategoryArticles($oCat);
            }
        } else {
            error_404_handler();
        }
    }

    /**
     * loads search articles
     *
     * @access public
     */
    public function searcharts()
    {
        if ($this->getConfig()->getConfigParam('bl_rssSearch')) {
            $sSearchParameter = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('searchparam', true);
            $sCatId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('searchcnid');
            $sVendorId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('searchvendor');
            $sManufacturerId = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('searchmanufacturer');

            $this->_getRssFeed()->loadSearchArticles($sSearchParameter, $sCatId, $sVendorId, $sManufacturerId);
        } else {
            error_404_handler();
        }
    }

    /**
     * loads recommendation lists
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @access public
     * @return void
     */
    public function recommlists()
    {
        if ($this->getViewConfig()->getShowListmania() && $this->getConfig()->getConfigParam('bl_rssRecommLists')) {
            $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
            if ($oArticle->load(\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('anid'))) {
                $this->_getRssFeed()->loadRecommLists($oArticle);

                return;
            }
        }
        error_404_handler();
    }

    /**
     * loads recommendation list articles
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @access public
     * @return void
     */
    public function recommlistarts()
    {
        if ($this->getConfig()->getConfigParam('bl_rssRecommListArts')) {
            $oRecommList = oxNew(\OxidEsales\Eshop\Application\Model\RecommendationList::class);
            if ($oRecommList->load(\OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('recommid'))) {
                $this->_getRssFeed()->loadRecommListArticles($oRecommList);

                return;
            }
        }
        error_404_handler();
    }

    /**
     * getBargain loads top shop articles to rss
     *
     * @access public
     */
    public function bargain()
    {
        if ($this->getConfig()->getConfigParam('bl_rssBargain')) {
            $this->_getRssFeed()->loadBargain();
        } else {
            error_404_handler();
        }
    }

    /**
     * Template variable getter. Returns rss channel
     *
     * @return object
     */
    public function getChannel()
    {
        if ($this->_oChannel === null) {
            $this->_oChannel = $this->_getRssFeed()->getChannel();
        }

        return $this->_oChannel;
    }

    /**
     * Returns if view should be cached
     *
     * @return bool
     */
    public function getCacheLifeTime()
    {
        return $this->_getRssFeed()->getCacheTtl();
    }
}
