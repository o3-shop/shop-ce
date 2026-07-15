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
     * Bump whenever label-template.png or LAYOUT changes; invalidates every
     * cached label via the content hash.
     */
    public const TEMPLATE_VERSION = 1;

    /**
     * Field placement, as FRACTIONS of template width/height so the layout
     * survives template re-exports at other resolutions.
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
     * Values calibrated visually against the official artwork in the
     * calibration task; adjust there, not ad hoc.
     */
    public const LAYOUT = [
        'years' => ['x' => 0.231, 'y' => 0.539, 'size' => 0.260, 'font' => 'Inter-ExtraBold.ttf', 'align' => 'center', 'maxw' => 0.430],
        'guarantor' => ['x' => 0.0203, 'y' => 0.265, 'size' => 0.032, 'font' => 'Inter-SemiBold.ttf', 'align' => 'left', 'maxw' => 0.282],
        'model' => ['x' => 0.7265, 'y' => 0.268, 'size' => 0.030, 'font' => 'Inter-Regular.ttf', 'align' => 'left', 'maxw' => 0.258],
    ];

    private const TEMPLATE_FILE = 'label-template.png';
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
            if (!$this->compose($targetFile, $years, $guarantor, $model)) {
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

    private function buildFilename(string $articleId, int $years, string $guarantor, string $model): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $articleId);
        $hash = md5($years . '|' . $guarantor . '|' . $model . '|' . self::TEMPLATE_VERSION);
        return $safeId . '_' . $hash . '.png';
    }

    /**
     * @return bool true when the target file was written
     */
    private function compose(string $targetFile, int $years, string $guarantor, string $model): bool
    {
        if (!function_exists('imagettftext')) {
            Registry::getLogger()->error(
                __METHOD__ . ' - GD FreeType support (imagettftext) is unavailable. The EU guarantee label cannot be generated.'
            );
            return false;
        }

        $templatePath = $this->getAssetDir() . self::TEMPLATE_FILE;
        if (!is_file($templatePath)) {
            Registry::getLogger()->error(
                __METHOD__ . " - Label template '$templatePath' is missing. The EU guarantee label cannot be generated."
            );
            return false;
        }

        $texts = [
            'years' => (string) $years,
            'guarantor' => $guarantor,
            'model' => $model,
        ];
        $layout = $this->layout ?? self::LAYOUT;

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

    private function getAssetDir(): string
    {
        return $this->assetDir ?? OX_BASE_PATH . 'Core/GuaranteeLabel/assets/';
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
