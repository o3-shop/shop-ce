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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\GuaranteeLabelGenerator;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;

class ArticleGuaranteeLabelUrlTest extends UnitTestCase
{
    private function makeArticle(int $years, string $guarantor = 'ACME', string $model = 'X-1'): Article
    {
        $article = oxNew(Article::class);
        $article->setId('_labelurltest');
        $article->oxarticles__o3guaranteeyears = new Field($years);
        $article->oxarticles__o3guaranteeguarantor = new Field($guarantor);
        $article->oxarticles__o3guaranteemodel = new Field($model);
        return $article;
    }

    private function stubGenerator(?string $returnUrl, ?string $nestedUrl = null): GuaranteeLabelGenerator
    {
        $generator = $this->getMockBuilder(GuaranteeLabelGenerator::class)
            ->onlyMethods(['getLabelUrl', 'getNestedBannerUrl'])
            ->getMock();
        $generator->method('getLabelUrl')->willReturn($returnUrl);
        $generator->method('getNestedBannerUrl')->willReturn($nestedUrl);
        return $generator;
    }

    public function testReturnsNullWhenMasterSwitchOff(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', false);
        $article = $this->makeArticle(5);
        $article->setGuaranteeLabelGenerator($this->stubGenerator('http://x/label.png'));

        $this->assertNull($article->getDurabilityGuaranteeLabelUrl());
    }

    public function testReturnsNullWhenNotEligible(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->makeArticle(2);
        $article->setGuaranteeLabelGenerator($this->stubGenerator('http://x/label.png'));

        $this->assertNull($article->getDurabilityGuaranteeLabelUrl());
    }

    public function testReturnsNullWhenGuarantorUnresolvable(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $article->method('getManufacturer')->willReturn(null);
        $article->oxarticles__o3guaranteeyears = new Field(5);
        $article->oxarticles__o3guaranteeguarantor = new Field('');
        $article->oxarticles__o3guaranteemodel = new Field('X-1');
        $article->setGuaranteeLabelGenerator($this->stubGenerator('http://x/label.png'));

        $this->assertNull($article->getDurabilityGuaranteeLabelUrl());
    }

    public function testReturnsGeneratorUrlWhenEligible(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->makeArticle(5);
        $article->setGuaranteeLabelGenerator($this->stubGenerator('http://x/label.png'));

        $this->assertSame('http://x/label.png', $article->getDurabilityGuaranteeLabelUrl());
    }

    public function testReturnsNullWhenGenerationFails(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->makeArticle(5);
        $article->setGuaranteeLabelGenerator($this->stubGenerator(null));

        $this->assertNull($article->getDurabilityGuaranteeLabelUrl());
    }

    public function testNestedReturnsNullWhenMasterSwitchOff(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', false);
        $article = $this->makeArticle(5);
        $article->setGuaranteeLabelGenerator($this->stubGenerator(null, 'http://x/nested.png'));

        $this->assertNull($article->getDurabilityGuaranteeNestedUrl());
    }

    public function testNestedReturnsNullWhenNotEligible(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->makeArticle(2);
        $article->setGuaranteeLabelGenerator($this->stubGenerator(null, 'http://x/nested.png'));

        $this->assertNull($article->getDurabilityGuaranteeNestedUrl());
    }

    public function testNestedReturnsNullWhenGuarantorUnresolvable(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $article->method('getManufacturer')->willReturn(null);
        $article->oxarticles__o3guaranteeyears = new Field(5);
        $article->oxarticles__o3guaranteeguarantor = new Field('');
        $article->oxarticles__o3guaranteemodel = new Field('X-1');
        $article->setGuaranteeLabelGenerator($this->stubGenerator(null, 'http://x/nested.png'));

        $this->assertNull($article->getDurabilityGuaranteeNestedUrl());
    }

    public function testNestedReturnsGeneratorUrlWhenEligible(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->makeArticle(5);
        $article->setGuaranteeLabelGenerator($this->stubGenerator(null, 'http://x/nested.png'));

        $this->assertSame('http://x/nested.png', $article->getDurabilityGuaranteeNestedUrl());
    }

    public function testNestedReturnsNullWhenGenerationFails(): void
    {
        Registry::getConfig()->setConfigParam('blShowDurabilityGuaranteeLabel', true);
        $article = $this->makeArticle(5);
        $article->setGuaranteeLabelGenerator($this->stubGenerator(null, null));

        $this->assertNull($article->getDurabilityGuaranteeNestedUrl());
    }
}
