# Twig support for O3-Shop v2 — design

Branch: `b-2.0-twig-support` (stacked on `b-2.0-update-symmfony`, PR o3-shop/shop-ce#276; merge after #276).

## Intent and why

O3-Shop v2 modernises the stack: the Symfony 3.4 → 5.4 upgrade (#276) and, together with it,
**Twig as the default template language for the storefront**. New shops start on Twig; the
O3-Theme for v2 is a Twig theme. Shops updating from 1.x keep their Smarty themes, modules and
customisations working unchanged and move to Twig only when they decide to (typically when they
rewrite their theme).

Twig is wanted for O3 itself. Compatibility with OXID 7 themes/modules is **not** a goal; where
OXID's conventions are reused it is because they already fit the codebase O3 forked from.

## Goals

1. The core ships a Twig engine (Twig 3) next to the existing Smarty engine.
2. The **active storefront theme decides the engine**: a theme declares `templateEngine` `twig` or
   `smarty`; a theme that declares nothing is Smarty (every existing theme keeps working).
3. Storefront pages, widgets and the **e-mails** (they live in the theme) render with the active
   theme's engine.
4. All shop template functions/filters the Smarty themes use are available in Twig
   (translation, prices, dates, SEO URLs, widgets, includes, scripts/styles, etc.).
5. Modules can extend a Twig theme's templates the Twig way and ship their own Twig templates.
6. When a Twig theme is active (or about to be activated) and an active module has storefront
   Smarty blocks/templates but no Twig counterparts, the admin shows a clear **warning** naming
   those modules. Nothing disappears silently.
7. O3-Theme 2.x (Twig) is the default theme of a fresh v2 install.

## Non-goals (v2)

- **Admin** stays Smarty (all 267 admin templates, module admin templates).
- **CMS content in the database** keeps Smarty syntax and is still parsed by Smarty:
  `UtilsView::parseThroughSmarty()` callers (content pages/snippets, category/article/news/action
  long descriptions, newsletters, price-alarm mail body, `oxifcontent`). No per-content engine
  switch, no content migration.
- No bridge that renders Smarty module blocks inside a Twig theme.
- No removal of Smarty from the core; Smarty stays a first-class engine in v2.
- No OXID 7 compatibility layer.

## Anti-goals (failure even if it "works")

- An updated 1.x shop renders differently, loses module output, or switches theme/engine without
  the shop owner doing it — with the one accepted exception that O3-Theme 2.x replaces O3-Theme
  1.x under the same package and id (decision D1): a shop that wants to stay on Smarty must pin
  `o3-shop/o3-theme` to `^1` before updating, and this must be impossible to miss (upgrade notes +
  admin warning, see 1.6).
- A module's storefront output vanishes in a Twig theme without any warning.
- Twig being enabled breaks Smarty rendering anywhere (admin, CMS content, Smarty themes).
- Licence violations when adopting OXID code (see Constraints).

## Constraints

- PHP floor **7.4** (composer `config.platform.php` = 7.4.33); CI matrix 7.4–8.2. Twig 3.x
  supports 7.4 up to Twig 3.11 → pin `twig/twig` to a 7.4-compatible range (Composer resolves it
  via the platform pin).
- Symfony 5.4 DI container; services are private by default (see `.claude/memory/
  symfony5-private-services-container-get.md`), module services are made public by
  `ContainerBuilder::makeProjectServicesPublic()`.
- Coding conventions: PSR-12 (`./docker.sh cs-fixer`), Doctrine DBAL QueryBuilder, logging rules
  in `CLAUDE.md`, graceful degradation for user-facing flows (`.claude/memory/
  feedback_graceful-degradation.md`).
- **Licence:** OXID `twig-component` 1.x and 2.x and `twig-theme` 1.x are GPL-3.0-only (LICENSE
  file in the repos; verified for twig-component v1.1.0). O3 shop-ce is GPL-3.0-only, so the code
  may be adopted. Obligations: keep OXID's copyright notice in every adopted file and add O3's
  (same pattern as `source/Application/views/o3-theme/theme.php`), mark files as modified (header
  note + CHANGELOG entry), keep GPL-3.0, never take code from the proprietary 8.x line, don't use
  the OXID brand in O3 product names.
- The engine abstraction already exists and must be used, not bypassed:
  `Internal/Framework/Templating/TemplateEngineInterface`, `TemplateRendererInterface`,
  `TemplateRendererBridgeInterface`, the resolver/locator/loader services, and the engine-neutral
  helpers in `Internal/Transition/Adapter/TemplateLogic/*` (25 classes; `InsertNewBasketItemLogicTwig`
  already exists).

## Approaches considered

1. **Fork OXID `twig-component` 1.x into the core** — *chosen.* It implements
   `TemplateEngineInterface` for exactly this core (OXID 6.2–6.5 lineage), maps all shop
   functions/filters onto the existing `TemplateLogic` helpers, has escapers, loaders, token
   parsers and 55 test files. Cost: adopting and maintaining foreign code, moving it from Twig 2.5
   to Twig 3.
2. Build our own Twig integration on the abstraction — full control, but re-implements ~30
   functions/filters and the module extension mechanism that already exist and are tested. More
   effort and risk for the same result.
3. Fork into a separate package `o3-shop/twig-component` required by default — same code as 1 with
   extra repo/release overhead; only worthwhile if Twig had to be optional or versioned separately,
   which it doesn't (it is part of v2).

**Important gap in the chosen source:** twig-component **1.x has no module template extension
mechanism** (its `FilesystemLoader` only knows theme + shop paths). Module extension arrived in
twig-component **2.x** (OXID 7) as the *template chain* (`src/Resolver/TemplateChain/*`,
`ModulesTemplateDirectoryResolver`, `ExtendsExtension`). 2.x is also GPL-3.0 but requires PHP 8 and
OXID 7's module structure. So: engine, extensions, escapers, nodes/token parsers come from **1.x**;
the template-chain concept is **ported down from 2.x** to PHP 7.4 and O3's module structure.

## Decisions taken

- **D1 — O3-Theme 2.x keeps package and id.** `o3-shop/o3-theme` 2.x (theme id `o3-theme`) *is*
  the Twig theme and replaces 1.x; v2 requires it by default. Shops that stay on Smarty pin
  `o3-shop/o3-theme:^1` in their project `composer.json` before updating to v2. (Alternative of a
  new package/id side by side was offered and not chosen.)
- **D2 — Twig autoescaping is ON** (`html` strategy) for storefront templates and e-mails.
  Values that legitimately contain HTML are marked explicitly (`|raw` or a dedicated filter):
  CMS content and long descriptions rendered through `parseThroughSmarty`, translations that
  contain HTML, and helper output that is already HTML (e.g. SEO links built as markup, widget
  output, `include_dynamic`). Plain-text e-mails render without HTML escaping (`autoescape false`
  block or a separate strategy for `tpl/email/plain`).
- **Builder's calls already made (check while reading):**
  - *Order:* plan and build part 1 (core) first. The Twig path is tested end to end with a
    minimal **fixture Twig theme** under `tests/`; the core may merge into `b-2.0` before O3-Theme
    2.x exists (`b-2.0` is unreleased). Switching the fresh-install default (1.5) lands together
    with O3-Theme 2.x.
  - *Module page templates:* a controller keeps naming its template as today
    (`$_sThisTemplate = 'mymodule_page.tpl'`, metadata `templates` mapping). Under a Twig theme
    the name resolver drops `.tpl` (as `LegacyTemplateNameResolver` already does) and the loader
    looks for `<same mapped path>.html.twig` next to the mapped `.tpl` file; module-own templates
    are additionally addressable as `@<moduleId>/…`. Existing module controllers need no change,
    only the added Twig file. The warning service (1.4) counts a module as Twig-capable for a
    template when that sibling `.html.twig` exists.
  - *Engine choice is per render call, not per request:* the engine follows the **admin mode at
    the moment of rendering**. `Core/Email` switches admin mode off while rendering e-mails, so
    e-mails sent from admin controllers (`OrderMain`, `OrderOverview` → `sendSendedNowMail`,
    `sendDownloadLinksMail`) render with the storefront theme's engine. Admin pages and admin
    exports (`GenericExportDo`) render in admin mode → Smarty. The resolver therefore must not cache
    one engine per request. `Email::getRenderer()`'s `setEngine($this->_getSmarty())` applies only
    when the storefront engine is Smarty.

## Design — part 1: core (shop-ce)

### 1.1 Adopt the Twig component

- Copy twig-component v1.1.0 `src/` into the core under
  `source/Internal/Framework/Twig/` (namespace `OxidEsales\EshopCommunity\Internal\Framework\Twig\`),
  with its `services.yaml` merged into the core's service tree (imported from
  `source/Internal/services.yaml` like the Smarty services). Tests go to
  `tests/Unit|Integration/Internal/Framework/Twig/`.
- Upgrade to Twig 3: `Twig_*` aliases → namespaced classes, `Twig\Extension\*` signatures,
  `TokenParser`/`Node` API changes (`Node::compile`, `Compiler`), loader API
  (`getSourceContext`, `exists`, `findTemplate` signatures), removed `spaceless`/`filter` tags.
- Add `twig/twig` (7.4-compatible 3.x range) to `composer.json`, refresh `composer.lock`.
- Licence headers/CHANGELOG as in Constraints.

### 1.2 Engine selection by active theme

- Theme metadata gets an optional key `'templateEngine' => 'twig' | 'smarty'` in `theme.php`.
  Missing → `smarty`. A child theme without the key inherits the parent theme's value.
- Replace the single compile-time binding of `TemplateEngineInterface` (today: `LegacySmartyEngine`
  in `Internal/Framework/Smarty/services.yaml`) with a **runtime decision**: a small
  "storefront engine resolver" that returns the Twig or Smarty engine based on the active theme of
  the current request/shop. Both engines are registered as separate services.
- **Admin requests always get Smarty**, regardless of the storefront theme.
- `TemplateNameResolver` appends the engine's default extension (`tpl` vs `html.twig`); with two
  engines it must use the resolved engine, not a fixed one.
- `TemplateRendererBridgeInterface` (used by `Core/Email`) must return the renderer for the
  storefront theme's engine, so e-mails render in Twig for a Twig theme. The Smarty bridge's
  `setEngine($smarty)` path stays for Smarty themes and for legacy callers.
- `UtilsView::parseThroughSmarty()` and all its callers stay Smarty (CMS content non-goal). In a
  Twig theme, CMS content rendered via Smarty is passed into Twig templates as already-rendered
  HTML (it is today a string in view data; templates must output it unescaped where Smarty did).
- Direct Smarty users must keep working: `Core/Email::_getSmarty()` (deprecated),
  `ShopAdapter::getSmarty()`, `UtilsView::getSmarty()`, `Output`, `ShopControl`. Each is checked:
  storefront rendering paths go through the resolver; paths that are inherently Smarty (CMS
  parsing, admin) keep using Smarty.

### 1.3 Module templates for Twig (template chain)

- Port twig-component 2.x's template chain to PHP 7.4 and O3:
  - a module ships Twig templates under its directory; module *own* templates are addressable as
    `@<moduleId>/<path>.html.twig`;
  - a module *extends* a theme template by providing a file at a conventional path mirroring the
    theme template (follow OXID 7's convention `views/twig/extensions/themes/<themeId>/<template>`
    plus a `default` fallback for any theme), using `{% extends %}` + `{% block %}`;
  - only **active** modules for the current shop contribute; order follows module activation order
    unless a sorting configuration says otherwise (port 2.x `TemplateChainSorter` + validator).
- Module metadata needs no new mandatory key; discovery is by directory convention. (Builder may
  add an optional metadata key if 2.x relies on one.)
- Smarty `blocks` (`oxtplblocks`) remain for Smarty themes only.

### 1.4 Missing-Twig-templates warning

- A service determines, for a given theme engine, which **active** modules have storefront
  Smarty output (metadata `blocks` entries or storefront `templates` with `.tpl`) but **no** Twig
  counterparts (no Twig extension files / own Twig templates).
- Shown in the admin: on theme activation (Extensions → Themes) when activating a Twig theme, and
  as a persistent notice in the admin home/system health while a Twig theme is active and such
  modules exist. Message names the modules. Activation is **not** blocked (warn, don't prevent).
- Logged once per change as `WARNING` following the logging rules.

### 1.5 Setup / default theme

- A fresh v2 install activates O3-Theme 2.x (Twig) — same id `o3-theme` as today (D1), so
  `initial_data.sql` keeps `sTheme = 'o3-theme'`; what changes is the required theme version.
- The core never changes the active theme on update. Whether an updated shop gets the Twig
  O3-Theme is decided by the `o3-shop/o3-theme` version Composer installs (D1).
- The builder must find where `o3-shop/o3-theme` is required (it is not in shop-ce's
  `composer.json`; it is installed to the gitignored `source/Application/views/o3-theme` and
  `source/out/o3-theme`, e.g. via the project/metapackage or `docker/entrypoint.sh`) and require
  `^2` there for v2.

### 1.6 Update safety for D1

- **Upgrade notes** (release notes / docs) for v2 state prominently: to keep a Smarty storefront,
  pin `o3-shop/o3-theme:^1` *before* updating; otherwise O3-Theme becomes the Twig version.
- **Engine mismatch warning:** a child theme whose own `templateEngine` (explicit, or `smarty` by
  default) differs from its parent's engine cannot work (e.g. a customer child theme of `o3-theme`
  written in Smarty after `o3-theme` became Twig). The admin shows a warning naming the child and
  parent theme (theme list, admin home/system health), logged as `WARNING`. A child theme without
  its own templates inherits the parent's engine and is fine.

## Design — part 2: O3-Theme 2.x (theme repository)

- Port the storefront templates of O3-Theme (220 `.tpl` files incl. `tpl/email/html|plain`) to
  `.html.twig`, keeping the visual design, markup and Bootstrap 5 assets of O3-Theme 1.x.
  OXID `twig-theme` 1.x (GPL-3.0) may be used as a reference for idioms; O3-Theme stays the visual
  source of truth.
- `theme.php` declares `'templateEngine' => 'twig'`, version 2.0.0, same id `o3-theme` and same
  package `o3-shop/o3-theme` (D1).
- Autoescaping is on (D2): every place that outputs HTML (CMS content, long descriptions, HTML
  translations, helper markup) is marked explicitly; plain-text mails are not HTML-escaped.
- Blocks: keep the Smarty block names as Twig block names wherever possible so module authors can
  map their Smarty blocks 1:1.
- O3-Theme 1.x (Smarty) and Wave remain available for updated shops.

## What is left to the builder

- Exact file layout and class names inside `Internal/Framework/Twig/`.
- The Twig 3 migration details and which twig-component 1.x tests need adapting.
- How the engine resolver is wired (decorating service vs. factory) as long as the admin is
  always Smarty and the storefront follows the theme.
- UI wording/placement details of the warning within the stated places.
- The porting order of theme templates.

## Testing

- Unit/integration tests for: engine resolution (Twig theme, Smarty theme, missing key, child
  theme inheritance, admin request), every adopted Twig extension (adapted OXID tests), template
  chain (no module, one module, several modules, inactive module ignored, ordering), the warning
  service.
- E2E (Playwright, existing suite): storefront smoke + checkout + order e-mail on the Twig theme
  (fixture theme while O3-Theme 2.x does not exist yet); the existing suite keeps passing on the
  Smarty theme.
- Autoescaping: a test that a value with `<script>` from the view is escaped, and that CMS
  content/long description HTML is rendered as HTML.
- Engine per render call: an e-mail sent from an admin controller renders with the Twig engine
  when the storefront theme is Twig; an admin page in the same request renders with Smarty.
- Engine mismatch warning: Smarty child theme on a Twig parent is reported.
- Manual: update a 1.x shop with a Smarty theme and Smarty modules → unchanged; activate the Twig
  theme → warning lists the Smarty-only modules.
