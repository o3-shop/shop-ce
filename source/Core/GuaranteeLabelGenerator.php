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

namespace OxidEsales\EshopCommunity\Core;

use OxidEsales\Eshop\Core\Registry;

/**
 * Composites the official EU durability-guarantee label (Reg. (EU) 2025/1960
 * Annex II) with the three trader-filled fields - duration in years,
 * producer/guarantor name, model identifier - using GD + FreeType and the
 * prescribed Inter typeface (#219).
 *
 * Caching: content-addressed. The target filename embeds
 * md5(years|guarantor|model|TEMPLATE_VERSION), so a stale file can never be
 * served and no save-hooks or invalidation logic exist. Writes are atomic
 * (temp file + rename); concurrent requests race benignly to identical bytes.
 *
 * Failure policy: NEVER throws out of getLabelUrl(). Any problem (missing
 * template/font, GD/FreeType unavailable, unwritable target) is logged and
 * yields null - the storefront then renders the text fallback instead of
 * the label (graceful degradation; templates handle null).
 */
class GuaranteeLabelGenerator
{
    /**
     * Bump whenever label-template.png, nested-template.png, LAYOUT or
     * NESTED_LAYOUT changes; invalidates every cached label via the content
     * hash. v3 = official Commission artwork (label 1400x1474 + nested banner
     * 2211x340) with recalibrated field placement.
     */
    public const TEMPLATE_VERSION = 3;

    /**
     * Field placement on the full label, as FRACTIONS of template
     * width/height so the layout survives template re-exports at other
     * resolutions.
     *   x, y      - anchor point (y = text BASELINE); x is the horizontal
     *               anchor interpreted per 'align'
     *   size      - font size as a fraction of template HEIGHT
     *   font      - TTF filename in the asset dir
     *   align     - 'center' (x = centre, default) or 'left' (x = left edge);
     *               the official artwork left-anchors the brand/model fields
     *               and centres the years figure in the "XX" placeholder.
     *   maxw      - width of the field's blanked box as a fraction of template
     *               WIDTH; text wider than 90% of it shrinks to fit (never
     *               overflows into neighbouring artwork).
     * Derived from the official GARAN Label_colour.svg blanked-field boxes
     * (1400x1474, scale 5.19886): brand [36,351,417,391] / model
     * [1026,351,1367,387] on baseline y=387 (Inter-Regular 9px SVG =46.8px);
     * "XX" box [36,480,635,782], baseline y=783 (Inter-ExtraBold 80px SVG
     * =415.9px), centred at x=336. Values calibrated visually against the
     * official artwork in the calibration task; adjust there, not ad hoc.
     */
    public const LAYOUT = [
        'years' => ['x' => 0.2400, 'y' => 0.5312, 'size' => 0.2822, 'font' => 'Inter-ExtraBold.ttf', 'align' => 'center', 'maxw' => 0.4286],
        'guarantor' => ['x' => 0.0257, 'y' => 0.2626, 'size' => 0.0317, 'font' => 'Inter-Regular.ttf', 'align' => 'left', 'maxw' => 0.2729],
        'model' => ['x' => 0.7329, 'y' => 0.2626, 'size' => 0.0317, 'font' => 'Inter-Regular.ttf', 'align' => 'left', 'maxw' => 0.2443],
    ];

    /**
     * Field placement on the official nested/reduced-display banner
     * (nested-template.png, 2211x340, scale 6x from viewBox 368.5x56.69).
     * Same fraction conventions as LAYOUT. Exactly one editable field: the
     * duration/year, ExtraBold, centred in the blanked "XX" box
     * [68,98,429,279] (baseline y=280, Inter-ExtraBold 41.56px SVG =249.4px,
     * centred at x=249). The "365" icon and vertical divider (px=562) sit to
     * the right and must not be overlapped, hence the maxw clamp.
     */
    public const NESTED_LAYOUT = [
        'years' => ['x' => 0.1126, 'y' => 0.8235, 'size' => 0.7334, 'font' => 'Inter-ExtraBold.ttf', 'align' => 'center', 'maxw' => 0.1637],
    ];

    private const TEMPLATE_FILE = 'label-template.png';
    private const NESTED_TEMPLATE_FILE = 'nested-template.png';
    private const TEXT_COLOR = [0, 0, 0]; // black per Annex II spec

    /** @var string|null test seam; null = repo default */
    private ?string $assetDir = null;

    /** @var string|null test seam; null = <picture dir>/generated/guarantee/ */
    private ?string $targetDir = null;

    /** @var string|null test seam; null = <picture url>/generated/guarantee/ */
    private ?string $targetUrl = null;

