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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use \oxField;
use OxidEsales\Eshop\Application\Model\Order;

class OrderarticlelistTest extends \OxidTestCase
{
    protected $_oOrderArticle = null;

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
        $oOrder = oxNew(Order::class);
        $oOrder->setId('_testOrderId');
        $oOrder->oxorder__oxuserid = new oxField('oxdefaultadmin', oxField::T_RAW);
        $oOrder->save();

        $this->_oOrderArticle = oxNew('oxorderarticle');
        $this->_oOrderArticle->setId('_testOrderArticleId');
        $this->_oOrderArticle->oxorderarticles__oxartid = new oxField('_testArticleId', oxField::T_RAW);
        $this->_oOrderArticle->oxorderarticles__oxorderid = new oxField($oOrder->getId());
        $this->_oOrderArticle->save();

        $oArticle = oxNew('oxArticle');
        $oArticle->setId('_testArticleId');
        $oArticle->oxarticles__oxtitle = new oxField('testArticleTitle', oxField::T_RAW);
        $oArticle->oxarticles__oxactive = new oxField('1', oxField::T_RAW);
        $oArticle->oxarticles__oxstock = new oxField('10', oxField::T_RAW);
        $oArticle->oxarticles__oxshopid = new oxField($this->getConfig()->getShopId(), oxField::T_RAW);

        $oArticle->save();
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxorderarticles');
        $this->cleanUpTable('oxorder');
        $this->cleanUpTable('oxarticles');

        parent::tearDown();
    }

    /*
     * Test loading order articles for user
     */
    public function testLoadOrderArticlesForUser()
    {
        $oOrderArticleList = oxNew('oxorderarticlelist');
        $oOrderArticleList->loadOrderArticlesForUser('oxdefaultadmin');
        $this->assertEquals(1, $oOrderArticleList->count());
        $oOrderArticle = $oOrderArticleList->current();
        $this->assertEquals('_testOrderArticleId', $oOrderArticle->getId());
    }

    /*
     * Test loading order articles, if user is not set
     */
    public function testLoadOrderArticlesForUserIfUserIsNotSet()
    {
        $oOrderArticleList = oxNew('oxorderarticlelist');
        $oOrderArticleList->loadOrderArticlesForUser(null);
        $this->assertEquals(0, $oOrderArticleList->count());
    }
}
