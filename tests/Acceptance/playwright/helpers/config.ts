import { execFile } from 'node:child_process';
import path from 'node:path';
import { promisify } from 'node:util';
import type { DbClient } from '../fixtures/db';

const execFileP = promisify(execFile);

/**
 * How the shop-side helpers (cache clears + category model saves) reach the
 * shop's PHP and files:
 *   - 'docker' (default): run the command INSIDE the shop container — the local
 *     docker-compose stack, matching its bind-mounted `/var/www/html` layout.
 *   - 'native': run the command directly on the host/runner — used in CI, where
 *     the shop code + PHP live on the same filesystem and there is no container.
 * Set `E2E_SHOP_EXEC=native` in CI.
 */
const SHOP_EXEC_MODE = (process.env.E2E_SHOP_EXEC ?? 'docker') as 'docker' | 'native';

/** Container name for the running shop (docker mode only). */
const SHOP_CONTAINER = process.env.SHOP_CONTAINER ?? 'o3shop-app';

/**
 * Shop root as seen by whoever runs the command: the container path in docker
 * mode, the checkout path on the runner in native mode. Override via SHOP_ROOT.
 */
const SHOP_ROOT =
  // `||` not `??`: fall back when SHOP_ROOT is unset OR blank — an empty
  // string would otherwise resolve every shop path against '' silently.
  process.env.SHOP_ROOT ||
  (SHOP_EXEC_MODE === 'native'
    ? path.resolve(__dirname, '..', '..', '..', '..') // …/shop-ce (repo root)
    : '/var/www/html');

/**
 * Run a command against the shop — inside the container (docker) or directly on
 * the runner (native). Path arguments must be absolute under SHOP_ROOT so they
 * resolve the same way in both modes.
 */
function runInShop(cmd: string, args: string[]): Promise<{ stdout: string; stderr: string }> {
  if (SHOP_EXEC_MODE === 'native') {
    return execFileP(cmd, args, { cwd: SHOP_ROOT });
  }
  return execFileP('docker', ['exec', SHOP_CONTAINER, cmd, ...args]);
}

/**
 * Clear the revocation anti-spam file cache. The default
 * `NoopAntiSpamService` writes per-IP rate-limit counters to OXID's tmp
 * cache (`oxc_o3rev_antispam_{f,s}_<hash>.txt`). One success locks the
 * IP out for 5 minutes; one set of failures hits the threshold within a
 * minute. Both make a multi-test browser suite unrunnable. Tests call
 * this helper in `beforeEach` to start each scenario with a clean slate.
 *
 * Delete via `runInShop` (docker exec / native rm), NOT host `fs.rm`: the
 * anti-spam files are written by the shop's PHP process (root inside the
 * container), and on a Docker-Desktop bind mount a host-side deletion is not
 * reliably visible to the container before the next request. A stale
 * post-success lockout then survives `beforeEach` and throttles every
 * following form submit — the storefront redirects the POST to a bare
 * `index.php` and the whole revocation suite fails after the first success.
 * Removing the files where the shop runs guarantees the next request sees an
 * empty rate-limit state. Same rationale as `clearShopRuntimeCache`.
 */
export async function clearRevocationAntiSpamCache(): Promise<void> {
  // sh -c so the glob expands where the command runs (container in docker mode,
  // runner in native mode).
  await runInShop('sh', [
    '-c',
    `rm -f ${SHOP_ROOT}/source/tmp/oxc_o3rev_antispam_*`,
  ]).catch(() => undefined); // tmp absent or shop down — ignore
}

/**
 * Wipe OXID's data caches under `source/tmp/` — `oxc_*` files only.
 *
 * Tests that mutate persistent state the storefront then renders
 * against — the category tree above all (`oxc_oxcategories_*` plus the
 * SEO caches) — must call this after the mutation, otherwise the
 * storefront serves stale HTML.
 *
 * What we DELIBERATELY DO NOT touch:
 *   - `container_cache.php`  the compiled Symfony DI container. Wiping
 *                            this leaves the shop unable to bootstrap;
 *                            a fresh page request returns blank-white
 *                            until the container is rebuilt.
 *   - `smarty/`              Smarty compiled templates. Wiping forces a
 *                            full recompile on the next request which
 *                            adds 5–10 s — enough to blow past the test
 *                            navigation timeout. Templates compile from
 *                            `.tpl` files which the test never touches,
 *                            so the cache here can never be stale.
 *
 * Why `docker exec` instead of host `fs.rm`?
 *   The bind mount between Docker-Desktop on macOS and the container
 *   has a sync window where host writes (here: deletions) are not yet
 *   visible to PHP processes running in the container. Removing files
 *   *inside* the container guarantees the next storefront request sees
 *   the empty cache.
 */
export async function clearShopRuntimeCache(): Promise<void> {
  // sh -c so the glob expands where the command runs (container in docker mode,
  // runner in native mode).
  await runInShop('sh', ['-c', `rm -f ${SHOP_ROOT}/source/tmp/oxc_*`]).catch(
    () => undefined,
  ); // tmp absent or container down — ignore
}

