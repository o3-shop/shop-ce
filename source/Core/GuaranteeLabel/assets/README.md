# EU Guarantee Label Assets — Provenance

These assets implement the two EU-mandated guarantee artefacts of
**Commission Implementing Regulation (EU) 2025/1960** (OJ L 2025/1960, 2.10.2025,
CELEX `32025R1960`): the harmonised **notice** on the legal guarantee of conformity
(Annex I) and the harmonised **label** for the commercial guarantee of durability
(Annex II).

Retrieval date: **2026-07-16** (v2 — official Commission artwork packages).

## Legal note

Artwork per Reg. (EU) 2025/1960 Annexes I/II. **The notice must never be modified** —
it is per-language fixed artwork; every element (text, colours, QR code) is baked in
and legally fixed. The **label** is one language-neutral artwork with exactly three
editable areas (duration in years, brand/trademark, model identifier); these variable
fields are composited at runtime by `GuaranteeLabelGenerator`. The **nested banner** is
the official reduced-display asset with exactly one editable area (the duration/year).
Both QR codes (notice and label) are static and part of the official artwork — they are
never generated or altered. Colour is legally mandatory for online display
(Pantone Reflex Blue C ≈ `#003399`, rendered `#034ea2` in the artwork; Pantone Yellow C
≈ `#FFED00`, rendered `#fff200`).

## Files

| File | Purpose |
|---|---|
| `label-template.png` | Language-neutral Annex II full label, colour, three variable areas blanked (1400×1474). |
| `nested-template.png` | Official nested/reduced-display banner, colour, year area blanked (2211×340). |
| `Inter-Regular.ttf` / `Inter-SemiBold.ttf` / `Inter-ExtraBold.ttf` | Fonts for runtime text compositing. |
| `Inter-LICENSE.txt` | SIL Open Font License 1.1 for the Inter fonts. |
| `../../../out/pictures/guarantee/notice-en.png` | Annex I notice, English, colour (1185×1675). |
| `../../../out/pictures/guarantee/notice-de.png` | Annex I notice, German, colour (1185×1675). |

## Sources (exact URLs)

**Official Commission artwork packages** (the Commission's own "ready-made files",
verified downloadable 2026-07-16):

- **GARAN label package** (colour/bw/nested, SVG + PNG + JPG):
  `https://commission.europa.eu/document/download/435fbeb1-fccc-4ead-bfa9-96625962ba09_en?filename=GARAN%20label%20for%20website.zip`
  — `label-template.png` is rasterized from `GARAN Label_colour.svg`; `nested-template.png`
  from `GARAN Label_nested display.svg`.
- **Harmonised notice, 24 languages** (colour + black-and-white, one PDF per language):
  `https://commission.europa.eu/document/download/29acbfc0-a26e-4c21-85af-8bc2b167103e_en?filename=Harmonised%20notice%20in%2024%20languages%20colour%20and%20black%20and%20white_0.zip`
  — `notice-de.png` from `Legal guarantee_notice DEN.pdf`, `notice-en.png` from
  `Legal guarantee_notice ENG.pdf`. Each PDF has 2 pages: **page 0 = colour** (used here),
  page 1 = black-and-white (unused).

The label SVG is language-neutral; the nested banner and the notice are the Commission's
official display assets. No third-party recreations were used.

**Inter fonts.**
`https://github.com/rsms/inter/releases/download/v4.1/Inter-4.1.zip`
(release **v4.1**, published 2024-11-16). Static desktop TTFs taken from `extras/ttf/`
inside the zip; license from `LICENSE.txt` (SIL OFL 1.1), saved as `Inter-LICENSE.txt`.

## Conversion / preparation commands

**Templates (SVG → PNG).** In *copies* of the two SVGs, the editable `<text>` elements
were blanked (their text content emptied; nothing else changed). All *fixed* typography
in the official SVGs (GARAN wordmark, "365", the 27-language legend) is stored as vector
paths, not live text — so the rasterizer never needs a text font for the fixed artwork, and
the only live `<text>` elements are exactly the editable fields (removed here). The Inter
TTFs above are still shipped for runtime compositing by `GuaranteeLabelGenerator`.