    /** @var array|null test seam; null = self::LAYOUT */
    private ?array $layout = null;

    public function setAssetDir(string $dir): void
    {
        $this->assetDir = rtrim($dir, '/') . '/';
    }

    public function setTargetDir(string $dir): void
    {
        $this->targetDir = rtrim($dir, '/') . '/';
    }

    public function setTargetUrl(string $url): void
    {
        $this->targetUrl = rtrim($url, '/') . '/';
    }

    public function setLayout(array $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * @param string $articleId  article OXID (used in the cache filename only)
     * @param int    $years      guarantee duration in whole years (caller
     *                           guarantees eligibility; not re-checked here)
     * @param string $guarantor  producer/brand name to composite
     * @param string $model      model identifier to composite
     *
     * @return string|null public URL of the label PNG, or null on failure
     */
    public function getLabelUrl(string $articleId, int $years, string $guarantor, string $model): ?string
    {
        try {
            $filename = $this->buildFilename($articleId, $years, $guarantor, $model);
            $targetDir = $this->getTargetDir();
            $targetFile = $targetDir . $filename;

            if (is_file($targetFile)) {
                return $this->getTargetUrl() . $filename;
            }

            if (!$this->ensureDirectory($targetDir)) {
                return null;
            }
            $texts = [
                'years' => (string) $years,
                'guarantor' => $guarantor,
                'model' => $model,
            ];
            if (!$this->compose($this->getAssetDir() . self::TEMPLATE_FILE, $this->layout ?? self::LAYOUT, $texts, $targetFile)) {
                return null;
            }

            return $this->getTargetUrl() . $filename;
        } catch (\Throwable $e) {
            Registry::getLogger()->error(
                __METHOD__ . " - Label composition failed for article-ID '$articleId': '{$e->getMessage()}'.",
                ['exception' => $e]
            );
            return null;
        }
    }

    /**
     * Composes the official nested/reduced-display banner, filling only the
     * single editable field (the duration in years) onto nested-template.png.
     *
     * Same content-addressed caching, atomic write and never-throws policy as
     * getLabelUrl(); shares the same target directory/URL. The cache filename
     * embeds md5(years|TEMPLATE_VERSION) so a stale banner can never be served.
     *
     * @param string $articleId article OXID (used in the cache filename only)
     * @param int    $years     guarantee duration in whole years
     *
     * @return string|null public URL of the banner PNG, or null on failure
     */
    public function getNestedBannerUrl(string $articleId, int $years): ?string
    {
        try {
            $filename = $this->buildNestedFilename($articleId, $years);
            $targetDir = $this->getTargetDir();
            $targetFile = $targetDir . $filename;

            if (is_file($targetFile)) {
                return $this->getTargetUrl() . $filename;
            }

            if (!$this->ensureDirectory($targetDir)) {
                return null;
            }
            if (!$this->compose($this->getAssetDir() . self::NESTED_TEMPLATE_FILE, self::NESTED_LAYOUT, ['years' => (string) $years], $targetFile)) {
                return null;
            }

            return $this->getTargetUrl() . $filename;
        } catch (\Throwable $e) {
            Registry::getLogger()->error(
                __METHOD__ . " - Nested banner composition failed for article-ID '$articleId': '{$e->getMessage()}'.",
                ['exception' => $e]
            );
            return null;
        }
    }

    private function buildFilename(string $articleId, int $years, string $guarantor, string $model): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $articleId);
        $hash = md5($years . '|' . $guarantor . '|' . $model . '|' . self::TEMPLATE_VERSION);
        return $safeId . '_' . $hash . '.png';
    }

    private function buildNestedFilename(string $articleId, int $years): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $articleId);
        $hash = md5($years . '|' . self::TEMPLATE_VERSION);
        return $safeId . '_nested_' . $hash . '.png';
    }

    /**
     * Composites the given text fields onto a template PNG and writes the
     * result atomically. Layout fractions and texts are keyed identically;
     * only fields present in $layout are drawn.
     *
     * @param string               $templatePath absolute path to the template PNG
     * @param array<string,array>  $layout       field placement fractions
     * @param array<string,string> $texts        field text keyed like $layout
     * @param string               $targetFile   absolute destination path
     *
     * @return bool true when the target file was written
     */
    private function compose(string $templatePath, array $layout, array $texts, string $targetFile): bool
    {
        if (!function_exists('imagettftext')) {
            Registry::getLogger()->error(
                __METHOD__ . ' - GD FreeType support (imagettftext) is unavailable. The EU guarantee label cannot be generated.'
            );
            return false;
        }

        if (!is_file($templatePath)) {
            Registry::getLogger()->error(
                __METHOD__ . " - Label template '$templatePath' is missing. The EU guarantee label cannot be generated."
            );
            return false;
        }

        foreach ($layout as $field => $spec) {
            if (!is_file($this->getAssetDir() . $spec['font'])) {
                Registry::getLogger()->error(
                    __METHOD__ . " - Font file '{$spec['font']}' is missing from '{$this->getAssetDir()}'. The EU guarantee label cannot be generated."
                );
                return false;
            }
        }

        $image = imagecreatefrompng($templatePath);
        if ($image === false) {
            Registry::getLogger()->error(
                __METHOD__ . " - Label template '$templatePath' could not be read as PNG."
            );
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $color = imagecolorallocate($image, ...self::TEXT_COLOR);

        foreach ($layout as $field => $spec) {
            $fontFile = $this->getAssetDir() . $spec['font'];
            $sizePx = $spec['size'] * $height;
            // imagettftext expects points; GD converts at 96 dpi -> pt = px * 72/96.
            $sizePt = $sizePx * 72 / 96;
            $text = $texts[$field];

            $box = imagettfbbox($sizePt, 0, $fontFile, $text);
            if ($box === false) {
                imagedestroy($image);
                Registry::getLogger()->error(
                    __METHOD__ . " - Measuring text for field '$field' failed with font '$fontFile'."
                );
                return false;
            }
            $textWidth = $box[2] - $box[0];
            // Shrink-to-fit: keep the text within 90% of its blanked box so a
            // long guarantor/model never overflows into neighbouring artwork.
            if (isset($spec['maxw'])) {
                $maxWidth = $spec['maxw'] * $width * 0.9;
                if ($textWidth > $maxWidth && $textWidth > 0) {
                    $sizePt *= $maxWidth / $textWidth;
                    $box = imagettfbbox($sizePt, 0, $fontFile, $text);
                    if ($box === false) {
                        imagedestroy($image);
                        Registry::getLogger()->error(
                            __METHOD__ . " - Re-measuring shrunk text for field '$field' failed with font '$fontFile'."
                        );
                        return false;
                    }
                    $textWidth = $box[2] - $box[0];
                }
            }
            // $box[0] is the left side bearing; subtract it so the visible
            // glyphs start exactly at the computed origin.
            if (($spec['align'] ?? 'center') === 'left') {
                $x = (int) round($spec['x'] * $width - $box[0]);
            } else {
                $x = (int) round($spec['x'] * $width - $textWidth / 2 - $box[0]);
            }
            $y = (int) round($spec['y'] * $height);

            imagettftext($image, $sizePt, 0, $x, $y, $color, $fontFile, $text);
        }

        $tmpFile = $targetFile . '.' . uniqid('', true) . '.tmp';
        $written = imagepng($image, $tmpFile);
        imagedestroy($image);

        if (!$written || !rename($tmpFile, $targetFile)) {
            @unlink($tmpFile);
            Registry::getLogger()->error(
                __METHOD__ . " - Writing composited label to '$targetFile' failed. Check directory permissions."
            );
            return false;
        }

        Registry::getLogger()->info(
            __METHOD__ . " - Generated EU guarantee label '$targetFile'."
        );
        return true;
    }

    private function ensureDirectory(string $dir): bool
    {
        if (is_dir($dir)) {
            return is_writable($dir);
        }
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            Registry::getLogger()->error(
                __METHOD__ . " - Target directory '$dir' could not be created."
            );
            return false;
        }
        return true;
    }

    /**
     * Templates and fonts ship next to this class, so they are addressed
     * relative to it. OX_BASE_PATH must NOT be used here: it points at the
     * shop's source directory, and the composer installer strips `Core/**` on
     * its way there (extra.oxideshop.blacklist-filter) because those classes
     * are autoloaded from vendor/ instead. A git checkout hides the difference
     * — a composer-installed shop does not.
     */
    private function getAssetDir(): string
    {
        return $this->assetDir ?? __DIR__ . '/GuaranteeLabel/assets/';
    }

    private function getTargetDir(): string
    {
        if ($this->targetDir !== null) {
            return $this->targetDir;
        }
        return Registry::getConfig()->getPictureDir(false) . 'generated/guarantee/';
    }

    private function getTargetUrl(): string
    {
        if ($this->targetUrl !== null) {
            return $this->targetUrl;
        }
        return Registry::getConfig()->getPictureUrl(null, false) . 'generated/guarantee/';
    }
}
