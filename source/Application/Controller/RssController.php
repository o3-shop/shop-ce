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

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\RecommendationList;
use OxidEsales\Eshop\Application\Model\RssFeed;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererInterface;

/**
 * Shop RSS page.
 */
class RssController extends FrontendController
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
        return $this->getRssFeed();
    }

    /**
     * get RssFeed
     *
     * @return RssFeed
     */
    protected function getRssFeed()
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
        // #2873: In demo-shop for RSS we set php_handling to SMARTY_PHP_PASSTHRU
        // as SMARTY_PHP_REMOVE removes not only php tags, but also xml
        if (Registry::getConfig()->isDemoShop()) {
            $renderer->php_handling = SMARTY_PHP_PASSTHRU;
        }

        $this->_aViewData['oxEngineTemplateId'] = $this->getViewId();
        // return rss xml, no further processing
        $sCharset = Registry::getLang()->translateString("charset");
        Registry::getUtils()->setHeader("Content-Type: text/xml; charset=" . $sCharset);
        Registry::getUtils()->showMessageAndExit(
            $this->processOutput(
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
        return $this->processOutput($sInput);
    }

    /**
     * Processes xml before outputting to user
     *
     * @param string $sInput input to process
     *
     * @return string
     */
    protected function processOutput($sInput)
    {
        return Str::getStr()->recodeEntities($sInput);
    }

    /**
     * getTopShop loads top shop articles to rss
     *
     * @access public
     */
    public function topshop()
    {
        if (Registry::getConfig()->getConfigParam('bl_rssTopShop')) {
            $this->getRssFeed()->loadTopInShop();
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
        if (Registry::getConfig()->getConfigParam('bl_rssNewest')) {
            $this->getRssFeed()->loadNewestArticles();
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
        if (Registry::getConfig()->getConfigParam('bl_rssCategories')) {
            $oCat = oxNew(Category::class);
            if ($oCat->load(Registry::getRequest()->getRequestEscapedParameter('cat'))) {
                $this->getRssFeed()->loadCategoryArticles($oCat);
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
        if (Registry::getConfig()->getConfigParam('bl_rssSearch')) {
            $sSearchParameter = Registry::getRequest()->getRequestEscapedParameter('searchparam', true);
            $sCatId = Registry::getRequest()->getRequestEscapedParameter('searchcnid');
            $sVendorId = Registry::getRequest()->getRequestEscapedParameter('searchvendor');
            $sManufacturerId = Registry::getRequest()->getRequestEscapedParameter('searchmanufacturer');

            $this->getRssFeed()->loadSearchArticles($sSearchParameter, $sCatId, $sVendorId, $sManufacturerId);
        } else {
            error_404_handler();
        }
    }

    /**
     * loads recommendation lists
     *
     * @return void
     * @throws DatabaseConnectionException
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @access public
     */
    public function recommlists()
    {
        if ($this->getViewConfig()->getShowListmania() && Registry::getConfig()->getConfigParam('bl_rssRecommLists')) {
            $oArticle = oxNew(Article::class);
            if ($oArticle->load(Registry::getRequest()->getRequestEscapedParameter('anid'))) {
                $this->getRssFeed()->loadRecommLists($oArticle);

                return;
            }
        }
        error_404_handler();
    }

    /**
     * loads recommendation list articles
     *
     * @return void
     * @throws DatabaseConnectionException
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @access public
     */
    public function recommlistarts()
    {
        if (Registry::getConfig()->getConfigParam('bl_rssRecommListArts')) {
            $oRecommList = oxNew(RecommendationList::class);
            if ($oRecommList->load(Registry::getRequest()->getRequestEscapedParameter('recommid'))) {
                $this->getRssFeed()->loadRecommListArticles($oRecommList);

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
        if (Registry::getConfig()->getConfigParam('bl_rssBargain')) {
            $this->getRssFeed()->loadBargain();
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
            $this->_oChannel = $this->getRssFeed()->getChannel();
        }

        return $this->_oChannel;
    }

    /**
     * Returns if view should be cached
     *
     * @return int
     */
    public function getCacheLifeTime()
    {
        return $this->getRssFeed()->getCacheTtl();
    }
}
