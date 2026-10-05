# Xdebug Step Debugging Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an opt-in `./docker.sh xdebug on|off|status` toggle that enables PhpStorm/VS Code step debugging against the `shop` container, with zero cost when off.

**Architecture:** A host-side PHP ini drop-in (`docker/xdebug/zz-xdebug-debug.ini`) lives in the already-bind-mounted repo tree; `PHP_INI_SCAN_DIR` makes PHP load it natively, so toggle state persists across restarts with no extra start logic. `extra_hosts: host-gateway` makes `host.docker.internal` resolve on Colima. The toggle copies/removes the file and `apache2ctl graceful`-reloads.

**Tech Stack:** Docker Compose, PHP 8.2 + Xdebug 3 (apache mod_php), bash (`docker.sh`).

**Spec:** `docs/superpowers/specs/2026-06-24-xdebug-step-debugging-design.md`
**Branch:** `82-xdebug-step-debugging`

---

## File Structure

- `docker/docker-compose.yml` — add `extra_hosts` + `PHP_INI_SCAN_DIR` to the `shop` service.
- `docker/Dockerfile` — baked default `xdebug.mode=coverage` (drop `debug`).
- `docker/xdebug/zz-xdebug-debug.ini.dist` — committed template (the "on" config). **New.**
- `.gitignore` — ignore the active `docker/xdebug/zz-xdebug-debug.ini`.
- `docker.sh` — `toggle_xdebug()` function + `xdebug)` case + help text.
- `docs/development/xdebug-step-debugging.md` — PhpStorm + VS Code guide. **New.**

---

## Task 1: Create the xdebug "on" config template

**Files:**
- Create: `docker/xdebug/zz-xdebug-debug.ini.dist`

- [ ] **Step 1: Write the template file**

Create `docker/xdebug/zz-xdebug-debug.ini.dist` with exactly:

```ini
; Active step-debugging config. Toggled on/off via `./docker.sh xdebug on|off`.
; This .dist template is copied to zz-xdebug-debug.ini (loaded by PHP via
; PHP_INI_SCAN_DIR). PHP only loads files ending in .ini, so this template is inert.
xdebug.mode=develop,debug,coverage
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
```

- [ ] **Step 2: Verify PHP would ignore the template name**

