---
name: reference_eu-official-artwork-fetch
description: How to fetch official EU regulation artwork/PDFs past the EUR-Lex WAF via the Publications Office CELLAR endpoint
type: reference
---

# Fetching official EU regulation artwork (EUR-Lex WAF workaround)

Context: EU guarantee labels feature (#219) needed the official Annex I/II artwork from
Commission Implementing Regulation (EU) 2025/1960 (CELEX `32025R1960`).

## Key facts

- `eur-lex.europa.eu` and the `commission.europa.eu/document/download/...` links sit behind
  an **AWS WAF JS-challenge** (`HTTP 202` + header `x-amzn-waf-action: challenge`).
  `curl`/WebFetch cannot pass it — only a real browser can.
- `publications.europa.eu` (the **CELLAR** content-negotiation endpoint) is **not** WAF-gated
  and serves the same authoritative documents. Use it instead.
- Post-2023 OJ acts are "born digital": primary manifestations are **xhtml** and **fmx4**,
  plus a **pdfa2a** (PDF/A-2a). There is no plain `application/pdf` datastream at the CELEX
  level — requesting `Accept: application/pdf` on the CELEX URI gives a misleading 404.

## Recipe

1. Enumerate manifestations from the branch notice:
   `curl -H 'Accept: application/xml; notice=branch' -H 'Accept-Language: eng' \
     http://publications.europa.eu/resource/celex/<CELEX>`
   Grep it for identifiers like `L_202501960.ENG.pdfa2a` / `.xhtml` / `.fmx4`.
2. Download the PDF/A via the **full item path** (the short `/resource/oj/<id>.pdfa2a`
   returns 404 — you need the `.<FILENAME>.pdf` suffix):
   `curl -L http://publications.europa.eu/resource/oj/L_202501960.ENG.pdfa2a.L_202501960EN.pdf`
   (swap `ENG`/`DEU`/… and the `EN`/`DE` filename token for other languages.)
3. XHTML manifestation embeds artwork as **base64 data URIs** (usable but lower-res); the
   **PDF/A embeds the same artwork at ~2× resolution** — prefer extracting from the PDF.
4. Extract embedded images at native resolution with PyMuPDF
   (`page.get_images(full=True)` + `doc.extract_image(xref)`); `colorspace==3` is RGB,
   `1` is grayscale/mono. No ghostscript/imagemagick/pdftoppm on the mac host — `sips`
   only rasterises page 1 of a multipage PDF, so PyMuPDF image extraction is the way.

Provenance and the derived assets for #219 live in
`source/Core/GuaranteeLabel/assets/README.md`.
