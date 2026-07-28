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
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Qualification + fallback-chain logic for the EU durability-guarantee
 * label (#219). The legal trigger (producer guarantee > 2 whole years,
 * Art. 6(1)(la) CRD) lives in exactly one predicate:
 * Article::isDurabilityGuaranteeEligible().
 */
class ArticleGuaranteeTest extends UnitTestCase
{
    private function makeArticle(array $fields = []): Article
    {
        $article = oxNew(Article::class);
        $article->setId('_guaranteetest');
        foreach ($fields as $field => $value) {
            $article->$field = new \OxidEsales\Eshop\Core\Field($value);
        }
        return $article;
    }

    /** @dataProvider yearsEligibilityProvider */
    public function testEligibilityThresholdIsMoreThanTwoYears(int $years, bool $expected): void
    {
        $article = $this->makeArticle(['oxarticles__o3guaranteeyears' => $years]);
        $this->assertSame($expected, $article->isDurabilityGuaranteeEligible());
        $this->assertSame($years, $article->getGuaranteeYears());
    }

    public function yearsEligibilityProvider(): array
    {
        return [
            'none' => [0, false],
            'one year' => [1, false],
            'exactly two years - legal minimum, not eligible' => [2, false],
            'three years' => [3, true],
            'ten years' => [10, true],
        ];
    }

    /**
     * Nullable column (#219 review): an unset years field is NULL, which must
     * read as 0 whole years and therefore never be label-eligible.
     */
    public function testNullYearsReadsAsZeroAndNotEligible(): void
    {
        $article = $this->makeArticle(['oxarticles__o3guaranteeyears' => null]);
        $this->assertSame(0, $article->getGuaranteeYears());
        $this->assertFalse($article->isDurabilityGuaranteeEligible());
    }

    public function testGuarantorPrefersOwnField(): void
    {
        $article = $this->makeArticle(['oxarticles__o3guaranteeguarantor' => 'ACME GmbH']);
        $this->assertSame('ACME GmbH', $article->getGuaranteeGuarantor());
    }

    public function testGuarantorFallsBackToManufacturerTitle(): void
    {
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $manufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
        $manufacturer->oxmanufacturers__oxtitle = new \OxidEsales\Eshop\Core\Field('Fallback Brand');
        $article->method('getManufacturer')->willReturn($manufacturer);
        $article->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('');

        $this->assertSame('Fallback Brand', $article->getGuaranteeGuarantor());
    }

    /**
     * #226 item 2: getManufacturer() does a fresh oxNew() + load() on every
     * call, and the guarantor is resolved up to 3x per article render on the
     * documented empty-field fallback path (twice from core, once from the
     * theme). That is 3 SELECTs from oxmanufacturers per article. Resolve once.
     */
    public function testGuarantorResolvesTheManufacturerOnlyOncePerInstance(): void
    {
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $manufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
        $manufacturer->oxmanufacturers__oxtitle = new \OxidEsales\Eshop\Core\Field('Fallback Brand');
        $article->expects($this->once())->method('getManufacturer')->willReturn($manufacturer);
        $article->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('');

        $this->assertSame('Fallback Brand', $article->getGuaranteeGuarantor());
        $this->assertSame('Fallback Brand', $article->getGuaranteeGuarantor());
        $this->assertSame('Fallback Brand', $article->getGuaranteeGuarantor());
    }

    /**
     * The empty guarantor is the expensive case AND a meaningful value ('' =
     * "cannot render"), so it must be memoised too - a null-coalescing cache
     * would re-resolve it on every call.
     */
    public function testEmptyGuarantorIsMemoisedToo(): void
    {
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $article->expects($this->once())->method('getManufacturer')->willReturn(null);
        $article->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('');

        $this->assertSame('', $article->getGuaranteeGuarantor());
        $this->assertSame('', $article->getGuaranteeGuarantor());
    }

