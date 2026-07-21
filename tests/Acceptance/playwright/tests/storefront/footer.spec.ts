import { test, expect } from '../../fixtures';

/**
 * Storefront footer — O3-Shop links + translation.
 *
 * Issue #30  — the footer must carry the O3-Shop links: the "community home
 *              page" link and the "powered by O3 Shop" link.
 * Issue #172 — the English footer link used the `DD_FOOTER_O3ShopLink`
 *              translation key, which was missing → the footer rendered the
 *              raw IDENT / an error string instead of a readable label.
 *              Both languages must now render a proper translated label,
 *              never a raw key.
 *
 * Pure-render checks (no session / DB) → the plain `storefrontPage` fixture.
 * The O3 links live in `.footer__legal .legal` (after the VAT/shipping row),
 * with the community link as the first anchor of that block:
 *
 *   <div class="footer__legal"> … <div class="legal">
 *       <div>Demo … <a href="https://o3-shop.com">…Community-Homepage</a></div>
 *       <a href="https://www.o3-shop.com/" target="_blank">powered by O3 Shop</a>
 *   </div></div>
 */

// A visible footer label like "DD_FOOTER_O3SHOPLINK" is an untranslated IDENT,
// not human copy.
const RAW_IDENT = /^[A-Z0-9]+(?:_[A-Z0-9]+)+$/;

test.describe('storefront footer O3 links + translation (#30, #172)', () => {
  test('P0 footer carries the O3 community link and the "powered by O3 Shop" link (#30)', async ({
    storefrontPage,
  }) => {
    await storefrontPage.goto('/', { waitUntil: 'domcontentloaded' });

    const legal = storefrontPage.locator('.footer__legal .legal');
    await expect(legal).toHaveCount(1);

    // First anchor of the legal block = the community-home-page link.
    const community = legal.locator('a').first();
    await expect(community).toBeVisible();
    expect(await community.getAttribute('href')).toMatch(/o3-shop\.com/i);

    const powered = storefrontPage
      .locator('.footer__legal a')
      .filter({ hasText: /powered by o3/i })
      .first();
    await expect(powered).toBeVisible();
    expect(await powered.getAttribute('href')).toMatch(/o3-shop\.com/i);
  });

  test('P0 English footer link renders the translation, not the raw DD_FOOTER_O3ShopLink key (#172)', async ({
    storefrontPage,
  }) => {
    // lang=1 = English. A missing translation key surfaces as the raw IDENT.
    await storefrontPage.goto('/index.php?lang=1', { waitUntil: 'domcontentloaded' });

    const community = storefrontPage.locator('.footer__legal .legal a').first();
    await expect(community).toBeVisible();

    const text = (await community.innerText()).trim();
    expect(text.length, 'footer link must have visible text').toBeGreaterThan(0);
    expect(text, `footer link must be a translated label, not a raw key: "${text}"`).not.toMatch(
      RAW_IDENT,
    );
    expect(text.toLowerCase(), `expected readable English label, got "${text}"`).toContain(
      'community',
    );
    expect(await community.getAttribute('href')).toMatch(/o3-shop\.com/i);
  });

  test('P1 German footer link renders the localized label (#172)', async ({ storefrontPage }) => {
    await storefrontPage.goto('/index.php?lang=0', { waitUntil: 'domcontentloaded' });

    const community = storefrontPage.locator('.footer__legal .legal a').first();
    await expect(community).toBeVisible();

    const text = (await community.innerText()).trim();
    expect(text.length).toBeGreaterThan(0);
    expect(text, `footer link must be a translated label, not a raw key: "${text}"`).not.toMatch(
      RAW_IDENT,
    );
    expect(text.toLowerCase()).toContain('community');
  });
});
