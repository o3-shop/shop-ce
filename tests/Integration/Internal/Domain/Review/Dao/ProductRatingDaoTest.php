<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Domain\Review\Dao;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\ProductRatingBridge;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge\ProductRatingBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Dao\ProductRatingDao;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\ProductRating;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\ProductRatingService;

class ProductRatingDaoTest extends \PHPUnit\Framework\TestCase
{
    public function testUpdateProductRating()
    {
        $this->createTestProduct();

        $productRating = new ProductRating();
        $productRating
            ->setProductId('testProduct')
            ->setRatingCount(66)
            ->setRatingAverage(3.7);

        $productRatingDao = $this->getProductRatingDao();
        $productRatingDao->update($productRating);

        $this->assertEquals(
            $productRating,
            $productRatingDao->getProductRatingById('testProduct')
        );
    }

    private function createTestProduct()
    {
        $product = oxNew(Article::class);
        $product->setId('testProduct');
        $product->save();
    }

    /**
     * Accessing the dao is difficult, because it is a private service.
     * In newer versions of the Symfony Container (since 4.1) this may be
     * done more elegant.
     *
     * @return ProductRatingDao
     */
    private function getProductRatingDao()
    {
        $bridge = ContainerFactory::getInstance()->getContainer()->get(ProductRatingBridgeInterface::class);
        $serviceProperty = new \ReflectionProperty(ProductRatingBridge::class, 'productRatingService');
        $serviceProperty->setAccessible(true);
        $service = $serviceProperty->getValue($bridge);
        $daoProperty = new \ReflectionProperty(ProductRatingService::class, 'productRatingDao');
        $daoProperty->setAccessible(true);

        return $daoProperty->getValue($service);
    }
}