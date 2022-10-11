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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Model;

use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Application\Model\Review;
use OxidEsales\Eshop\Application\Model\Rating;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Exception\ReviewAndRatingObjectTypeException;
use OxidEsales\EshopCommunity\Internal\Domain\Review\ViewDataObject\ReviewAndRating;
use OxidEsales\TestingLibrary\UnitTestCase;

class ReviewTest extends UnitTestCase
{
    public function testReviewAndRatingListByUserId()
    {
        $review = oxNew(Review::class);
        $review->setId('id1');
        $review->oxreviews__oxactive = new Field(1);
        $review->oxreviews__oxuserid = new Field('testUser');
        $review->oxreviews__oxobjectid = new Field('xx1');
        $review->oxreviews__oxtype = new Field('oxarticle');
        $review->oxreviews__oxtext = new Field('revtext');
        $review->save();

        $review = oxNew(Review::class);
        $review->setId('id2');
        $review->oxreviews__oxactive = new Field(1);
        $review->oxreviews__oxuserid = new Field('testUser');
        $review->oxreviews__oxobjectid = new Field('xx2');
        $review->oxreviews__oxtype = new Field('oxrecommlist');
        $review->oxreviews__oxtext = new Field('revtext');
        $review->save();

        $rating = oxNew(Rating::class);
        $rating->oxratings__oxuserid = new Field('testUser');
        $rating->oxratings__oxtype = new Field('oxarticle');
        $rating->save();

        $review = oxNew(Review::class);

        $reviewAndRatingList = $review->getReviewAndRatingListByUserId('testUser');

        $this->assertIsArray(
            $reviewAndRatingList
        );

        $this->assertCount(
            3,
            $reviewAndRatingList
        );

        $this->assertContainsOnlyInstancesOf(
            ReviewAndRating::class,
            $reviewAndRatingList
        );
    }

    public function testReviewAndRatingListByUserIdWithWrongRatingType()
    {
        $this->expectException(ReviewAndRatingObjectTypeException::class);

        $rating = oxNew(Rating::class);
        $rating->oxratings__oxuserid = new Field('testUser');
        $rating->oxratings__oxtype = new Field('wrong_type');
        $rating->save();

        $review = oxNew(Review::class);

        $review->getReviewAndRatingListByUserId('testUser');
    }
}
