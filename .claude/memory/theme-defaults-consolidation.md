# Theme defaults: theme.php vs SQL drift (#122 phase 3)

`Theme::activate()` (and `oe:theme:activate`) writes the `theme:<id>` `oxconfig` + `oxconfigdisplay` rows **only for settings declared in that theme's `theme.php` `settings` array** (via `SettingsHandler::addModuleSettings`, which writes BOTH tables — `oxconfig` value + `oxconfigdisplay` group/constraint/pos).

Historically the same theme config was ALSO hand-maintained as SQL in three places: `shop-ce/source/Setup/Sql/initial_data.sql`, `shop-demodata-ce/src/demodata.sql`, and the legacy per-theme `setup.sql`. These drift from `theme.php`.

**The trap (hit in #122 phase 3):** before deleting the SQL blocks and declaring "theme.php is the single source of truth", you MUST verify theme.php is a complete superset of the SQL. It wasn't — o3-theme's `theme.php` was missing **8 admin-exposed settings** (`bl_showWishlist`, `bl_showCompareList`, `bl_showListmania`, `bl_showGiftWrapping`, `bl_showPriceAlarm`, `bl_articleAmountMax`, `blFooterShowLinks`, `blFooterShowNews`) that the SQL seeded and `theme_options.php` has labels for. Deleting the SQL without first adding them to theme.php would silently drop those settings (revert to code defaults, vanish from admin).

**How to check before removing theme SQL:**
1. Extract `theme:<id>` `oxconfig` varnames from the SQL block (the value rows).
2. Get theme.php setting names authoritatively: `php -r '$aTheme=[];include "…/theme.php"; echo implode("\n",array_column($aTheme["settings"],"name"));'`
3. Diff. Anything in SQL-not-in-theme.php must be ADDED to theme.php first (derive type/group/constraint/default from the SQL oxconfig row + the oxconfigdisplay row). Anything in theme.php-not-in-SQL is fine (newer settings).
4. Decisive proof activation is lossless: on a live shop, `DELETE FROM oxconfig WHERE oxmodule='theme:<id>'; DELETE FROM oxconfigdisplay WHERE oxcfgmodule='theme:<id>';` then `php bin/oe-console oe:theme:activate` and recount — must equal `count($aTheme['settings'])` for both tables.

**Note:** `theme.php` lives in the external theme repos (`o3-shop/o3-Theme`, `o3-shop/wave-theme`) — gitignored in shop-ce (see [[architecture_theme-repos]]). So completing theme.php is a theme-repo change, separate from the shop-ce/demodata SQL removal. wave's theme.php was already complete (its only SQL delta was the defunct `sGooglePlusUrl`, correctly dropped).

**`setup.sql`** (`source/Application/views/<theme>/setup.sql`) is legacy/deprecated: a hand-authored mirror operators once imported manually (`mysql … < setup.sql`) or via admin upload. NO code path loads it (o3-theme README: "legacy; Theme::activate() handles this on O3-Shop 1.6+"; wave CHANGELOG: "deprecated"). Safe to delete as cleanup.

See [[console-commands-and-php-floor]] for the oe:theme:* commands.
