---
name: o3-theme migration (in progress)
description: wave → o3-theme migration runs this week; storefront template work must be portable
type: project
---

A migration from the current storefront theme `wave` to a new theme `o3-theme` is underway and is planned to land **before 2026-05-01** (commitment captured during scoping of issue #99 / change `add-electronic-revocation` on 2026-04-26).

**Why:** o3-theme is the new default theme replacing wave. Any storefront feature work merged in the days leading up to that cutover will need to be carried over to o3-theme on the day of the migration; templates that bake in wave-specific assumptions multiply that work.

**How to apply:**
- New storefront templates added this week MUST be authored as theme-portable: route every user-facing string through the translation engine, do not embed wave-specific class names or DOM IDs in PHP/controller output, do not branch controller logic on `Registry::getConfig()->getConfigParam('sTheme')` or `getActiveTheme()`.
- When you place a template under `source/Application/views/wave/`, expect that an `o3-theme/` mirror is coming. Keep file names and Smarty include paths predictable so a `cp -r wave/.../ o3-theme/.../` style migration is mechanical.
- If a feature ships before 2026-05-01 and ONLY exists in `wave`, it is the implementing branch's responsibility to either (a) include the `o3-theme` mirror, or (b) leave a clear TODO note in the change/PR so the theme-migration commit can pick it up.
- After 2026-05-01, this memory should be revisited: either deleted (migration done) or updated with the new state. **Do not act on this memory blindly past that date — verify the current theme situation in `source/Application/views/` first.**
