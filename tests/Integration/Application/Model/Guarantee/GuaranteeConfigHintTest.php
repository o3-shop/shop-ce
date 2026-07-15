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

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Model\Guarantee;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\EshopCommunity\Application\Controller\Admin\GuaranteeConfigController;
use OxidEsales\TestingLibrary\UnitTestCase;
use ReflectionClass;

/**
 * Integration tests for the admin "no qualifying products" hint
 * ({@see GuaranteeConfigController::hasQualifyingProduct()}, issue #219).
 *
 * The hint must mirror {@see \OxidEsales\Eshop\Application\Model\Article::isDurabilityGuaranteeLabelEligible()}:
 * a product qualifies only when the (variant-inherited) durability guarantee
 * exceeds 24 months AND a guarantor is resolvable (own field, inherited from
 * the parent, or the linked manufacturer's title). A duration-only check is
 * wrong: it reports qualifying products that can never actually render a label
 * (no guarantor), and — once a guarantor condition is added — must still honour
 * variant field inheritance, which happens at load time, not in the DB row.
 */
class GuaranteeConfigHintTest extends UnitTestCase
{
    private const PREFIX = 'o3guartest_';

    protected function tearDown(): void
    {
        $database = DatabaseProvider::getDb();
        $database->execute(
            'DELETE FROM oxarticles WHERE OXID LIKE ?',
            [self::PREFIX . '%']
        );
        $database->execute(
            'DELETE FROM oxmanufacturers WHERE OXID LIKE ?',
            [self::PREFIX . '%']
        );
        parent::tearDown();
    }

    public function testStandaloneWithoutGuarantorDoesNotQualify(): void
    {
        // Duration > 24 but no guarantor and no manufacturer: the label can
        // never render, so the hint must NOT treat it as qualifying.
        $this->insertArticle('a', ['duration' => 36, 'guarantor' => '']);

        $this->assertFalse($this->hasQualifyingProduct());
    }

    public function testStandaloneWithGuarantorQualifies(): void
    {
        $this->insertArticle('a', ['duration' => 36, 'guarantor' => 'ACME Corp']);

        $this->assertTrue($this->hasQualifyingProduct());
    }

    public function testManufacturerTitleActsAsGuarantorFallback(): void
    {
        $this->insertManufacturer('m', 'MakerCo');
        $this->insertArticle('a', ['duration' => 36, 'guarantor' => '', 'manufacturer' => self::PREFIX . 'm']);

        $this->assertTrue($this->hasQualifyingProduct());
    }

    public function testDurationAtOrBelowThresholdDoesNotQualify(): void
    {
        // Exactly 24 months is "two years", not "more than two years".
        $this->insertArticle('a', ['duration' => 24, 'guarantor' => 'ACME Corp']);

        $this->assertFalse($this->hasQualifyingProduct());
    }

    public function testVariantInheritsParentGuarantor(): void
    {
        // Parent carries the guarantor but a duration at/below threshold; the
        // variant carries its own qualifying duration but an EMPTY guarantor
        // that is inherited from the parent at load time. Only inheritance-aware
        // logic sees this as qualifying — a raw guarantor check on the variant
        // row would wrongly reject it.
        $this->insertArticle('parent', ['duration' => 0, 'guarantor' => 'ACME Corp']);
        $this->insertArticle('child', [
            'duration'  => 30,
            'guarantor' => '',
            'parent'    => self::PREFIX . 'parent',
        ]);

        $this->assertTrue($this->hasQualifyingProduct());
    }

    public function testVariantInheritsParentDuration(): void
    {
        // Mirror image: parent carries the qualifying duration, the variant
        // carries its own guarantor but an empty (inherited) duration.
        $this->insertArticle('parent', ['duration' => 36, 'guarantor' => '']);
        $this->insertArticle('child', [
            'duration'  => 0,
            'guarantor' => 'ACME Corp',
            'parent'    => self::PREFIX . 'parent',
        ]);

        $this->assertTrue($this->hasQualifyingProduct());
    }

    /**
     * @param array{duration:int,guarantor:string,manufacturer?:string,parent?:string} $fields
     */
    private function insertArticle(string $suffix, array $fields): void
    {
        $database = DatabaseProvider::getDb();
        $database->execute(
            'INSERT INTO oxarticles '
            . '(OXID, OXSHOPID, OXPARENTID, OXACTIVE, OXTITLE, OXARTNUM, '
            . 'OXMANUFACTURERID, O3GUARANTEEDURATIONMONTHS, O3GUARANTEEGUARANTOR) '
            . 'VALUES (?, 1, ?, 1, ?, ?, ?, ?, ?)',
            [
                self::PREFIX . $suffix,
                $fields['parent'] ?? '',
                'Guarantee test article ' . $suffix,
                self::PREFIX . $suffix,
                $fields['manufacturer'] ?? '',
                $fields['duration'],
                $fields['guarantor'],
            ]
        );
    }

    private function insertManufacturer(string $suffix, string $title): void
    {
        $database = DatabaseProvider::getDb();
        $database->execute(
            'INSERT INTO oxmanufacturers (OXID, OXSHOPID, OXACTIVE, OXTITLE) VALUES (?, 1, 1, ?)',
            [self::PREFIX . $suffix, $title]
        );
    }

    private function hasQualifyingProduct(): bool
    {
        $reflection = new ReflectionClass(GuaranteeConfigController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('hasQualifyingProduct');
        $method->setAccessible(true);

        return (bool) $method->invoke($controller);
    }
}
