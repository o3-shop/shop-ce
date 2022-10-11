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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Component\Widget;

use \oxTestModules;
use RecommList;

/**
 * Tests for oxwRecomm class
 */
class RecommendationTest extends \OxidTestCase
{

    /**
     * Testing oxwRecomm::getSimilarRecommLists()
     *
     * @return null
     */
    public function testGetSimilarRecommLists_empty()
    {
        $aParams["aArticleIds"] = array();
        $oRecomm = oxNew('oxwRecommendation');
        $oRecomm->setViewParameters($aParams);
        $oRecommList = $oRecomm->getSimilarRecommLists();
        $this->assertTrue(!isset($oRecommList), "Should be empty if no articles id given");
    }

    /**
     * Testing oxwRecomm::getSimilarRecommLists()
     *
     * @return null
     */
    public function testGetSimilarRecommLists()
    {
        $oRecommList = $this->getMock(\OxidEsales\Eshop\Application\Model\RecommendationList::class, array("getRecommListsByIds"));
        $oRecommList->expects($this->once())->method("getRecommListsByIds")->with($this->equalTo(array("articleId")))->will($this->returnValue("oxRecommListMock"));
        oxTestModules::addModuleObject('oxrecommlist', $oRecommList);

        $aParams["aArticleIds"] = array("articleId");

        $oRecomm = oxNew('oxwRecommendation');
        $oRecomm->setViewParameters($aParams);

        $this->assertEquals("oxRecommListMock", $oRecomm->getSimilarRecommLists(), "Should try to create RecommList object.");
    }

    /**
     * Testing oxwRecommendation::getRecommList()
     *
     * @return null
     */
    public function testGetRecommList()
    {
        $oRecommList = oxNew('oxwRecommendation');
        $this->assertTrue($oRecommList->getRecommList() instanceof \OxidEsales\EshopCommunity\Application\Controller\RecommListController);
    }
}