Run: `ls docker/xdebug/`
Expected: `zz-xdebug-debug.ini.dist` present. (Name ends in `.dist`, not `.ini`, so PHP's scan-dir loader skips it.)

- [ ] **Step 3: Commit**

```bash
git add docker/xdebug/zz-xdebug-debug.ini.dist
git commit -m "feat(#82): add xdebug step-debugging ini template

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01WVUDMsh8WVBbWY9T15fQd2"
```

---

## Task 2: Ignore the active drop-in, keep the template tracked

**Files:**
- Modify: `.gitignore`

- [ ] **Step 1: Append the ignore rule**

Add to `.gitignore` (under the existing docker entries, after `docker/mailpit/data`):

```
# Active xdebug step-debugging drop-in, toggled by ./docker.sh xdebug on|off.
# The .dist template stays tracked; the activated copy is local state.
docker/xdebug/zz-xdebug-debug.ini
```

- [ ] **Step 2: Verify the rule works**

```bash
cp docker/xdebug/zz-xdebug-debug.ini.dist docker/xdebug/zz-xdebug-debug.ini
git status --porcelain docker/xdebug/
```
Expected: only `?? docker/xdebug/zz-xdebug-debug.ini.dist` would appear *if untracked*, but it's committed in Task 1, so output is **empty** — the active `.ini` is ignored and the `.dist` is tracked/clean. Then clean up:

```bash
rm docker/xdebug/zz-xdebug-debug.ini
```

- [ ] **Step 3: Commit**

```bash
git add .gitignore
git commit -m "chore(#82): gitignore the active xdebug drop-in

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01WVUDMsh8WVBbWY9T15fQd2"
```

---

## Task 3: Wire compose to load the drop-in and resolve the host

**Files:**
- Modify: `docker/docker-compose.yml` (the `shop` service)

- [ ] **Step 1: Add `extra_hosts` and `PHP_INI_SCAN_DIR` to the `shop` service**

In `docker/docker-compose.yml`, change the `shop` service from:

```yaml
  shop:
    build:
      dockerfile: Dockerfile
    volumes:
      - ./../:/var/www/html
    environment:
      PATH: /var/www/html/vendor/o3-shop/testing-library/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
      TZ: Europe/Berlin
    ports:
      - "${O3SHOP_PORT_HTTP:-8080}:80"
```

to:

```yaml
  shop:
    build:
      dockerfile: Dockerfile
    extra_hosts:
      # Makes host.docker.internal resolve to the host on Colima / plain Linux
      # Docker (auto on Docker Desktop). Required for Xdebug to reach the IDE.
      - "host.docker.internal:host-gateway"
    volumes:
      - ./../:/var/www/html
    environment:
      PATH: /var/www/html/vendor/o3-shop/testing-library/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
      TZ: Europe/Berlin
      # Scan the compiled-in default conf.d (leading ':') PLUS the bind-mounted
      # xdebug dir, so ./docker.sh xdebug on|off takes effect without a rebuild.
      PHP_INI_SCAN_DIR: ":/var/www/html/docker/xdebug"
    ports:
      - "${O3SHOP_PORT_HTTP:-8080}:80"
```

- [ ] **Step 2: Validate the compose file parses**

Run: `cd docker && docker compose config >/dev/null && echo OK; cd ..`
Expected: `OK` (no YAML/compose errors).

- [ ] **Step 3: Recreate the shop container so env + extra_hosts apply**

Run: `./docker.sh start`
Expected: containers come up; the `shop` container is recreated (compose detects the config change).

- [ ] **Step 4: Verify host resolution and scan dir inside the container**

```bash
docker compose -f docker/docker-compose.yml exec shop getent hosts host.docker.internal
docker compose -f docker/docker-compose.yml exec shop php -i | grep -i "scan this dir\|Scan Dir"
```
Expected: `host.docker.internal` resolves to a gateway IP; the additional ini scan dir includes `/var/www/html/docker/xdebug`.

- [ ] **Step 5: Commit**

```bash
git add docker/docker-compose.yml
git commit -m "feat(#82): make host.docker.internal resolve and scan xdebug ini dir

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01WVUDMsh8WVBbWY9T15fQd2"
```

---

## Task 4: Drop `debug` from the baked default mode

**Files:**
- Modify: `docker/Dockerfile:46`

- [ ] **Step 1: Change the baked xdebug mode**

In `docker/Dockerfile`, change line 46 from:

```dockerfile
    && echo "xdebug.mode=coverage,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```

to:

```dockerfile
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```

- [ ] **Step 2: Verify the edit**

Run: `grep -n "xdebug.mode" docker/Dockerfile`
Expected: one match showing `xdebug.mode=coverage` (no `,debug`).

- [ ] **Step 3: Commit** (rebuild is exercised in Task 7; the toggle overrides mode regardless)

```bash
git add docker/Dockerfile
git commit -m "feat(#82): default baked xdebug.mode to coverage-only

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01WVUDMsh8WVBbWY9T15fQd2"
```

---

## Task 5: Add the `xdebug` toggle to `docker.sh`

**Files:**
- Modify: `docker.sh` (add `toggle_xdebug()` function, `xdebug)` case, help text)

- [ ] **Step 1: Add the `toggle_xdebug` function**

In `docker.sh`, after the `run_quarantine_tests()` function (ends ~line 213) and before `run_npm_audits()`, insert:

```bash
toggle_xdebug() {
  GREEN='\033[0;32m'
  YELLOW='\033[1;33m'
  RED='\033[0;31m'
  NC='\033[0m'

  MY_DIR=$(getMyPath)
  local dist="$MY_DIR/docker/xdebug/zz-xdebug-debug.ini.dist"
  local active="$MY_DIR/docker/xdebug/zz-xdebug-debug.ini"

  # status needs no container and no docker — just report host-file presence.
  if [ "$1" = "status" ]; then
      if [ -f "$active" ]; then
          echo -e "${GREEN}xdebug step debugging: ON${NC}"
      else
          echo -e "${YELLOW}xdebug step debugging: OFF${NC} (coverage-only default)"
      fi
      return 0
  fi

  if [ "$1" != "on" ] && [ "$1" != "off" ]; then
      echo "Usage: $0 xdebug <on|off|status>"
      exit 1
  fi

  cd "$MY_DIR/docker" || { echo "Error: Docker directory not found"; exit 1; }
  check_docker_compose

  if ! $DOCKER_COMPOSE ps shop 2>/dev/null | grep -q "Up\|running"; then
      echo -e "${RED} ✗ shop container is NOT running – start it first with './docker.sh start'. ${NC}"
      exit 1
  fi

  if [ "$1" = "on" ]; then
      if [ ! -f "$dist" ]; then
          echo -e "${RED}Template not found: $dist${NC}"
          exit 1
      fi
      cp "$dist" "$active" || { echo -e "${RED}Failed to write $active${NC}"; exit 1; }
      $DOCKER_COMPOSE exec shop apache2ctl graceful
      echo -e "${GREEN}✓ xdebug step debugging ENABLED.${NC}"
      echo -e "${YELLOW}  → Start your IDE's debug listener on port 9003, then load a page.${NC}"
      echo -e "${YELLOW}  → Connection log: $DOCKER_COMPOSE exec shop cat /tmp/xdebug.log${NC}"
      echo -e "${YELLOW}  → Setup guide: docs/development/xdebug-step-debugging.md${NC}"
  else
      rm -f "$active"
      $DOCKER_COMPOSE exec shop apache2ctl graceful
      echo -e "${GREEN}✓ xdebug step debugging DISABLED (coverage-only).${NC}"
  fi

  cd "$MY_DIR"
}
```

- [ ] **Step 2: Add the `xdebug)` case to the dispatch block**

In `docker.sh`, in the `case "$1" in` block, add this case right after the `cs-fixer)` case (ends with its `;;`):

```bash
    xdebug)
        shift
        toggle_xdebug "$@" || exit 127
        ;;
```

- [ ] **Step 3: Add `xdebug` to the help text**

In the `*)` usage branch, after the `cs-fixer` description line:

```bash
        echo "  cs-fixer     Run php-cs-fixer on the entire codebase"
```

add:

```bash
        echo "  xdebug       Toggle step debugging: $0 xdebug <on|off|status>"
```

- [ ] **Step 4: Verify bash syntax and the new command surface**

```bash
bash -n docker.sh && echo "syntax OK"
./docker.sh 2>&1 | grep xdebug
```
Expected: `syntax OK`; help text shows the `xdebug` line.

- [ ] **Step 5: Verify `status` works with the container up (no .ini present)**

Run: `./docker.sh xdebug status`
Expected: `xdebug step debugging: OFF (coverage-only default)`.

- [ ] **Step 6: Commit**

```bash
git add docker.sh
git commit -m "feat(#82): add ./docker.sh xdebug on|off|status toggle

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01WVUDMsh8WVBbWY9T15fQd2"
```

---

## Task 6: Write the developer setup guide

**Files:**
- Create: `docs/development/xdebug-step-debugging.md`

- [ ] **Step 1: Write the guide**

Create `docs/development/xdebug-step-debugging.md` with:

````markdown
# Step Debugging with Xdebug

Xdebug is installed in the `shop` container. Step debugging is **off by default**
(coverage-only, zero overhead) and toggled on per developer when needed.

## Toggle

```bash
./docker.sh xdebug on       # enable step debugging (graceful apache reload)
./docker.sh xdebug off      # back to coverage-only
./docker.sh xdebug status   # show current state
```

`on` connects to your IDE on **every** request while enabled, so turn it **off**
before running the full test suite. The state is a file under `docker/xdebug/`
and survives `./docker.sh start`.

> First-time setup only: the `host.docker.internal` resolution and ini scan dir
> are applied by `docker/docker-compose.yml`. If you set this repo up before that
> change, run `./docker.sh start` once to recreate the `shop` container.

## PhpStorm

1. **Settings → PHP → Servers** → add a server:
   - Name: `o3shop` (any name)
   - Host: `localhost`, Port: `8080`
   - Debugger: Xdebug
   - **Use path mappings** → map your project root (this repo) to `/var/www/html`.
2. **Settings → PHP → Debug** → confirm Debug port `9003`.
3. **Run → Start Listening for PHP Debug Connections** (the phone icon turns green).
4. `./docker.sh xdebug on`, set a breakpoint, load a page at `http://localhost:8080`.

## VS Code

1. Install the **PHP Debug** extension (xdebug.php-debug).
2. Add to `.vscode/launch.json`:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "${workspaceFolder}"
      }
    }
  ]
}
```

3. Start the "Listen for Xdebug" configuration (F5).
4. `./docker.sh xdebug on`, set a breakpoint, load a page.

## CLI debugging

With xdebug on, `oe-console` and other CLI scripts also connect:

```bash
docker compose -f docker/docker-compose.yml exec shop php bin/oe-console <command>
```

## Troubleshooting

- **No breakpoint hit?** Make sure the IDE listener is running *before* you load the page.
- **Check the connection log inside the container:**

```bash
docker compose -f docker/docker-compose.yml exec shop cat /tmp/xdebug.log
```

  `Connected to debugging client` = success. `Could not connect ... 9003` means the
  IDE isn't listening or the port is blocked.
- **`host.docker.internal` not resolving?** Confirm the `extra_hosts: host-gateway`
  entry exists in `docker/docker-compose.yml` and recreate the container with
  `./docker.sh start`.
- **Path mapping wrong?** Breakpoints stay hollow / never bind — re-check that the
  project root maps to `/var/www/html`.
````

- [ ] **Step 2: Verify the file renders as valid markdown (no broken fences)**

Run: `grep -c '```' docs/development/xdebug-step-debugging.md`
Expected: an **even** number (all code fences closed).

- [ ] **Step 3: Commit**

```bash
git add docs/development/xdebug-step-debugging.md
git commit -m "docs(#82): add PhpStorm + VS Code xdebug setup guide

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01WVUDMsh8WVBbWY9T15fQd2"
```

---

## Task 7: End-to-end verification

**Files:** none (verification only)

- [ ] **Step 1: Rebuild so the baked `xdebug.mode=coverage` default lands**

Run: `./docker.sh rebuild`
Expected: image rebuilds, containers start.

- [ ] **Step 2: Confirm the OFF default is coverage-only**

```bash
./docker.sh xdebug status
docker compose -f docker/docker-compose.yml exec shop php -i | grep "xdebug.mode"
```
Expected: status `OFF`; `xdebug.mode => coverage => coverage`.

- [ ] **Step 3: Toggle ON and confirm the mode + connection config**

```bash
./docker.sh xdebug on
docker compose -f docker/docker-compose.yml exec shop php -i | grep -E "xdebug.mode|xdebug.client_host|xdebug.client_port|start_with_request"
```
Expected: `xdebug.mode => develop,debug,coverage`, `client_host => host.docker.internal`, `client_port => 9003`, `start_with_request => yes`.

- [ ] **Step 4: Confirm a connection attempt is logged**

```bash
curl -s -o /dev/null http://localhost:8080/
docker compose -f docker/docker-compose.yml exec shop cat /tmp/xdebug.log
```
Expected: a `[Step Debug]` line attempting to connect to `host.docker.internal:9003` (it will fail to connect with no IDE listening — that proves config is active and reaching the host).

- [ ] **Step 5: Real breakpoint check (manual, PhpStorm or VS Code)**

Start the IDE listener (per the guide), set a breakpoint in a controller (e.g. a
`render()` method), reload `http://localhost:8080/`. Expected: execution pauses at the
breakpoint. Repeat once for the other IDE if available.

