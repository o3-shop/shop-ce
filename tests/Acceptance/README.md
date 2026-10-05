# Browser / Acceptance Tests

This directory hosts O3-Shop's browser-driven acceptance tests. Two suites
live side by side, in different states of maturity.

| Subdir / file                          | Suite                       | Maturity                |
|----------------------------------------|-----------------------------|-------------------------|
| `Admin/`, `Frontend/`, `Javascript/`   | Codeception + Selenium      | Stable, in CI           |
| `*TestCase.php`, `*.php` test files    | Codeception base / helpers  | Stable, in CI           |
| `playwright/`                          | Playwright + TypeScript     | **Experimental** (see below) |

## Codeception / Selenium suite (stable)

The PHP files here drive the shop through a real Selenium-controlled
browser. They extend `AcceptanceTestCase` (or one of its specialisations:
`AdminTestCase`, `FrontendTestCase`, `FlowThemeTestCase`,
`JavascriptTestCase`) which come from `o3-shop/testing-library`.

Coverage:
- `Admin/` — admin panel: navigation, product/category/user CRUD, SEO,
  AJAX, basket workflows, query-logger.
- `Frontend/` — storefront: navigation, basket, product info, contact
  form, CSRF, post-update validation, shop-setup.
- `Javascript/` — storefront-side JS sanity.

These run in CI today via the standard test runners.

## Playwright suite (experimental — `playwright/`)

A net-new browser-test infrastructure built on **Playwright + TypeScript**,
introduced alongside the Codeception suite with the long-term intent of
**replacing** it. See `playwright/README.md` for layout, conventions, and
how to run.

Today's coverage:
- Smoke spec (storefront, admin auth, Mailpit, MySQL reachability).
- §356a BGB electronic revocation feature: storefront submission form,
  admin config form, admin detail view.
- Issue #116 regression: `oxiddebitnote` payment-method toggle in the
  o3-theme checkout payment page.

> ## ⚠️ Experimental — not yet in CI/CD
>
> The Playwright suite is **explicitly experimental**. It is not wired into
> any CI/CD pipeline yet, and merging or releasing must not depend on its
> green status.
>
> It will be promoted into regular CI/CD only **once stable, verified,
> probed and approved** — that means:
>
> 1. **Stable** across multiple consecutive full-suite runs on developer
>    machines and a fresh shop install.
> 2. **Verified** against representative deployments (German + English
>    storefront, demo-data shop).
> 3. **Probed** for flake — repeated runs with `--repeat-each=N`, headed +
>    headless, with and without warm caches.
> 4. **Approved** by the maintainers (issue / PR review sign-off).
>
> Until then: run it locally, treat failures as data, and **do not** gate
> releases or merges on it.

## Running

```bash
# Codeception / Selenium (stable)
./docker.sh test       # via PHPUnit harness
# or via Codeception/testing-library entrypoints — see o3-shop/testing-library

# Playwright (experimental)
./docker.sh playwright                        # whole suite
./docker.sh playwright tests/admin/revocation # one feature
./docker.sh playwright -g "submits"           # by test-name regex
```

The Playwright runner auto-installs Node deps + Chromium on first run.

## Adding tests

- **Bug fixes / regressions on existing features** that the Codeception
  suite already covers → add a Codeception test next to the existing
  ones (Stable suite).
- **New features and anything net-new** → write the Playwright spec.
  When the Playwright suite reaches the bar above, the existing
  Codeception tests will be migrated and this README updated.
