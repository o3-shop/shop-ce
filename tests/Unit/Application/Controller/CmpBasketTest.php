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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller;

use oxRegistry;
use oxTestModules;
use stdClass;

class CmpBasketTest extends \OxidTestCase
{
    public function testToBasketReturnsNull()
    {
        /** @var oxcmp_basket|PHPUnit\Framework\MockObject\MockObject $o */
        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getItems']);
        $o->expects($this->once())->method('getItems')->will($this->returnValue(false));

        oxTestModules::addFunction('oxUtils', 'isSearchEngine', '{return true;}');
        $this->assertSame(null, $o->tobasket());
        oxTestModules::addFunction('oxUtils', 'isSearchEngine', '{return false;}');
        $this->assertSame(null, $o->tobasket());
    }

    public function testToBasketAddProducts()
    {
        $this->getConfig()->setConfigParam('iNewBasketItemMessage', '2');

        $aProducts = [
            'sProductId' => [
                'am'           => 10,
                'sel'          => null,
                'persparam'    => null,
                'override'     => 0,
                'basketitemid' => '',
            ],
        ];

        /** @var oxBasketItem|PHPUnit\Framework\MockObject\MockObject $oBItem */
        $oBItem = $this->getMock(\OxidEsales\Eshop\Application\Model\BasketItem::class, ['getTitle', 'getProductId', 'getAmount', 'getdBundledAmount']);
        $oBItem->expects($this->once())->method('getTitle')->will($this->returnValue('ret:getTitle'));
        $oBItem->expects($this->once())->method('getProductId')->will($this->returnValue('ret:getProductId'));
        $oBItem->expects($this->once())->method('getAmount')->will($this->returnValue('ret:getAmount'));
        $oBItem->expects($this->once())->method('getdBundledAmount')->will($this->returnValue('ret:getdBundledAmount'));

        /** @var oxcmp_basket|PHPUnit\Framework\MockObject\MockObject $o */
        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getItems', 'setLastCallFnc', '_addItems']);
        $o->expects($this->once())->method('getItems')->will($this->returnValue($aProducts));
        $o->expects($this->once())->method('setLastCallFnc')->with($this->equalTo('tobasket'))->will($this->returnValue(null));
        $o->expects($this->once())->method('_addItems')->with($this->equalTo($aProducts))->will($this->returnValue($oBItem));

        $this->assertEquals('start?', $o->tobasket());

        $oNewItem = $this->getSessionParam('_newitem');
        $this->assertTrue($oNewItem instanceof stdClass);
        $this->assertEquals('ret:getTitle', $oNewItem->sTitle);
        $this->assertEquals('ret:getProductId', $oNewItem->sId);
        $this->assertEquals('ret:getAmount', $oNewItem->dAmount);
        $this->assertEquals('ret:getdBundledAmount', $oNewItem->dBundledAmount);
    }

    public function testToBasketAddProductsNoBasketMsgAndRedirect()
    {
        $this->getConfig()->setConfigParam('iNewBasketItemMessage', '0');

        $aProducts = [
            'sProductId' => [
                'am'           => 10,
                'sel'          => null,
                'persparam'    => null,
                'override'     => 0,
                'basketitemid' => '',
            ],
        ];

        /** @var oxBasketItem|PHPUnit\Framework\MockObject\MockObject $oBItem */
        $oBItem = $this->getMock(\OxidEsales\Eshop\Application\Model\BasketItem::class, ['getTitle', 'getProductId', 'getAmount', 'getdBundledAmount']);
        $oBItem->expects($this->never())->method('getTitle')->will($this->returnValue('ret:getTitle'));
        $oBItem->expects($this->never())->method('getProductId')->will($this->returnValue('ret:getProductId'));
        $oBItem->expects($this->never())->method('getAmount')->will($this->returnValue('ret:getAmount'));
        $oBItem->expects($this->never())->method('getdBundledAmount')->will($this->returnValue('ret:getdBundledAmount'));

        /** @var oxcmp_basket|PHPUnit\Framework\MockObject\MockObject $o */
        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getItems', 'setLastCallFnc', '_addItems', 'getRedirectUrl']);
        $o->expects($this->once())->method('getItems')->will($this->returnValue($aProducts));
        $o->expects($this->once())->method('setLastCallFnc')->with($this->equalTo('tobasket'))->will($this->returnValue(null));
        $o->expects($this->once())->method('_addItems')->with($this->equalTo($aProducts))->will($this->returnValue($oBItem));
        $o->expects($this->once())->method('getRedirectUrl')->will($this->returnValue('new url'));

        $this->assertEquals('new url', $o->tobasket());

        $oNewItem = oxRegistry::getSession()->getVariable('_newitem');
        $this->assertSame(null, $oNewItem);
    }

