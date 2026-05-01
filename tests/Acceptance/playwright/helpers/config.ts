import fs from 'node:fs/promises';
import path from 'node:path';
import type { DbClient } from '../fixtures/db';

/**
 * Path to the host-mounted OXID file cache (matches the docker-compose
 * bind mount). Override via OXID_TMP_DIR if testing against a
 * differently-arranged install.
 */
const OXID_TMP_DIR =
  process.env.OXID_TMP_DIR ?? path.join(__dirname, '..', '..', '..', '..', 'source', 'tmp');

/**
 * Clear the revocation anti-spam file cache. The default
 * `NoopAntiSpamService` writes per-IP rate-limit counters to OXID's tmp
 * cache (`oxc_o3rev_antispam_{f,s}_<hash>.txt`). One success locks the
 * IP out for 5 minutes; one set of failures hits the threshold within a
 * minute. Both make a multi-test browser suite unrunnable. Tests call
 * this helper in `beforeEach` to start each scenario with a clean slate.
 */
export async function clearRevocationAntiSpamCache(): Promise<void> {
  const dir = path.resolve(OXID_TMP_DIR);
  let files: string[];
  try {
    files = await fs.readdir(dir);
  } catch {
    // tmp dir absent in this layout — nothing to clean.
    return;
  }
  await Promise.all(
    files
      .filter((name) => /^oxc_o3rev_antispam_/.test(name))
      .map((name) => fs.rm(path.join(dir, name), { force: true })),
  );
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
