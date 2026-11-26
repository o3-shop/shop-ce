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
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright © OXID eSales AG. All rights reserved.
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 *
 * Modified for O3-Shop (2026): adopted from OXID twig-component v1.1.0, ported to Twig 3.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Twig\Extensions;

use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\Filters\TranslateExtension as TranslateFilterExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\IncludeWidgetExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\InsertNewBasketItemExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\MailtoExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\ScriptExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\StyleExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Twig\Extensions\TranslateExtension;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\IncludeWidgetLogic;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\InsertNewBasketItemLogicTwig;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\ScriptLogic;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\StyleLogic;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\TranslateFilterLogic;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\TranslateFunctionLogic;
use PHPUnit\Framework\TestCase;
use Twig\Node\Node;

/**
 * Autoescaping is on for storefront templates: helpers returning markup are safe, translations stay escaped.
 */
class ExtensionEscapingTest extends TestCase
{
    public function testMarkupHelpersAreSafeForHtml(): void
    {
        $functions = array_merge(
            (new IncludeWidgetExtension($this->createMock(IncludeWidgetLogic::class)))->getFunctions(),
            (new ScriptExtension($this->createMock(ScriptLogic::class)))->getFunctions(),
            (new StyleExtension($this->createMock(StyleLogic::class)))->getFunctions(),
            (new MailtoExtension())->getFunctions(),
            (new InsertNewBasketItemExtension($this->createMock(InsertNewBasketItemLogicTwig::class)))->getFunctions()
        );

        $this->assertCount(5, $functions);
        foreach ($functions as $function) {
            $this->assertSame(['html'], $function->getSafe(new Node()), $function->getName());
        }
    }

    public function testTranslateFunctionAndFilterStayUnsafe(): void
    {
        $function = (new TranslateExtension($this->createMock(TranslateFunctionLogic::class)))->getFunctions()[0];
        $filter = (new TranslateFilterExtension($this->createMock(TranslateFilterLogic::class)))->getFilters()[0];

        $this->assertEmpty($function->getSafe(new Node()));
        $this->assertEmpty($filter->getSafe(new Node()));
    }
}
