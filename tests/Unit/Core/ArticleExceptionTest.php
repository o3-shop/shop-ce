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
 * Testing oxArticleException class.
 */
class ArticleExceptionTest extends \OxidEsales\TestingLibrary\UnitTestCase
{

    /**
     * Contains a test object of oxarticleexception
     *
     * @var object
     */
    private $_oTestObject = null;

    /**
     * a mock message
     *
     * @var string
     */
    private $_sMsg = 'Erik was here..';

    /**
     * a mock article number
     *
     * @var string
     */
    private $_sArticle = 'sArticleNumber';

    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->_oTestObject = oxNew('oxArticleException', $this->_sMsg);
        $this->_oTestObject->setArticleNr($this->_sArticle);
        $this->_oTestObject->setProductId($this->_sArticle);
    }

    /**
     * Test set/get product id.
     *
     * @return null
     */
    public function testSetProductIdGetProductId()
    {
        $oTestObject = oxNew('oxArticleException', $this->_sMsg);
        $this->assertNull($oTestObject->getProductId());

        $this->_oTestObject->setProductId('xxx');
        $this->assertEquals('xxx', $this->_oTestObject->getProductId());
    }

    /**
     * Test set type.
     */
    public function testType()
    {
        $this->assertEquals('OxidEsales\Eshop\Core\Exception\ArticleException', get_class($this->_oTestObject));
    }

    /**
     * Test set/get article nr.
     *
     * @return null
     */
    public function testSetGetArticleNr()
    {
        $this->assertEquals($this->_sArticle, $this->_oTestObject->getArticleNr());
    }

    /**
     * Test set string.
     *
     * We check on class name and message only - rest is not checked yet.
     *
     * @return null
     */
    public function testSetString()
    {
        $sStringOut = $this->_oTestObject->getString();
        $this->assertStringContainsString($this->_sMsg, $sStringOut); // Message
        $this->assertStringContainsString('ArticleException', $sStringOut); // Exception class name
        $this->assertStringContainsString($this->_sArticle, $sStringOut); // Article nr
    }

    /**
     * Test get Values.
     *
     * @return null
     */
    public function testGetValues()
    {
        $aRes = $this->_oTestObject->getValues();
        $this->assertArrayHasKey('articleNr', $aRes);
        $this->assertArrayHasKey('productId', $aRes);
        $this->assertTrue($this->_sArticle === $aRes['articleNr']);
        $this->assertTrue($this->_sArticle === $aRes['productId']);
    }

    /**
     * Test type getter.
     */
    public function testGetType()
    {
        $class = 'oxArticleException';
        $exception = oxNew($class);
        $this->assertSame($class, $exception->getType());
    }
}
