import { test, expect } from '../../fixtures';

/**
 * Issue #120 regression — three independent defects in the o3-theme
 * favicon set, all of which silently broke browser-tab icons / PWA
 * install:
 *
 *   1. `manifest.webmanifest` shipped a `// manifest.webmanifest`
 *      comment as line 1 → invalid JSON → manifest never parses.
 *   2. The icons referenced from the manifest used root-relative
 *      paths (`/icon-192.png`) → 404'd at runtime.
 *   3. `favicon.ico` was 16-color 4bpp + 2-color 1bpp, no 32-bit
 *      RGBA entry → pixelated on HiDPI.
 *
 * If any of these three drift back, this spec fails.
 */

const FAVICON_BASE = '/out/o3-theme/src/favicon';

test.describe('o3-theme favicon assets (issue #120)', () => {
  test('P0 manifest.webmanifest is valid JSON with PWA basics', async ({ storefrontPage }) => {
    const res = await storefrontPage.request.get(`${FAVICON_BASE}/manifest.webmanifest`);
    expect(res.status(), 'manifest reachable').toBe(200);

    const body = await res.text();
    let manifest: Record<string, unknown>;
    expect(
      () => {
        manifest = JSON.parse(body) as Record<string, unknown>;
      },
      `manifest body must be valid JSON; got first line: ${body.split('\n', 1)[0]}`,
    ).not.toThrow();

    // PWA basics that make the shop installable.
    expect(manifest!.name, 'name').toBeTruthy();
    expect(manifest!.start_url, 'start_url').toBeTruthy();
    expect(manifest!.display, 'display').toBeTruthy();
    expect(Array.isArray(manifest!.icons), 'icons[]').toBe(true);
    expect((manifest!.icons as unknown[]).length).toBeGreaterThan(0);
  });

  test('P0 every icon referenced from the manifest resolves 200', async ({ storefrontPage }) => {
    const res = await storefrontPage.request.get(`${FAVICON_BASE}/manifest.webmanifest`);
    const manifest = (await res.json()) as { icons: { src: string }[] };

    for (const icon of manifest.icons) {
      // `src` should be relative-to-manifest. Resolving against the
      // manifest URL gives the absolute URL the browser would request.
      const iconUrl = new URL(icon.src, `http://localhost:8080${FAVICON_BASE}/manifest.webmanifest`)
        .toString();
      const iconRes = await storefrontPage.request.get(iconUrl);
      expect(iconRes.status(), `icon ${icon.src} (resolved to ${iconUrl})`).toBe(200);
    }
  });

  test('P0 favicon.ico has multiple 32-bit RGBA entries (no 4bpp / 1bpp legacy)', async ({
    storefrontPage,
  }) => {
    const res = await storefrontPage.request.get(`${FAVICON_BASE}/favicon.ico`);
    expect(res.status()).toBe(200);
    const buf = Buffer.from(await res.body());

    // ICONDIR header: 6 bytes — reserved (2) + type (2) + count (2).
    // Then `count` ICONDIRENTRY records of 16 bytes each.
    expect(buf.length, 'ico must have at least the 6-byte header').toBeGreaterThanOrEqual(6);
    const reserved = buf.readUInt16LE(0);
    const type = buf.readUInt16LE(2);
    const count = buf.readUInt16LE(4);
    expect(reserved, 'ICO reserved field').toBe(0);
    expect(type, 'ICO type (1 = icon)').toBe(1);
    expect(count, 'ICO must declare ≥2 entries (16/32 minimum)').toBeGreaterThanOrEqual(2);

    // Each entry's 7th byte is bitCount. We require all entries to
    // declare 32 bpp — anything less is the legacy palette format that
    // shipped the bug.
    let bppEntries: number[] = [];
    for (let i = 0; i < count; i++) {
      const entryOffset = 6 + i * 16;
      const bitCount = buf.readUInt16LE(entryOffset + 6);
      bppEntries.push(bitCount);
    }
    for (const bpp of bppEntries) {
      expect(bpp, `every ICO entry must be 32 bpp; saw entries: ${bppEntries.join(', ')}`).toBe(32);
    }
  });
});
