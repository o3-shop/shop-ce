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

/**
 * Tests for oxwCategoryTree class
 */
class RatingTest extends \OxidTestCase
{
    /**
     * Testing oxwRating::render()
     *
     * @return null
     */
    public function testRender()
    {
        $oRating = oxNew('oxwRating');
        $this->assertEquals('widget/reviews/rating.tpl', $oRating->render());
    }

    /**
     * Testing oxwRating::getRatingValue()
     *
     * @return null
     */
    public function testGetRatingValue()
    {
        $oRating = oxNew('oxwRating');
        $oRating->setViewParameters(array("dRatingValue" => 2.59));
        $this->assertEquals(2.6, $oRating->getRatingValue());
    }

    /**
     * Testing oxwRating::getRatingCount()
     *
     * @return null
     */
    public function testGetRatingCount()
    {
        $oRating = oxNew('oxwRating');
        $oRating->setViewParameters(array("dRatingCount" => 6));
        $this->assertEquals(6, $oRating->getRatingCount());
    }

    /**
     * Testing oxwRating::canRate()
     *
     * @return null
     */
    public function testCanRate()
    {
        $oRating = oxNew('oxwRating');
        $oRating->setViewParameters(array("blCanRate" => true));
        $this->assertTrue($oRating->canRate());
    }

    /**
     * Testing oxwRating::getArticleId()
     *
     * @return null
     */
    public function testGetArticleNId()
    {
        $oRating = oxNew('oxwRating');
        $oRating->setViewParameters(array('anid' => 'testanid'));
        $this->assertEquals('testanid', $oRating->getArticleNId());
    }

    /**
     * Testing oxwRating::getRateUrl()
     *
     * @return null
     */
    public function testGetRateUrl_RateUrlParamSet_RateUrlValue()
    {
        $oRating = oxNew('oxwRating');
        $oRating->setViewParameters(array("sRateUrl" => "testUrl"));
        $this->assertEquals('testUrl', $oRating->getRateUrl());
    }

    /**
     * Testing oxwRating::getRateUrl()
     *
     * @return null
     */
    public function testGetRateUrl_NoRateUrlParam_Null()
    {
        $oRating = oxNew('oxwRating');
        $this->assertEquals(null, $oRating->getRateUrl());
    }
}
