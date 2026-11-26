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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Twig;

use OxidEsales\Eshop\Application\Controller\StartController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopControl;
use PHPUnit\Framework\TestCase;

/**
 * End to end: a storefront controller rendered through the shop pipeline with the Twig fixture theme
 * (Fixtures/views/twigfixture), and the Smarty theme rendering as before.
 *
 * Separate processes: widgets send HTTP headers while the page renders, which fails once PHPUnit has printed.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class StorefrontRenderingTest extends TestCase
{
    use FixtureThemeTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installFixtureTheme();
    }

    protected function tearDown(): void
    {
        $this->uninstallFixtureTheme();
        parent::tearDown();
    }

    public function testStartPageRendersWithFixtureTheme(): void
    {
        $output = $this->renderController(StartController::class);

        $this->assertStringContainsString('TWIG-FIXTURE-LAYOUT', $output);
        $this->assertStringContainsString('view:start;', $output);
        $this->assertMatchesRegularExpression(
            '/TWIG-FIXTURE-START-BEGIN.*TWIG-FIXTURE-MINIBASKET.*TWIG-FIXTURE-START-END/s',
            $output,
            'The widget output is part of the page, at the place of include_widget.'
        );
        $this->assertStringContainsString(
            'translated:' . Registry::getLang()->translateString('HOME') . ';',
            $output
        );
        $this->assertStringContainsString('price:1.234,50', $output);
        $this->assertStringContainsString('escaped:&lt;b&gt;;', $output);
    }

    public function testSmartyThemeUnaffected(): void
    {
        $this->activateTheme('o3-theme', '');

        // Pre-existing, not Twig related: the o3-theme start page raises PHP 8 E_WARNINGs (unset Smarty
        // variables such as $removeFunction in widget/product/listitem_line.tpl, Category::$_blIsVisible read
        // from a template). The shop only reports them; PHPUnit would throw. ShopControl::_runOnce() resets
        // error_reporting per widget, so plain E_WARNINGs are swallowed here and everything else still reaches
        // PHPUnit's handler.
        $previous = null;
        $previous = set_error_handler(
            static function (int $level, string $message, string $file = '', int $line = 0) use (&$previous): bool {
                if ($level === E_WARNING) {
                    return true;
                }

                return $previous ? (bool) $previous($level, $message, $file, $line) : false;
            }
        );
        try {
            $output = $this->renderController(StartController::class);
        } finally {
            restore_error_handler();
        }

        $this->assertStringNotContainsString('TWIG-FIXTURE', $output);
        $this->assertStringContainsString('id="newItems"', $output, 'o3-theme page/shop/start.tpl is rendered.');
        $this->assertStringContainsString('id="offcanvas__basket"', $output, 'o3-theme minibasket widget is rendered.');
    }

    private function renderController(string $class): string
    {
        $shopControl = new class () extends ShopControl {
            public function renderController(string $class): string
            {
                $view = $this->_initializeViewObject($class, null);
                try {
                    return $this->formOutput($view);
                } finally {
                    $this->getConfig()->dropLastActiveView();
                }
            }
        };

        return $shopControl->renderController($class);
    }
}