    public function testChangeBasketSearchEngine()
    {
        oxRegistry::getUtils()->setSearchEngine(true);

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['_getItems']);
        $o->expects($this->never())->method('_getItems');

        $this->assertSame(null, $o->changebasket());
    }

    public function testChangeBasketTakesParamsFromArgsGetItemsNull()
    {
        $this->prepareSessionChallengeToken();

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getItems', 'getSession']);
        $o->expects($this->once())->method('getItems')
            ->with(
                $this->equalTo('abc'),
                $this->equalTo(10),
                $this->equalTo('sel'),
                $this->equalTo('persparam'),
                $this->equalTo('override')
            )->will($this->returnValue(null));
        $o->expects($this->never())->method('getSession');

        $this->assertSame(null, $o->changebasket('abc', 10, 'sel', 'persparam', 'override'));
    }

    public function testChangeBasketTakesParamsFromArgs()
    {
        $this->prepareSessionChallengeToken();

        $aProducts = [
            'sProductId' => [
                'am'           => 10,
                'sel'          => null,
                'persparam'    => null,
                'override'     => 0,
                'basketitemid' => '',
            ],
        ];
        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['onUpdate']);
        $oBasket->expects($this->once())->method('onUpdate')->will($this->returnValue(null));
        \OxidEsales\Eshop\Core\Registry::getSession()->setBasket($oBasket);

        $oBItem = $this->getMock(\OxidEsales\Eshop\Application\Model\BasketItem::class, ['getTitle', 'getProductId', 'getAmount', 'getdBundledAmount']);
        $oBItem->expects($this->never())->method('getTitle')->will($this->returnValue('ret:getTitle'));
        $oBItem->expects($this->never())->method('getProductId')->will($this->returnValue('ret:getProductId'));
        $oBItem->expects($this->never())->method('getAmount')->will($this->returnValue('ret:getAmount'));
        $oBItem->expects($this->never())->method('getdBundledAmount')->will($this->returnValue('ret:getdBundledAmount'));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getItems', 'setLastCallFnc', '_addItems']);
        $o->expects($this->once())->method('getItems')
            ->with(
                $this->equalTo('abc'),
                $this->equalTo(11),
                $this->equalTo('sel'),
                $this->equalTo('persparam'),
                $this->equalTo('override')
            )->will($this->returnValue($aProducts));
        $o->expects($this->once())->method('setLastCallFnc')->with($this->equalTo('changebasket'))->will($this->returnValue(null));
        $o->expects($this->once())->method('_addItems')->with($this->equalTo($aProducts))->will($this->returnValue($oBItem));

        $this->assertSame(null, $o->changebasket('abc', 11, 'sel', 'persparam', 'override'));
    }

    public function testChangeBasketTakesParamsFromRequestArtByBindex()
    {
        $this->prepareSessionChallengeToken();

        $oArt = $this->getMock(\OxidEsales\Eshop\Application\Model\Article::class, ['getProductId']);
        $oArt->expects($this->once())->method('getProductId')->will($this->returnValue('b:artid'));
        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['getContents']);
        $oBasket->expects($this->once())->method('getContents')->will($this->returnValue(['b:bindex' => $oArt]));
        \OxidEsales\Eshop\Core\Registry::getSession()->setBasket($oBasket);

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getItems']);
        $o->expects($this->once())->method('getItems')
            ->with(
                $this->equalTo('b:artid'),
                $this->equalTo('b:am'),
                $this->equalTo('b:sel'),
                $this->equalTo('b:persparam'),
                $this->equalTo(true)
            )->will($this->returnValue(null));

        $this->setRequestParameter('bindex', 'b:bindex');
        $this->setRequestParameter('am', 'b:am');
        $this->setRequestParameter('sel', 'b:sel');
        $this->setRequestParameter('persparam', 'b:persparam');
        $this->assertSame(null, $o->changebasket());
    }

    public function testChangeBasketTakesParamsFromRequestArtByAid()
    {
        $this->prepareSessionChallengeToken();

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getItems', 'getSession']);
        $o->expects($this->once())->method('getItems')
            ->with(
                $this->equalTo('b:artid'),
                $this->equalTo('b:am'),
                $this->equalTo('b:sel'),
                $this->equalTo('b:persparam'),
                $this->equalTo(true)
            )->will($this->returnValue(null));
        $o->expects($this->never())->method('getSession')->will($this->returnValue($oSession));

        $this->setRequestParameter('aid', 'b:artid');
        $this->setRequestParameter('am', 'b:am');
        $this->setRequestParameter('sel', 'b:sel');
        $this->setRequestParameter('persparam', 'b:persparam');
        $this->assertSame(null, $o->changebasket());
    }

    public function testGetRedirectUrl()
    {
        foreach ([
                     'cnid', // category id
                     'mnid', // manufacturer id
                     'anid', // active article id
                     'tpl', // spec. template
                     'listtype', // list type
                     'searchcnid', // search category
                     'searchvendor', // search vendor
                     'searchmanufacturer', // search manufacturer
                     'searchrecomm', // search recomendation
                     'recommid', // recomm. list id
                 ] as $key) {
            $this->setRequestParameter($key, 'value:' . $key . ':v');
        }

        $this->setRequestParameter('cl', 'cla');
        $this->setRequestParameter('searchparam', 'search&&a');
        $this->setRequestParameter('pgNr', 123);

        $o = oxNew(\OxidEsales\Eshop\Application\Component\BasketComponent::class);

        $this->getConfig()->setConfigParam('iNewBasketItemMessage', 0);
        $this->assertEquals('cla?cnid=value:cnid:v&mnid=value:mnid:v&anid=value:anid:v&tpl=value:tpl:v&listtype=value:listtype:v&searchcnid=value:searchcnid:v&searchvendor=value:searchvendor:v&searchmanufacturer=value:searchmanufacturer:v&searchrecomm=value:searchrecomm:v&recommid=value:recommid:v&searchparam=search%26%26a&pgNr=123&', $o->UNITgetRedirectUrl());

        $this->setRequestParameter('cl', null);
        $this->setRequestParameter('pgNr', 'a123');
        $this->getConfig()->setConfigParam('iNewBasketItemMessage', 0);
        $this->assertEquals('start?cnid=value:cnid:v&mnid=value:mnid:v&anid=value:anid:v&tpl=value:tpl:v&listtype=value:listtype:v&searchcnid=value:searchcnid:v&searchvendor=value:searchvendor:v&searchmanufacturer=value:searchmanufacturer:v&searchrecomm=value:searchrecomm:v&recommid=value:recommid:v&searchparam=search%26%26a&', $o->UNITgetRedirectUrl());

        $this->assertEquals(null, oxRegistry::getSession()->getVariable('_backtoshop'));

        $this->setRequestParameter('pgNr', '0');
        $this->getConfig()->setConfigParam('iNewBasketItemMessage', 3);
        $this->assertEquals('basket?cnid=value:cnid:v&mnid=value:mnid:v&anid=value:anid:v&tpl=value:tpl:v&listtype=value:listtype:v&searchcnid=value:searchcnid:v&searchvendor=value:searchvendor:v&searchmanufacturer=value:searchmanufacturer:v&searchrecomm=value:searchrecomm:v&recommid=value:recommid:v&searchparam=search%26%26a&', $o->UNITgetRedirectUrl());
        $this->assertEquals('start?cnid=value:cnid:v&mnid=value:mnid:v&anid=value:anid:v&tpl=value:tpl:v&listtype=value:listtype:v&searchcnid=value:searchcnid:v&searchvendor=value:searchvendor:v&searchmanufacturer=value:searchmanufacturer:v&searchrecomm=value:searchrecomm:v&recommid=value:recommid:v&searchparam=search%26%26a&', oxRegistry::getSession()->getVariable('_backtoshop'));
    }

    public function testGetItemsFromArgs()
    {
        $o = oxNew('oxcmp_basket');
        $this->assertEquals(
            [
            'abc' => [
                'am'           => 10,
                'sel'          => 'sel',
                'persparam'    => 'persparam',
                'override'     => 'override',
                'basketitemid' => '',
            ],
            ],
            $o->UNITgetItems('abc', 10, 'sel', 'persparam', 'override')
        );
    }

    public function testGetItemsFromArgsEmpty()
    {
        $o = oxNew('oxcmp_basket');
        $this->assertEquals(false, $o->UNITgetItems('', 10, 'sel', 'persparam', 'override'));
    }

    public function testGetItemsFromArgsRm()
    {
        $this->setRequestParameter(
            'aproducts',
            [
                              'abc' => [
                                  'am'           => 10,
                                  'sel'          => 'sel',
                                  'persparam'    => 'persparam',
                                  'override'     => 'override',
                                  'basketitemid' => '',
                                  'remove'       => 1,
                              ],
                         ]
        );
        $this->setRequestParameter('removeBtn', 1);
        $o = oxNew('oxcmp_basket');
        $this->assertEquals(
            [
                 'abc' => [
                     'am'           => 0,
                     'sel'          => 'sel',
                     'persparam'    => 'persparam',
                     'override'     => 'override',
                     'basketitemid' => '',
                     'remove'       => 1,
                 ],
            ],
            $o->UNITgetItems('', 10, 'sel', 'persparam', 'override')
        );
    }

    public function testGetItemsFromRequest()
    {
        $this->setRequestParameter('aid', 'b:artid');
        $this->setRequestParameter('anid', 'b:artidn');
        $this->setRequestParameter('am', 'b:am');
        $this->setRequestParameter('sel', 'b:sel');
        $this->setRequestParameter('persparam', ['details' => 'b:persparam']);
        $this->setRequestParameter('bindex', 'bindex');

        $o = oxNew('oxcmp_basket');
        $this->assertEquals(
            [
            'b:artid' => [
                'am'           => 'b:am',
                'sel'          => 'b:sel',
                'persparam'    => ['details' => 'b:persparam'],
                'override'     => false,
                'basketitemid' => 'bindex',
            ],
            ],
            $o->UNITgetItems()
        );

        $this->setRequestParameter('persparam', 'b:persparam');
        $this->assertSame(
            [
            'b:artid' => [
                'am'           => 'b:am',
                'sel'          => 'b:sel',
                'persparam'    => null,
                'override'     => false,
                'basketitemid' => 'bindex',
            ],
            ],
            $o->UNITgetItems(),
            '"Details" field in persparams is mandatory'
        );
    }

    public function testGetItemsFromRequestRemoveBtn()
    {
        $this->setRequestParameter('removeBtn', '1');
        $this->setRequestParameter('aid', 'b:artid');
        $this->setRequestParameter('anid', 'b:artidn');
        $this->setRequestParameter('am', 'b:am');
        $this->setRequestParameter('sel', 'b:sel');
        $this->setRequestParameter('persparam', 'b:persparam');
        $this->setRequestParameter('bindex', 'bindex');

        $o = oxNew('oxcmp_basket');
        $this->assertEquals(
            [],
            $o->UNITgetItems()
        );
    }

    public function testAddItems()
    {
        $this->markTestSkipped('Bug: get null back');
        $oBasketItem = $this->getMock(\OxidEsales\Eshop\Application\Model\BasketItem::class, ['getAmount']);
        $oBasketItem->expects($this->any())->method('getAmount')->will($this->returnValue(12));
        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['getBasketSummary', 'addToBasket']);
        $oBasket->expects($this->at(1))->method('addToBasket')
            ->with(
                $this->equalTo('a_aid'),
                $this->equalTo('a_am'),
                $this->equalTo('a_sel'),
                $this->equalTo(['details' => 'a_persparam']),
                $this->equalTo('a_override'),
                $this->equalTo(true),
                $this->equalTo('a_basketitemid')
            )->will($this->returnValue($oBasketItem));
        $oBasket->expects($this->at(2))->method('addToBasket')
            ->with(
                $this->equalTo('b_aid'),
                $this->equalTo('b_am'),
                $this->equalTo('b_sel'),
                $this->equalTo(['details' => 'b_persparam']),
                $this->equalTo('b_override'),
                $this->equalTo(true),
                $this->equalTo('b_basketitemid')
            )->will($this->returnValue($oBasketItem));
        $oBasket->expects($this->any())->method('getBasketSummary')->will($this->returnValue(null));
        $oSession = $this->getMock(\OxidEsales\Eshop\Core\Session::class, ['getBasket']);
        $oSession->expects($this->once())->method('getBasket')->will($this->returnValue($oBasket));

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getSession']);
        $o->expects($this->once())->method('getSession')->will($this->returnValue($oSession));

        $this->assertEquals(
            $oBasketItem,
            $o->UNITaddItems(
                [
                     [
                         'aid'          => 'a_aid',
                         'am'           => 'a_am',
                         'sel'          => 'a_sel',
                         'persparam'    => ['details' => 'a_persparam'],
                         'override'     => 'a_override',
                         'bundle'       => 'a_bundle',
                         'basketitemid' => 'a_basketitemid',
                     ],
                     [
                         'aid'          => 'b_aid',
                         'am'           => 'b_am',
                         'sel'          => 'b_sel',
                         'persparam'    => ['details' => 'b_persparam'],
                         'override'     => 'b_override',
                         'bundle'       => 'b_bundle',
                         'basketitemid' => 'b_basketitemid',
                     ],
                ]
            )
        );
    }

    public function testAddItemsOutOfStockException()
    {
        $oException = $this->getMock(\OxidEsales\Eshop\Core\Exception\OutOfStockException::class, ['setDestination']);
        $oException->expects($this->once())->method('setDestination')->with($this->equalTo('Errors:a'))->will($this->returnValue(null));

        $oUtilsView = $this->getMock(\OxidEsales\Eshop\Core\UtilsView::class, ['addErrorToDisplay']);
        $oUtilsView->expects($this->once())->method('addErrorToDisplay')
            ->with(
                $this->equalTo($oException),
                $this->equalTo(false),
                $this->equalTo(true),
                $this->equalTo('Errors:a')
            );

        oxTestModules::addModuleObject('oxUtilsView', $oUtilsView);

        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['addToBasket', 'getBasketSummary']);
        $oBasket->expects($this->once())->method('addToBasket')
            ->will($this->throwException($oException));
        $oBasket->expects($this->any())->method('getBasketSummary')->will($this->returnValue((object) ['aArticles' => ['b_aid' => 15]]));
        \OxidEsales\Eshop\Core\Registry::getSession()->setBasket($oBasket);

        $oView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['getErrorDestination']);
        $oView->expects($this->once())->method('getErrorDestination')->will($this->returnValue('Errors:a'));
        $this->getConfig()->setActiveView($oView);

        $o = oxNew(\OxidEsales\Eshop\Application\Component\BasketComponent::class);

        $this->assertEquals(
            null,
            $o->UNITaddItems(
                [
                     [],
                ]
            )
        );
    }

    public function testAddItemsOutOfStockExceptionNoErrorPlace()
    {
        $this->getConfig()->setConfigParam('iNewBasketItemMessage', 2);

        $oException = $this->getMock(\OxidEsales\Eshop\Core\Exception\OutOfStockException::class, ['setDestination']);
        $oException->expects($this->once())->method('setDestination')->with($this->equalTo(''))->will($this->returnValue(null));

        $oUtilsView = $this->getMock(\OxidEsales\Eshop\Core\UtilsView::class, ['addErrorToDisplay']);
        $oUtilsView->expects($this->once())->method('addErrorToDisplay')
            ->with(
                $this->equalTo($oException),
                $this->equalTo(false),
                $this->equalTo(true),
                $this->equalTo('popup')
            );
        oxTestModules::addModuleObject('oxUtilsView', $oUtilsView);

        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['addToBasket', 'getBasketSummary']);
        $oBasket->expects($this->once())->method('addToBasket')
            ->will($this->throwException($oException));
        $oBasket->expects($this->any())->method('getBasketSummary')->will($this->returnValue((object) ['aArticles' => ['b_aid' => 15]]));
        \OxidEsales\Eshop\Core\Registry::getSession()->setBasket($oBasket);

        $oView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['getErrorDestination']);
        $oView->expects($this->once())->method('getErrorDestination')->will($this->returnValue(''));
        $this->getConfig()->setActiveView($oView);

        $o = oxNew(\OxidEsales\Eshop\Application\Component\BasketComponent::class);

        $this->assertEquals(
            null,
            $o->UNITaddItems(
                [
                     [],
                ]
            )
        );
    }

    public function testAddItemsArticleInputException()
    {
        $oException = $this->getMock(\OxidEsales\Eshop\Core\Exception\ArticleInputException::class, ['setDestination']);
        $oException->expects($this->once())->method('setDestination')->with($this->equalTo('Errors:a'))->will($this->returnValue(null));

        $oUtilsView = $this->getMock(\OxidEsales\Eshop\Core\UtilsView::class, ['addErrorToDisplay']);
        $oUtilsView->expects($this->once())->method('addErrorToDisplay')
            ->with(
                $this->equalTo($oException),
                $this->equalTo(false),
                $this->equalTo(true),
                $this->equalTo('Errors:a')
            );
        oxTestModules::addModuleObject('oxUtilsView', $oUtilsView);

        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['addToBasket', 'getBasketSummary']);
        $oBasket->expects($this->once())->method('addToBasket')
            ->will($this->throwException($oException));
        $oBasket->expects($this->any())->method('getBasketSummary')->will($this->returnValue((object) ['aArticles' => ['b_aid' => 15]]));
        \OxidEsales\Eshop\Core\Registry::getSession()->setBasket($oBasket);

        $oView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['getErrorDestination']);
        $oView->expects($this->once())->method('getErrorDestination')->will($this->returnValue('Errors:a'));
        $this->getConfig()->setActiveView($oView);

        $o = oxNew(\OxidEsales\Eshop\Application\Component\BasketComponent::class);

        $this->assertEquals(
            null,
            $o->UNITaddItems(
                [
                     [],
                ]
            )
        );
    }

    public function testAddItemsNoArticleException()
    {
        $oException = $this->getMock(\OxidEsales\Eshop\Core\Exception\NoArticleException::class, ['setDestination']);
        $oException->expects($this->never())->method('setDestination');

        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['addToBasket', 'getBasketSummary']);
        $oBasket->expects($this->once())->method('addToBasket')
            ->will($this->throwException($oException));
        $oBasket->expects($this->any())->method('getBasketSummary')->will($this->returnValue((object) ['aArticles' => ['b_aid' => 15]]));
        \OxidEsales\Eshop\Core\Registry::getSession()->setBasket($oBasket);

        $oView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['getErrorDestination']);
        $oView->expects($this->once())->method('getErrorDestination')->will($this->returnValue('Errors:a'));
        $this->getConfig()->setActiveView($oView);

        $o = oxNew(\OxidEsales\Eshop\Application\Component\BasketComponent::class);

        $this->assertEquals(
            null,
            $o->UNITaddItems(
                [
                     [],
                ]
            )
        );
    }

    // #2172: oxcmp_basket::tobasket sets wrong article amount to _setLastCall
    public function testAddItemsIfAmountChanges()
    {
        $oView = $this->getMock(\OxidEsales\Eshop\Core\Controller\BaseController::class, ['getErrorDestination']);
        $oView->expects($this->any())->method('getErrorDestination')->will($this->returnValue(''));
        $this->getConfig()->setActiveView($oView);

        $aBasketInfo = (object) [
            'aArticles' => ['a_aid' => 5],
        ];
        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['getBasketSummary', 'addToBasket']);
        $oBasket->expects($this->once())->method('addToBasket')
            ->with(
                $this->equalTo('a_aid'),
                $this->equalTo(10),
                $this->equalTo('a_sel'),
                $this->equalTo(['details' => 'a_persparam']),
                $this->equalTo('a_override'),
                $this->equalTo(true),
                $this->equalTo('a_basketitemid')
            )->will($this->returnValue(null));
        $oBasket->expects($this->any())->method('getBasketSummary')->will($this->returnValue($aBasketInfo));
        \OxidEsales\Eshop\Core\Registry::getSession()->setBasket($oBasket);

        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getLastCallFnc']);
        $o->expects($this->any())->method('getLastCallFnc')->will($this->returnValue('tobasket'));

        $this->assertEquals(
            null,
            $o->UNITaddItems(
                [
                     [
                         'aid'          => 'a_aid',
                         'am'           => 10,
                         'sel'          => 'a_sel',
                         'persparam'    => ['details' => 'a_persparam'],
                         'override'     => 'a_override',
                         'bundle'       => 'a_bundle',
                         'basketitemid' => 'a_basketitemid',
                     ],
                ]
            )
        );
        $this->assertEquals(
            ['tobasket' =>
                      [
                          [
                              'aid'          => 'a_aid',
                              'am'           => 5,
                              'sel'          => 'a_sel',
                              'persparam'    => ['details' => 'a_persparam'],
                              'override'     => 'a_override',
                              'bundle'       => 'a_bundle',
                              'basketitemid' => 'a_basketitemid',
                              'oldam'        => 5,
                          ],
                      ]],
            oxRegistry::getSession()->getVariable('aLastcall')
        );
    }

    public function testRender()
    {
        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['calculateBasket']);
        $oBasket->expects($this->once())->method('calculateBasket')->with($this->equalTo(false))->will($this->returnValue(null));
        $oSession = $this->getMock(\OxidEsales\Eshop\Core\Session::class, ['getBasket']);
        $oSession->expects($this->once())->method('getBasket')->will($this->returnValue($oBasket));

        // Production uses Registry::getSession(), not $this->getSession()
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Session::class, $oSession);

        $o = oxNew(\OxidEsales\Eshop\Application\Component\BasketComponent::class);
        $this->assertSame($oBasket, $o->render());
    }

    public function testSetLastCall()
    {
        $aProductInfo = [
            'a_aid' => [
                'aid'          => 'a_aid',
                'am'           => 'a_am',
                'sel'          => 'a_sel',
                'persparam'    => 'a_persparam',
                'override'     => 'a_override',
                'bundle'       => 'a_bundle',
                'basketitemid' => 'a_basketitemid',
                'oldam'        => 0,
            ],
            'b_aid' => [
                'aid'          => 'b_aid',
                'am'           => 'b_am',
                'sel'          => 'b_sel',
                'persparam'    => 'b_persparam',
                'override'     => 'b_override',
                'bundle'       => 'b_bundle',
                'basketitemid' => 'b_basketitemid',
                'oldam'        => 15,
            ],
        ];
        $aBasketInfo = (object) [
            'aArticles' => ['b_aid' => 15],
        ];
        $o = oxNew('oxcmp_basket');
        $this->assertSame(null, $o->UNITsetLastCall('sCallName', $aProductInfo, $aBasketInfo));
        $this->assertEquals(['sCallName' => $aProductInfo], oxRegistry::getSession()->getVariable('aLastcall'));
    }

    /**
     * Testing oxcmp_categories::isRootCatChanged() test case used for bascet exclude
     *
     * @return null
     */
    public function testIsRootCatChanged_clean()
    {
        $this->getConfig()->setConfigParam('blBasketExcludeEnabled', true);

        $oCmp = oxNew('oxcmp_basket');
        $this->assertFalse($oCmp->isRootCatChanged());
    }

    /**
     * Testing oxcmp_categories::isRootCatChanged() test case used for bascet exclude
     *
     * @return null
     */
    public function testIsRootCatChanged_unchanged_session()
    {
        $this->getConfig()->setConfigParam('blBasketExcludeEnabled', true);

        $oCmp = oxNew('oxcmp_basket');
        $this->assertFalse($oCmp->isRootCatChanged());
    }

    /**
     * Testing oxcmp_categories::isRootCatChanged() test case used for bascet exclude
     *
     * @return null
     */
    public function testIsRootCatChanged_ShowCatChangeWarning()
    {
        $oB = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['showCatChangeWarning', 'setCatChangeWarningState']);
        $oB->expects($this->once())->method('showCatChangeWarning')->will($this->returnValue(true));
        $oB->expects($this->once())->method('setCatChangeWarningState')->will($this->returnValue(null));

        $oS = $this->getMock(\OxidEsales\Eshop\Core\Session::class, ['getBasket']);
        $oS->expects($this->once())->method('getBasket')->will($this->returnValue($oB));

        // Production uses Registry::getSession(), not $this->getSession()
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Session::class, $oS);

        $oCB = oxNew(\OxidEsales\Eshop\Application\Component\BasketComponent::class);
        $this->assertTrue($oCB->isRootCatChanged());
    }

    public function testInitNormalShop()
    {
        $this->getConfig()->setConfigParam('blPsBasketReservationEnabled', false);

        $oS = $this->getMock(\OxidEsales\Eshop\Core\Session::class, ['getBasketReservations', 'getBasket']);
        $oS->expects($this->never())->method('getBasketReservations');
        $oS->expects($this->never())->method('getBasket');

        $oCB = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getSession']);
        $oCB->expects($this->any())->method('getSession')->will($this->returnValue($oS));

        $oCB->init();
    }

    public function testInitReservationNotTimeouted()
    {
        $this->getConfig()->setConfigParam('blPsBasketReservationEnabled', true);
        $this->getConfig()->setConfigParam('iBasketReservationCleanPerRequest', 320);

        $oBR = $this->getMock('stdclass', ['getTimeLeft', 'discardUnusedReservations']);
        $oBR->expects($this->once())->method('getTimeLeft')->will($this->returnValue(2));
        $oBR->expects($this->once())->method('discardUnusedReservations')->with($this->equalTo(320));

        $oS = $this->getMock(\OxidEsales\Eshop\Core\Session::class, ['getBasketReservations', 'getBasket']);
        $oS->expects($this->once())->method('getBasketReservations')->will($this->returnValue($oBR));
        $oS->expects($this->never())->method('getBasket');

        // Production uses Registry::getSession(), not $this->getSession()
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Session::class, $oS);

        $oCB = oxNew(\OxidEsales\Eshop\Application\Component\BasketComponent::class);
        $oCB->init();
    }

    public function testInitReservationTimeouted()
    {
        $this->getConfig()->setConfigParam('blPsBasketReservationEnabled', true);
        // also check the default (hardcoded) value is 200, if iBasketReservationCleanPerRequest is 0
        $this->getConfig()->setConfigParam('iBasketReservationCleanPerRequest', 0);

        $oB = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['deleteBasket', 'getProductsCount']);
        $oB->expects($this->once())->method('deleteBasket')->will($this->returnValue(0));
        $oB->expects($this->once())->method('getProductsCount')->will($this->returnValue(1));

        $oBR = $this->getMock('stdclass', ['getTimeLeft', 'discardUnusedReservations']);
        $oBR->expects($this->once())->method('getTimeLeft')->will($this->returnValue(0));
        $oBR->expects($this->once())->method('discardUnusedReservations')->with($this->equalTo(200));

        $oS = $this->getMock(\OxidEsales\Eshop\Core\Session::class, ['getBasketReservations', 'getBasket']);
        $oS->expects($this->once())->method('getBasketReservations')->will($this->returnValue($oBR));
        $oS->expects($this->once())->method('getBasket')->will($this->returnValue($oB));

        // Production uses Registry::getSession(), not $this->getSession()
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Session::class, $oS);

        $oCB = oxNew(\OxidEsales\Eshop\Application\Component\BasketComponent::class);
        $oCB->init();
    }

    public function testSetGetLastCallFnc()
    {
        $o = oxNew('oxcmp_basket');
        $o->UNITsetLastCallFnc('tobasket');
        $this->assertEquals('tobasket', $o->UNITgetLastCallFnc());
    }

    public function testExecuteUserChoiceToBasket()
    {
        $this->setRequestParameter('tobasket', true);

        $oCB = oxNew('oxcmp_basket');
        $this->assertEquals('basket', $oCB->executeuserchoice());
    }

    public function testExecuteUserChoiceElseCase()
    {
        $oB = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, ['deleteBasket']);
        $oB->expects($this->once())->method('deleteBasket')->will($this->returnValue(null));

        $oS = $this->getMock(\OxidEsales\Eshop\Core\Session::class, ['getBasket']);
        $oS->expects($this->once())->method('getBasket')->will($this->returnValue($oB));

        // Production uses Registry::getSession(), not $this->getSession()
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Session::class, $oS);

        $oP = $this->getMock('stdclass', ['setRootCatChanged']);
        $oP->expects($this->once())->method('setRootCatChanged')->will($this->returnValue(null));

        $oCB = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, ['getParent']);
        $oCB->expects($this->any())->method('getParent')->will($this->returnValue($oP));

        $this->assertNull($oCB->executeuserchoice());
    }

    private function prepareSessionChallengeToken()
    {
        $this->setRequestParameter('stoken', \OxidEsales\Eshop\Core\Registry::getSession()->getSessionChallengeToken());
    }
}
