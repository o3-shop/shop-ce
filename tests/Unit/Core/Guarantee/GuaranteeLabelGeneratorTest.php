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

        // synthetic 600x100 white nested banner template
        $nested = imagecreatetruecolor(600, 100);
        imagefill($nested, 0, 0, imagecolorallocate($nested, 255, 255, 255));
        imagepng($nested, $this->workDir . '/assets/nested-template.png');
        imagedestroy($nested);

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
        // Plain glob() (no GLOB_BRACE): brace expansion is a GNU-libc
        // extension and is absent on musl/Alpine PHP builds.
        foreach (glob($this->workDir . '/assets/*') ?: [] as $f) {
            unlink($f);
        }
        foreach (glob($this->workDir . '/target/*') ?: [] as $f) {
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
        // Force mkdir() to fail robustly, independent of the runtime UID:
        // make the target's PARENT a regular file, then point the target at a
        // subdirectory of it. mkdir() then fails with ENOTDIR everywhere -
        // even as root (root bypasses mode bits, so chmod 0555 would not
        // block it) and on musl/Alpine PHP alike - so getLabelUrl() must
        // return null via its graceful-degradation path.
        $blockingFile = $this->workDir . '/not-a-dir';
        file_put_contents($blockingFile, 'x');
        try {
            $this->generator->setTargetDir($blockingFile . '/cannot-create/');
            $this->assertNull($this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000'));
        } finally {
            unlink($blockingFile);
        }
    }

    public function testArticleIdIsSanitizedForFilesystem(): void
    {
        $url = $this->generator->getLabelUrl('../evil/../id', 5, 'ACME GmbH', 'X-2000');

        $this->assertNotNull($url);
        $this->assertStringNotContainsString('..', basename($url));
        $this->assertStringNotContainsString('/', substr($url, strlen('http://shop.local/out/pictures/generated/guarantee/')));
    }

    public function testNestedBannerComposesValidPngWithTemplateDimensions(): void
    {
        $url = $this->generator->getNestedBannerUrl('art1', 5);

        $this->assertNotNull($url);
        $file = $this->workDir . '/target/' . basename($url);
        $this->assertFileExists($file);
        $info = getimagesize($file);
        $this->assertSame(600, $info[0]);
        $this->assertSame(100, $info[1]);
        $this->assertSame('image/png', $info['mime']);
    }

    public function testNestedBannerFilenameCarriesNestedMarker(): void
    {
        $url = $this->generator->getNestedBannerUrl('art1', 5);

        $this->assertNotNull($url);
        $this->assertStringStartsWith('art1_nested_', basename($url));
        // The full-label and nested outputs for the same inputs must not collide.
        $labelUrl = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');
        $this->assertNotSame(basename($labelUrl), basename($url));
    }

    public function testNestedBannerCompositedTextChangesPixels(): void
    {
        $url = $this->generator->getNestedBannerUrl('art1', 5);
        $im = imagecreatefrompng($this->workDir . '/target/' . basename($url));

        $found = false;
        for ($x = 0; $x < 600 && !$found; $x += 2) {
            for ($y = 0; $y < 100 && !$found; $y += 2) {
                if ((imagecolorat($im, $x, $y) & 0xFFFFFF) !== 0xFFFFFF) {
                    $found = true;
                }
            }
        }
        imagedestroy($im);
        $this->assertTrue($found, 'Composited nested banner must contain rendered text pixels.');
    }

    public function testNestedBannerCacheHitReturnsSameUrlWithoutRegenerating(): void
    {
        $url1 = $this->generator->getNestedBannerUrl('art1', 5);
        $file = $this->workDir . '/target/' . basename($url1);
        $mtime = filemtime($file);
        touch($file, $mtime - 100);
        clearstatcache();

        $url2 = $this->generator->getNestedBannerUrl('art1', 5);

        $this->assertSame($url1, $url2);
        clearstatcache();
        $this->assertSame($mtime - 100, filemtime($file), 'Cache hit must not rewrite the file.');
    }

    public function testNestedBannerContentChangeProducesNewFilename(): void
    {
        $url1 = $this->generator->getNestedBannerUrl('art1', 5);
        $url2 = $this->generator->getNestedBannerUrl('art1', 10);

        $this->assertNotSame($url1, $url2);
        $this->assertStringStartsWith('art1_nested_', basename($url1));
        $this->assertStringStartsWith('art1_nested_', basename($url2));
    }

    public function testNestedBannerMissingTemplateReturnsNullAndDoesNotThrow(): void
    {
        unlink($this->workDir . '/assets/nested-template.png');
        $this->assertNull($this->generator->getNestedBannerUrl('art1', 5));
    }

    public function testNestedBannerArticleIdIsSanitizedForFilesystem(): void
    {
        $url = $this->generator->getNestedBannerUrl('../evil/../id', 5);

        $this->assertNotNull($url);
        $this->assertStringNotContainsString('..', basename($url));
        $this->assertStringNotContainsString('/', substr($url, strlen('http://shop.local/out/pictures/generated/guarantee/')));
    }

    // ---------------------------------------------------------------- #226/6

    /**
     * #226 item 6: the layout was absent from the cache hash, so two different
     * layouts produced the SAME filename and the second silently served the
     * first one's cached bytes. Harmless in production (the layout is a
     * constant) but a real trap during calibration work.
     */
    public function testDifferentCustomLayoutsProduceDifferentFilenames(): void
    {
        $layoutA = ['years' => ['x' => 0.1, 'y' => 0.5, 'size' => 0.2, 'font' => 'Inter-ExtraBold.ttf']];
        $layoutB = ['years' => ['x' => 0.9, 'y' => 0.5, 'size' => 0.2, 'font' => 'Inter-ExtraBold.ttf']];

        $this->generator->setLayout($layoutA);
        $first = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');

        $this->generator->setLayout($layoutB);
        $second = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNotSame(basename($first), basename($second));
    }

    /**
     * The layout hash must only participate when a CUSTOM layout is set,
     * otherwise shipping this change would rename every already-generated
     * production label at once and orphan the whole directory.
     */
    public function testDefaultLayoutFilenameIsUnchangedByTheLayoutHash(): void
    {
        $expected = 'art1_' . md5('5|ACME GmbH|X-2000|' . GuaranteeLabelGenerator::TEMPLATE_VERSION) . '.png';

        $url = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000');

        $this->assertNotNull($url);
        $this->assertSame($expected, basename($url));
    }

    public function testDefaultNestedLayoutFilenameIsUnchangedByTheLayoutHash(): void
    {
        $expected = 'art1_nested_' . md5('5|' . GuaranteeLabelGenerator::TEMPLATE_VERSION) . '.png';

        $url = $this->generator->getNestedBannerUrl('art1', 5);

        $this->assertNotNull($url);
        $this->assertSame($expected, basename($url));
    }

    /**
     * setLayout() only ever affected the full label; the nested banner ignored
     * it and always used the NESTED_LAYOUT constant, so the seam was asymmetric
     * and the banner could not be calibrated at all.
     */
    public function testNestedLayoutSeamIsHonouredAndHashed(): void
    {
        $default = $this->generator->getNestedBannerUrl('art1', 5);

        $this->generator->setNestedLayout(
            ['years' => ['x' => 0.8, 'y' => 0.9, 'size' => 0.5, 'font' => 'Inter-ExtraBold.ttf']]
        );
        $custom = $this->generator->getNestedBannerUrl('art1', 5);

        $this->assertNotNull($default);
        $this->assertNotNull($custom);
        $this->assertNotSame(basename($default), basename($custom));
        // Different placement must actually land different pixels.
        $this->assertNotSame(
            md5_file($this->workDir . '/target/' . basename($default)),
            md5_file($this->workDir . '/target/' . basename($custom))
        );
    }

    // ---------------------------------------------------------------- #226/5

    /**
     * #226 item 5: content-addressed filenames orphan the previous PNG on every
     * field edit, and nothing ever collected them. Keep what is current, drop
     * the rest.
     */
    public function testPurgeKeepsCurrentLabelsAndRemovesOrphans(): void
    {
        $currentLabel = basename((string) $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', 'X-2000'));
        $currentNested = basename((string) $this->generator->getNestedBannerUrl('art1', 5));
        // Orphans from earlier edits / an earlier TEMPLATE_VERSION.
        $orphans = [
            'art1_' . str_repeat('a', 32) . '.png',
            'art1_nested_' . str_repeat('b', 32) . '.png',
        ];
        foreach ($orphans as $orphan) {
            file_put_contents($this->workDir . '/target/' . $orphan, 'stale');
        }

        $removed = $this->generator->purgeOutdatedLabels('art1', 5, 'ACME GmbH', 'X-2000');

        $this->assertSame(2, $removed);
        $this->assertFileExists($this->workDir . '/target/' . $currentLabel);
        $this->assertFileExists($this->workDir . '/target/' . $currentNested);
        foreach ($orphans as $orphan) {
            $this->assertFileDoesNotExist($this->workDir . '/target/' . $orphan);
        }
    }

    /**
     * Another article's labels must never be collateral damage, not even when
     * its id shares this one's prefix.
     */
    public function testPurgeNeverTouchesAnotherArticlesLabels(): void
    {
        $otherArticle = 'art1_2_' . str_repeat('c', 32) . '.png';
        $unrelated = 'somethingelse.png';
        file_put_contents($this->workDir . '/target/' . $otherArticle, 'keep');
        file_put_contents($this->workDir . '/target/' . $unrelated, 'keep');

        $removed = $this->generator->purgeOutdatedLabels('art1', 5, 'ACME GmbH', 'X-2000');

        $this->assertSame(0, $removed);
        $this->assertFileExists($this->workDir . '/target/' . $otherArticle);
        $this->assertFileExists($this->workDir . '/target/' . $unrelated);
    }

    public function testPurgeIsANoOpWhenNothingWasEverGenerated(): void
    {
        $generator = oxNew(GuaranteeLabelGenerator::class);
        $generator->setTargetDir($this->workDir . '/does-not-exist/');

        $this->assertSame(0, $generator->purgeOutdatedLabels('art1', 5, 'ACME GmbH', 'X-2000'));
    }

    public function testPurgeIsIdempotent(): void
    {
        file_put_contents($this->workDir . '/target/art1_' . str_repeat('a', 32) . '.png', 'stale');

        $this->assertSame(1, $this->generator->purgeOutdatedLabels('art1', 5, 'ACME GmbH', 'X-2000'));
        $this->assertSame(0, $this->generator->purgeOutdatedLabels('art1', 5, 'ACME GmbH', 'X-2000'));
    }

    // ---------------------------------------------------------------- #226/4

    /**
     * #226 item 4: shrink-to-fit had no lower bound, so a title-derived model
     * identifier could be scaled to illegibility while still "rendering
     * successfully" - on a label component Reg. (EU) 2025/1960 Annex II
     * requires to be legible. The text must now be truncated at the floor
     * instead of shrunk past it.
     */
    public function testOverlongModelIsTruncatedRatherThanShrunkToIllegibility(): void
    {
        $long = str_repeat('Sehr langer Produktname ', 20);

        $url = $this->generator->getLabelUrl('art1', 5, 'ACME GmbH', $long);

        $this->assertNotNull($url, 'The label must still render - truncation, not refusal.');
        $file = $this->workDir . '/target/' . basename($url);
        $this->assertNotFalse(getimagesize($file));
    }

    /**
     * The floor is what makes truncation observable: the drawn glyph height for
     * an overlong string must not collapse below it. Rendered with a
     * single-field layout so the measured ink band IS the model text and
     * nothing else (the full LAYOUT also draws the huge years figure, which
     * would dominate the band and hide the regression).
     */
    public function testOverlongTextKeepsTheMinimumGlyphHeight(): void
    {
        $modelOnly = [
            'model' => [
                'x' => 0.05, 'y' => 0.5, 'size' => 0.0317,
                'font' => 'Inter-Regular.ttf', 'align' => 'left', 'maxw' => 0.2443,
            ],
        ];
        $this->generator->setLayout($modelOnly);

        $shortUrl = $this->generator->getLabelUrl('artShort', 5, 'ACME GmbH', 'X-1');
        $longUrl = $this->generator->getLabelUrl('artLong', 5, 'ACME GmbH', str_repeat('WIDE MODEL NAME ', 30));

        $shortInk = $this->measureInkHeight($this->workDir . '/target/' . basename((string) $shortUrl));
        $longInk = $this->measureInkHeight($this->workDir . '/target/' . basename((string) $longUrl));

        $this->assertGreaterThan(0, $shortInk, 'Baseline text must be drawn.');
        $this->assertGreaterThan(0, $longInk, 'Truncated text must still be drawn.');
        $this->assertGreaterThan(
            $shortInk * 0.5,
            $longInk,
            'Overlong text was shrunk far below the legibility floor instead of being truncated.'
        );
    }

    /**
     * Truncation must not silently swallow the problem: the operator has to
     * learn that this article needs an explicit O3GUARANTEEMODEL. Proven via
     * the ink WIDTH staying inside the blanked box while the source string is
     * far wider than the box could ever hold at the floor size.
     */
    public function testTruncatedTextStaysInsideTheBlankedBox(): void
    {
        $modelOnly = [
            'model' => [
                'x' => 0.05, 'y' => 0.5, 'size' => 0.0317,
                'font' => 'Inter-Regular.ttf', 'align' => 'left', 'maxw' => 0.2443,
            ],
        ];
        $this->generator->setLayout($modelOnly);

        $url = $this->generator->getLabelUrl('artBox', 5, 'ACME GmbH', str_repeat('WIDE MODEL NAME ', 30));

        $this->assertNotNull($url);
        [$inkLeft, $inkRight] = $this->measureInkColumns($this->workDir . '/target/' . basename($url));
        // Template is 500px wide in the fixture; box = 0.05..0.05+0.2443.
        $boxRightEdge = (0.05 + 0.2443) * 500;

        $this->assertLessThanOrEqual(
            (int) ceil($boxRightEdge) + 1,
            $inkRight,
            'Truncated text must not overflow the blanked box into neighbouring artwork.'
        );
        $this->assertGreaterThan(0, $inkRight - $inkLeft);
    }

    /**
     * @return array{0:int,1:int} leftmost and rightmost column containing ink
     */
    private function measureInkColumns(string $file): array
    {
        $im = imagecreatefrompng($file);
        $this->assertNotFalse($im);
        $width = imagesx($im);
        $height = imagesy($im);

        $left = null;
        $right = 0;
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                if ((imagecolorat($im, $x, $y) & 0xFFFFFF) !== 0xFFFFFF) {
                    $left ??= $x;
                    $right = $x;
                    break;
                }
            }
        }
        imagedestroy($im);

        return [$left ?? 0, $right];
    }

    /**
     * Height in pixels of the vertical band containing non-white pixels.
     */
    private function measureInkHeight(string $file): int
    {
        $im = imagecreatefrompng($file);
        $this->assertNotFalse($im);
        $width = imagesx($im);
        $height = imagesy($im);

        $top = null;
        $bottom = null;
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ((imagecolorat($im, $x, $y) & 0xFFFFFF) !== 0xFFFFFF) {
                    $top ??= $y;
                    $bottom = $y;
                    break;
                }
            }
        }
        imagedestroy($im);

        return $top === null ? 0 : $bottom - $top + 1;
    }
}
