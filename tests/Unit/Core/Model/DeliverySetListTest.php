<?php

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Model;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Model\Basket;
use OxidEsales\EshopCommunity\Application\Model\DeliverySetList;
use OxidEsales\TestingLibrary\UnitTestCase;
use Psr\Log\Test\TestLogger;

class DeliverySetListTest extends UnitTestCase
{
    public function testGetBasketPrice()
    {
        $deliverySetList = new DeliverySetList();

        $oCur = new \stdClass();  // ← Object statt null!
        $oCur->rate = 10;

        $basketMock = $this->getMockBuilder(Basket::class)
            ->onlyMethods(['getPriceForPayment'])
            ->getMock();

        $basketMock->expects($this->once())->method('getPriceForPayment')->willReturn(100);
        $basketPrice = $deliverySetList->getBasketPrice($basketMock, $oCur);

        $this->assertEquals(10, $basketPrice);
    }

    public function testGetBasketPriceWithZeroCur()
    {
        $oCur = new \stdClass();  // ← Object statt null!
        $oCur->rate = 0;

        $deliverySetList = new DeliverySetList();
        $basketMock = $this->getMockBuilder(Basket::class)
            ->onlyMethods(['getPriceForPayment'])
            ->getMock();

        $logger = new TestLogger();
        Registry::set('logger', $logger);

        $basketMock->expects($this->once())->method('getPriceForPayment')->willReturn(100);
        $basketPrice = $deliverySetList->getBasketPrice($basketMock, $oCur);

        $this->assertTrue(
            $logger->hasErrorThatContains(
                'Currency rate is zero or invalid'
            )
        );
        $this->assertEquals(100, $basketPrice);
    }
}
