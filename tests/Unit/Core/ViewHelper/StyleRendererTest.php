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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\ViewHelper;

use OxidEsales\EshopCommunity\Core\ViewHelper\StyleRegistrator;
use OxidEsales\EshopCommunity\Core\ViewHelper\StyleRenderer;

class StyleRendererTest extends \OxidTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear any global styles state set by earlier tests.
        $config = $this->getConfig();
        $config->setGlobalParameter(StyleRegistrator::STYLES_PARAMETER_NAME, []);
        $config->setGlobalParameter(StyleRegistrator::CONDITIONAL_STYLES_PARAMETER_NAME, []);
        $config->setGlobalParameter(StyleRegistrator::STYLES_PARAMETER_NAME . '_dynamic', []);
        $config->setGlobalParameter(StyleRegistrator::CONDITIONAL_STYLES_PARAMETER_NAME . '_dynamic', []);
    }

    public function testRenderProducesStyleLinksForRegisteredFiles(): void
    {
        $this->getConfig()->setGlobalParameter(StyleRegistrator::STYLES_PARAMETER_NAME, [
            'https://shop.example/a.css',
            'https://shop.example/b.css',
        ]);

        $output = (new StyleRenderer())->render('', false, false);

        $this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="https://shop.example/a.css" />', $output);
        $this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="https://shop.example/b.css" />', $output);
    }

    public function testRenderProducesConditionalCommentsForConditionalStyles(): void
    {
        $this->getConfig()->setGlobalParameter(StyleRegistrator::CONDITIONAL_STYLES_PARAMETER_NAME, [
            'https://shop.example/ie.css' => 'IE 9',
        ]);

        $output = (new StyleRenderer())->render('', false, false);

        $this->assertStringContainsString('<!--[if IE 9]>', $output);
        $this->assertStringContainsString('https://shop.example/ie.css', $output);
        $this->assertStringContainsString('<![endif]-->', $output);
    }

    public function testRenderReturnsEmptyStringForWidgetWhenForceRenderIsFalse(): void
    {
        $this->getConfig()->setGlobalParameter(StyleRegistrator::STYLES_PARAMETER_NAME, ['https://shop.example/a.css']);

        $output = (new StyleRenderer())->render('mywidget', false, false);
        $this->assertSame('', $output);
    }

    public function testRenderInWidgetWithForceRenderProducesOutput(): void
    {
        $this->getConfig()->setGlobalParameter(StyleRegistrator::STYLES_PARAMETER_NAME, ['https://shop.example/a.css']);

        $output = (new StyleRenderer())->render('mywidget', true, false);
        $this->assertStringContainsString('a.css', $output);
    }

    public function testDynamicSuffixSelectsAlternateGlobalParam(): void
    {
        $this->getConfig()->setGlobalParameter(StyleRegistrator::STYLES_PARAMETER_NAME, ['https://shop.example/static.css']);
        $this->getConfig()->setGlobalParameter(StyleRegistrator::STYLES_PARAMETER_NAME . '_dynamic', ['https://shop.example/dynamic.css']);

        $output = (new StyleRenderer())->render('', false, true);

        $this->assertStringContainsString('dynamic.css', $output);
        $this->assertStringNotContainsString('static.css', $output);
    }
}
