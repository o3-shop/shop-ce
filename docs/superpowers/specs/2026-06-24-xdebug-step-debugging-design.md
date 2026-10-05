# Xdebug Step Debugging — Design (#82)

**Issue:** [#82](https://github.com/o3-shop/shop-ce/issues/82) — *setup xdebug in shop-app container for step debugging with IDE*
**Date:** 2026-06-24
**Branch:** `82-xdebug-step-debugging` (off `b-1.6`)

## Problem

Xdebug is already installed in the `shop` container (`docker/Dockerfile:44-46`) with
`xdebug.mode=coverage,debug`, but **no step-debugging connection settings exist**:
no `client_host`, no `client_port`, no request-activation config. Xdebug initiates the
connection *to* the IDE, so without these it can never reach an IDE running on the host —
which is why every attempt in the issue failed.

Two concrete gaps:

1. **No connection target.** Xdebug doesn't know where the IDE is.
2. **`host.docker.internal` does not resolve on Colima / plain Linux Docker.** There is no
   `extra_hosts` / `host-gateway` entry in `docker-compose.yml`. On Docker Desktop for Mac
   this name resolves automatically; on the project's Colima setup it does not.

## Goals

- Step debugging from PhpStorm **and** VS Code against the `shop` container.
- **Zero performance cost by default** — normal contributors are unaffected.
- Explicit, ergonomic opt-in: a `./docker.sh xdebug on|off|status` toggle.
- Toggle state **persists across `./docker.sh start`** without bespoke re-apply logic.
- Coverage (`xdebug.mode=coverage`) keeps working for the test suite at all times.

## Non-goals

- Profiling / tracing configuration (out of scope; `mode` only carries `coverage` by
  default and `develop,debug,coverage` when toggled on).
- Trigger-based (browser-extension) activation. Decided: when **on**, connect on **every**
  request (`start_with_request=yes`). When **off**, no debug at all.
- Acceptance/Playwright debugging.

## Decisions (from brainstorming)

| Question | Decision |
|---|---|
| Activation model | **Toggle script** — off by default, explicit `./docker.sh xdebug on` |
| On-mode behaviour | **Connect every request** (`start_with_request=yes`) |
| IDE docs | **PhpStorm + VS Code** |
| Persistence mechanism | **`PHP_INI_SCAN_DIR` + host-side ini file** (no marker file) |

## Architecture

The toggle controls a single PHP ini drop-in **on the host**, inside a directory that is
already bind-mounted into the container (`./../:/var/www/html`). PHP is told to scan that
directory via `PHP_INI_SCAN_DIR`, so the file is loaded natively on every container boot —
persistence is free, and `start_containers()` needs no extra logic.

```
host: docker/xdebug/zz-xdebug-debug.ini   ──bind mount──▶  /var/www/html/docker/xdebug/zz-xdebug-debug.ini
                                                                         │
                          PHP_INI_SCAN_DIR=":/var/www/html/docker/xdebug"│  (default conf.d + this dir)
                                                                         ▼
                                            php (apache mod_php + `exec shop php` CLI) loads it
```

- File **present** → step debugging on. File **absent** → coverage-only default from the image.
- `apache2ctl graceful` re-reads PHP ini for the web SAPI; the CLI re-reads on next invocation.

## Components

### 1. `docker/docker-compose.yml` — `shop` service
Add (merging into the existing `environment` map):
```yaml
extra_hosts:
  - "host.docker.internal:host-gateway"
environment:
  # existing PATH, TZ ...
  PHP_INI_SCAN_DIR: ":/var/www/html/docker/xdebug"
```
The leading `:` keeps the compiled-in default scan dir (`/usr/local/etc/php/conf.d`) **and**
adds ours. Both changes require a one-time container recreate (next `./docker.sh start`).

### 2. `docker/Dockerfile:46`
Change the baked default from:
```
xdebug.mode=coverage,debug
```
to:
```
xdebug.mode=coverage
```
Off-state is then strictly coverage-only (no step-debug machinery), regardless of any
`start_with_request` default. Needs a `./docker.sh rebuild` to take effect, but the toggle's
drop-in overrides `xdebug.mode` regardless, so `xdebug on` works even before a rebuild.

### 3. `docker/xdebug/zz-xdebug-debug.ini.dist` (committed template)
PHP only loads files ending in `.ini`, so the `.dist` template is inert until copied to the
active `.ini` name by the toggle.
```ini
xdebug.mode=develop,debug,coverage
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
```
- `develop` adds nicer var dumps / stack traces while debugging.
- `coverage` retained so coverage still works if a run happens while on.
- `xdebug.log` makes failed connections diagnosable (the #1 support issue for step debugging).

### 4. `docker.sh` — new `xdebug` subcommand
`xdebug on|off|status`, dispatched from the `case "$1"` block; help text updated.
- **on**: copy `docker/xdebug/zz-xdebug-debug.ini.dist` → `docker/xdebug/zz-xdebug-debug.ini`
  (host file via the bind mount), then `$DOCKER_COMPOSE exec shop apache2ctl graceful`.
  Print confirmation + reminder to start the IDE listener on port 9003.
- **off**: remove `docker/xdebug/zz-xdebug-debug.ini`, then graceful reload.
- **status**: report on/off purely from host-file presence (no `exec` needed).
- Reuse the existing container-running guard pattern (as in `run_tests` / `run_php_cs_fixer`).
  `on`/`off` need the container up (for the reload); `status` does not.

### 5. `.gitignore`
Ignore the active drop-in; keep the template tracked:
```
docker/xdebug/zz-xdebug-debug.ini
```

### 6. `docs/development/xdebug-step-debugging.md`
Developer guide:
- Toggle commands (`./docker.sh xdebug on|off|status`).
- **PhpStorm**: Settings → PHP → Servers — name (any), host `localhost`, port `8080`,
  "Use path mappings", map repo root → `/var/www/html`; debug port `9003`;
  Run → Start Listening for PHP Debug Connections; set a breakpoint; load a page.
- **VS Code**: install *PHP Debug* extension; `launch.json` "Listen for Xdebug" with
  `"port": 9003` and `"pathMappings": { "/var/www/html": "${workspaceFolder}" }`.
- **Troubleshooting**: read `/tmp/xdebug.log` inside the container
  (`./docker.sh ... exec shop cat /tmp/xdebug.log`); note the `host.docker.internal` /
  `host-gateway` requirement; remind that the IDE listener must be running first.

## Data flow

`./docker.sh xdebug on`
→ writes host ini → `apache2ctl graceful` re-reads it
→ PHP (web + CLI) now connects to `host.docker.internal:9003` on every request
→ IDE (listening) breaks at breakpoints.

`./docker.sh xdebug off`
→ deletes the ini → graceful reload → coverage-only default restored.

## Error handling

- `xdebug on/off` when the container is down → clear error + abort (mirrors existing guards).
- `.dist` template missing → error in `on` rather than silently producing no-op.
- Failed `host.docker.internal` resolution → surfaced via `/tmp/xdebug.log` connection errors,
  documented in the troubleshooting section. The `extra_hosts` entry is the fix.

## Testing & verification

`docker.sh` is bash, not under PHPUnit. Verification is observable:

1. `./docker.sh xdebug status` reflects on/off correctly.
2. After `on`: `exec shop php -i | grep "xdebug.mode"` shows `develop,debug,coverage`;
   `/tmp/xdebug.log` records a connection attempt to `host.docker.internal:9003`.
3. A real breakpoint hit from PhpStorm (and VS Code) against a shop page.
4. `./docker.sh test` still passes with xdebug **off** (coverage intact, no regression).
5. After `off` + a page load: no connection attempts; `xdebug.mode` is `coverage`.

## Risks

- **Recreate required**: the compose env/`extra_hosts` changes only apply after the next
  `start`/recreate. Documented.
- **`start_with_request=yes` while on** means CLI tools (incl. the test suite) also try to
  connect every invocation. Acceptable: the toggle defaults off, and the guide notes to turn
  it off before running the full suite.
- **`apache2ctl graceful` re-reads ini**: relied upon for instant pickup; if an environment
  doesn't honour it, a `./docker.sh stop && start` is the documented fallback.
