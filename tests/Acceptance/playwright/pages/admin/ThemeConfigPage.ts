import { Page, Locator } from '@playwright/test';
import { BaseAdminPage } from './BaseAdminPage';

/**
 * POM for the admin theme settings form (cl=theme_config).
 *
 * Like the other admin config screens, navigating the top-level page straight
 * to the controller URL renders the `form#myedit` markup directly (the OXID
 * frameset only wraps the admin "home" view), so selectors are top-level.
 *
 * theme_config.tpl renders one `.groupExp` block per setting group; the group
 * heading is `a.rc` with `oxmultilang SHOP_THEME_GROUP_<group>`, and each
 * setting is an input named `confstrs[<varname>]` / `confbools[<varname>]`.
 * The `colors` group (o3-theme/theme.php) is the "Farben & Appearance"
 * section that #186 reported missing.
 */
export class AdminThemeConfigPage extends BaseAdminPage {
  constructor(page: Page) {
    super(page);
  }

  async goto(): Promise<void> {
    const stoken = await this.extractStoken();
    await this.page.goto(
      `/admin/index.php?cl=theme_config&oxid=o3-theme&stoken=${stoken}`,
      { waitUntil: 'domcontentloaded' },
    );
  }

  get form(): Locator {
    return this.page.locator('#myedit');
  }

  /** A `str`-type theme setting input, e.g. colorField('sPrimaryColor'). */
  colorField(varname: string): Locator {
    return this.page.locator(`#myedit input[name="confstrs[${varname}]"]`);
  }

  /** The `.groupExp` heading of the group that contains the primary-color field. */
  get colorsGroupHeading(): Locator {
    return this.page
      .locator('#myedit .groupExp')
      .filter({ has: this.page.locator('input[name="confstrs[sPrimaryColor]"]') })
      .locator('a.rc')
      .first();
  }
}
