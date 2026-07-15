# EU Guarantee Label Assets — Provenance

These assets implement the two EU-mandated guarantee artefacts of
**Commission Implementing Regulation (EU) 2025/1960** (OJ L 2025/1960, 2.10.2025,
CELEX `32025R1960`): the harmonised **notice** on the legal guarantee of conformity
(Annex I) and the harmonised **label** for the commercial guarantee of durability
(Annex II).

Retrieval date: **2026-07-15**.

## Legal note

Artwork per Reg. (EU) 2025/1960 Annexes I/II. **The notice must never be modified** —
it is per-language fixed artwork; every element (text, colours, QR code) is baked in
and legally fixed. The **label** is one language-neutral artwork with exactly three
editable areas (duration in years, brand/trademark, model identifier); these variable
fields are composited at runtime by `GuaranteeLabelGenerator`. Both QR codes (notice
and label) are static and part of the official artwork — they are never generated or
altered. Colour is legally mandatory for online display
(Pantone Reflex Blue C ≈ `#003399`, Pantone Yellow C ≈ `#FFED00`).

## Files

| File | Purpose |
|---|---|
| `label-template.png` | Language-neutral Annex II label, colour, variable areas blanked (1481×1559). |
| `Inter-Regular.ttf` / `Inter-SemiBold.ttf` / `Inter-ExtraBold.ttf` | Fonts for runtime text compositing. |
| `Inter-LICENSE.txt` | SIL Open Font License 1.1 for the Inter fonts. |
| `../../../out/pictures/guarantee/notice-en.png` | Annex I notice, English, colour (1186×1675). |
| `../../../out/pictures/guarantee/notice-de.png` | Annex I notice, German, colour (1224×1675). |

## Sources (exact URLs)

**Official artwork — Regulation (EU) 2025/1960.**
The Commission "ready-made files" landing page
`https://commission.europa.eu/publications/harmonised-notice-legal-guarantee-conformity-and-harmonised-label-commercial-guarantee-durability_en`
offers only the regulation and its annexes as documents; its "Annexes" link redirects to
the EUR-Lex OJ rendition, which is served behind an AWS WAF JS-challenge and cannot be
fetched non-interactively. The authoritative artwork was therefore taken from the official
**OJ PDF/A-2a manifestations** (the authoritative depiction) via the Publications Office
CELLAR content-negotiation endpoint (not WAF-gated):

- English act (Annex I EN notice + Annex II label):
  `http://publications.europa.eu/resource/oj/L_202501960.ENG.pdfa2a.L_202501960EN.pdf`
- German act (Annex I DE notice):
  `http://publications.europa.eu/resource/oj/L_202501960.DEU.pdfa2a.L_202501960DE.pdf`

(CELEX `32025R1960` resolves to CELLAR work `cellar:471b4f1e-9f2a-11f0-97c8-01aa75ed71a1`.)
The label is language-neutral; it was taken from the English PDF (identical artwork appears
in every language edition). No third-party recreations were used.

**Inter fonts.**
`https://github.com/rsms/inter/releases/download/v4.1/Inter-4.1.zip`
(release **v4.1**, published 2024-11-16). Static desktop TTFs taken from `extras/ttf/`
inside the zip; license from `LICENSE.txt` (SIL OFL 1.1), saved as `Inter-LICENSE.txt`.

## Conversion / preparation commands

Embedded artwork images were extracted from the PDF/A at native resolution with PyMuPDF
(`page.get_images` + `doc.extract_image`). The relevant XObjects were:

- notice colour: EN 2372×3350, DE 2437×3336 (RGB JPEG)
- label colour (language-neutral): 1481×1559 (RGB JPEG)
- (mono variants, the annotated specification diagram and the horizontal banner variant
  were present in the PDF but are not used here.)

PNG conversion + notice downscale (PHP GD):

```php
// JPEG -> truecolour RGB PNG (label kept at native 1481×1559)
$im = imagecreatefromjpeg($jpeg); imagepng($rgbCopy, $out, 9);

// notice downscaled to 1675 px height (bicubic), preserving aspect ratio
imagecopyresampled($dst, $im, 0,0,0,0, $nw, 1675, $w, $h); imagepng($dst, $out, 9);
```

Resulting sizes: `label-template.png` 1481×1559, `notice-en.png` 1186×1675,
`notice-de.png` 1224×1675. All RGB, `image/png`, QR modules verified crisp and scannable.

## Blanked variable areas (label-template.png, 1481×1559)

Each region was blanked by filling a rectangle with the white background colour sampled at
the box's top-left corner (`imagecolorat`). Coordinates are pixels on the final template,
origin top-left, given as `[x0, y0, x1, y1]` (inclusive fill rectangle):

| # | Field | `[x0, y0, x1, y1]` | x, y, w, h |
|---|---|---|---|
| VII  | Brand/Trademark  | `[30, 368, 448, 419]`   | x=30,  y=368, w=418, h=51  |
| VIII | Model identifier | `[1076, 366, 1458, 418]` | x=1076, y=366, w=382, h=52 |
| VI   | Duration ("XX")  | `[24, 496, 660, 840]`   | x=24,  y=496, w=636, h=344 |

**Fixed reference points for runtime compositing (Task 5), same coordinate system:**

- Original placeholder text extents: Brand/Trademark x≈41–434 y≈378–409;
  Model identifier x≈1087–1445 y≈376–410; "XX" x≈41–653 y≈509–827.
- Calendar/"365" icon (fixed, keep clear): left edge at **x≈667**.
- Inner border lines (double frame): left **x≈19**, right **x≈1463**; outer frame right **x≈1479**.
- Divider under "GARAN": **y≈336–340**. Bottom multilingual legend box starts at **y≈922**.
- The EU shield (top-right), GARAN wordmark + checkmark, QR code, "365" calendar and the
  27-language legend are all fixed artwork and were left untouched.
