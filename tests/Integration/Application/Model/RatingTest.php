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

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Rating;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\TestingLibrary\UnitTestCase;

class RatingTest extends UnitTestCase
{
    public function testUpdateProductRatingOnRatingDelete()
    {
        $this->createTestProduct();
        $this->createTestRatings();

        $rating = oxNew(Rating::class);
        $rating->load('id3');
        $rating->delete();

        $product = oxNew(Article::class);
        $product->load('testId');

        $this->assertEquals(
            2,
            $product->oxarticles__oxratingcnt->value
        );

        $this->assertEquals(
            1.5,
            $product->oxarticles__oxrating->value
        );
    }

    public function testUpdateProductRatingOnRatingDeleteWhenAllRatingsForProductAreDeleted()
    {
        $this->createTestProduct();
        $this->createTestRatings();

        $rating = oxNew(Rating::class);

        $rating->load('id1');
        $rating->delete();

        $rating->load('id2');
        $rating->delete();

        $rating->load('id3');
        $rating->delete();

        $product = oxNew(Article::class);
        $product->load('testId');

        $this->assertEquals(
            0,
            $product->oxarticles__oxratingcnt->value
        );

        $this->assertEquals(
            0,
            $product->oxarticles__oxrating->value
        );
    }

    private function createTestProduct()
    {
        $product = oxNew(Article::class);
        $product->setId('testId');
        $product->oxarticles__oxrating = new Field(2);
        $product->oxarticles__oxratingcnt = new Field(3);
        $product->save();
    }

    private function createTestRatings()
    {
        $rating = oxNew(Rating::class);
        $rating->setId('id1');
        $rating->oxratings__oxobjectid = new Field('testId');
        $rating->oxratings__oxtype = new Field('oxarticle');
        $rating->oxratings__oxrating = new Field(1);
        $rating->save();

        $rating = oxNew(Rating::class);
        $rating->setId('id2');
        $rating->oxratings__oxobjectid = new Field('testId');
        $rating->oxratings__oxtype = new Field('oxarticle');
        $rating->oxratings__oxrating = new Field(2);
        $rating->save();

        $rating = oxNew(Rating::class);
        $rating->setId('id3');
        $rating->oxratings__oxobjectid = new Field('testId');
        $rating->oxratings__oxtype = new Field('oxarticle');
        $rating->oxratings__oxrating = new Field(3);
        $rating->save();
    }
}
