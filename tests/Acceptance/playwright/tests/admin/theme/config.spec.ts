import { test, expect } from '../../../fixtures';
import { AdminThemeConfigPage } from '../../../pages/admin/ThemeConfigPage';

/**
 * Issue #186 — after PR #10 removed the theme's color entries without
 * replacing them, the "Farben & Appearance" (colors) section disappeared
 * from the admin theme settings screen. The color settings were restored in
 * o3-theme/theme.php.
 *
 * This guards the regression: the theme_config screen must render the colors
 * section (its `confstrs[s*Color]` fields) with a translated group heading.
 */

// The str-type settings of the o3-theme `colors` group (o3-theme/theme.php).
const COLOR_FIELDS = ['sPrimaryColor', 'sSecondaryColor', 'sAccentColor', 'sFooterColor', 'sBackgroundColor'];

test.describe('admin theme settings — colors/"Farben & Appearance" section (cl=theme_config, #186)', () => {
  test('P0 theme config renders the colors section fields', async ({ adminPage }) => {
    const theme = new AdminThemeConfigPage(adminPage);
    await theme.goto();

    await expect(theme.form, 'theme_config form must render').toHaveCount(1);

    for (const varname of COLOR_FIELDS) {
      await expect(theme.colorField(varname), `color setting ${varname} must render`).toHaveCount(1);
    }
  });

  test('P0 colors group has a translated heading, not the raw SHOP_THEME_GROUP_colors key', async ({
    adminPage,
  }) => {
    const theme = new AdminThemeConfigPage(adminPage);
    await theme.goto();

    const heading = theme.colorsGroupHeading;
    await expect(heading).toBeVisible();

    const text = (await heading.innerText()).trim();
    expect(text.length, 'group heading must have visible text').toBeGreaterThan(0);
    expect(text, `heading must be translated, got "${text}"`).not.toContain('SHOP_THEME_GROUP');
    // DE admin: "Farben & Appearance"; EN admin: "Colors".
    expect(text, `unexpected colors group heading: "${text}"`).toMatch(/Farben|Colors|Appearance/i);
  });
});
