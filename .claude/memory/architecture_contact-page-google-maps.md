---
name: Contact page Google Map uses per-theme sGoogleMapsAddr, not shop address
description: The contact-page map renders a separate per-theme option with a placeholder default; it never derives from the shop's contact address
type: reference
---

## Behavior

The contact page (`tpl/page/info/contact.tpl` in both `wave` and `o3-theme`)
embeds the Google Map from a dedicated theme option `sGoogleMapsAddr`, read via
`$oViewConf->getViewThemeParam('sGoogleMapsAddr')`. It does **not** use the
shop's contact address (`oxshops__oxstreet/oxzip/oxcity/oxcountry`).

- The option is stored **per theme** (config namespace `theme:wave` vs
  `theme:o3-theme`) — setting it in one theme does NOT carry over to the other.
- The factory default (in each theme's `theme.php` + `setup.sql`) is a fictional
  placeholder: `O3-Shop, Musterstraße 17, 12345 Musterstadt`. Google geocodes
  that to an arbitrary location → map looks "wrong" out of the box.
- The templates and option declarations are **identical** between wave and
  o3-theme, so any "works in one theme, not the other" report is a
  config-state difference, not a code difference.

Admin location of the setting: Extensions → Themes → <theme> → Settings →
group "Contact" → "Google Maps Address".

Captured confirming community report
https://community.o3-shop.com/t/version-1-6-1-problem/187 →
filed as o3-shop/o3-shop#196 (2026-06-17). Proposed fix: ship empty default
and/or fall back to shop contact address when blank.