Rasterized with **headless Google Chrome** (deterministic; renders the vector paths and the
blue/yellow colours exactly). Each blanked SVG was inlined into a minimal wrapper HTML
(`@font-face` pointing at the local Inter TTFs, `svg{width:<target>px;height:auto}`,
white background) and captured at 1:1:

```
"Google Chrome" --headless=new --disable-gpu --hide-scrollbars \
  --force-device-scale-factor=1 --default-background-color=ffffffff \
  --window-size=1400,1474 --screenshot=label-template.png file://.../label_blank.html
# nested: --window-size=2211,340
```

- Full label: `GARAN Label_colour.svg` viewBox `269.29×283.46`, target width 1400 px
  (scale ≈ **5.19886**) → **1400×1474**.
- Nested banner: `GARAN Label_nested display.svg` viewBox `368.5×56.69`, **6×** → **2211×340**.

Result verified visually against the package's official `GARAN Label_colour.jpg`
(fixed typography, QR modules and colours identical).

**Notices (PDF → PNG).** Rendered with **PyMuPDF** (`page.get_pixmap`) at a zoom of
`1675 / 841.89 ≈ 1.9896` so the A4 page (595.276×841.89 pt) yields **1185×1675 px**
(height ≤ 1675). Page 0 (colour) only. The full official page is preserved unmodified —
**no cropping** (the notice may never be altered). QR modules verified crisp/scannable.

Resulting sizes: `label-template.png` 1400×1474, `nested-template.png` 2211×340,
`notice-en.png` 1185×1675, `notice-de.png` 1185×1675. All RGB, `image/png`.

## Blanked variable areas — bounding boxes for runtime compositing (Task 2)

Boxes are the **ink bounding box of the original placeholder text** (the pixels that
disappeared when the field was blanked), measured by diffing the original vs. blanked
render. Pixels, origin top-left, `[x0, y0, x1, y1]` inclusive plus `x, y, w, h`.
The SVG anchor is the `<text>` element's `translate()` origin, i.e. the **left edge of the
baseline** — the natural reference for re-compositing runtime text in the correct font.

### `label-template.png` (1400×1474) — scale ≈ 5.19886 from viewBox 269.29×283.46

| Field | SVG `<text>` (font) | SVG anchor (baseline L) | Baseline px (x, y) | Ink box `[x0,y0,x1,y1]` | x, y, w, h |
|---|---|---|---|---|---|
| Brand/Trademark  | `translate(6.32, 74.52)` cls-5, Inter-Regular 9px      | (6.32, 74.52)   | (33, 387)   | `[36, 351, 417, 391]`   | x=36,  y=351, w=382, h=41  |
| Model identifier | `translate(196.75, 74.52)` cls-5, Inter-Regular 9px    | (196.75, 74.52) | (1023, 387) | `[1026, 351, 1367, 387]` | x=1026, y=351, w=342, h=37 |
| Duration ("XX")  | `translate(5.07, 150.57)` cls-3, Inter-ExtraBold 80px  | (5.07, 150.57)  | (26, 783)   | `[36, 480, 635, 782]`   | x=36,  y=480, w=600, h=303 |

### `nested-template.png` (2211×340) — scale 6× from viewBox 368.5×56.69

| Field | SVG `<text>` (font) | SVG anchor (baseline L) | Baseline px (x, y) | Ink box `[x0,y0,x1,y1]` | x, y, w, h |
|---|---|---|---|---|---|
| Duration/year ("XX") | `translate(10.39, 46.65)` cls-1, Inter-ExtraBold 41.56px | (10.39, 46.65) | (62, 280) | `[68, 98, 429, 279]` | x=68, y=98, w=362, h=182 |

Fixed elements left untouched on both templates: the GARAN wordmark + checkmark, the EU
shield ("G"), the QR code, the "365" calendar icon and (full label) the 27-language legend.
On the nested banner the "365" calendar icon and the vertical divider line (SVG x=93.73 →
px≈562) sit to the right of the year field; the divider is the right boundary of the
editable area.
