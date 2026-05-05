import { test, expect } from '../../../fixtures';
import { CategoryAdminPage } from '../../../pages/admin/CategoryAdminPage';
import { StorefrontCategoryPage } from '../../../pages/storefront/CategoryPage';
import {
    clearShopRuntimeCache,
    deleteCategoriesViaModel,
} from '../../../helpers/config';

/**
 * Composed E2E test for issue #141.
 *
 * Flow (the order the user asked for, in case anything fails midway):
 *   1. CREATE three categories via the admin form (cl=category_main):
 *        TestL2A     — direct child of `Pandas`     (level-2)
 *        TestL2B     — direct child of `Pandas`     (level-2)
 *        TestL3      — direct child of TestL2A      (level-3)
 *      Pandas is used because the demo data ships it without sub-cats,
 *      so the test starts from a known-empty subtree.
 *   2. NAVIGATE the frontend and assert:
 *        - Pandas now exposes a megamenu with TestL2A + TestL2B
 *        - /Pandas/ inline strip lists TestL2A + TestL2B as chips
 *        - /Pandas/<TestL2A>/ inline strip lists TestL3 as a chip
 *        - both views show the **same** depth (no level-3 in the megamenu)
 *   3. CLEAN UP by cascading-deleting the TestL2A subtree (removes
 *      TestL2A + TestL3) and TestL2B. Cleanup runs in `afterAll` so a
 *      mid-test failure still triggers it.
 */

const PANDAS_OXID = '5a1b82c8a7c97d78f270315bb7d5e20b';
const L2A = 'PW-Test-L2-A';
const L2B = 'PW-Test-L2-B';
const L3 = 'PW-Test-L3';

