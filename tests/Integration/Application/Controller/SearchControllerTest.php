<?php

declare(strict_types=1);

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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Controller;

use OxidEsales\EshopCommunity\Application\Controller\SearchController;
use OxidEsales\EshopCommunity\Application\Model\Article;
use OxidEsales\EshopCommunity\Core\Field;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;

final class SearchControllerTest extends UnitTestCase
{
    private $productTitle1 = '1000';
    private $productid1 = 'seacharticle1000';
    private $productTitle2 = '1001';
    private $productid2 = 'seacharticle1001';

    protected function setUp(): void
    {
        parent::setUp();

        $product1 = oxNew(Article::class);
        $product1->setId($this->productid1);
        $product1->oxarticles__oxtitle = new Field($this->productTitle1);
        $product1->oxarticles__oxsearchkeys = new Field($this->productTitle1);
        $product1->save();

        $product2 = oxNew(Article::class);
        $product2->setId($this->productid2);
        $product2->oxarticles__oxtitle = new Field($this->productTitle2);
        $product2->oxarticles__oxsearchkeys = new Field($this->productTitle2);
        $product2->save();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $delete = oxNew(Article::class);
        $delete->delete($this->productid1);
        $delete->delete($this->productid2);
    }

    public function testSearchAnd(): void
    {
        Registry::getConfig()->setConfigParam('blSearchUseAND', true);

        $this->setRequestParameter('searchparam', $this->productTitle1 . ' ' . $this->productTitle2);

        $searchController = oxNew(SearchController::class);
        $searchController->init();

        $this->assertEquals(0, ($searchController->getArticleList())->count());

        $this->setRequestParameter('searchparam', $this->productTitle1);
        $searchController->init();

        $articleList = $searchController->getArticleList();

        $this->assertEquals(1, ($searchController->getArticleList())->count());
        $this->assertEquals($this->productid1, $articleList->current()->getId());
    }

    public function testSearchOr(): void
    {
        Registry::getConfig()->setConfigParam('blSearchUseAND', false);

        $this->setRequestParameter('searchparam', $this->productTitle1 . ' ' . $this->productTitle2);

        $searchController = oxNew(SearchController::class);
        $searchController->init();

        $articleList = $searchController->getArticleList();
        $this->assertEquals(2, $articleList->count());

        $articleArray = $articleList->getArray();

        $this->assertTrue(
            array_key_exists(
                $this->productid1,
                $articleArray
            )
        );
        $this->assertTrue(
            array_key_exists(
                $this->productid2,
                $articleArray
            )
        );
    }
}