    /**
     * Memoisation must not outlive the record: OXID reuses model instances, so
     * a re-load()ed article must never serve the previous article's guarantor.
     */
    public function testGuarantorMemoisationIsResetOnAssign(): void
    {
        $first = oxNew(Article::class);
        $first->setId('_guaranteememo1');
        $first->oxarticles__oxartnum = new \OxidEsales\Eshop\Core\Field('MEMO-1');
        $first->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('First Brand');
        $first->save();

        $second = oxNew(Article::class);
        $second->setId('_guaranteememo2');
        $second->oxarticles__oxartnum = new \OxidEsales\Eshop\Core\Field('MEMO-2');
        $second->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('Second Brand');
        $second->save();

        $article = oxNew(Article::class);
        $article->load('_guaranteememo1');
        $this->assertSame('First Brand', $article->getGuaranteeGuarantor());

        $article->load('_guaranteememo2');
        $this->assertSame('Second Brand', $article->getGuaranteeGuarantor());
    }

    /**
     * #226 item 5 wiring: saving an article must collect its own orphaned
     * labels. The generator-level behaviour is covered in
     * GuaranteeLabelGeneratorTest; this proves save() actually calls it.
     */
    public function testSavingAnArticleCollectsItsOrphanedLabels(): void
    {
        $targetDir = sys_get_temp_dir() . '/guarantee_purge_' . uniqid() . '/';
        mkdir($targetDir, 0777, true);
        $orphan = $targetDir . '_guaranteepurge_' . str_repeat('a', 32) . '.png';
        $foreign = $targetDir . 'otherarticle_' . str_repeat('b', 32) . '.png';
        file_put_contents($orphan, 'stale');
        file_put_contents($foreign, 'keep');

        $generator = oxNew(\OxidEsales\Eshop\Core\GuaranteeLabelGenerator::class);
        $generator->setTargetDir($targetDir);

        try {
            $article = oxNew(Article::class);
            $article->setId('_guaranteepurge');
            $article->oxarticles__oxartnum = new \OxidEsales\Eshop\Core\Field('PURGE-1');
            $article->setGuaranteeLabelGenerator($generator);
            $article->save();

            $this->assertFileDoesNotExist($orphan, 'save() must collect the article\'s orphaned labels.');
            $this->assertFileExists($foreign, 'save() must never touch another article\'s labels.');
        } finally {
            @unlink($orphan);
            @unlink($foreign);
            @rmdir($targetDir);
        }
    }

    public function testGuarantorEmptyWhenNoFieldAndNoManufacturer(): void
    {
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $article->method('getManufacturer')->willReturn(null);
        $article->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('');

        $this->assertSame('', $article->getGuaranteeGuarantor());
    }

    /**
     * The linked manufacturer exists but carries no title: the label component
     * is still unresolvable, so the getter must yield '' (the caller treats ''
     * as "cannot render the mandatory guarantor line").
     */
    public function testGuarantorEmptyWhenManufacturerHasEmptyTitle(): void
    {
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $manufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
        $manufacturer->oxmanufacturers__oxtitle = new \OxidEsales\Eshop\Core\Field('');
        $article->method('getManufacturer')->willReturn($manufacturer);
        $article->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('');

        $this->assertSame('', $article->getGuaranteeGuarantor());
    }

    /**
     * Round-trip regression (#219): the guarantor is composited as PLAINTEXT
     * onto the official label artwork. An ampersand in the brand name must
     * survive a save/load cycle unescaped - the getter must never return the
     * HTML-entity form (`H&amp;M GmbH`), which would be baked into the PNG and
     * re-encoded again in templates.
     */
    public function testGuarantorRoundTripReturnsPlaintextAmpersand(): void
    {
        $article = oxNew(Article::class);
        $article->setId('_guaranteeampersand');
        $article->oxarticles__oxartnum = new \OxidEsales\Eshop\Core\Field('AMP-1');
        $article->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('H&M GmbH');
        $article->save();

        $loaded = oxNew(Article::class);
        $loaded->load('_guaranteeampersand');

        $this->assertSame('H&M GmbH', $loaded->getGuaranteeGuarantor());
    }

