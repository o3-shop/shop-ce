import { Page, Locator, expect } from '@playwright/test';
import { BaseStorefrontPage } from './BaseStorefrontPage';

function appendLangParam(path: string, lang: number): string {
    const sep = path.includes('?') ? '&' : '?';
    return `${path}${sep}lang=${lang}`;
}

/**
 * POM for any o3-theme category page (PLP) and the storefront megamenu.
 *
 * Backs the issue #141 specs — 2nd-level menu rendering on the frontend.
 */
export class StorefrontCategoryPage extends BaseStorefrontPage {
    constructor(page: Page) {
        super(page);
    }

    /**
     * Navigate to a storefront URL.
     *
     * Always pins the language to German (`lang=0`). Why: OXID's homepage
     * has no SEO path of its own, so visiting `/` falls back to the
     * Accept-Language header — and Chromium's default `en-US` would send
     * us into the English shop where the test expectations ("Einhörner",
     * "Party-Pinguine", "In dieser Kategorie") don't exist. SEO paths
     * like `/Einhoerner/` carry an implicit language anyway, so the
     * extra `?lang=0` is harmless on those.
     */
    async goto(path: string): Promise<void> {
        const url = path.includes('lang=') ? path : appendLangParam(path, 0);
        await this.page.goto(url, { waitUntil: 'domcontentloaded' });
    }

    // ─── Top-nav megamenu ─────────────────────────────────────────────────────

    /**
     * Top-nav `<li>` whose link's visible text matches `title`.
     *
     * Scoped to `.header__mainnav` so the mobile offcanvas duplicate of the
     * nav (always present in DOM, hidden via Bootstrap utilities) doesn't
     * confuse the match.
     */
    topNavItem(title: string): Locator {
        return this.page
            .locator('.header__mainnav .nav-item')
            .filter({ hasText: title });
    }

    /** True iff the top-nav item exposes the .mega-dropdown affordance. */
    async hasMegamenu(title: string): Promise<boolean> {
        return (await this.topNavItem(title).evaluate((li) =>
            li.classList.contains('mega-dropdown'),
        )) as boolean;
    }

    /** Open the megamenu for `title` by hovering the top-nav link. */
    async openMegamenu(title: string): Promise<Locator> {
        const item = this.topNavItem(title);
        await item.locator('.nav-link').first().hover();
        const menu = item.locator('.megamenu');
        await expect(menu).toBeVisible({ timeout: 2000 });
        return menu;
    }

    /** Close any open megamenu by moving the cursor far away. */
    async closeMegamenu(): Promise<void> {
        // hover the page logo (always present, never a megamenu owner)
        await this.page.locator('.header__logo, header .logo').first().hover();
    }

    /** All level-2 link texts inside the open megamenu for `title`. */
    async megamenuItems(title: string): Promise<string[]> {
        const menu = await this.openMegamenu(title);
        return await menu.locator('a[data-level="is-level-2"]').allTextContents();
    }

    // ─── Sub-category listing on the PLP ──────────────────────────────────────
    //
    // o3-theme 1.5 gained the `blShowSubcatTiles` display setting. With it
    // OFF (theme default) `page/list/list.tpl` renders the direct children
    // as a labelled pill strip; with it ON it renders them as
    // product-box-style tiles *instead*. Both list exactly the visible
    // direct children, so every depth assertion in the #141 specs holds in
    // either mode — the accessors below hide the difference, and only the
    // genuinely mode-specific chrome (eyebrow label, pill radius, tile
    // picture) is asserted per mode by the specs.

    /** The labelled <nav> rendered above the product grid (pill mode). */
    get inlineSubcatNav(): Locator {
        return this.page.locator('nav.alist__orga-subcats');
    }

    /**
     * The sub-category tiles rendered above the product grid (tile mode).
     *
     * Scoped to `.alist__orga`: `.component__productbox` is also the
     * product grid's box class, and that grid lives outside this container.
     */
    get subcatTiles(): Locator {
        return this.page.locator('.alist__orga .component__productbox');
    }

    /** Which of the two renderings this shop is configured for. */
    async subcatMode(): Promise<'tiles' | 'pills'> {
        return (await this.subcatTiles.count()) > 0 ? 'tiles' : 'pills';
    }

    /** The link to the sub-category named `title`, in whichever mode is active. */
    subcatLink(title: string): Locator {
        return this.page
            .locator(
                '.alist__orga a.component__productbox-title, nav.alist__orga-subcats a.btn',
            )
            .filter({ hasText: title });
    }

    /** Titles of the listed direct sub-categories, in whichever mode is active. */
    async subcatTitles(): Promise<string[]> {
        const links =
            (await this.subcatMode()) === 'tiles'
                ? this.subcatTiles.locator('a.component__productbox-title')
                : this.inlineSubcatNav.locator('a.btn');
        return (await links.allTextContents()).map((text) => text.trim());
    }

    /** Eyebrow label text, read from the data-label attribute (rendered via ::before). */
    async inlineSubcatLabel(): Promise<string | null> {
        return await this.inlineSubcatNav.getAttribute('data-label');
    }

    // ─── Geometry checks (positioning bugs from #141) ─────────────────────────

    /**
     * Pixel gap between the parent <li>'s bottom edge and the megamenu's top edge.
     * Should be 0 — the menu uses a transparent padding-top as hover-bridge so
     * the cursor never crosses an unhovered region (#141 issue 2).
     */
    async measureHoverBridgeGap(title: string): Promise<number> {
        const item = this.topNavItem(title);
        await item.locator('.nav-link').first().hover();
        return await item.evaluate((li) => {
            const menu = li.querySelector('.megamenu') as HTMLElement | null;
            if (!menu) return Number.NaN;
            const liRect = li.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();
            return Math.round(menuRect.top - liRect.bottom);
        });
    }

    /**
     * True iff the open megamenu is anchored to the *right* of its parent.
     *
     * Checks the geometry rather than `getComputedStyle().left/right`,
     * because once `right: 0` resolves, `left` collapses from `auto` to
     * a negative pixel value (parent-width minus menu-width). The
     * semantically correct check is: the menu's right edge aligns with
     * the parent LI's right edge.
     */
    async isMegamenuRightAnchored(title: string): Promise<boolean> {
        const item = this.topNavItem(title);
        await item.locator('.nav-link').first().hover();
        return await item.evaluate((li) => {
            const menu = li.querySelector('.megamenu') as HTMLElement | null;
            if (!menu) return false;
            const liRect = li.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();
            // Right edges within 5 px (subpixel + the panel pseudo's borders)
            return Math.abs(menuRect.right - liRect.right) <= 5;
        });
    }

    /** True iff the open megamenu's right edge lies within the viewport. */
    async isMegamenuOnScreen(title: string): Promise<boolean> {
        const item = this.topNavItem(title);
        await item.locator('.nav-link').first().hover();
        return await item.evaluate((li) => {
            const menu = li.querySelector('.megamenu') as HTMLElement | null;
            if (!menu) return false;
            const r = menu.getBoundingClientRect();
            return r.left >= 0 && r.right <= window.innerWidth + 1;
        });
    }
}
