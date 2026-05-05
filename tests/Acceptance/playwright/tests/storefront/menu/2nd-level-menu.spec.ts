import { test, expect } from '../../../fixtures';
import { StorefrontCategoryPage } from '../../../pages/storefront/CategoryPage';

/**
 * Issue #141 — 2nd-level menu polish.
 *
 * These specs run against **existing demo data** so they're the cheapest
 * smoke tests of the megamenu / inline sub-category strip. The composed
 * E2E spec (see ../menu-e2e/...) creates its own categories before
 * asserting; the tests here just validate what's already shipped.
 *
 * Demo categories the specs depend on:
 *   • Einhörner       → has sub-cat `Unter-Einhörner` (left-anchored case)
 *   • Party-Pinguine  → has sub-cat `Unter-Party-Pinguine` plus level-3
 *                       (right-edge case — must flip the dropdown anchor)
 *   • Pandas          → no sub-cats (negative test — no megamenu rendered)
 */

test.describe('storefront / 2nd-level menu (#141)', () => {
    test('PLP renders the labelled sub-category strip with chip-styled links', async ({
        storefrontPage,
    }) => {
        const plp = new StorefrontCategoryPage(storefrontPage);
        await plp.goto('/Einhoerner/');

        // Eyebrow label is rendered via data-label + CSS ::before
        const label = await plp.inlineSubcatLabel();
        expect(label).toBe('In dieser Kategorie');

        // ARIA label echoes the same string for screen-reader users
        await expect(plp.inlineSubcatNav).toHaveAttribute('aria-label', 'In dieser Kategorie');

        // Demo data: Einhörner has at least the user-created `Unter-Einhörner`
        const chips = await plp.inlineSubcatChips();
        expect(chips.length).toBeGreaterThan(0);
        expect(chips).toContain('Unter-Einhörner');

        // Chips are pill-shaped (border-radius >= 24 → effectively 999px clamped)
        const radius = await plp.inlineSubcatNav
            .locator('a.btn')
            .first()
            .evaluate((a) => parseFloat(getComputedStyle(a).borderRadius));
        expect(radius).toBeGreaterThanOrEqual(20);
    });

    test('top-nav megamenu opens with caret and styled panel (left-anchored)', async ({
        storefrontPage,
    }) => {
        const plp = new StorefrontCategoryPage(storefrontPage);
        await plp.goto('/');

        expect(await plp.hasMegamenu('Einhörner')).toBe(true);
        const menu = await plp.openMegamenu('Einhörner');

        // Visible panel: white bg + rounded radius drawn by ::before pseudo
        const panelStyles = await menu.evaluate((el) => {
            const cs = getComputedStyle(el, '::before');
            return {
                bg: cs.backgroundColor,
                radius: parseFloat(cs.borderRadius),
                hasShadow: cs.boxShadow !== 'none' && cs.boxShadow !== '',
            };
        });
        expect(panelStyles.bg).toBe('rgb(255, 255, 255)');
        expect(panelStyles.radius).toBeGreaterThanOrEqual(8);
        expect(panelStyles.hasShadow).toBe(true);

        // Caret (::after pseudo) is anchored to the LEFT side for left-side items
        const caret = await menu.evaluate((el) => {
            const cs = getComputedStyle(el, '::after');
            return { left: cs.left, right: cs.right };
        });
        expect(caret.left).not.toBe('auto');
    });

    test('right-side top-nav megamenu flips anchoring to stay on screen', async ({
        storefrontPage,
    }) => {
        const plp = new StorefrontCategoryPage(storefrontPage);
        await plp.goto('/');

        expect(await plp.hasMegamenu('Party-Pinguine')).toBe(true);

        // The CSS flip target the last two .nav-items so the panel doesn't
        // overflow the viewport on the right.
        expect(await plp.isMegamenuRightAnchored('Party-Pinguine')).toBe(true);
        expect(await plp.isMegamenuOnScreen('Party-Pinguine')).toBe(true);
    });

    test('hover bridge: no dead zone between parent and dropdown', async ({ storefrontPage }) => {
        // Issue #141 issue 2: cursor moving parent → menu must never cross
        // an unhovered region. The menu uses padding-top: 12px as a
        // transparent hit-area bridge; the visible panel is offset via ::before.
        //
        // Contract: `gap <= 0`. Zero means menu's hit area starts exactly
        // at LI bottom; negative means it overlaps the LI from above
        // (e.g. -5 from subpixel rounding / chevron transform). Both are
        // safe — only a *positive* gap would leave a dead zone.
        const plp = new StorefrontCategoryPage(storefrontPage);
        await plp.goto('/');

        const gap = await plp.measureHoverBridgeGap('Einhörner');
        expect(gap).toBeLessThanOrEqual(0);
    });

    test('megamenu and inline strip render the same depth (one level)', async ({
        storefrontPage,
    }) => {
        // Issue #141 issue 3: the megamenu used to render level-2 *and*
        // level-3 categories nested; the inline strip rendered only level-2.
        // The fix removes the level-3 rendering from categorylist.tpl so
        // both views show direct children only — consistent depth.
        const plp = new StorefrontCategoryPage(storefrontPage);
        await plp.goto('/Party-Pinguine/');

        // The megamenu under Party-Pinguine must NOT contain any [data-level="is-level-3"]
        const menu = await plp.openMegamenu('Party-Pinguine');
        const level3Count = await menu.locator('a[data-level="is-level-3"]').count();
        expect(level3Count).toBe(0);

        // Inline strip on the PLP shows only direct children — same depth contract
        const chips = await plp.inlineSubcatChips();
        expect(chips.length).toBeGreaterThan(0);
    });

    test('top-nav category without sub-cats does NOT render a megamenu', async ({
        storefrontPage,
    }) => {
        // Demo: Pandas has no sub-cats, so it must not gain .mega-dropdown
        // and its <li> must contain no .megamenu node.
        const plp = new StorefrontCategoryPage(storefrontPage);
        await plp.goto('/');

        const pandas = plp.topNavItem('Pandas');
        await expect(pandas).toHaveCount(1);
        const isMega = await plp.hasMegamenu('Pandas');
        expect(isMega).toBe(false);
        await expect(pandas.locator('.megamenu')).toHaveCount(0);
    });
});
