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

    /**
     * Legibility floor for shrink-to-fit, as a fraction of the field's own
     * configured size. Text that still does not fit at this size is TRUNCATED
     * rather than shrunk further (#226 item 4).
     *
     * Why a floor exists at all: the model identifier is a mandatory label
     * component under Reg. (EU) 2025/1960 Annex II and must be legible.
     * `Article::getGuaranteeModel()` falls back to the full article title when
     * neither O3GUARANTEEMODEL nor OXARTNUM is set, and an unbounded shrink
     * scaled such a title down to ~1px of ink — technically "rendered", legally
     * useless, and (because imagettfbbox is unreliable at sub-point sizes) it
     * overflowed the blanked box into neighbouring artwork as well.
     *
     * This is the calibration knob: 0.6 keeps the field at >= 60% of its
     * designed size (~28px of ~47px on the official 1400x1474 artwork).
     */
    private const MIN_FONT_SCALE = 0.6;

    /** Appended to truncated text; present in the shipped Inter fonts. */
    private const TRUNCATION_SUFFIX = '…';

    /** @var string|null test seam; null = repo default */
    private ?string $assetDir = null;

    /** @var string|null test seam; null = <picture dir>/generated/guarantee/ */
    private ?string $targetDir = null;

    /** @var string|null test seam; null = <picture url>/generated/guarantee/ */
    private ?string $targetUrl = null;

    /** @var array|null test seam; null = self::LAYOUT. Full label only - see setNestedLayout(). */
    private ?array $layout = null;

    /** @var array|null test seam; null = self::NESTED_LAYOUT */
    private ?array $nestedLayout = null;

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

    /**
     * Overrides the FULL-label layout. The nested banner has its own seam -
     * setNestedLayout() - because it uses a different template and field set.
     * Both participate in the cache filename, so two layouts can never collide
     * on one cached file (#226 item 6).
     */
    public function setLayout(array $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Overrides the nested/reduced-display banner layout.
     */
    public function setNestedLayout(array $layout): void
    {
        $this->nestedLayout = $layout;
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
            $layout = $this->nestedLayout ?? self::NESTED_LAYOUT;
            if (!$this->compose($this->getAssetDir() . self::NESTED_TEMPLATE_FILE, $layout, ['years' => (string) $years], $targetFile)) {
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

    /**
     * Deletes this article's generated labels that are no longer current
     * (#226 item 5).
     *
     * Filenames are content-addressed, so every edit to years/guarantor/model —
     * and every TEMPLATE_VERSION bump — orphans the previous PNG, and nothing
     * ever collected them: the directory only grew. Rather than tracking which
     * fields changed, this computes the two filenames that ARE current and
     * removes every other file belonging to the article. That makes it
     * idempotent, self-correcting, and it also collects TEMPLATE_VERSION
     * orphans for the articles that get touched.
     *
     * Deliberately narrow: only files whose name matches this article's exact
     * `<id>_[nested_]<md5>.png` shape are considered, so a prefix-sharing id can
     * never have its labels deleted.
     *
     * @return int number of files removed
     */
    public function purgeOutdatedLabels(string $articleId, int $years, string $guarantor, string $model): int
    {
        $targetDir = $this->getTargetDir();
        // One stat call, and it short-circuits the overwhelmingly common case:
        // the feature is off / no label was ever generated for this shop.
        if (!is_dir($targetDir)) {
            return 0;
        }

        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $articleId);
        if ($safeId === '') {
            return 0;
        }

        $keep = [
            $this->buildFilename($articleId, $years, $guarantor, $model),
            $this->buildNestedFilename($articleId, $years),
        ];
        $ownFile = '/^' . preg_quote($safeId, '/') . '_(nested_)?[0-9a-f]{32}\.png$/';

        $removed = 0;
        foreach (glob($targetDir . $safeId . '_*.png') ?: [] as $file) {
            $name = basename($file);
            if (!preg_match($ownFile, $name) || in_array($name, $keep, true)) {
                continue;
            }
            if (@unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    private function buildFilename(string $articleId, int $years, string $guarantor, string $model): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $articleId);
        $hash = md5(
            $years . '|' . $guarantor . '|' . $model . '|' . self::TEMPLATE_VERSION
            . $this->getLayoutDiscriminator($this->layout)
        );
        return $safeId . '_' . $hash . '.png';
    }

    private function buildNestedFilename(string $articleId, int $years): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $articleId);
        $hash = md5(
            $years . '|' . self::TEMPLATE_VERSION
            . $this->getLayoutDiscriminator($this->nestedLayout)
        );
        return $safeId . '_nested_' . $hash . '.png';
    }

    /**
     * Cache-key contribution of an overridden layout (#226 item 6).
     *
     * Without it, two different layouts produced the same filename and the
     * second silently served the first one's cached bytes — a real trap during
     * calibration work. Contributes NOTHING for the default layout on purpose:
     * hashing the constant would rename every already-generated production
     * label at once and orphan the whole directory for no benefit (the constant
     * is already covered by TEMPLATE_VERSION).
     *
     * @param array|null $layout the override, or null when the constant is used
     *
     * @return string '' for the default layout
     */
    private function getLayoutDiscriminator(?array $layout): string
    {
        return $layout === null ? '' : '|layout:' . md5(json_encode($layout));
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
            // The shrink stops at MIN_FONT_SCALE; anything still too wide at
            // that size is truncated instead, because a label component that is
            // present but illegible does not satisfy Annex II (#226 item 4).
            if (isset($spec['maxw'])) {
                $maxWidth = $spec['maxw'] * $width * 0.9;
                if ($textWidth > $maxWidth && $textWidth > 0) {
                    $sizePt = max($sizePt * $maxWidth / $textWidth, $sizePt * self::MIN_FONT_SCALE);
                    $box = imagettfbbox($sizePt, 0, $fontFile, $text);
                    if ($box === false) {
                        imagedestroy($image);
                        Registry::getLogger()->error(
                            __METHOD__ . " - Re-measuring shrunk text for field '$field' failed with font '$fontFile'."
                        );
                        return false;
                    }
                    $textWidth = $box[2] - $box[0];

                    if ($textWidth > $maxWidth) {
                        $truncated = $this->truncateToWidth($text, $sizePt, $fontFile, $maxWidth);
                        if ($truncated === null) {
                            imagedestroy($image);
                            Registry::getLogger()->error(
                                __METHOD__ . " - Measuring truncated text for field '$field' failed with font '$fontFile'."
                            );
                            return false;
                        }
                        Registry::getLogger()->notice(
                            __METHOD__ . " - Field '$field' is too long for its label box and was truncated to"
                            . " '$truncated'. Set a shorter value explicitly so the mandatory label component stays"
                            . ' complete and legible.'
                        );
                        $text = $truncated;
                        $box = imagettfbbox($sizePt, 0, $fontFile, $text);
                        $textWidth = $box === false ? $textWidth : $box[2] - $box[0];
                    }
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

    /**
     * Longest leading substring of $text that, with the ellipsis appended, fits
     * into $maxWidth at $sizePt.
     *
     * Measured rather than estimated: glyph widths vary, so character counts
     * cannot be derived arithmetically. Binary search keeps this at ~log2(n)
     * imagettfbbox() calls instead of one per character.
     *
     * @return string|null null when measuring failed
     */
    private function truncateToWidth(string $text, float $sizePt, string $fontFile, float $maxWidth): ?string
    {
        $measure = static function (string $candidate) use ($sizePt, $fontFile): ?float {
            $box = imagettfbbox($sizePt, 0, $fontFile, $candidate);

            return $box === false ? null : (float) ($box[2] - $box[0]);
        };

        // The ellipsis alone may already exceed the box on a pathological
        // layout; there is nothing sensible left to draw in that case.
        $suffixWidth = $measure(self::TRUNCATION_SUFFIX);
        if ($suffixWidth === null) {
            return null;
        }
        if ($suffixWidth > $maxWidth) {
            return self::TRUNCATION_SUFFIX;
        }

        $low = 0;
        $high = mb_strlen($text);
        $best = '';
        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $candidate = rtrim(mb_substr($text, 0, $mid)) . self::TRUNCATION_SUFFIX;
            $candidateWidth = $measure($candidate);
            if ($candidateWidth === null) {
                return null;
            }
            if ($candidateWidth <= $maxWidth) {
                $best = $candidate;
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        return $best === '' ? self::TRUNCATION_SUFFIX : $best;
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