- [ ] **Step 6: Toggle OFF and confirm coverage-only is restored**

```bash
./docker.sh xdebug off
docker compose -f docker/docker-compose.yml exec shop php -i | grep "xdebug.mode"
curl -s -o /dev/null http://localhost:8080/
```
Expected: `xdebug.mode => coverage => coverage`; no new connection attempts in `/tmp/xdebug.log` from this load.

- [ ] **Step 7: Confirm the test suite still passes with xdebug OFF**

Run: `./docker.sh test`
Expected: suite passes (coverage mode intact, no step-debug regression).

- [ ] **Step 8: Run the quality gate**

Run: `/finish` (cs-fixer + full tests + coverage), then update `.claude/memory/` with any lessons.

---

## Self-Review

**Spec coverage:**
- `extra_hosts` host-gateway → Task 3 ✓
- `PHP_INI_SCAN_DIR` → Task 3 ✓
- Baked `xdebug.mode=coverage` → Task 4 ✓
- `zz-xdebug-debug.ini.dist` template (mode/start_with_request/client_host/client_port/log) → Task 1 ✓
- `.gitignore` active drop-in → Task 2 ✓
- `./docker.sh xdebug on|off|status` (guard, graceful reload, host-file ops) → Task 5 ✓
- PhpStorm + VS Code + troubleshooting guide → Task 6 ✓
- Verification (status, mode, log, breakpoint, tests pass) → Task 7 ✓

No spec requirement left without a task.

**Placeholder scan:** No TBD/TODO/"handle edge cases"; every code/edit step shows exact content and exact commands with expected output.

**Type/name consistency:** Active file `docker/xdebug/zz-xdebug-debug.ini`, template `…​.ini.dist`, function `toggle_xdebug`, command `xdebug on|off|status`, scan dir `/var/www/html/docker/xdebug`, port `9003`, host `host.docker.internal` — all used identically across Tasks 1, 3, 5, 6, 7.
