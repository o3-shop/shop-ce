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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use \oxField;
use \oxRegistry;

/**
 * Testing news class.
 */
class NewsTest extends \OxidTestCase
{
    public $aNews = array();

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
        // cleaning
        $this->aNews = array();

        $this->aNews[0] = oxNew('oxBase');
        $this->aNews[0]->init('oxnews');
        $this->aNews[0]->setId(1);
        $this->aNews[0]->oxnews__oxshortdesc = new oxField('Test 0', oxField::T_RAW);
        $this->aNews[0]->oxnews__oxactive = new oxField(1, oxField::T_RAW);
        $this->aNews[0]->oxnews__oxdate = new oxField('2007-01-01', oxField::T_RAW);
        $this->aNews[0]->save();

        $this->aNews[1] = oxNew('oxBase');
        $this->aNews[1]->init('oxnews');
        $this->aNews[1]->setId(2);
        $this->aNews[1]->oxnews__oxshortdesc = new oxField('Test 1', oxField::T_RAW);
        $this->aNews[1]->oxnews__oxactive = new oxField(1, oxField::T_RAW);
        $this->aNews[1]->oxnews__oxdate = new oxField('2007-01-02', oxField::T_RAW);
        $this->aNews[1]->save();
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        foreach ($this->aNews as $oNew) {
            $oNew->delete();
        }
        parent::tearDown();
    }

    /**
     * Testing news list loading
     *
     * @return null
     */
    public function testGetNews()
    {
        $oNews = oxNew('news');
        $oNewsList = $oNews->getNews();

        $this->assertEquals(2, $oNewsList->count());

        $oItem = $oNewsList->current();
        $this->assertEquals(2, $oItem->getId());

        $oNewsList->next();
        $oItem = $oNewsList->current();
        $this->assertEquals(1, $oItem->getId());
    }

    public function testRender()
    {
        $n = $this->getMock(\OxidEsales\Eshop\Application\Controller\NewsController::class, array('getNews'));
        $n->expects($this->once())->method('getNews')->will($this->returnValue('newse'));

        $this->assertEquals('page/info/news.tpl', $n->render());
        $this->assertEquals('newse', $n->getNews());
    }

    /**
     * Testing News::getBreadCrumb()
     *
     * @return null
     */
    public function testGetBreadCrumb()
    {
        $oShop = oxNew('oxShop');
        $oShop->oxshops__oxname = new oxField('shop');

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array('getActiveShop'));
        $oConfig->expects($this->any())->method('getActiveShop')->will($this->returnValue($oShop));

        $oNews = $this->getMock(\OxidEsales\Eshop\Application\Controller\NewsController::class, array('getConfig'));
        $oNews->expects($this->any())->method('getConfig')->will($this->returnValue($oConfig));

        $aResult = array();
        $aResults = array();

        $aResult["title"] = oxRegistry::getLang()->translateString('LATEST_NEWS_AND_UPDATES_AT', oxRegistry::getLang()->getBaseLanguage(), false) . ' shop';
        $aResult["link"] = $oNews->getLink();

        $aResults[] = $aResult;

        $this->assertEquals($aResults, $oNews->getBreadCrumb());
    }

    /**
     * Test get list page navigation.
     *
     * @return null
     */
    public function testGetPageNavigation()
    {
        $oObj = $this->getMock(\OxidEsales\Eshop\Application\Controller\NewsController::class, array('generatePageNavigation'));
        $oObj->expects($this->any())->method('generatePageNavigation')->will($this->returnValue("aaa"));
        $this->assertEquals('aaa', $oObj->getPageNavigation());
    }

    /**
     * Test get title.
     */
    public function testGetTitle()
    {
        $oShop = oxNew('oxShop');
        $oShop->oxshops__oxname = new oxField('shop');

        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array('getActiveShop'));
        $oConfig->expects($this->any())->method('getActiveShop')->will($this->returnValue($oShop));

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\NewsController::class, array('getConfig'));
        $oView->expects($this->any())->method('getConfig')->will($this->returnValue($oConfig));

        $this->assertEquals(oxRegistry::getLang()->translateString('LATEST_NEWS_AND_UPDATES_AT', oxRegistry::getLang()->getBaseLanguage(), false) . ' shop', $oView->getTitle());
    }
}
