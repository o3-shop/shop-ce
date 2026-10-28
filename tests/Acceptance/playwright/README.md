# O3-Shop Playwright Tests

End-to-end browser tests for O3-Shop CE using **Playwright + TypeScript**.

This is the new browser-test infrastructure being built in parallel to the
existing Codeception/Selenium suite at `tests/Acceptance/`. The Codeception
suite will be retired once Playwright coverage is sufficient.

## Quick start

```bash
cd tests/Acceptance/playwright
npm install
npm run install-browsers
npm test
```

You need the shop running first — `./docker.sh start` from the repo root.

## Directory layout

```
tests/Acceptance/playwright/
├── package.json              # Node deps (Playwright, mysql2, TS)
├── playwright.config.ts      # test runner config
├── tsconfig.json
├── globalSetup.ts            # pre-flight credential check (fail fast)
├── fixtures/                 # Playwright test fixtures
│   ├── index.ts              # composed `test` export — import this in specs
│   ├── auth.ts               # admin / customer auth contexts (per-test login)
│   ├── mailpit.ts            # Mailpit REST client (read + clear inbox)
│   └── db.ts                 # mysql2 client + revocation row helpers
├── helpers/                  # non-fixture utilities
│   ├── urls.ts               # canonical admin / storefront URLs
│   └── config.ts             # set/reset oxconfig values via DB
├── pages/                    # Page Object Models — mirror the surface
│   ├── admin/
│   │   ├── BaseAdminPage.ts
│   │   └── LoginPage.ts
│   └── storefront/
│       └── BaseStorefrontPage.ts
└── tests/                    # specs — directory mirrors `pages/`
    └── smoke.spec.ts         # pipeline-validation test
```

When new feature tests land:

- New POMs go in `pages/admin/<feature>/` or `pages/storefront/<feature>/`.
- Specs go in `tests/admin/<feature>/` or `tests/storefront/<feature>/`,
  one `*.spec.ts` per surface under that feature.
- New fixtures (e.g. for a feature-specific seed) go in `fixtures/`,
  composed into the `test` export in `fixtures/index.ts`.

## Running

| Command | What it does |
|---|---|
| `npm test` | Run all specs, headless |
| `npm run test:ui` | Open Playwright UI mode (great for picking single tests) |
| `npm run test:headed` | Run headless=false so you can watch the browser |
| `npm run test:debug` | Step through with the Playwright Inspector |
| `npm run report` | Open the last HTML report |
| `npm run codegen` | Generate test code by clicking around the shop |

Filter examples:

```bash
npm test -- tests/admin/revocation/        # only one folder
npm test -- -g "submits successfully"      # by test name
npm test -- --headed --workers=1           # interactive single-runner
```

## Environment variables

| Var | Default | Purpose |
|---|---|---|
| `SHOP_URL` | `http://localhost:8080` | Storefront base URL |
| `ADMIN_USER` | `admin@example.com` | Admin login (used by globalSetup) |
| `ADMIN_PASS` | `admin123` | Admin password |
| `MAILPIT_URL` | `http://localhost:8025` | Mailpit base URL for the REST API |
| `DB_HOST` | `127.0.0.1` | MySQL host |
| `DB_PORT` | `3306` | MySQL port (mapped from the docker mariadb service) |
| `DB_USER` | `root` | MySQL user |
| `DB_PASS` | `supersecret` | MySQL password (matches `docker/.env` `O3SHOP_CONF_DBROOT`) |
| `DB_NAME` | `o3shop` | OXID database name (matches `docker/.env` `O3SHOP_CONF_DBNAME`) |

## Conventions

- **Selectors live in Page Object classes** under `pages/` — never inline in
  specs. POM methods are imperative (`fillName()`, `clickResend()`) or
  return locators (`get nameInput()`).
- **Tests clean up after themselves**. Seed via `db` fixture, delete by
  OXID at the end. No global truncate.
- **Mailpit** is reset between tests (the `mailpit` fixture handles this).
- **Form-input-preservation invariant**: when a server-side validation
  rejects a submit, assert that the user's submitted values are still in
  the form on the re-render — never make the user re-type.
- **Graceful-degradation invariant**: storefront flows must not break on
  missing assets — assert fallback behaviour, not error pages.

## Adding a new test surface

1. Pick the surface (admin / storefront), pick the feature.
2. Create `pages/<surface>/<feature>/<Thing>Page.ts` extending the relevant
   base page object. Encapsulate every selector + interaction here.
3. Create `tests/<surface>/<feature>/<thing>.spec.ts` with `test.describe`
   blocks per behaviour.
4. If you need a new fixture (DB seeder, external service stub), add it
   under `fixtures/` and re-export the composed `test` from
   `fixtures/index.ts`.

## Troubleshooting

- **globalSetup fails with "admin login failed"**: the shop is up but the
  admin credentials don't work. Check `ADMIN_USER` / `ADMIN_PASS` and the
  shop install state.
- **globalSetup hangs**: shop isn't reachable at `SHOP_URL`. Run
  `./docker.sh start` and curl the URL manually.
- **`Cannot find module 'mysql2'`**: run `npm install`.
- **Browser binary missing**: run `npm run install-browsers`.
- **Why a fresh login on every admin test?** OXID admin sessions use a
  per-request stoken plus a `force_admin_sid` URL param emitted at login
  time; cookie storageState alone gets rejected by the next request. The
  existing Codeception suite logs in fresh per test for the same reason.
