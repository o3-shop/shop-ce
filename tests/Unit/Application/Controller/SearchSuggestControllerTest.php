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

use OxidEsales\Eshop\Application\Controller\SearchSuggestController;
use OxidEsales\Eshop\Application\Model\Search;
use OxidEsales\Eshop\Core\Header;
use OxidEsales\Eshop\Core\Registry;
use oxRegistry;
use oxTestModules;

class SearchSuggestControllerTest extends \OxidTestCase
{
    /**
     * Captures the payload handed to Utils::showMessageAndExit() instead of exiting.
     *
     * @return string
     */
    private function getRenderedJson()
    {
        $utils = oxRegistry::getUtils();
        $this->assertNotEmpty($utils->showMessageAndExitCall, 'showMessageAndExit was not called');

        return $utils->showMessageAndExitCall[0];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->getConfig()->setConfigParam('blSearchSuggest', true);
        $this->getConfig()->setConfigParam('iSearchSuggestCount', SearchSuggestController::DEFAULT_SUGGESTIONS);
        $this->setRequestParameter('searchparam', null);
        oxTestModules::addFunction('oxutils', 'showMessageAndExit', '{$this->showMessageAndExitCall[] = $aA[0];}');
    }

    public function testRenderReturnsEmptyWhenFeatureIsDisabled()
    {
        $this->getConfig()->setConfigParam('blSearchSuggest', false);

        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $this->assertSame('[]', $this->getRenderedJson());
    }

    public function testRenderReturnsEmptyWhenNoSearchParamGiven()
    {
        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $this->assertSame('[]', $this->getRenderedJson());
    }

    public function testRenderReturnsEmptyWhenSearchParamIsSingleCharacter()
    {
        $this->setRequestParameter('searchparam', 'a');

        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $this->assertSame('[]', $this->getRenderedJson());
    }

    public function testRenderReturnsEmptyWhenSearchParamIsWhitespaceOnly()
    {
        $this->setRequestParameter('searchparam', '   ');

        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $this->assertSame('[]', $this->getRenderedJson());
    }

    public function testRenderReturnsSuggestions()
    {
        $suggestions = [
            ['id' => 'art1', 'title' => 'Article One', 'price' => '10,00', 'icon' => '', 'link' => 'http://shop/index.php?cl=details&anid=art1'],
        ];

        $search = $this->getMock(Search::class, ['getSearchSuggestions']);
        $search->expects($this->once())
            ->method('getSearchSuggestions')
            ->with($this->equalTo('test'), $this->equalTo(SearchSuggestController::DEFAULT_SUGGESTIONS))
            ->will($this->returnValue($suggestions));
        oxTestModules::addModuleObject(Search::class, $search);

        $this->setRequestParameter('searchparam', 'test');

        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $this->assertSame(json_encode($suggestions, JSON_THROW_ON_ERROR), $this->getRenderedJson());
    }

    public function testRenderTrimsSearchParamBeforeSearching()
    {
        $search = $this->getMock(Search::class, ['getSearchSuggestions']);
        $search->expects($this->once())
            ->method('getSearchSuggestions')
            ->with($this->equalTo('test'), $this->anything())
            ->will($this->returnValue([]));
        oxTestModules::addModuleObject(Search::class, $search);

        $this->setRequestParameter('searchparam', '  test  ');

        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $this->assertSame('[]', $this->getRenderedJson());
    }

    public function testRenderUsesConfiguredLimit()
    {
        $this->getConfig()->setConfigParam('iSearchSuggestCount', 5);

        $search = $this->getMock(Search::class, ['getSearchSuggestions']);
        $search->expects($this->once())
            ->method('getSearchSuggestions')
            ->with($this->equalTo('test'), $this->equalTo(5))
            ->will($this->returnValue([]));
        oxTestModules::addModuleObject(Search::class, $search);

        $this->setRequestParameter('searchparam', 'test');

        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $this->assertSame('[]', $this->getRenderedJson());
    }

    public function testRenderFallsBackToDefaultLimitWhenConfiguredLimitIsInvalid()
    {
        $this->getConfig()->setConfigParam('iSearchSuggestCount', 0);

        $search = $this->getMock(Search::class, ['getSearchSuggestions']);
        $search->expects($this->once())
            ->method('getSearchSuggestions')
            ->with($this->equalTo('test'), $this->equalTo(SearchSuggestController::DEFAULT_SUGGESTIONS))
            ->will($this->returnValue([]));
        oxTestModules::addModuleObject(Search::class, $search);

        $this->setRequestParameter('searchparam', 'test');

        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $this->assertSame('[]', $this->getRenderedJson());
    }

    public function testRenderFallsBackToDefaultLimitWhenConfiguredLimitIsNegative()
    {
        $this->getConfig()->setConfigParam('iSearchSuggestCount', -3);

        $search = $this->getMock(Search::class, ['getSearchSuggestions']);
        $search->expects($this->once())
            ->method('getSearchSuggestions')
            ->with($this->equalTo('test'), $this->equalTo(SearchSuggestController::DEFAULT_SUGGESTIONS))
            ->will($this->returnValue([]));
        oxTestModules::addModuleObject(Search::class, $search);

        $this->setRequestParameter('searchparam', 'test');

        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $this->assertSame('[]', $this->getRenderedJson());
    }

    public function testRenderClampsConfiguredLimitToMaximum()
    {
        $this->getConfig()->setConfigParam('iSearchSuggestCount', 100);

        $search = $this->getMock(Search::class, ['getSearchSuggestions']);
        $search->expects($this->once())
            ->method('getSearchSuggestions')
            ->with($this->equalTo('test'), $this->equalTo(SearchSuggestController::MAX_SUGGESTIONS))
            ->will($this->returnValue([]));
        oxTestModules::addModuleObject(Search::class, $search);

        $this->setRequestParameter('searchparam', 'test');

        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $this->assertSame('[]', $this->getRenderedJson());
    }

    public function testRenderSetsJsonContentTypeHeader()
    {
        $controller = oxNew(SearchSuggestController::class);
        $controller->render();

        $aHeaders = Registry::get(Header::class)->getHeader();
        $this->assertContains('Content-Type: application/json; charset=UTF-8' . "\r\n", $aHeaders);
    }
}