test.describe.serial('storefront / 2nd-level menu E2E (#141)', () => {
    let oxidL2A: string;
    let oxidL2B: string;
    let oxidL3: string;

    test.beforeAll(async ({ db }) => {
        // Belt-and-braces: if a previous run died before cleanup, drop
        // any leftovers before recreating. We resolve OXIDs and delete
        // through Category::delete() so the parent's nested-set ranges
        // shrink correctly — a plain DELETE leaves Pandas with bloated
        // OXRIGHT, which the next run's getSubCatList() reads as
        // "Pandas has children" even when the rows are gone.
        const ids = (
            await Promise.all([
                db.findCategoryByTitle(L3),
                db.findCategoryByTitle(L2A),
                db.findCategoryByTitle(L2B),
            ])
        ).filter((id): id is string => !!id);
        if (ids.length > 0) {
            await deleteCategoriesViaModel(ids);
            await clearShopRuntimeCache();
        }
    });

    test.afterAll(async ({ db }) => {
        const ids = (
            await Promise.all([
                db.findCategoryByTitle(L3),
                db.findCategoryByTitle(L2A),
                db.findCategoryByTitle(L2B),
            ])
        ).filter((id): id is string => !!id);
        if (ids.length > 0) {
            await deleteCategoriesViaModel(ids);
        }
        // Bust the storefront category-tree cache so a follow-up run starts clean.
        await clearShopRuntimeCache();
    });

    test('1) admin: create level-2 + level-3 sub-categories under Pandas', async ({
        adminPage,
        db,
    }) => {
        const admin = new CategoryAdminPage(adminPage);

        oxidL2A = await admin.createCategory({ title: L2A, parentId: PANDAS_OXID });
        oxidL2B = await admin.createCategory({ title: L2B, parentId: PANDAS_OXID });
        expect(oxidL2A).not.toBe('');
        expect(oxidL2B).not.toBe('');
        expect(oxidL2A).not.toBe(oxidL2B);

        oxidL3 = await admin.createCategory({ title: L3, parentId: oxidL2A });
        expect(oxidL3).not.toBe('');

        // OXID caches the category tree in source/tmp/oxc_aLocalCatCache.txt;
        // without clearing it the storefront serves stale HTML and the
        // megamenu won't show the new sub-cats.
        await clearShopRuntimeCache();

        // DB sanity: rows exist with the expected parent links
        await expect.poll(() => db.categoryExists(oxidL2A)).toBe(true);
        await expect.poll(() => db.categoryExists(oxidL2B)).toBe(true);
        await expect.poll(() => db.categoryExists(oxidL3)).toBe(true);

        const l3parent = await db.query<{ OXPARENTID: string }>(
            `SELECT OXPARENTID FROM oxcategories WHERE OXID = ? LIMIT 1`,
            [oxidL3],
        );
        expect(l3parent[0]?.OXPARENTID).toBe(oxidL2A);

        // Pandas's oxright must have grown to enclose its three new
        // descendants — otherwise `Category::getSubCatList()` returns
        // empty and the storefront megamenu never appears (#141 spec).
        // 1 left + 3 descendants × 2 (L+R) + 1 right = 8.
        const pandasRange = await db.query<{ OXLEFT: number; OXRIGHT: number }>(
            `SELECT OXLEFT, OXRIGHT FROM oxcategories WHERE OXID = ? LIMIT 1`,
            [PANDAS_OXID],
        );
        expect(pandasRange[0]?.OXRIGHT).toBeGreaterThanOrEqual(8);
    });

    test('2) storefront: megamenu and inline strip reflect the new tree (consistent depth)', async ({
        storefrontPage,
    }) => {
        const plp = new StorefrontCategoryPage(storefrontPage);

        // Pandas now has children → its top-nav <li> must gain .mega-dropdown.
        // Poll a few times: OXID's category-list build caches per request,
        // so the very first storefront response after the admin mutations
        // can occasionally race the cache repopulation. A reload or two
        // is enough.
        await expect
            .poll(
                async () => {
                    await plp.goto('/');
                    return plp.hasMegamenu('Pandas');
                },
                { message: 'Pandas should expose .mega-dropdown after admin create', timeout: 10_000 },
            )
            .toBe(true);
        const megamenuItems = await plp.megamenuItems('Pandas');
        expect(megamenuItems).toEqual(expect.arrayContaining([L2A, L2B]));

        // Hover bridge: no dead zone (gap ≤ 0) on a freshly-grown megamenu.
        const gap = await plp.measureHoverBridgeGap('Pandas');
        expect(gap).toBeLessThanOrEqual(0);

        // Crucially: NO level-3 appears in the megamenu, even though L3
        // exists as a grandchild. This is the consistency contract from
        // issue #141 — both megamenu and inline strip show direct children only.
        await plp.closeMegamenu();
        const menu = await plp.openMegamenu('Pandas');
        expect(await menu.locator('a[data-level="is-level-3"]').count()).toBe(0);

        // PLP /Pandas/ → inline strip shows the same two level-2 chips.
        await plp.goto('/Pandas/');
        const pandasChips = await plp.inlineSubcatChips();
        expect(pandasChips).toEqual(expect.arrayContaining([L2A, L2B]));
        expect(pandasChips).not.toContain(L3); // direct children only

        // Drilling into TestL2A → its PLP shows TestL3 as the lone chip.
        // We follow the chip to keep the test data-driven (no hard-coded URL).
        await plp.inlineSubcatNav.locator('a.btn', { hasText: L2A }).click();
        await storefrontPage.waitForLoadState('domcontentloaded');
        const l2aChips = await plp.inlineSubcatChips();
        expect(l2aChips).toEqual([L3]);

        // Eyebrow label is rendered on the L2A PLP too.
        expect(await plp.inlineSubcatLabel()).toBe('In dieser Kategorie');
    });

    test('3) admin/db: cleanup removes the seeded categories', async ({ db }) => {
        // Delete through Category::delete() so the parent's nested-set
        // ranges shrink correctly. Order matters: L3 first (deepest), then
        // L2A and L2B; otherwise the model-level delete on a parent skips
        // the descendant rows and leaves them orphaned.
        const out = await deleteCategoriesViaModel([oxidL3, oxidL2A, oxidL2B]);
        expect(out).toContain(`deleted: ${oxidL3}`);
        expect(out).toContain(`deleted: ${oxidL2A}`);
        expect(out).toContain(`deleted: ${oxidL2B}`);

        await expect.poll(() => db.categoryExists(oxidL2A)).toBe(false);
        await expect.poll(() => db.categoryExists(oxidL2B)).toBe(false);
        await expect.poll(() => db.categoryExists(oxidL3)).toBe(false);

        // Bust the cache so the next test sees a Pandas without sub-cats.
        await clearShopRuntimeCache();
    });

    test('3b) storefront: Pandas no longer exposes a megamenu after cleanup', async ({
        storefrontPage,
    }) => {
        // Bust any stale OXID list-rendering cache that could keep stale
        // children on screen briefly. We hit a different page first to
        // avoid serving the prior /Pandas/ HTML out of any browser cache.
        const plp = new StorefrontCategoryPage(storefrontPage);
        await plp.goto('/Fuechse/');
        await plp.goto('/');

        // Without children, Pandas reverts to a plain .nav-item.
        // Smarty/Composer caches can lag — poll a few times before failing.
        await expect
            .poll(async () => plp.hasMegamenu('Pandas'), {
                message: 'Pandas should no longer have .mega-dropdown after cleanup',
                timeout: 5_000,
            })
            .toBe(false);
    });
});
