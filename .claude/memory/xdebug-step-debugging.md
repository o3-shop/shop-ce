---
name: xdebug-step-debugging
description: how the ./docker.sh xdebug toggle works + the Colima host-gateway gotcha that blocked #82
type: reference
---

# Xdebug step debugging (#82)

Step debugging is **opt-in** via `./docker.sh xdebug on|off|status`. Off by default =
coverage-only (`xdebug.mode=coverage`), so the test suite is unaffected.

## How the toggle works
- `docker-compose.yml` (shop service) sets `PHP_INI_SCAN_DIR=":/var/www/html/docker/xdebug"`.
  Leading `:` = scan the compiled-in default conf.d **plus** that bind-mounted dir.
- `xdebug on` copies the committed template `docker/xdebug/zz-xdebug-debug.ini.dist` to the
  active (gitignored) `docker/xdebug/zz-xdebug-debug.ini`, then `apache2ctl graceful`.
- PHP only auto-loads files ending in `.ini`, so the `.dist` template is inert until copied.
  State persists across `./docker.sh start` for free (PHP scans the host file natively).
- `status` is a pure host-file check (no docker). `on`/`off` need the container running.

## The gotcha that broke every prior attempt (#82)
On **Colima / plain Linux Docker**, `host.docker.internal` does NOT resolve inside the
container by default (it's automatic only on Docker Desktop). Xdebug initiates the
connection *to* the IDE, so without resolution it can never reach it. Fix: the shop service
has `extra_hosts: ["host.docker.internal:host-gateway"]`. Verified: resolves to the gateway
(e.g. 192.168.5.2). This single missing entry is why hand-config attempts failed.

## On-config: trigger mode + NO develop (do not revert)
Active template `docker/xdebug/zz-xdebug-debug.ini.dist` uses `xdebug.mode=debug,coverage`
and `start_with_request=trigger`. Two hard-won decisions (originally `develop,debug,coverage`
+ `yes`, both caused real pain — see #82 session):
- **No `develop`**: on PHP 8.2 the old Doctrine DBAL 2.x + Smarty stack emits ~33k
  `Deprecated` notices PER request; `develop` streams every one to the IDE as an error
  notification → floods the debug session AND makes PhpStorm pop bogus "path mapping
  misconfiguration" warnings for the `vendor/` files those notices reference.
- **`trigger`, not `yes`**: `yes` opens a debug session on EVERY page request and EVERY CLI
  command (oe-console, composer, even `php -r`). The IDE grabs+pauses them → apache worker
  pool fills with stuck requests → whole site hangs; CLI commands freeze. `trigger` only
  activates when a request carries `XDEBUG_TRIGGER` (browser "Xdebug Helper" ext,
  `?XDEBUG_TRIGGER=1`, or `-e XDEBUG_TRIGGER=1` for CLI).

PhpStorm path mapping: local `source/` → `/var/www/html/source` (DocumentRoot is `source/`).

## Apply-time costs (easy to forget)
- Changing compose `environment`/`extra_hosts` → needs a container **recreate** (`up -d`).
- Changing the baked `xdebug.mode` in the Dockerfile → needs a **rebuild**.
- Toggling the ini → `apache2ctl graceful` (the toggle does this). A stuck paused session
  needs a **hard** `apache2ctl -k restart` to clear workers; graceful won't free them.

Dev guide: `docs/development/xdebug-step-debugging.md` (PhpStorm + VS Code). Port 9003,
path mapping repo-root ↔ `/var/www/html`.
