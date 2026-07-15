---
name: eu-reg-2025-1960-guarantee-labels
description: EU Implementing Regulation 2025/1960 facts — Annex I Gewährleistungs-Label is static per-language artwork with a fixed EU QR (no runtime QR generation needed); Annex II GARAN label has trader-editable fields
type: reference
---

# EU Implementing Regulation (EU) 2025/1960 — harmonised guarantee notice + durability label

Primary source: OJ L 2025/1960, 2.10.2025, ELI http://data.europa.eu/eli/reg_impl/2025/1960/oj
(EUR-Lex web UI blocks curl/WebFetch; fetch the XHTML from CELLAR instead:
`curl -H "Accept: application/xhtml+xml" -H "Accept-Language: eng|deu" http://publications.europa.eu/resource/celex/32025R1960` — label artwork is embedded as base64 JPEGs in the XHTML.)

Applies from **2026-09-27** (Art. 3), same date as Directive (EU) 2024/825 (EmpCo) transposition.

## Annex I — harmonised notice on the legal guarantee (Gewährleistungs-Label)

- **Fully static artwork. "None of the elements of the harmonised notice can be edited."** (Annex I note 1)
- **QR code is fixed and identical for every trader** — it leads to the Your Europe portal, NOT to trader content
  (Annex I note 3). Decoded from official artwork: EN → `https://europa.eu/youreurope/guarantees`,
  DE → `https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_de.htm`
  (printed short URL on DE artwork: `europa.eu/youreurope/garantien`). Each OJ language version carries its
  own language-specific QR. → **Ship as static per-language image assets; no QR library, no runtime generation.**
- Colours (notes 2): Blue Pantone Reflex Blue C #003399, Yellow Pantone Yellow C #FFED00
  (OJ text has typo "#FFEDOO" with letter O — RGB 255/237/0 confirms FFED00), Black #000000, White #FFFFFF.
- Print/off-line: colour (CMYK) or B/W allowed, minimum size **A4** (note 4).
  **Online (distance contracts via online interface): colour (RGB) is mandatory** (note 5). No pixel minimum,
  but QR "shall be scannable ... using a standard mobile device". No nested-display option for the notice
  (nesting is only allowed for the Annex II label).
- The notice is pre-contractual info under Art. 5(1)(e)/6(1)(l) + 22a Directive 2011/83/EU → must be shown
  prominently before the consumer is bound (i.e. before order submission).
- 24 official EU language versions exist (one artwork per OJ language). The Regulation itself does not say
  which language a shop must use — that follows national language rules for pre-contractual information.

## Annex II — durability label (GARAN, not yet built)

- Language-neutral single design; "producer guarantee in years" translated into all 24 languages at the bottom.
- Editable: XX = duration in years, Brand/Trademark = guarantor, Model identifier. Everything else fixed,
  incl. QR → `https://europa.eu/youreurope/commercial-guarantee-durability/index.htm` (same for all traders).
- Font **Inter** (Regular/SemiBold/ExtraBold) — prescribed for Annex II only. Min print size 95×100 mm;
  online must be colour; nested display allowed (full label on first click/roll-over/expansion).
