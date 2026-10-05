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

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\SeoEncoder;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsUrl;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic\SeoUrlLogic;

class SeoUrlLogicTest_StubArticle extends Article
{
    public static bool $loadReturns = true;
    public static string $link = 'https://shop.example/article-link.html';
    public bool $priceLoadDisabled = false;
    public bool $variantLoadingDisabled = false;
    public ?string $loadedWith = null;

    public function __construct($params = null)
    {
    }

    public function disablePriceLoad()
    {
        $this->priceLoadDisabled = true;
    }

    public function setNoVariantLoading($flag)
    {
        $this->variantLoadingDisabled = (bool) $flag;
    }

    public function load($oxId)
    {
        $this->loadedWith = (string) $oxId;
        return self::$loadReturns;
    }

    public function getLink($iLang = null, $blMain = false)
    {
        return self::$link;
    }
}

class SeoUrlLogicTest_StubContent extends Content
{
    public static bool $loadByIdentReturns = true;
    public static string $link = 'https://shop.example/content-link.html';
    public ?string $loadedByIdent = null;

    public function __construct()
    {
    }

    public function loadByIdent($loadId, $onlyActive = false)
    {
        $this->loadedByIdent = (string) $loadId;
        return self::$loadByIdentReturns;
    }

    public function getLink($iLang = null)
    {
        return self::$link;
    }
}

class SeoUrlLogicTest extends \OxidTestCase
{
    public function testReturnsEmptyStringWhenNoUsableParams(): void
    {
        $logic = new SeoUrlLogic();
        $this->assertSame('', $logic->seoUrl([]));
    }

    public function testReturnsArticleLinkForOxArticleType(): void
    {
        SeoUrlLogicTest_StubArticle::$loadReturns = true;
        SeoUrlLogicTest_StubArticle::$link = 'https://shop.example/article-7.html';
        \oxTestModules::addModuleObject('oxarticle', new SeoUrlLogicTest_StubArticle());

        $logic = new SeoUrlLogic();
        $this->assertSame(
            'https://shop.example/article-7.html',
            $logic->seoUrl(['type' => 'oxarticle', 'oxid' => 'art-7'])
        );
    }

    public function testReturnsContentLinkForOxContentTypeWithIdent(): void
    {
        SeoUrlLogicTest_StubContent::$loadByIdentReturns = true;
        SeoUrlLogicTest_StubContent::$link = 'https://shop.example/about.html';
        \oxTestModules::addModuleObject('oxcontent', new SeoUrlLogicTest_StubContent());

        $logic = new SeoUrlLogic();
        $this->assertSame(
            'https://shop.example/about.html',
            $logic->seoUrl(['type' => 'oxcontent', 'ident' => 'about'])
        );
    }

    public function testReturnsStaticUrlWhenSeoActiveAndStaticUrlAvailable(): void
    {
        $utils = $this->getMock(Utils::class, ['seoIsActive']);
        $utils->expects($this->any())->method('seoIsActive')->willReturn(true);
        Registry::set(Utils::class, $utils);

        $encoder = $this->getMockBuilder(SeoEncoder::class)
            ->onlyMethods(['getStaticUrl'])
            ->getMock();
        $encoder->expects($this->once())
            ->method('getStaticUrl')
            ->with('http://example.com/page')
            ->willReturn('https://shop.example/seo/page');
        Registry::set(SeoEncoder::class, $encoder);

        $logic = new SeoUrlLogic();
        $this->assertSame(
            'https://shop.example/seo/page',
            $logic->seoUrl(['ident' => 'http://example.com/page'])
        );
    }

    public function testFallsBackToProcessUrlWhenStaticUrlIsEmpty(): void
    {
        $utils = $this->getMock(Utils::class, ['seoIsActive']);
        $utils->expects($this->any())->method('seoIsActive')->willReturn(true);
        Registry::set(Utils::class, $utils);

        $encoder = $this->getMockBuilder(SeoEncoder::class)
            ->onlyMethods(['getStaticUrl'])
            ->getMock();
        $encoder->expects($this->any())->method('getStaticUrl')->willReturn('');
        Registry::set(SeoEncoder::class, $encoder);

        $utilsUrl = $this->getMock(UtilsUrl::class, ['processUrl']);
        $utilsUrl->expects($this->once())
            ->method('processUrl')
            ->with('http://example.com/page')
            ->willReturn('http://example.com/page?lang=1');
        Registry::set(UtilsUrl::class, $utilsUrl);

        $logic = new SeoUrlLogic();
        $this->assertSame(
            'http://example.com/page?lang=1',
            $logic->seoUrl(['ident' => 'http://example.com/page'])
        );
    }

    public function testReturnsEmptyStringWhenArticleLoadFails(): void
    {
        SeoUrlLogicTest_StubArticle::$loadReturns = false;
        \oxTestModules::addModuleObject('oxarticle', new SeoUrlLogicTest_StubArticle());

        $logic = new SeoUrlLogic();
        // Type given but article fails to load → URL stays at the seed
        // (which was null, so empty string).
        $this->assertSame('', $logic->seoUrl(['type' => 'oxarticle', 'oxid' => 'missing']));
    }
}
