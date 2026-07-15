---
name: o3-theme data-js conventions and template verification loop
description: How o3-theme wires template buttons to JS (data-js hooks, build/js bundle), and how to live-test a theme tpl change against the running shop
type: reference
---

## data-js hook convention (o3-Theme repo)

Interactive storefront elements are wired by `data-js="<hook>"` attributes, bound by
plain scripts in `build/js/*.js`. All of them are imported by `build/js/main.bundle.js`
and bundled by gulp/esbuild into `out/o3-theme/src/js/main.js` (+ `.min.js`), loaded
via `[{oxscript include="js/main.min.js"}]` in `tpl/layout/base.tpl`.

Consequences:

- **A `data-js` value only works if a script actually binds that exact selector.**
  Issue #214's root cause: the minibasket remove button had
  `data-js="remove-from-minibasket"` — no script binds that; the handler is
  `build/js/remove-from-basket.js` binding `[data-js='remove-from-basket']`.
- Handlers bind with `querySelectorAll` at script load, NOT delegated. This is safe
  because the theme has no AJAX re-renders: minibasket & co. are server-rendered via
  `oxid_include_dynamic` and every basket action is a full-page form POST.
- `remove-from-basket.js` finds its hidden `[remove]` input via
  `button.previousElementSibling` — the input MUST directly precede the button.
- Remove flow shop-side: named `removeBtn` submit + `aproducts[...][remove]=1` →
  `BasketComponent::changeBasket()` sets that item's amount to 0
  (`source/Application/Component/BasketComponent.php` around line 424).
- Template-only changes need **no gulp rebuild**; JS/SCSS changes must be rebuilt and
  the generated `out/` files are committed to the theme repo.

## Live-testing a theme tpl change

Two clones exist locally:
- `/Users/nick/o3/o3-theme` — standalone clone of o3-shop/o3-Theme (branch work)
- `/Users/nick/o3/shop-ce/source/Application/views/o3-theme/` — the installed theme;
  it is ITSELF a git checkout (`.git` present), gitignored by shop-ce

Loop: edit in the standalone clone → copy the file into the installed dir (`cp` is
aliased to `cp -i` in this shell; use `tee < src > dst` or `command cp`) → clear the
Smarty compile cache in the container:
`docker exec o3shop-shop-ce-shop-1 sh -c 'rm -rf /var/www/html/source/tmp/smarty/*'`
→ verify in the browser at localhost:8080. Alternatively branch directly inside the
installed checkout ([[Storefront themes live in separate repos (gitignored locally)]]).

Docker note: if another project's container squats a compose port (e.g. standalone
`mailpit` on 8025), `docker compose up` aborts mid-way and leaves `db` in "Created" —
the shop container then dies on "Database not ready" timeout. Report to Nick, don't
self-fix (see feedback_docker_environment).
