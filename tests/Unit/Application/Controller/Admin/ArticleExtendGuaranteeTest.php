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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Application\Controller\Admin\ArticleExtend;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Non-blocking guarantee-field advisories on the article "Extended" tab.
 * They inform, never block (house graceful-degradation rule).
 */
class ArticleExtendGuaranteeTest extends UnitTestCase
{
    /**
     * Exercises the advisory logic directly on the already-loaded article
     * (post-review: collectGuaranteeAdvisories() no longer re-loads it, so
     * there is no render()/DB round-trip to drive here).
     */
    private function advisoriesFor(int $years, string $guarantor, ?string $manufacturerTitle = null): array
    {
        $article = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getManufacturer'])
            ->getMock();
        $manufacturer = null;
        if ($manufacturerTitle !== null) {
            $manufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
            $manufacturer->oxmanufacturers__oxtitle = new Field($manufacturerTitle);
        }
        $article->method('getManufacturer')->willReturn($manufacturer);
        $article->oxarticles__o3guaranteeyears = new Field($years);
        $article->oxarticles__o3guaranteeguarantor = new Field($guarantor);

        $controller = oxNew(ArticleExtend::class);
        $method = new \ReflectionMethod(ArticleExtend::class, 'collectGuaranteeAdvisories');
        $method->setAccessible(true);

        return $method->invoke($controller, $article);
    }

    public function testNoWarningsWhenNoGuaranteeEntered(): void
    {
        $this->assertSame([], $this->advisoriesFor(0, ''));
    }

    public function testShortDurationYieldsNotEligibleInfo(): void
    {
        $warnings = $this->advisoriesFor(2, 'ACME');
        $this->assertContains('O3_GUARANTEE_ADMIN_WARN_NOT_ELIGIBLE', $warnings);
    }

    public function testEligibleWithoutResolvableGuarantorYieldsWarning(): void
    {
        $warnings = $this->advisoriesFor(5, '', null);
        $this->assertContains('O3_GUARANTEE_ADMIN_WARN_NO_GUARANTOR', $warnings);
    }

    public function testEligibleWithManufacturerFallbackYieldsNoGuarantorWarning(): void
    {
        $warnings = $this->advisoriesFor(5, '', 'Brand Co');
        $this->assertNotContains('O3_GUARANTEE_ADMIN_WARN_NO_GUARANTOR', $warnings);
    }
}
