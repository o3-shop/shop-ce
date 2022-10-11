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

class RecommAddTest extends \OxidTestCase
{

    /**
     * In case product uses alternative template, adding to list mania is impossible (#0001444)
     */
    public function testForUseCase()
    {
        $oProduct = oxNew('oxArticle');
        $oProduct->load("1126");
        $oProduct->oxarticles__oxtemplate->value = 'details_persparam.tpl';

        $oRecomm = $this->getMock(\OxidEsales\Eshop\Application\Controller\RecommendationAddController::class, array("getProduct"));
        $oRecomm->expects($this->any())->method('getProduct')->will($this->returnValue($oProduct));
        $oRecomm->init();

        $oBlankRecomm = oxNew('RecommAdd');
        $this->assertEquals($oBlankRecomm->getTemplateName(), $oRecomm->render());
    }

    /**
     * Getting view values
     */
    public function testGetRecommLists()
    {
        $oUser = $this->getMock(\OxidEsales\Eshop\Application\Model\User::class, array('getUserRecommLists'));
        $oUser->expects($this->once())->method('getUserRecommLists')->will($this->returnValue('testRecommList'));

        $oRecomm = oxNew('RecommAdd');
        $oRecomm->setUser($oUser);
        $this->assertEquals('testRecommList', $oRecomm->getRecommLists('test'));
    }

    /**
     * Test get title.
     */
    public function testGetTitle()
    {
        $oProduct = oxNew('oxArticle');
        $oProduct->oxarticles__oxtitle = new oxField('title');
        $oProduct->oxarticles__oxvarselect = new oxField('select');

        $oView = $this->getMock(\OxidEsales\Eshop\Application\Controller\RecommendationAddController::class, array('getProduct'));
        $oView->expects($this->any())->method('getProduct')->will($this->returnValue($oProduct));

        $this->assertEquals('title select', $oView->getTitle());
    }
}