/**
 * Delete one or more categories using OXID's `Category::delete()` so
 * the oxleft / oxright nested-set ranges in the surrounding tree are
 * kept consistent. A plain `DELETE FROM oxcategories` (as `db.deleteCategory`
 * does) is fine for stand-alone rows but corrupts the parent's right-edge
 * once a subtree is removed — symptom: `Category::getSubCatList()` returns
 * empty for an apparently-populated parent on the next test run.
 *
 * Implementation: shells out to `php` inside the shop container and runs
 * the `category-cleanup.php` helper. Idempotent — unknown OXIDs are
 * reported but don't fail the call.
 *
 * Returns the helper's stdout for diagnostics; non-zero exit codes throw.
 */
export async function deleteCategoriesViaModel(oxids: string[]): Promise<string> {
  if (oxids.length === 0) return '';
  const scriptPath = `${SHOP_ROOT}/tests/Acceptance/playwright/helpers/category-cleanup.php`;
  const { stdout } = await runInShop('php', [scriptPath, ...oxids]);
  return stdout;
}

/**
 * Create a single category through `Category::save()` and return its OXID.
 *
 * Used by the issue #141 frontend specs to seed Unter-Einhörner /
 * Sub-Category / Sub-Sub-Category at test start so the suite is
 * self-contained — i.e. survives a `docker.sh stop / remove volume /
 * start` cycle that wipes any manually-added demo data.
 *
 * Why the model save, not a raw INSERT? It updates the parent's
 * oxleft/oxright nested-set range, generates a real 32-char OXID, and
 * runs the same SEO/cache hooks an admin form save would. The frontend
 * megamenu won't show direct DB inserts because the parent's OXRIGHT
 * stays at the pre-insert value.
 */
export async function seedCategoryViaModel(
  title: string,
  parentOxid: string,
): Promise<string> {
  const scriptPath = `${SHOP_ROOT}/tests/Acceptance/playwright/helpers/category-seed.php`;
  const { stdout } = await runInShop('php', [scriptPath, title, parentOxid]);
  const oxid = stdout.trim();
  if (!oxid) {
    throw new Error(
      `seedCategoryViaModel: empty OXID for title='${title}' parent='${parentOxid}'`,
    );
  }
  return oxid;
}

// oxconfig.OXSHOPID is INT — owning shop ID. Single-shop CE uses 1.
const OXSHOPID = 1;

export type OxConfigType = 'bool' | 'str' | 'int' | 'arr' | 'aarr';

export interface OxConfigEntry {
  name: string;
  type: OxConfigType;
  value: string;
}

/**
 * Read a single oxconfig entry. As of migration `Version20230322213324`
 * (March 2023), OXVARVALUE is plain TEXT — no DECODE() needed.
 */
export async function readConfigVar(db: DbClient, name: string): Promise<string | null> {
  const rows = await db.query<{ oxvarvalue: string }>(
    `SELECT oxvarvalue FROM oxconfig WHERE oxshopid = ? AND oxvarname = ? LIMIT 1`,
    [OXSHOPID, name],
  );
  return rows[0]?.oxvarvalue ?? null;
}

/**
 * Set the four revocation oxconfig keys atomically — used by tests that
 * need a known starting state. Pass undefined to leave a key untouched.
 */
export interface RevocationConfigInput {
  blShowRevocationForm?: boolean;
  blRevocationRequireLogin?: boolean;
  blRevocationNotifyOperator?: boolean;
  sRevocationOperatorEmail?: string;
}

export async function writeRevocationConfig(
  db: DbClient,
  input: RevocationConfigInput,
): Promise<void> {
  const entries: OxConfigEntry[] = [];
  if (input.blShowRevocationForm !== undefined) {
    entries.push({ name: 'blShowRevocationForm', type: 'bool', value: input.blShowRevocationForm ? '1' : '0' });
  }
  if (input.blRevocationRequireLogin !== undefined) {
    entries.push({ name: 'blRevocationRequireLogin', type: 'bool', value: input.blRevocationRequireLogin ? '1' : '0' });
  }
  if (input.blRevocationNotifyOperator !== undefined) {
    entries.push({ name: 'blRevocationNotifyOperator', type: 'bool', value: input.blRevocationNotifyOperator ? '1' : '0' });
  }
  if (input.sRevocationOperatorEmail !== undefined) {
    entries.push({ name: 'sRevocationOperatorEmail', type: 'str', value: input.sRevocationOperatorEmail });
  }

  for (const entry of entries) {
    await db.query(
      `DELETE FROM oxconfig WHERE oxshopid = ? AND oxvarname = ?`,
      [OXSHOPID, entry.name],
    );
    // oxconfig.OXID is CHAR(32); UUID() returns 36 chars w/ hyphens. Strip
    // them — matches what UtilsObject::generateUID() produces. OXVARVALUE
    // is plain TEXT post-2023 migration (Version20230322213324).
    await db.query(
      `INSERT INTO oxconfig (oxid, oxshopid, oxvarname, oxvartype, oxvarvalue)
       VALUES (REPLACE(UUID(), '-', ''), ?, ?, ?, ?)`,
      [OXSHOPID, entry.name, entry.type, entry.value],
    );
  }
}
