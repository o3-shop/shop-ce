---
name: ci-self-hosted-runner-8.2-coverage
description: Self-hosted runner for the PHP 8.2 coverage leg — the environment gauntlet to make it work, the worker/flakiness ceiling, and where the leg's time actually goes
type: project
---

# Self-hosted runner for the 8.2 coverage leg (b-2.0 CI)

`.github/workflows/code-quality.yml` routes ONLY the PHP 8.2 coverage leg to a
self-hosted runner, and only for trusted, non-fork pushes:

```yaml
runs-on: >-
  ${{ (matrix.php-version == '8.2'
       && github.event.pull_request.head.repo.fork != true
       && contains(fromJSON('["nlo-tronet"]'), github.actor))
      && 'self-hosted' || 'ubuntu-latest' }}
```

7.4/8.0/8.1 stay on hosted (no coverage). Repo is PUBLIC, so the fork check +
actor allowlist are the guardrails; also turn on "require approval for outside
collaborators" in repo settings.

## The environment gauntlet (a fresh box needs ALL of these, in order)
A minimal self-hosted box fails these one by one — hosted runners hide them
because their image ships everything:
1. **Docker** must be installed (the `services: mariadb` container needs it).
2. **Passwordless sudo** for the runner user (`setup-php` installs PHP via apt).
3. **mysql client** (`default-mysql-client`) — the workflow + testing-library shell
   out to `mysql`/`mysqldump` on the HOST, not just in the container.
4. **`~/.my.cnf` with `skip-ssl`** for the runner user — Debian's MariaDB client
   requires SSL but the container doesn't offer it. Covers the workflow's own
   `mysql`/`mysqladmin` calls.
5. The testing-library's `mysql`/`mysqldump` use `--defaults-file`, which IGNORES
   `~/.my.cnf` → the library's original `--skip-ssl` must be KEPT on self-hosted
   (the patch step strips it only on github-hosted, where the MySQL-8 client
   rejects it). Vendor cache key includes `runner.environment` so a patched copy
   from one env can't poison the other.
6. **`$GITHUB_WORKSPACE` paths**: `.env.ci` hardcoded `/home/runner/work/...`
   (hosted path) for `O3SHOP_CONF_SHOPDIR`/`COMPILEDIR`; rewrite to
   `$GITHUB_WORKSPACE` in the config-copy step or the shop install writes to a
   nonexistent dir.
7. **Full PHP extension set** in `setup-php` (bcmath, gd, soap, intl, …) — hosted
   PHP ships them; a minimal self-hosted PHP errors (bcmod/imagecreatetruecolor/
   SoapFault) mid-suite. Mirror `docker/Dockerfile`.
8. **Wave theme staleness**: wave is gitignored/downloaded; the install step used
   to skip if the dir existed, so a persistent box reused a stale theme. Always
   `rm -rf` + re-download (a stale wave `order_cust.tpl` calling `getArticle()`
   caused NOPRODUCTID — but that specific bug was actually the wave #219 PR,
   reverted upstream; see [[architecture_theme-repos]]).

## Speed: what worked, and the ceiling
Started at ~16 min (broken installs) → **~2:49 leg, 114s ParaTest step, 90.7% cov**.
The wins that mattered (in `options`/paratest command):
- **MariaDB data dir on tmpfs** (`--tmpfs /var/lib/mysql:rw,size=4g`) — biggest win;
  the per-worker installs + restore cycles were disk-bound, not CPU-bound.
- **CLI opcache on every worker** (`--passthru-php` + main php: `opcache.enable_cli=1
  validate_timestamps=0 max_accelerated_files=130000 jit_buffer_size=0`).
- **Tighter service healthcheck** (interval 3s + start-period) — container init 33s→10s.
- Runtime `SET GLOBAL innodb_flush_log_at_trx_commit=0; max_connections=512;`
  (service containers can't take server flags, so tune dynamically after start;
  `innodb_buffer_pool_size` resize is a runtime no-op — don't bother).

**Worker count plateaus**: `-p 12→16→20` only moved ParaTest 122→116→114s (workers
wait on the single MariaDB, CPU stays ~40-65%). `-p 24` is pointless. Higher `-p`
also surfaces intermittent **order-coupling flakes** (`BasketTest::testForBugEntry2163`,
`PictureHandlerTest::testGetProductPicUrl` — now `@group parallel-unsafe`). So there's
a speed↔reliability tension above ~p12-16. Locked at `-p 20`.

## Where the 169s leg actually goes (for future tuning)
- ~28s pre-overhead: container init ~10s, cache restore ~10s, deps/checkout/php ~8s.
- **114s ParaTest** (67%) — dominated by the ~20 per-worker shop installs, NOT test
  execution (tests are ~2 min).
- ~15s "Stop containers" (GitHub service teardown — not tunable).

**~2:49 is near the practical floor for this architecture — profiling proved it.**
The 95s parallel run INCLUDES the 20 per-worker DB populations (installs). The
tempting fix (install once → clone the DB into each worker) does NOT help: cloning
20 DBs hits the same single MariaDB, so it's the same DB-bound cost as 20 installs.
Skipping the parent-process install saves little. So don't chase <2:30 with
install/clone tweaks — the real lever is rearchitecting per-worker isolation itself
(e.g. ONE shared DB with per-test transaction rollback instead of N physical DBs +
restore), which is a large, risky change. Not worth it vs the 2:49-under-3min result.
See [[paratest-parallel-runner]].
