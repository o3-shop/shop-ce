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
 * GD composition + content-hash caching for the EU durability-guarantee
 * label (#219). Tests run against a SYNTHETIC template (solid colour PNG)
 * so they exercise composition mechanics without the official artwork.
 */
class GuaranteeLabelGeneratorTest extends UnitTestCase
{
    private string $workDir;
    private GuaranteeLabelGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir() . '/guarantee_' . uniqid();
        mkdir($this->workDir . '/assets', 0777, true);
        mkdir($this->workDir . '/target', 0777, true);

        // synthetic 500x520 white template
        $im = imagecreatetruecolor(500, 520);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        imagepng($im, $this->workDir . '/assets/label-template.png');
        imagedestroy($im);

        // real Inter fonts are required for imagettftext; copy from the repo
        foreach (['Inter-Regular.ttf', 'Inter-SemiBold.ttf', 'Inter-ExtraBold.ttf'] as $font) {
            copy(
                OX_BASE_PATH . 'Core/GuaranteeLabel/assets/' . $font,
                $this->workDir . '/assets/' . $font
            );
        }

        $this->generator = oxNew(GuaranteeLabelGenerator::class);
        $this->generator->setAssetDir($this->workDir . '/assets/');
        $this->generator->setTargetDir($this->workDir . '/target/');
        $this->generator->setTargetUrl('http://shop.local/out/pictures/generated/guarantee/');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->workDir . '/{assets,target}/*', GLOB_BRACE) ?: [] as $f) {
            unlink($f);
        }
        rmdir($this->workDir . '/assets');
        rmdir($this->workDir . '/target');
        rmdir($this->workDir);
        parent::tearDown();
    }

    public function testComposesValidPngWithTemplateDimensions(): void
    {
        $url = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');

        $this->assertNotNull($url);
        $file = $this->workDir . '/target/' . basename($url);
        $this->assertFileExists($file);
        $info = getimagesize($file);
        $this->assertSame(500, $info[0]);
        $this->assertSame(520, $info[1]);
        $this->assertSame('image/png', $info['mime']);
    }

    public function testCompositedTextChangesPixelsAgainstBlankTemplate(): void
    {
        $url = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');
        $im = imagecreatefrompng($this->workDir . '/target/' . basename($url));

        // The white synthetic template must have gained non-white pixels
        // (the rendered text). Scan for any non-white pixel.
        $found = false;
        for ($x = 0; $x < 500 && !$found; $x += 2) {
            for ($y = 0; $y < 520 && !$found; $y += 2) {
                if ((imagecolorat($im, $x, $y) & 0xFFFFFF) !== 0xFFFFFF) {
                    $found = true;
                }
            }
        }
        imagedestroy($im);
        $this->assertTrue($found, 'Composited label must contain rendered text pixels.');
    }

    public function testCacheHitReturnsSameUrlWithoutRegenerating(): void
    {
        $url1 = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');
        $file = $this->workDir . '/target/' . basename($url1);
        $mtime = filemtime($file);
        touch($file, $mtime - 100); // age it so regeneration would be visible
        clearstatcache();

        $url2 = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');

        $this->assertSame($url1, $url2);
        clearstatcache();
        $this->assertSame($mtime - 100, filemtime($file), 'Cache hit must not rewrite the file.');
    }

    public function testContentChangeProducesNewFilename(): void
    {
        $url1 = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');
        $url2 = $this->generator->getLabelUrl('art1', 6, 'ACME GmbH', 'X-2000');

        $this->assertNotSame($url1, $url2);
        $this->assertStringStartsWith('art1_', basename($url1));
        $this->assertStringStartsWith('art1_', basename($url2));
    }

    public function testMissingTemplateReturnsNullAndDoesNotThrow(): void
    {
        unlink($this->workDir . '/assets/label-template.png');
        $this->assertNull($this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000'));
    }

    public function testMissingFontReturnsNullAndDoesNotThrow(): void
    {
        unlink($this->workDir . '/assets/Inter-ExtraBold.ttf');
        $this->assertNull($this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000'));
    }

    public function testUnwritableTargetDirReturnsNull(): void
    {
        $this->generator->setTargetDir($this->workDir . '/does-not-exist-and-mkdir-fails/../../../../../../root/x/');
        $this->assertNull($this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000'));
    }

    public function testArticleIdIsSanitizedForFilesystem(): void
    {
        $url = $this->generator->getLabelUrl('../evil/../id', 5, 'ACME GmbH', 'X-2000');

        $this->assertNotNull($url);
        $this->assertStringNotContainsString('..', basename($url));
        $this->assertStringNotContainsString('/', substr($url, strlen('http://shop.local/out/pictures/generated/guarantee/')));
    }
}
