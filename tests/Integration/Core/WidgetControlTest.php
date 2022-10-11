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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core;

use OxidEsales\Eshop\Core\Exception\ObjectException;
use OxidEsales\Eshop\Core\Routing\ControllerClassNameResolver;
use OxidEsales\Eshop\Core\WidgetControl;
use OxidEsales\TestingLibrary\UnitTestCase;

class WidgetControlTest extends UnitTestCase
{
    /**
     * Test checks if exception was thrown. Need to catch this exception so nothing would be logged in exception log file.
     */
    public function testIfDoesNotAllowToInitiateNonWidgetClass()
    {
        $originalDebugMode = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Core\ConfigFile::class)->getVar('iDebug');
        /** Set iDebug to 1, so the exception will be rethrown */
        \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Core\ConfigFile::class)->setVar('iDebug', 1);
        $_SERVER["REQUEST_METHOD"] = 'POST';

        $wasExceptionThrown = false;
        try {
            /** @var WidgetControl $widgetControll */
            $widgetControll = oxNew(WidgetControl::class);
            $nonWidgetClass = (new ControllerClassNameResolver())->getIdByClassName(\OxidEsales\Eshop\Application\Controller\SearchController::class);
            $widgetControll->start($nonWidgetClass);
        } catch (ObjectException $exception) {
            $wasExceptionThrown = true;
        } finally {
            \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Core\ConfigFile::class)->setVar('iDebug', $originalDebugMode);
        }

        $this->assertLoggedException(\OxidEsales\Eshop\Core\Exception\ObjectException::class);
        $this->assertTrue($wasExceptionThrown, 'It was expected, that widget controll will not accept any other class, than widget.');
    }
}
