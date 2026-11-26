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

use OxidEsales\Eshop\Application\Controller\ContentController;
use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopControl;
use PHPUnit\Framework\TestCase;

/**
 * CMS content rendered on a page sees the page's variables (components such as oxcmp_shop, oViewConf) on both
 * engines. On the Smarty theme they reach the CMS text because the page itself was rendered by the same Smarty
 * instance; on a Twig theme that instance never saw the page, so UtilsView passes the active views' data.
 *
 * Separate processes: widgets send HTTP headers, IfContentLogic caches content per process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class CmsContentViewDataTest extends TestCase
{
    use FixtureThemeTrait;
    private const CMS_TEXT = 'shop:[{$oxcmp_shop->oxshops__oxname->value}];lang:[{$oViewConf->getActLanguageId()}];'
        . 'view:[{$oView->getClassKey()}];';

    /** @var Content[] */
    private $contents = [];

    /** @var array */
    private $originalGet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installFixtureTheme();
        $this->originalGet = $_GET;
        $_GET['oxloadid'] = 'c2_vars';
        $this->createContent('c2_vars', self::CMS_TEXT);
        $this->createContent('c2_widget', 'shop:[{$oxcmp_shop->oxshops__oxname->value}];');
    }

    protected function tearDown(): void
    {
        foreach ($this->contents as $content) {
            $content->delete();
        }
        $_GET = $this->originalGet;
        $this->uninstallFixtureTheme();
        parent::tearDown();
    }

    public function testTwigContentPageRendersPageVariablesInCmsContent(): void
    {
        // A missing variable is a PHP warning inside Smarty; the shop prints it empty, which is what is asserted.
        $output = $this->withoutPhpWarnings(function (): string {
            return $this->renderController(ContentController::class);
        });

        $this->assertStringContainsString('PARSED[' . $this->expected($this->freshViewKey()) . ']', $output);
        $this->assertStringContainsString('INCLUDE[' . $this->expected('content') . ']', $output);
        $this->assertStringContainsString('IFCONTENT[' . $this->expected('content') . ']', $output);
        $this->assertStringContainsString('WIDGET[shop:' . $this->shopName() . ';]', $output, 'CMS inside a widget sees the page components.');
    }

    public function testSmartyContentPageRendersPageVariablesInCmsContent(): void
    {
        $this->activateTheme('wave', '');

        $output = $this->withoutPhpWarnings(function (): string {
            return $this->renderController(ContentController::class);
        });

        $this->assertStringNotContainsString('TWIG-FIXTURE', $output);
        $this->assertStringContainsString($this->expected($this->freshViewKey()), $output);
    }

    public function testWithoutActiveViewCmsContentRendersWithoutPageVariables(): void
    {
        $config = Registry::getConfig();
        $this->assertSame([], $config->getActiveViewsList());

        $this->assertSame(
            'shop:;x',
            Registry::getUtilsView()->parseThroughSmarty('shop:[{$oxcmp_shop->oxshops__oxname->value}];x', 'c2_cli')
        );
        $this->assertSame([], $config->getActiveViewsList(), 'No view is pushed as a side effect.');
    }

    /**
     * Null-view callers (getParsedContent, long descriptions) get a fresh FrontendController as $oView, as before.
     */
    private function freshViewKey(): string
    {
        return (string) oxNew(FrontendController::class)->getClassKey();
    }

    private function expected(string $viewKey): string
    {
        return 'shop:' . $this->shopName() . ';lang:' . Registry::getLang()->getBaseLanguage() . ';view:' . $viewKey . ';';
    }

    private function shopName(): string
    {
        return (string) Registry::getConfig()->getActiveShop()->oxshops__oxname->value;
    }

    private function createContent(string $ident, string $text): void
    {
        $content = oxNew(Content::class);
        $content->oxcontents__oxloadid = new Field($ident);
        $content->oxcontents__oxshopid = new Field(Registry::getConfig()->getShopId());
        $content->oxcontents__oxactive = new Field(1);
        $content->oxcontents__oxtitle = new Field($ident);
        $content->oxcontents__oxcontent = new Field($text, Field::T_RAW);
        $content->save();
        $this->contents[] = $content;
    }

    /**
     * Pre-existing, not Twig related: Smarty theme pages raise PHP 8 E_WARNINGs (see StorefrontRenderingTest).
     */
    private function withoutPhpWarnings(callable $callback): string
    {
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
            return $callback();
        } finally {
            restore_error_handler();
        }
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
