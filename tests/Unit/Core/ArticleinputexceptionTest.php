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
namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

/**
 * Testing \OxidEsales\Eshop\Core\Exception\ArticleInputException class
 */
class ArticleinputexceptionTest extends \OxidEsales\TestingLibrary\UnitTestCase
{

    /**
     * Test set string.
     *
     * We check on class name and message only - rest is not checked yet.
     */
    public function testGetString()
    {
        $msg = 'Erik was here..';
        $testObject = oxNew(\OxidEsales\Eshop\Core\Exception\ArticleInputException::class, $msg);
        $this->assertEquals('OxidEsales\Eshop\Core\Exception\ArticleInputException', get_class($testObject));
        $articleNumber = 'sArticleNumber';
        $testObject->setArticleNr($articleNumber);
        $stringOut = $testObject->getString();
        $this->assertStringContainsString($msg, $stringOut); // Message
        $this->assertStringContainsString('ArticleInputException', $stringOut); // Exception class name
        $this->assertStringContainsString($articleNumber, $stringOut); // Article nr
    }

    /**
     * Test type getter.
     */
    public function testGetType()
    {
        $class = 'oxArticleInputException';
        $exception = oxNew($class);
        $this->assertSame($class, $exception->getType());
    }
}
