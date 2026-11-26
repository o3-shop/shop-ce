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

use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use PHPUnit\Framework\TestCase;

/**
 * CMS content inside Twig templates (ifcontent, include_content) is parsed by Smarty, as in the Smarty theme.
 *
 * Separate processes: IfContentLogic caches loaded content per process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class CmsContentTwigTest extends TestCase
{
    use FixtureThemeTrait;

    /** @var Content[] */
    private $contents = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->installFixtureTheme();
        $this->setProductiveMode(true);
    }

    protected function tearDown(): void
    {
        foreach ($this->contents as $content) {
            $content->delete();
        }
        $this->uninstallFixtureTheme();
        parent::tearDown();
    }

    public function testIfContentBodyIsParsedBySmarty(): void
    {
        $this->createContent('c1_ifcontent', '<b>A[{ "B" }]C</b>');

        $this->assertSame(
            '[<b>ABC</b>]',
            $this->render('[{% ifcontent ident "c1_ifcontent" set oCont %}{{ oCont.oxcontents__oxcontent.value|raw }}{% endifcontent %}]')
        );
    }

    public function testIfContentInsideSetCaptureAssignsTheParsedBody(): void
    {
        $this->createContent('c1_ifcontent_set', 'A[{ "B" }]C');

        $this->assertSame(
            '|ABC|',
            $this->render('{% set x %}{% ifcontent ident "c1_ifcontent_set" %}{{ oCont.oxcontents__oxcontent.value|raw }}{% endifcontent %}{% endset %}|{{ x }}|')
        );
    }

    public function testIncludeContentRendersParsedContentUnescaped(): void
    {
        $this->createContent('c1_include', '<b>A[{ "B" }]C</b>', 'Title [{if 1}]T[{/if}]');

        $this->assertSame('[<b>ABC</b>]', $this->render("[{{ include_content('c1_include') }}]"));
        $this->assertSame('Title T', $this->render("{{ include_content('c1_include', 'oxtitle') }}"));
    }

    public function testIncludeContentLoadsByOxid(): void
    {
        $oxid = $this->createContent('c1_include_oxid', 'A[{ "B" }]C')->getId();

        $this->assertSame('ABC', $this->render("{{ include_content(oxid = '" . $oxid . "') }}"));
    }

    public function testIncludeContentSeesTheTemplateVariables(): void
    {
        $this->createContent('c1_include_vars', 'Hello [{ $greeting }]');

        $this->assertSame('Hello World', $this->render("{{ include_content('c1_include_vars') }}", ['greeting' => 'World']));
    }

    public function testTwigSyntaxInCmsContentIsNotExecuted(): void
    {
        $this->createContent('c1_twig_syntax', 'X{{ 7*7 }}[{ "Y" }]');

        $this->assertSame('X{{ 7*7 }}Y', $this->render("{{ include_content('c1_twig_syntax') }}"));
        $this->assertSame(
            'X{{ 7*7 }}Y',
            $this->render('{% ifcontent ident "c1_twig_syntax" %}{{ oCont.oxcontents__oxcontent.value|raw }}{% endifcontent %}')
        );
    }

    public function testMissingOrInactiveContentRendersNothingInProductiveMode(): void
    {
        $this->createContent('c1_inactive', 'X', '', false);

        $this->assertSame('[]', $this->render("[{{ include_content('c1_missing') }}]"));
        $this->assertSame('[]', $this->render("[{{ include_content('c1_inactive') }}]"));
        $this->assertSame('[]', $this->render('[{% ifcontent ident "c1_missing" %}body{% endifcontent %}]'));
    }

    public function testMissingContentShowsAHintOutsideProductiveMode(): void
    {
        $this->setProductiveMode(false);

        $this->assertSame(
            '<b>content not found ! check ident(c1_missing) !</b>',
            $this->render("{{ include_content('c1_missing') }}")
        );
    }

    public function testIncludeContentReturnsTheRawFieldWhenSmartyIsDeactivatedForCmsContent(): void
    {
        $this->createContent('c1_no_smarty', 'A[{ "B" }]C');
        Registry::getConfig()->setConfigParam('deactivateSmartyForCmsContent', true);

        $this->assertSame('A[{ "B" }]C', $this->render("{{ include_content('c1_no_smarty') }}"));
    }

    private function render(string $template, array $context = []): string
    {
        return ContainerFactory::getInstance()->getContainer()
            ->get('oxid_esales.templating.twig_engine')
            ->renderFragment($template, 'ox:' . md5($template), $context);
    }

    private function createContent(string $ident, string $text, string $title = '', bool $active = true): Content
    {
        $content = oxNew(Content::class);
        $content->oxcontents__oxloadid = new Field($ident);
        $content->oxcontents__oxshopid = new Field(Registry::getConfig()->getShopId());
        $content->oxcontents__oxactive = new Field($active ? 1 : 0);
        $content->oxcontents__oxtitle = new Field($title, Field::T_RAW);
        $content->oxcontents__oxcontent = new Field($text, Field::T_RAW);
        $content->save();

        return $this->contents[] = $content;
    }

    private function setProductiveMode(bool $productive): void
    {
        Registry::getConfig()->getActiveShop()->oxshops__oxproductive = new Field($productive ? 1 : 0);
    }
}
