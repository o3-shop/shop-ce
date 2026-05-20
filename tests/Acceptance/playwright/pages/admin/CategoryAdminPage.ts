import { Page, Locator, expect } from '@playwright/test';
import { BaseAdminPage } from './BaseAdminPage';

export interface NewCategoryInput {
    /** The category title (oxtitle in default lang). */
    title: string;
    /**
     * Parent category — either an OXID or the literal string `'oxrootid'`
     * for a top-level category. The admin's parent <select> is keyed on
     * OXID; passing a title is intentionally unsupported because the
     * dropdown's option labels include " - " indentation prefixes.
     */
    parentId?: string;
    /** Activate the category. Defaults to `true`. */
    active?: boolean;
}

/**
 * POM for `cl=category_main` — the admin category-edit form. Used by
 * issue #141 specs to seed test categories before exercising the
 * storefront menu, and to read back the OXID so cleanup can target the
 * exact row.
 *
 * The framed admin home rendered by the `adminPage` fixture exposes a
 * fresh `stoken` on every nav link — we read it from the navigation
 * frame on first use and reuse it for the lifetime of this POM.
 */
export class CategoryAdminPage extends BaseAdminPage {
    private cachedStoken: string | null = null;

    constructor(page: Page) {
        super(page);
    }

    private async stoken(): Promise<string> {
        if (this.cachedStoken) return this.cachedStoken;
        const navFrame = this.page
            .frameLocator('frame[name="navigation"]')
            .frameLocator('frame[name="adminnav"]');
        const href = await navFrame.locator('a[href*="stoken="]').first().getAttribute('href');
        const m = href?.match(/stoken=([A-Za-z0-9]+)/);
        if (!m) {
            throw new Error('CategoryAdminPage: could not extract stoken from admin nav.');
        }
        this.cachedStoken = m[1] ?? '';
        return this.cachedStoken;
    }

    /**
     * Navigate to the empty-form variant of `cl=category_main` so we can
     * fill it and submit a new category. Bypasses the framed admin tree —
     * `category_main` renders the form at the top level when invoked
     * with `oxid=-1`, so the test interacts with regular DOM (no frame).
     */
    async gotoNewForm(): Promise<void> {
        const stoken = await this.stoken();
        await this.page.goto(`/admin/index.php?cl=category_main&oxid=-1&stoken=${stoken}`, {
            waitUntil: 'domcontentloaded',
        });
        await expect(this.page.locator('form#myedit')).toHaveCount(1);
    }

    /**
     * Submit a new category using the admin form. Returns the OXID of the
     * created row.
     *
     * Why use the GUI rather than a raw INSERT? Because it goes through
     * `Category::save()` and exercises the same validation, OXIDs, SEO
     * URL generation, and oxleft/oxright nested-set bookkeeping a real
     * admin user would. Issue #141 is a presentation bug; using the same
     * code path catches regressions if the admin save side ever drifts.
     */
    async createCategory(input: NewCategoryInput): Promise<string> {
        await this.gotoNewForm();

        const form = this.page.locator('form#myedit');
        await form.locator('input[name="editval[oxcategories__oxtitle]"]').fill(input.title);

        if (input.parentId !== undefined) {
            await form
                .locator('select[name="editval[oxcategories__oxparentid]"]')
                .selectOption(input.parentId);
        }

        // OXID's category-create form ships with the OXACTIVE checkbox
        // **unchecked** (verified empirically — saving without touching
        // it lands an `oxactive=0` row that the storefront then hides).
        // Set the `checked` property directly in the DOM rather than
        // `.check()` — the latter clicks the box, which fires inline
        // onChange handlers in the OXID admin that nudge the form into
        // a state where save() returns oxid=-1.
        const activate = input.active !== false;
        await form
            .locator('input[type="checkbox"][name="editval[oxcategories__oxactive]"]')
            .evaluate((el, on) => {
                (el as HTMLInputElement).checked = on;
            }, activate);

        // OXID admin uses a non-standard JS submit path. It assigns
        // `document.myedit.fnc.value = "save"` and then calls submit().
        // Mimic that here so we don't depend on a particular button label
        // (which is i18n-dependent).
        await Promise.all([
            this.page.waitForLoadState('domcontentloaded'),
            this.page.evaluate(() => {
                const f = document.querySelector<HTMLFormElement>('form#myedit');
                if (!f) throw new Error('form#myedit not found at submit time');
                const fnc = f.querySelector<HTMLInputElement>('input[name="fnc"]');
                if (!fnc) throw new Error('hidden fnc input not found');
                fnc.value = 'save';
                f.submit();
            }),
        ]);

        // OXID re-renders the same form post-save; the URL still says
        // `oxid=-1` but the form's hidden inputs now carry the assigned
        // OXID (UtilsObject::generateUID() output). Read it from
        // editval[oxcategories__oxid] which is more specific than the
        // top-level `oxid` hidden (which the controller updates last).
        const newOxid = await this.page
            .locator('form#myedit input[name="editval[oxcategories__oxid]"]')
            .first()
            .inputValue();
        if (!newOxid || newOxid === '-1') {
            throw new Error(
                `CategoryAdminPage.createCategory: save did not assign an OXID ` +
                    `(url=${this.page.url()}, hidden oxid='${newOxid}').`,
            );
        }
        return newOxid;
    }

    // ─── Discovery helpers ────────────────────────────────────────────────────

    /** Visit the populated parent <select> and return its options. */
    async listParentOptions(): Promise<Array<{ value: string; text: string }>> {
        await this.gotoNewForm();
        return await this.page
            .locator('select[name="editval[oxcategories__oxparentid]"]')
            .evaluate((sel) =>
                [...(sel as HTMLSelectElement).options].map((o) => ({
                    value: o.value,
                    text: o.text.trim(),
                })),
            );
    }
}
