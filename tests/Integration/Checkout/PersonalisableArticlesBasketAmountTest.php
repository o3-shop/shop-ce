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
namespace OxidEsales\EshopCommunity\Tests\Integration\Checkout;

use oxBasket;
use oxField;
use oxRegistry;

/**
 * Test basket contents for configurable articles (oxarticles.oxisconfigurable = true).
 */
class PersonalisableArticlesBasketAmountTest extends \OxidTestCase
{
    /**
     * Make a copy of The Barrel for testing, it is already configurable
     */
    const SOURCE_ARTICLE_ID = 'f4f73033cf5045525644042325355732';

    /**
     * Generated oxid for test article
     *
     * @var string
     */
    private $testArticleId = null;

    /**
     * Fixture setUp.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->insertArticle();
    }

    /**
    * Fixture tearDown.
    */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxarticles');

        parent::tearDown();
    }

    /**
     * Simulate changed basket, only article amounts being changed.
     */
    public function testChangeBasketSomeAmountsOnly()
    {
        $basket = $this->prepareBasket();
        oxRegistry::getSession()->setBasket($basket);

        $products = $this->getRequestParameters();
        $products[$this->getItemKey('first')]['am'] = 2;
        $products[$this->getItemKey('fourth')]['am'] = 3;
        $this->setRequestParameter('aproducts', $products);
        $this->prepareSessionChallengeToken();

        $basketComponent = oxNew('oxcmp_basket');
        $basketComponent->changebasket();

        $basket = oxRegistry::getSession()->getBasket();

        $this->assertSame(7, $this->getAmountInBasket());
        $this->assertSame(4, count($basket->getBasketArticles()));
    }

    /**
     * Simulate changed basket, change personal details for all to first article details.
     */
    public function testChangeBasketUseFirstArticlePersistentDetailsForAll()
    {
        $basket = $this->prepareBasket();
        oxRegistry::getSession()->setBasket($basket);

        $products = $this->getRequestParameters();
        $products[$this->getItemKey('first')]['persparam'] = $this->getPersistentParameters('first');
        $products[$this->getItemKey('second')]['persparam'] = $this->getPersistentParameters('first');
        $products[$this->getItemKey('third')]['persparam'] = $this->getPersistentParameters('first');
        $products[$this->getItemKey('fourth')]['persparam'] = $this->getPersistentParameters('first');
        $this->setRequestParameter('aproducts', $products);
        $this->prepareSessionChallengeToken();

        $basketComponent = oxNew('oxcmp_basket');
        $basketComponent->changebasket();

        $basket = oxRegistry::getSession()->getBasket();

        $this->assertSame(4, $this->getAmountInBasket());
        $this->assertSame(1, count($basket->getBasketArticles()));
    }

    /**
     * Simulate changed basket, change personal details for all to second article details.
     */
    public function testChangeBasketUseSecondArticlePersistentDetailsForAll()
    {
        $basket = $this->prepareBasket();
        oxRegistry::getSession()->setBasket($basket);

        $products = $this->getRequestParameters();
        $products[$this->getItemKey('first')]['persparam'] = $this->getPersistentParameters('second');
        $products[$this->getItemKey('second')]['persparam'] = $this->getPersistentParameters('second');
        $products[$this->getItemKey('third')]['persparam'] = $this->getPersistentParameters('second');
        $products[$this->getItemKey('fourth')]['persparam'] = $this->getPersistentParameters('second');
        $this->setRequestParameter('aproducts', $products);
        $this->prepareSessionChallengeToken();

        $basketComponent = oxNew('oxcmp_basket');
        $basketComponent->changebasket();

        $basket = oxRegistry::getSession()->getBasket();

        $this->assertSame(4, $this->getAmountInBasket());
        $this->assertSame(1, count($basket->getBasketArticles()));
    }

    /**
     * Simulate changed basket, change personal details for all to fourth article details.
     */
    public function testChangeBasketUseFourthArticlePersistentDetailsForAll()
    {
        $basket = $this->prepareBasket();
        oxRegistry::getSession()->setBasket($basket);

        $products = $this->getRequestParameters();
        $products[$this->getItemKey('first')]['persparam'] = $this->getPersistentParameters('fourth');
        $products[$this->getItemKey('second')]['persparam'] = $this->getPersistentParameters('fourth');
        $products[$this->getItemKey('third')]['persparam'] = $this->getPersistentParameters('fourth');
        $products[$this->getItemKey('fourth')]['persparam'] = $this->getPersistentParameters('fourth');
        $this->setRequestParameter('aproducts', $products);
        $this->prepareSessionChallengeToken();

        $basketComponent = oxNew('oxcmp_basket');
        $basketComponent->changebasket();

        $basket = oxRegistry::getSession()->getBasket();

        $this->assertSame(4, $this->getAmountInBasket());
        $this->assertSame(1, count($basket->getBasketArticles()));
    }

    /**
     * Test helper, get an array matching itemkey and personal details.
     *
     * @return array
     */
    private function getPersistent()
    {
        $firstPersistent = array('details' => 'first');
        $secondPersistent = array('details' => 'second');
        $thirdPersistent = array('details' => 'third');
        $fourthPersistent = array('details' => 'fourth');

        $ret = array();
        $ret['first']  = array($this->generateItemKey($firstPersistent)  => $firstPersistent);
        $ret['second'] = array($this->generateItemKey($secondPersistent) => $secondPersistent);
        $ret['third']  = array($this->generateItemKey($thirdPersistent)  => $thirdPersistent);
        $ret['fourth'] = array($this->generateItemKey($fourthPersistent) => $fourthPersistent);

        return $ret;
    }

    /**
     * Test helper for preparing the basket with articles (one each)
     *
     * @return oxBasket
     */
    private function prepareBasket()
    {
        $basket = oxNew('oxBasket');

        $amount = 1;
        $selectList = array();
        $override = true;
        $bundle = false;
        $oldBasketItemId = null;

        $personal = $this->getPersistent();
        foreach ($personal as $details) {
            $set = array_values($details);
            $set = $set[0];
            $basket->addToBasket($this->testArticleId, $amount, $selectList, $set, $override, $bundle, $oldBasketItemId);
        }

        return $basket;
    }

    /**
     * Make a copy of article and variant for testing.
     */
    private function insertArticle()
    {
        $this->testArticleId = substr_replace(oxRegistry::getUtilsObject()->generateUId(), '_', 0, 1);

        //copy from original article
        $articleParent = oxNew('oxarticle');
        $articleParent->disableLazyLoading();
        $articleParent->load(self::SOURCE_ARTICLE_ID);
        $articleParent->setId($this->testArticleId);
        $articleParent->oxarticles__oxartnum = new oxField('667-T', oxField::T_RAW);
        $articleParent->save();
    }

    /**
     * Test helper to get the item key.
     *
     * @param array $personal
     *
     * @return string
     */
    private function generateItemKey($personal = array())
    {
        $basket = oxNew('oxBasket');

        $selectList = array();
        $bundle = false;

        return $basket->getItemKey($this->testArticleId, $selectList, $personal, $bundle);
    }

    /**
     * Prepare request parameters
     */
    private function getRequestParameters()
    {
        $personal = $this->getPersistent();
        $firstItemKey = $this->getItemKey('first');
        $secondItemKey = $this->getItemKey('second');
        $thirdItemKey = $this->getItemKey('third');
        $fourthItemKey = $this->getItemKey('fourth');

        $products = array();
        $products[$firstItemKey] = array('persparam'    => $this->getPersistentParameters('first'),
                                         'aid'          => $this->testArticleId,
                                         'basketitemid' => $firstItemKey,
                                         'override'     => 1,
                                         'am'           => 1);
        $products[$secondItemKey] = array('persparam'    => $this->getPersistentParameters('second'),
                                          'aid'          => $this->testArticleId,
                                          'basketitemid' => $secondItemKey,
                                          'override'     => 1,
                                          'am'           => 1);
        $products[$thirdItemKey] = array('persparam'    => $this->getPersistentParameters('third'),
                                         'aid'          => $this->testArticleId,
                                         'basketitemid' => $thirdItemKey,
                                         'override'     => 1,
                                         'am'           => 1);
        $products[$fourthItemKey] = array('persparam'    => $this->getPersistentParameters('fourth'),
                                          'aid'          => $this->testArticleId,
                                          'basketitemid' => $fourthItemKey,
                                          'override'     => 1,
                                          'am'           => 1);

        return $products;
    }

    /**
     * Test helper for getting itemkeys.
     *
     * @param string $article can be 'first', 'second', 'third', 'fourth'
     *
     * @return string
     */
    private function getItemKey($article)
    {
        $persistent = $this->getPersistent();
        $keys = array_keys($persistent[$article]);
        return (string) $keys[0];
    }

    /**
     * Test helper for getting itemkeys.
     *
     * @param string $article can be 'first', 'second', 'third', 'fourth'
     *
     * @return string
     */
    private function getPersistentParameters($article)
    {
        $persistent = $this->getPersistent();
        $values = array_values($persistent[$article]);
        return $values[0];
    }

    /**
     * Test helper.
     */
    private function prepareSessionChallengeToken()
    {
        $this->setRequestParameter('stoken', \OxidEsales\Eshop\Core\Registry::getSession()->getSessionChallengeToken());
    }

    /**
     * NOTE: Do not use Basket::getBasketSummary() as this method adds up on every call.
     *
     * Test helper to get amount of test artile in basket.
     *
     * @return integer
     */
    private function getAmountInBasket()
    {
        $return = 0;
        $basket = \OxidEsales\Eshop\Core\Registry::getSession()->getBasket();
        $basketContents = $basket->getContents();

        foreach ($basketContents as $basketItem) {
            $return += $basketItem->getAmount();
        }
        return (int) $return;
    }
}
