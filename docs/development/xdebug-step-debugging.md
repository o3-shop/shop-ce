# Step Debugging with Xdebug

Xdebug is installed in the `shop` container. Step debugging is **off by default**
(coverage-only, zero overhead) and toggled on per developer when needed.

## Toggle

```bash
./docker.sh xdebug on       # enable step debugging (graceful apache reload)
./docker.sh xdebug off      # back to coverage-only
./docker.sh xdebug status   # show current state
```

The state is a file under `docker/xdebug/` and survives `./docker.sh start`.

### Triggering a debug session

`on` uses **trigger mode** (`start_with_request=trigger`): being "on" does NOT make
every request debug. A request only breaks into the debugger when it carries an
`XDEBUG_TRIGGER`. This is deliberate — `start_with_request=yes` would make every page
request *and* every CLI command (`oe-console`, `composer`, even `php -r`) open a session
your IDE can grab and freeze, which is unusable against this codebase.

- **Browser:** install the **Xdebug Helper** extension (Chrome/Firefox) and set it to
  *Debug*, or append `?XDEBUG_TRIGGER=1` to the URL.
- **CLI:** prefix the command with the env var:
  ```bash
  docker compose -f docker/docker-compose.yml exec -e XDEBUG_TRIGGER=1 shop \
    php bin/oe-console <command>
  ```

> First-time setup only: the `host.docker.internal` resolution and ini scan dir
> are applied by `docker/docker-compose.yml`. If you set this repo up before that
> change, run `./docker.sh start` once to recreate the `shop` container.

## PhpStorm

1. **Settings → PHP → Servers** → add a server (PhpStorm may auto-create one on the
   first incoming connection):
   - Name: `localhost` (any name)
   - Host: `localhost`, Port: `8080`
   - Debugger: Xdebug
   - **Use path mappings** → map your local **`source/`** directory to
     **`/var/www/html/source`** (Apache's DocumentRoot is `source/`, not the repo root).
     PhpStorm usually fills this in for you. Mapping the repo root → `/var/www/html`
     also works.
2. **Settings → PHP → Debug** → confirm Debug port `9003`.
3. **Run → Start Listening for PHP Debug Connections** (the phone icon turns green).
4. `./docker.sh xdebug on`, set a breakpoint, then load a **triggered** page (Xdebug
   Helper set to *Debug*, or `http://localhost:8080/?XDEBUG_TRIGGER=1`). See
   [Triggering a debug session](#triggering-a-debug-session).

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
4. `./docker.sh xdebug on`, set a breakpoint, then load a **triggered** page (Xdebug
   Helper set to *Debug*, or `?XDEBUG_TRIGGER=1`).

## CLI debugging

Prefix the command with `XDEBUG_TRIGGER=1` (trigger mode means a bare command will
*not* start a debug session — that's what keeps `oe-console`/`composer` from hanging):

```bash
docker compose -f docker/docker-compose.yml exec -e XDEBUG_TRIGGER=1 shop \
  php bin/oe-console <command>
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
- **Path mapping wrong?** Breakpoints stay hollow / never bind — re-check that local
  `source/` maps to `/var/www/html/source` (or repo root → `/var/www/html`).
- **PhpStorm keeps warning about path mappings for `vendor/...` files?** That was the
  old `develop` mode forwarding the codebase's thousands of PHP 8.2 deprecation notices
  (from `vendor/`) to the IDE. The current config uses `xdebug.mode=debug,coverage`
  (no `develop`), which stops the flood. Re-run `./docker.sh xdebug off && ./docker.sh xdebug on`
  if you toggled it on before this change.
- **Debugger stops on the first line of every request (no breakpoint set)?** That's the
  IDE's "break at first line" option, not a misconfiguration — it confirms the connection
  works. Turn it off in PhpStorm under **Run → Break at first line in PHP scripts**, or in
  VS Code by removing `"stopOnEntry": true` from the launch config.