    /**
     * Round-trip regression (#219): a model identifier with a double quote
     * (`19" Rack`) must come back verbatim, not as `19&quot; Rack`.
     */
    public function testModelRoundTripReturnsPlaintextQuotes(): void
    {
        $article = oxNew(Article::class);
        $article->setId('_guaranteemodelquotes');
        $article->oxarticles__oxartnum = new \OxidEsales\Eshop\Core\Field('RACK-1');
        $article->oxarticles__o3guaranteemodel = new \OxidEsales\Eshop\Core\Field('19" Rack');
        $article->save();

        $loaded = oxNew(Article::class);
        $loaded->load('_guaranteemodelquotes');

        $this->assertSame('19" Rack', $loaded->getGuaranteeModel());
    }

    /**
     * Round-trip regression (#219): guarantee conditions text with an
     * ampersand must round-trip as plaintext (`Terms & conditions apply.`),
     * not `Terms &amp; conditions apply.`.
     */
    public function testConditionsRoundTripReturnsPlaintextAmpersand(): void
    {
        $article = oxNew(Article::class);
        $article->setId('_guaranteeconditionsamp');
        $article->oxarticles__oxartnum = new \OxidEsales\Eshop\Core\Field('COND-1');
        $article->oxarticles__o3guaranteeconditions = new \OxidEsales\Eshop\Core\Field('Terms & conditions apply.');
        $article->save();

        $loaded = oxNew(Article::class);
        $loaded->load('_guaranteeconditionsamp');

        $this->assertSame('Terms & conditions apply.', $loaded->getGuaranteeConditions());
    }

    public function testModelFallbackChainFieldThenArtnumThenTitle(): void
    {
        $article = $this->makeArticle([
            'oxarticles__o3guaranteemodel' => 'X-2000',
            'oxarticles__oxartnum' => 'ART-1',
            'oxarticles__oxtitle' => 'Widget',
        ]);
        $this->assertSame('X-2000', $article->getGuaranteeModel());

        $article = $this->makeArticle([
            'oxarticles__o3guaranteemodel' => '',
            'oxarticles__oxartnum' => 'ART-1',
            'oxarticles__oxtitle' => 'Widget',
        ]);
        $this->assertSame('ART-1', $article->getGuaranteeModel());

        $article = $this->makeArticle([
            'oxarticles__o3guaranteemodel' => '',
            'oxarticles__oxartnum' => '',
            'oxarticles__oxtitle' => 'Widget',
        ]);
        $this->assertSame('Widget', $article->getGuaranteeModel());
    }

    public function testConditionsReturnEmptyStringWhenUnset(): void
    {
        $article = $this->makeArticle([]);
        $this->assertSame('', $article->getGuaranteeConditions());

        $article = $this->makeArticle(['oxarticles__o3guaranteeconditions' => 'Full terms here.']);
        $this->assertSame('Full terms here.', $article->getGuaranteeConditions());
    }

    /**
     * Variant inheritance: a child with years = 0 must inherit the parent's
     * value. oxarticles ints are only treated as "empty" (= inheritable) when
     * whitelisted in Article::_isFieldEmpty()'s zero-value list — this test
     * pins that whitelisting.
     */
    public function testVariantChildWithZeroYearsInheritsParentValue(): void
    {
        $parent = oxNew(Article::class);
        $parent->setId('_guaranteeparent');
        $parent->oxarticles__oxartnum = new \OxidEsales\Eshop\Core\Field('PARENT-1');
        $parent->oxarticles__o3guaranteeyears = new \OxidEsales\Eshop\Core\Field(5);
        $parent->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('ACME GmbH');
        $parent->save();

        $child = oxNew(Article::class);
        $child->setId('_guaranteechild');
        $child->oxarticles__oxparentid = new \OxidEsales\Eshop\Core\Field('_guaranteeparent');
        $child->oxarticles__o3guaranteeyears = new \OxidEsales\Eshop\Core\Field(0);
        $child->oxarticles__o3guaranteeguarantor = new \OxidEsales\Eshop\Core\Field('');
        $child->save();

        $loaded = oxNew(Article::class);
        $loaded->load('_guaranteechild');

        $this->assertSame(5, $loaded->getGuaranteeYears());
        $this->assertSame('ACME GmbH', $loaded->getGuaranteeGuarantor());
        $this->assertTrue($loaded->isDurabilityGuaranteeEligible());
    }
}
