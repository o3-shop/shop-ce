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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Guarantee;

use OxidEsales\Eshop\Core\GuaranteeLabelGenerator;
use OxidEsales\TestingLibrary\UnitTestCase;

/**
 * Smoke test against the REAL shipped artwork + fonts (no synthetic
 * fixtures): assets exist, are valid PNGs/TTFs, and composition succeeds.
 */
class GuaranteeLabelArtworkSmokeTest extends UnitTestCase
{
    public function testShippedAssetsExistAndAreValid(): void
    {
        $assetDir = OX_BASE_PATH . 'Core/GuaranteeLabel/assets/';

        $template = getimagesize($assetDir . 'label-template.png');
        $this->assertNotFalse($template);
        $this->assertSame('image/png', $template['mime']);
        $this->assertGreaterThanOrEqual(1000, $template[0], 'Label template must be >= 1000px wide.');

        $nested = getimagesize($assetDir . 'nested-template.png');
        $this->assertNotFalse($nested, 'Nested banner template must exist.');
        $this->assertSame('image/png', $nested['mime']);
        $this->assertGreaterThanOrEqual(1000, $nested[0], 'Nested banner template must be >= 1000px wide.');
        $this->assertGreaterThan($nested[1], $nested[0], 'Nested banner template must be landscape (wide banner).');

        foreach (['Inter-Regular.ttf', 'Inter-SemiBold.ttf', 'Inter-ExtraBold.ttf'] as $font) {
            $this->assertFileExists($assetDir . $font);
            $this->assertGreaterThan(10000, filesize($assetDir . $font), "Font '$font' looks truncated.");
        }

        foreach (['notice-de.png', 'notice-en.png'] as $notice) {
            $info = getimagesize(OX_BASE_PATH . 'out/pictures/guarantee/' . $notice);
            $this->assertNotFalse($info, "Notice artwork '$notice' must exist.");
            $this->assertSame('image/png', $info['mime']);
        }
    }

    public function testCompositionSucceedsWithRealArtwork(): void
    {
        $targetDir = sys_get_temp_dir() . '/guarantee_smoke_' . uniqid() . '/';
        $generator = oxNew(GuaranteeLabelGenerator::class);
        $generator->setTargetDir($targetDir);
        $generator->setTargetUrl('http://shop.local/labels/');

        $url = $generator->getLabelUrl('smoke', 5, 'ACME GmbH', 'X-2000');

        $this->assertNotNull($url);
        $file = $targetDir . basename($url);
        $template = getimagesize(OX_BASE_PATH . 'Core/GuaranteeLabel/assets/label-template.png');
        $generated = getimagesize($file);
        $this->assertSame($template[0], $generated[0]);
        $this->assertSame($template[1], $generated[1]);

        unlink($file);
        rmdir($targetDir);
    }

    public function testNestedBannerCompositionSucceedsWithRealArtwork(): void
    {
        $targetDir = sys_get_temp_dir() . '/guarantee_nested_smoke_' . uniqid() . '/';
        $generator = oxNew(GuaranteeLabelGenerator::class);
        $generator->setTargetDir($targetDir);
        $generator->setTargetUrl('http://shop.local/labels/');

        $url = $generator->getNestedBannerUrl('smoke', 5);

        $this->assertNotNull($url);
        $file = $targetDir . basename($url);
        $template = getimagesize(OX_BASE_PATH . 'Core/GuaranteeLabel/assets/nested-template.png');
        $generated = getimagesize($file);
        $this->assertSame($template[0], $generated[0]);
        $this->assertSame($template[1], $generated[1]);

        unlink($file);
        rmdir($targetDir);
    }
}
