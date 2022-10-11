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
 * Tests for oxwReview class
 */
class ReviewTest extends \OxidTestCase
{
    /**
     * Testing oxwReview::getReviewType()
     *
     * @return null
     */
    public function testGetReviewTypeLowerCase()
    {
        $oReviewWidget = oxNew('oxwReview');
        $oReviewWidget->setViewParameters(array('type' => 'testreviewtype'));
        $this->assertEquals('testreviewtype', $oReviewWidget->getReviewType());
    }

    /**
     * Testing oxwReview::getReviewType()
     *
     * @return null
     */
    public function testGetReviewTypeUpperCase()
    {
        $oReviewWidget = oxNew('oxwReview');
        $oReviewWidget->setViewParameters(array('type' => 'TESTREVIEWTYPE'));
        $this->assertEquals('testreviewtype', $oReviewWidget->getReviewType());
    }

    /**
     * Testing oxwReview::getArticleId()
     *
     * @return null
     */
    public function testGetArticleId()
    {
        $oReviewWidget = oxNew('oxwReview');
        $oReviewWidget->setViewParameters(array('aid' => 'testaid'));
        $this->assertEquals('testaid', $oReviewWidget->getArticleId());
    }

    /**
     * Testing oxwReview::getArticleId()
     *
     * @return null
     */
    public function testGetArticleNId()
    {
        $oReviewWidget = oxNew('oxwReview');
        $oReviewWidget->setViewParameters(array('anid' => 'testanid'));
        $this->assertEquals('testanid', $oReviewWidget->getArticleNId());
    }

    /**
     * Testing oxwReview::getRecommListId()
     *
     * @return null
     */
    public function testGetRecommListId()
    {
        $oReviewWidget = oxNew('oxwReview');
        $oReviewWidget->setViewParameters(array('recommid' => 'testrecommid'));
        $this->assertEquals('testrecommid', $oReviewWidget->getRecommListId());
    }

    /**
     * Testing oxwReview::canRate()
     *
     * @return null
     */
    public function testCanRate()
    {
        $oReviewWidget = oxNew('oxwReview');
        $oReviewWidget->setViewParameters(array('canrate' => 'testcanrate'));
        $this->assertEquals('testcanrate', $oReviewWidget->canRate());
    }

    /**
     * Testing oxwReview::getReviewUserHash()
     *
     * @return null
     */
    public function testGetReviewUserHash()
    {
        $oReviewWidget = oxNew('oxwReview');
        $oReviewWidget->setViewParameters(array('reviewuserhash' => 'testreviewuserhash'));
        $this->assertEquals('testreviewuserhash', $oReviewWidget->getReviewUserHash());
    }
}
