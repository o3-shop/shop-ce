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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Component;

use OxidEsales\Eshop\Application\Component\BasketComponent;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\TestingLibrary\UnitTestCase;

class BasketComponentTest extends UnitTestCase
{
    public function testChangingBasketWhenSessionChallengeValidationNotPassed()
    {
        $this->actAsSearchEngine(false);
        $this->sessionTokenIsCorrect(false);
        $this->initiateBasketChange();

        $this->assertFalse($this->isBasketChanged());
    }

    public function testChangingBasketWhenSessionChallengeValidationPassed()
    {
        $this->actAsSearchEngine(false);
        $this->sessionTokenIsCorrect(true);
        $this->initiateBasketChange();

        $this->assertTrue($this->isBasketChanged());
    }

    public function testChangingBasketWhenIsSearchEngine()
    {
        $this->actAsSearchEngine(true);
        $this->sessionTokenIsCorrect(true);
        $this->initiateBasketChange();

        $this->assertFalse($this->isBasketChanged());
    }

    public function testChangingBasketWhenIsNotSearchEngine()
    {
        $this->actAsSearchEngine(false);
        $this->sessionTokenIsCorrect(true);
        $this->initiateBasketChange();

        $this->assertTrue($this->isBasketChanged());
    }

    private function actAsSearchEngine($isSearchEngine)
    {
        $utilities = $this->getMockBuilder(Utils::class)
            ->setMethods(['isSearchEngine'])->getMock();
        $utilities->method('isSearchEngine')->willReturn($isSearchEngine);
        Registry::set(Utils::class, $utilities);
    }

    private function sessionTokenIsCorrect($isCorrect)
    {
        $session = $this->getMockBuilder(Session::class)
            ->setMethods(['checkSessionChallenge'])->getMock();
        $session->method('checkSessionChallenge')->willReturn($isCorrect);
        Registry::set(Session::class, $session);
    }

    private function initiateBasketChange()
    {
        /** @var \OxidEsales\Eshop\Application\Component\BasketComponent $basketComponent */
        $basketComponent = oxNew(BasketComponent::class);
        $basketComponent->changeBasket(1000, 2);
    }

    /**
     * @return bool
     */
    private function isBasketChanged()
    {
        return isset($_SESSION['aLastcall']['changebasket'][1000]);
    }
}
