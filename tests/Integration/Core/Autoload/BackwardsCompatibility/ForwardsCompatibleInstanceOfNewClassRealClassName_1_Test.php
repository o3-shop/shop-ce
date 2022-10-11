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

class ForwardsCompatibleInstanceOfNewClassRealClassName_1_Test extends \OxidEsales\TestingLibrary\UnitTestCase
{

    /**
     * Test the backwards compatibility of class instances created with oxNew and the alias class name
     */
    public function testForwardsCompatibleInstanceOfNewClassRealClassName()
    {
        if ('CE' !== $this->getConfig()->getEdition()) {
            // $this->markTestSkipped(
            //    'This test will fail on Travis and CI as it MUST run in an own PHP process, which is not possible.'
            //);
        }

        $realClassName = \OxidEsales\EshopCommunity\Application\Model\Article::class;
        $unifiedNamespaceClassName = \OxidEsales\Eshop\Application\Model\Article::class;
        $backwardsCompatibleClassAlias = \oxArticle::class;

        $object = new $realClassName();

        $message = 'An object created with new \OxidEsales\EshopCommunity\Application\Model\Article() is not an instance of "\oxArticle::class"';
        $this->assertNotInstanceOf($backwardsCompatibleClassAlias, $object, $message);

        $message = 'An object created with new \OxidEsales\EshopCommunity\Application\Model\Article() is an instance of \OxidEsales\EshopCommunity\Application\Model\Article::class';
        $this->assertInstanceOf($realClassName, $object, $message);

        $message = 'An object created with new \OxidEsales\EshopCommunity\Application\Model\Article() is not an instance of \OxidEsales\Eshop\Application\Model\Article::class';
        $this->assertNotInstanceOf($unifiedNamespaceClassName, $object, $message);
    }
}
