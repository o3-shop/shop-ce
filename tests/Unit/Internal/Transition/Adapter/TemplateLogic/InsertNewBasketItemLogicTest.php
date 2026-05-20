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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\AbstractInsertNewBasketItemLogic;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\InsertNewBasketItemLogicSmarty;
use Smarty;

/**
 * Concrete subclass that lets each test steer the abstract methods —
 * exercises the AbstractInsertNewBasketItemLogic body without committing
 * to Smarty or Twig specifics.
 */
class InsertNewBasketItemLogicTest_StubLogic extends AbstractInsertNewBasketItemLogic
{
    public bool $validates = true;
    public bool $loadCalled = false;
    public bool $renderCalled = false;
    public string $renderOutput = '<rendered/>';
    public ?string $renderedTemplateName = null;

    protected function validateTemplateEngine($templateEngine)
    {
        return $this->validates;
    }

    protected function loadArticleObject($newItem, $templateEngine)
    {
        $this->loadCalled = true;
    }

    protected function renderTemplate(string $templateName, $templateEngine)
    {
        $this->renderCalled = true;
        $this->renderedTemplateName = $templateName;
        return $this->renderOutput;
    }
}

class InsertNewBasketItemLogicTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Registry::getSession()->deleteVariable('_newitem');
    }

    public function testThrowsWhenTemplateEngineFailsValidation(): void
    {
        $logic = new InsertNewBasketItemLogicTest_StubLogic();
        $logic->validates = false;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('correct template engine');
        $logic->getNewBasketItemTemplate(['type' => 'message', 'tpl' => '', 'ajax' => false], new \stdClass());
    }

    public function testReturnsEmptyStringWhenNoNewItemAndNotAjax(): void
    {
        $this->getConfig()->setConfigParam('iNewBasketItemMessage', 1);
        $logic = new InsertNewBasketItemLogicTest_StubLogic();

        $this->assertSame(
            '',
            $logic->getNewBasketItemTemplate(['type' => 'message', 'tpl' => '', 'ajax' => false], new \stdClass())
        );
        $this->assertFalse($logic->renderCalled);
        $this->assertFalse($logic->loadCalled);
    }

    public function testRendersTemplateWhenNewItemPresentAndCorrectMessageType(): void
    {
        $this->getConfig()->setConfigParam('iNewBasketItemMessage', 1);
        Registry::getSession()->setVariable('_newitem', (object) ['sId' => 'art-1']);

        $logic = new InsertNewBasketItemLogicTest_StubLogic();
        $logic->renderOutput = '<inc>';
        $output = $logic->getNewBasketItemTemplate(['type' => 'message', 'tpl' => 'foo.tpl', 'ajax' => false], new \stdClass());

        $this->assertSame('<inc>', $output);
        $this->assertTrue($logic->loadCalled);
        $this->assertTrue($logic->renderCalled);
        $this->assertSame('foo.tpl', $logic->renderedTemplateName);
    }

    public function testReturnsEmptyWhenMessageTypeMismatchesConfig(): void
    {
        // Config says popup (2) but caller asked for message → mismatch → no render.
        $this->getConfig()->setConfigParam('iNewBasketItemMessage', 2);
        Registry::getSession()->setVariable('_newitem', (object) ['sId' => 'art-1']);

        $logic = new InsertNewBasketItemLogicTest_StubLogic();
        $output = $logic->getNewBasketItemTemplate(['type' => 'message', 'tpl' => '', 'ajax' => false], new \stdClass());

        $this->assertSame('', $output);
        $this->assertFalse($logic->renderCalled);
    }

    public function testAjaxPopupForcesRenderEvenWithoutNewItem(): void
    {
        $this->getConfig()->setConfigParam('iNewBasketItemMessage', 2);
        Registry::getSession()->deleteVariable('_newitem');

        $logic = new InsertNewBasketItemLogicTest_StubLogic();
        $logic->renderOutput = '<popup/>';
        $output = $logic->getNewBasketItemTemplate(['type' => '', 'tpl' => '', 'ajax' => true], new \stdClass());

        $this->assertSame('<popup/>', $output);
        $this->assertTrue($logic->renderCalled);
    }

    public function testDefaultTemplateNameUsedWhenTplParamEmpty(): void
    {
        $this->getConfig()->setConfigParam('iNewBasketItemMessage', 1);
        Registry::getSession()->setVariable('_newitem', (object) ['sId' => 'art-1']);

        $logic = new InsertNewBasketItemLogicTest_StubLogic();
        $logic->getNewBasketItemTemplate(['type' => 'message', 'tpl' => '', 'ajax' => false], new \stdClass());

        $this->assertSame('inc_newbasketitem.snippet.html.twig', $logic->renderedTemplateName);
    }

    public function testSmartyImplementationValidatesInstance(): void
    {
        $logic = new InsertNewBasketItemLogicSmarty();
        $method = new \ReflectionMethod($logic, 'validateTemplateEngine');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke($logic, new Smarty()));
        $this->assertFalse($method->invoke($logic, new \stdClass()));
    }

    // Twig variant (InsertNewBasketItemLogicTwig) deliberately omitted —
    // Twig is not part of the unit-test environment's vendor set.

    public function testSmartyRenderTemplateDelegatesToFetch(): void
    {
        $smarty = $this->getMockBuilder(Smarty::class)
            ->onlyMethods(['fetch'])
            ->getMock();
        $smarty->expects($this->once())->method('fetch')->with('foo.tpl')->willReturn('Smarty rendered');

        $logic = new InsertNewBasketItemLogicSmarty();
        $method = new \ReflectionMethod($logic, 'renderTemplate');
        $method->setAccessible(true);
        $this->assertSame('Smarty rendered', $method->invoke($logic, 'foo.tpl', $smarty));
    }

    public function testSmartyLoadArticleObjectLoadsAssignsAndClearsSession(): void
    {
        $article = new class () extends \OxidEsales\EshopCommunity\Application\Model\Article {
            public ?string $loadedWith = null;
            public function __construct()
            {
            }
            public function load($oxId)
            {
                $this->loadedWith = (string) $oxId;
                return true;
            }
        };
        \oxTestModules::addModuleObject('oxarticle', $article);
        Registry::getSession()->setVariable('_newitem', (object) ['sId' => 'art-1']);

        $smarty = $this->getMockBuilder(Smarty::class)
            ->onlyMethods(['assign'])
            ->getMock();
        $smarty->expects($this->once())
            ->method('assign')
            ->with('_newitem', $this->isInstanceOf(\stdClass::class));

        $newItem = (object) ['sId' => 'art-1'];
        $logic = new InsertNewBasketItemLogicSmarty();
        $method = new \ReflectionMethod($logic, 'loadArticleObject');
        $method->setAccessible(true);
        $method->invoke($logic, $newItem, $smarty);

        $this->assertSame('art-1', $article->loadedWith);
        $this->assertSame($article, $newItem->oArticle);
        $this->assertNull(Registry::getSession()->getVariable('_newitem'));
    }
}
