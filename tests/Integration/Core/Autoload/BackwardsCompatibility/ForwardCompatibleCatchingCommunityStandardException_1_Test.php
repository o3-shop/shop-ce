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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core\Autoload\BackwardsCompatibility;

class ForwardCompatibleCatchingCommunityStandardException_1_Test extends \PHPUnit\Framework\TestCase
{

    /**
     * Try to catch an \oxException when a given Exception is thrown
     *
     * @throws \Exception $exception
     */
    public function testForwardCompatibleCatchingCommunityStandardException()
    {
        $exception = oxNew(\OxidEsales\EshopCommunity\Core\Exception\StandardException::class);
        try {
            throw $exception;
        } catch (\OxidEsales\EshopCommunity\Core\Exception\StandardException $exception) {
            /** If the exception has been caught, the test has passed */
            $this->assertTrue(true, 'The given exception (oxNew(\OxidEsales\EshopCommunity\Core\Exception\StandardException::class)) was caught as \OxidEsales\EshopCommunity\Core\Exception\StandardException');
        } catch (\Exception $exception) {
            /** If the exception has not been caught before, the test has failed */
            $this->fail('The given exception (oxNew(\OxidEsales\EshopCommunity\Core\Exception\StandardException::class)) was not caught as \OxidEsales\EshopCommunity\Core\Exception\StandardException');
        }
    }
}