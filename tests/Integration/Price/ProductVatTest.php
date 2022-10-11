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

declare(strict_types=1);

namespace Integration\Price;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopIdCalculator;
use OxidEsales\TestingLibrary\UnitTestCase;

class ProductVatTest extends UnitTestCase
{
    private const FIRST_ARTICLE_ID = '101';
    private const SECOND_ARTICLE_ID = '102';
    private const THIRD_ARTICLE_ID = '103';

    private $countriesId = [
        'germany' => 'a7c40f631fc920687.20179984',
        'switzerland' => 'a7c40f6321c6f6109.43859248',
    ];

    protected function setUp(): void
    {
        $this->createArticle(self::FIRST_ARTICLE_ID, 20);
        $this->createArticle(self::SECOND_ARTICLE_ID, 30);
        $this->createArticle(self::THIRD_ARTICLE_ID, 40);

        $this->createActiveUser('germany');
        $this->updateArticleVat(self::FIRST_ARTICLE_ID, 5);
        $this->updateArticleVat(self::SECOND_ARTICLE_ID, 10);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testProductVat(): void
    {
        $basket = oxNew(Basket::class);
        $basket->addToBasket(self::FIRST_ARTICLE_ID, 1);
        $basket->addToBasket(self::SECOND_ARTICLE_ID, 1);
        $basket->addToBasket(self::THIRD_ARTICLE_ID, 1);

        $basket->calculateBasket(true);
        $this->assertSame(79.93, $basket->getNettoSum());

        $this->assertSame([
            5  => '0,95',
            10 => '2,73',
            19 => '6,39',
        ], $basket->getProductVats(true));

        $this->loginUser();

        $this->changeUserAddress('switzerland');

        $basket->calculateBasket(true);
        $this->assertSame(79.93, $basket->getNettoSum());

        $this->assertSame([
            0  => '0,00',
        ], $basket->getProductVats(true));
    }

    /**
     * @param string $country
     *
     * @return User
     */
    private function createActiveUser(string $country): User
    {
        $sTestUserId = substr_replace(Registry::getUtilsObject()->generateUId(), '_', 0, 1);

        $user = oxNew(User::class);
        $user->setId($sTestUserId);

        $user->oxuser__oxactive = new Field('1');
        $user->oxuser__oxrights = new Field('user');
        $user->oxuser__oxshopid = new Field(ShopIdCalculator::BASE_SHOP_ID);
        $user->oxuser__oxusername = new Field('testuser@oxideshop.dev');
        $user->oxuser__oxpassword = new Field(
            'c630e7f6dd47f9ad60ece4492468149bfed3da3429940181464baae99941d0ffa5562' .
            'aaecd01eab71c4d886e5467c5fc4dd24a45819e125501f030f61b624d7d'
        ); //password is asdfasdf
        $user->oxuser__oxpasssalt = new Field('3ddda7c412dbd57325210968cd31ba86');
        $user->oxuser__oxcustnr = new Field('667');
        $user->oxuser__oxfname = new Field('Erna');
        $user->oxuser__oxlname = new Field('Helvetia');
        $user->oxuser__oxstreet = new Field('Dorfstrasse');
        $user->oxuser__oxstreetnr = new Field('117');
        $user->oxuser__oxcity = new Field('Oberbuchsiten');
        $user->oxuser__oxcountryid = new Field($this->countriesId[strtolower($country)]);
        $user->oxuser__oxzip = new Field('4625');
        $user->oxuser__oxsal = new Field('MRS');
        $user->oxuser__oxcreate = new Field('2015-05-20 22:10:51');
        $user->oxuser__oxregister = new Field('2015-05-20 22:10:51');
        $user->oxuser__oxboni = new Field('1000');

        $user->save();

        return $user;
    }

    /**
     * @param string $country
     */
    private function changeUserAddress(string $country): void
    {
        $countryInfo = [
            'germany' => [
                'oxuser__oxfname'     => 'Erna',
                'oxuser__oxlname'     => 'Hahnentritt',
                'oxuser__oxstreetnr'  => '117',
                'oxuser__oxstreet'    => 'Landstrasse',
                'oxuser__oxzip'       => '22769',
                'oxuser__oxcity'      => 'Hamburg',
                'oxuser__oxcountryid' => $this->countriesId['germany']
            ],
            'switzerland' => [
                'oxuser__oxfname'     => 'Erna',
                'oxuser__oxlname'     => 'Hahnentritt',
                'oxuser__oxstreetnr'  => '117',
                'oxuser__oxstreet'    => 'Landstrasse',
                'oxuser__oxzip'       => '3741',
                'oxuser__oxcity'      => 'PULKAU',
                'oxuser__oxcountryid' => $this->countriesId['switzerland']
            ]
        ];

        $_POST['invadr'] = $countryInfo[strtolower($country)];
        $_POST['stoken'] = Registry::getSession()->getSessionChallengeToken();

        $userComponent = oxNew('oxcmp_user');
        $this->assertSame('payment', $userComponent->changeUser());
    }

    /**
     *
     * @return string
     */
    private function loginUser(): string
    {
        $_POST['lgn_usr'] = 'testuser@oxideshop.dev';
        $_POST['lgn_pwd'] = 'asdfasdf';
        $oCmpUser = oxNew('oxcmp_user');
        return $oCmpUser->login();
    }

    private function updateArticleVat(string $articleId, int $vat): void
    {
        $article = oxNew(Article::class);
        $article->setId($articleId);
        $article->oxarticles__oxvat = new Field($vat);
        $article->save();
    }

    private function createArticle(string $articleId, int $price): void
    {
        $oArticle = oxNew(Article::class);
        $oArticle->setAdminMode(null);
        $oArticle->setId($articleId);
        $oArticle->oxarticles__oxprice = new Field($price);
        $oArticle->oxarticles__oxshopid = new Field(1);
        $oArticle->oxarticles__oxtitle = new Field("test");
        $oArticle->save();
    }
}
