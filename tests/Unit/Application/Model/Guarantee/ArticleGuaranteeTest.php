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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Model\Guarantee;

use oxField;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Manufacturer;

/**
 * EU harmonised guarantee labels (EmpCo Directive (EU) 2024/825, issue #219).
 *
 * Covers the qualification logic added to {@see Article}:
 *   - duration-threshold (>24 months) trigger
 *   - guarantor fallback chain (own field -> linked manufacturer title)
 *   - variant/parent inheritance for the duration field (0 = unset, copies
 *     from the parent article like other zero-meaningful numeric fields)
 */
class ArticleGuaranteeTest extends \OxidTestCase
{
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxarticles');
        $this->cleanUpTable('oxmanufacturers');
        parent::tearDown();
    }

    public function testGuaranteeDurationDefaultsToZero(): void
    {
        $article = $this->createArticle('_testGuaranteeArt1');

        $this->assertSame(0, $article->getGuaranteeDurationMonths());
        $this->assertFalse($article->isDurationGuaranteeEligible());
    }

    public function testNotEligibleAtExactlyTwoYears(): void
    {
        $article = $this->createArticle('_testGuaranteeArt2', 24, 'Acme');

        $this->assertFalse(
            $article->isDurationGuaranteeEligible(),
            'Exactly 24 months is not "more than two years" (Art. 22a).'
        );
    }

    public function testEligibleWhenDurationExceedsTwoYearsAndGuarantorIsSet(): void
    {
        $article = $this->createArticle('_testGuaranteeArt3', 25, 'Acme');

        $this->assertTrue($article->isDurationGuaranteeEligible());
        $this->assertSame('Acme', $article->getGuaranteeGuarantor());
        $this->assertTrue($article->isDurabilityGuaranteeLabelEligible());
    }

    public function testNotLabelEligibleWhenNoGuarantorCanBeResolved(): void
    {
        $article = $this->createArticle('_testGuaranteeArt4', 36, '');

        $this->assertTrue($article->isDurationGuaranteeEligible());
        $this->assertSame('', $article->getGuaranteeGuarantor());
        $this->assertFalse(
            $article->isDurabilityGuaranteeLabelEligible(),
            'No guarantor and no manufacturer to fall back to -> not eligible.'
        );
    }

    public function testGuarantorFallsBackToManufacturerTitleWhenOwnFieldEmpty(): void
    {
        $manufacturer = oxNew(Manufacturer::class);
        $manufacturer->setId('_testGuaranteeMan1');
        $manufacturer->oxmanufacturers__oxtitle = new oxField('Manufacturer Brand', oxField::T_RAW);
        $manufacturer->oxmanufacturers__oxactive = new oxField(1, oxField::T_RAW);
        $manufacturer->save();

        $article = $this->createArticle('_testGuaranteeArt5', 36, '');
        $article->oxarticles__oxmanufacturerid = new oxField('_testGuaranteeMan1', oxField::T_RAW);
        $article->save();

        $this->assertSame('Manufacturer Brand', $article->getGuaranteeGuarantor());
        $this->assertTrue($article->isDurabilityGuaranteeLabelEligible());

        $manufacturer->delete();
    }

    public function testGuarantorFallsBackToManufacturerTitleEvenWhenManufacturerIsInactive(): void
    {
        $manufacturer = oxNew(Manufacturer::class);
        $manufacturer->setId('_testGuaranteeMan2');
        $manufacturer->oxmanufacturers__oxtitle = new oxField('Inactive Brand', oxField::T_RAW);
        $manufacturer->oxmanufacturers__oxactive = new oxField(0, oxField::T_RAW);
        $manufacturer->save();

        $article = $this->createArticle('_testGuaranteeArt7', 36, '');
        $article->oxarticles__oxmanufacturerid = new oxField('_testGuaranteeMan2', oxField::T_RAW);
        $article->save();

        $this->assertSame(
            'Inactive Brand',
            $article->getGuaranteeGuarantor(),
            'The legal guarantor fallback must not depend on the storefront-visibility '
            . '"active" flag of the linked manufacturer.'
        );
        $this->assertTrue($article->isDurabilityGuaranteeLabelEligible());

        $manufacturer->delete();
    }

    public function testGuaranteeConditionsIsNullWhenEmpty(): void
    {
        $article = $this->createArticle('_testGuaranteeArt6');

        $this->assertNull($article->getGuaranteeConditions());
    }

    public function testGuaranteeModelUsesOwnFieldWhenSet(): void
    {
        $article = $this->createArticle('_testGuaranteeArt8', 36, 'Acme');
        $article->oxarticles__oxartnum = new oxField('ART-123', oxField::T_RAW);
        $article->oxarticles__o3guaranteemodel = new oxField('SuperWidget X1', oxField::T_RAW);
        $article->save();

        $this->assertSame('SuperWidget X1', $article->getGuaranteeModel());
    }

    public function testGuaranteeModelFallsBackToArtNumWhenOwnFieldEmpty(): void
    {
        $article = $this->createArticle('_testGuaranteeArt9', 36, 'Acme');
        $article->oxarticles__oxartnum = new oxField('ART-456', oxField::T_RAW);
        $article->save();

        $this->assertSame(
            'ART-456',
            $article->getGuaranteeModel(),
            'An empty model identifier must fall back to the article number.'
        );
    }

    public function testVariantInheritsParentModelWhenOwnValueIsUnset(): void
    {
        $parent = $this->createArticle('_testGuaranteeParent3', 36, 'Acme');
        $parent->oxarticles__o3guaranteemodel = new oxField('ParentModel', oxField::T_RAW);
        $parent->save();

        $variant = oxNew(Article::class);
        $variant->setId('_testGuaranteeVar3');
        $variant->oxarticles__oxparentid = new oxField('_testGuaranteeParent3', oxField::T_RAW);
        $variant->oxarticles__oxshopid = new oxField($this->getConfig()->getBaseShopId(), oxField::T_RAW);
        $variant->oxarticles__oxtitle = new oxField('variant', oxField::T_RAW);
        $variant->save();

        $loadedVariant = oxNew(Article::class);
        $loadedVariant->load('_testGuaranteeVar3');

        $this->assertSame(
            'ParentModel',
            $loadedVariant->getGuaranteeModel(),
            'A variant with no own model identifier must inherit the parent value.'
        );
    }

    public function testVariantInheritsParentDurationWhenOwnValueIsUnset(): void
    {
        $parent = $this->createArticle('_testGuaranteeParent1', 36, 'Acme');

        $variant = oxNew(Article::class);
        $variant->setId('_testGuaranteeVar1');
        $variant->oxarticles__oxparentid = new oxField('_testGuaranteeParent1', oxField::T_RAW);
        $variant->oxarticles__oxshopid = new oxField($this->getConfig()->getBaseShopId(), oxField::T_RAW);
        $variant->oxarticles__oxtitle = new oxField('variant', oxField::T_RAW);
        $variant->save();

        $loadedVariant = oxNew(Article::class);
        $loadedVariant->load('_testGuaranteeVar1');

        $this->assertSame(
            36,
            $loadedVariant->getGuaranteeDurationMonths(),
            'A variant with no own duration (0) must inherit the parent article value.'
        );
        $this->assertSame('Acme', $loadedVariant->getGuaranteeGuarantor());
    }

    public function testVariantOwnDurationOverridesParent(): void
    {
        $this->createArticle('_testGuaranteeParent2', 36, 'Acme');

        $variant = oxNew(Article::class);
        $variant->setId('_testGuaranteeVar2');
        $variant->oxarticles__oxparentid = new oxField('_testGuaranteeParent2', oxField::T_RAW);
        $variant->oxarticles__oxshopid = new oxField($this->getConfig()->getBaseShopId(), oxField::T_RAW);
        $variant->oxarticles__oxtitle = new oxField('variant', oxField::T_RAW);
        $variant->oxarticles__o3guaranteedurationmonths = new oxField(30, oxField::T_RAW);
        $variant->save();

        $loadedVariant = oxNew(Article::class);
        $loadedVariant->load('_testGuaranteeVar2');

        $this->assertSame(30, $loadedVariant->getGuaranteeDurationMonths());
    }

    private function createArticle(string $id, int $durationMonths = 0, ?string $guarantor = null): Article
    {
        $article = oxNew(Article::class);
        $article->setId($id);
        $article->oxarticles__oxprice = new oxField(15.5, oxField::T_RAW);
        $article->oxarticles__oxshopid = new oxField($this->getConfig()->getBaseShopId(), oxField::T_RAW);
        $article->oxarticles__oxtitle = new oxField('test', oxField::T_RAW);
        $article->oxarticles__o3guaranteedurationmonths = new oxField($durationMonths, oxField::T_RAW);
        if ($guarantor !== null) {
            $article->oxarticles__o3guaranteeguarantor = new oxField($guarantor, oxField::T_RAW);
        }
        $article->save();

        return $article;
    }
}
