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
namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use \oxField;

/**
 * Testing oxuserlist class
 */
class UserlistTest extends \OxidTestCase
{
    /**
     * Initialize the fixture.
     *
     * @return null
     */
    public function setup(): void
    {
        parent::setUp();
        $oUser = oxNew('oxuser');
        $oUser->setId('user1');
        $oUser->oxuser__oxactive = new oxField(1, oxField::T_RAW);
        $oUser->oxuser__oxshopid = new oxField($this->getConfig()->getBaseShopId(), oxField::T_RAW);
        $oUser->oxuser__oxusername = new oxField('user1@gmail.com', oxField::T_RAW);
        $oUser->save();


        $oUser = oxNew('oxuser');
        $oUser->setId('user2');
        $oUser->oxuser__oxactive = new oxField(1, oxField::T_RAW);
        $oUser->oxuser__oxshopid = new oxField(2, oxField::T_RAW);
        $oUser->oxuser__oxusername = new oxField('user2@yahoo.com', oxField::T_RAW);
        $oUser->save();

        $oBasket = oxNew('OxUserBasket');
        $oBasket->setId("testUserBasket");
        $oBasket->oxuserbaskets__oxuserid = new oxField('user2', oxField::T_RAW);
        $oBasket->oxuserbaskets__oxtitle = new oxField('wishlist', oxField::T_RAW);
        $oBasket->oxuserbaskets__oxpublic = new oxField(1, oxField::T_RAW);
        $oBasket->save();

        $oBasketItem = oxNew('OxUserBasketItem');
        $oBasketItem->setId("testUserBasketItem");
        $oBasketItem->oxuserbasketitems__oxbasketid = new oxField('testUserBasket', oxField::T_RAW);
        $oBasketItem->oxuserbasketitems__oxamount = new oxField(1, oxField::T_RAW);
        $oBasketItem->oxuserbasketitems__oxartid = new oxField('test', oxField::T_RAW);
        $oBasketItem->save();

        $oBasket = oxNew('OxUserBasket');
        $oBasket->setId("testUserBasket2");
        $oBasket->oxuserbaskets__oxuserid = new oxField('user1', oxField::T_RAW);
        $oBasket->oxuserbaskets__oxtitle = new oxField('wishlist', oxField::T_RAW);
        $oBasket->oxuserbaskets__oxpublic = new oxField(1, oxField::T_RAW);
        $oBasket->save();

        $oBasketItem = oxNew('OxUserBasketItem');
        $oBasketItem->setId("testUserBasketItem2");
        $oBasketItem->oxuserbasketitems__oxbasketid = new oxField('testUserBasket2', oxField::T_RAW);
        $oBasketItem->oxuserbasketitems__oxamount = new oxField(1, oxField::T_RAW);
        $oBasketItem->oxuserbasketitems__oxartid = new oxField('test', oxField::T_RAW);
        $oBasketItem->save();
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    public function tearDown(): void
    {
        $oUser = oxNew('oxuser');
        $oUser->delete('user1');
        $oUser->delete('user2');
        $oUserBasket = oxNew('oxUserBasket');
        $oUserBasket->delete("testUserBasket");
        $oUserBasket->delete("testUserBasket2");
        $oUserBasket = oxNew('OxUserBasketItem');
        $oUserBasket->delete("testUserBasketItem");
        $oUserBasket->delete("testUserBasketItem2");

        parent::tearDown();
    }

    public function testUserListLoadingDisabledShopcheck()
    {
        $iUserCount = '3';
        if ($this->getConfig()->getEdition() === 'EE') {
            $iUserCount = '8';
        }

        $oUser = oxNew('oxuser');
        $oUserList = oxNew('oxuserlist');
        $oUserList->selectString($oUser->buildSelectString());

        $this->assertEquals($iUserCount, $oUserList->count());
    }

    /**
     * @dataProvider loadWishListUsersDataProvider
     * @param $searchText
     * @param $expectResult
     */
    public function testLoadWishListUsers($searchText, $expectResult)
    {
        // selecting count from DB
        $oUserList = oxNew('oxuserlist');

        $oUserList->loadWishlistUsers($searchText);
        $this->assertEquals($expectResult, $oUserList->count());
    }

    public function loadWishListUsersDataProvider()
    {
        return array(
            array('user1@gmail.com', 1),
            array('user2@yahoo.com', 1),
            array('user', 0),
            array('@', 0),
            array('@yahoo', 0),
            array('@gmail.com', 0),
        );
    }
}
