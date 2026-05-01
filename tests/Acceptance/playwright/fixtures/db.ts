import mysql, { Pool, RowDataPacket } from 'mysql2/promise';

export interface DbConfig {
  host: string;
  port: number;
  user: string;
  password: string;
  database: string;
}

export const DEFAULT_DB_CONFIG: DbConfig = {
  host: process.env.DB_HOST ?? '127.0.0.1',
  port: Number(process.env.DB_PORT ?? 3306),
  user: process.env.DB_USER ?? 'root',
  password: process.env.DB_PASS ?? 'supersecret',
  database: process.env.DB_NAME ?? 'o3shop',
};

export class DbClient {
  private readonly pool: Pool;

  constructor(config: DbConfig = DEFAULT_DB_CONFIG) {
    this.pool = mysql.createPool({
      ...config,
      waitForConnections: true,
      connectionLimit: 5,
      queueLimit: 0,
    });
  }

  async query<T = RowDataPacket>(sql: string, params: unknown[] = []): Promise<T[]> {
    const [rows] = await this.pool.query<RowDataPacket[]>(sql, params);
    return rows as T[];
  }

  async execute(sql: string, params: unknown[] = []): Promise<void> {
    await this.pool.execute(sql, params);
  }

  async close(): Promise<void> {
    await this.pool.end();
  }

  /**
   * Insert a synthetic revocation row for tests that need pre-existing
   * data. Caller is responsible for cleaning up via `deleteRevocation`.
   */
  async seedRevocation(input: {
    oxid?: string;
    shopId?: number;
    lang?: number;
    name: string;
    orderIdent: string;
    email: string;
    freeText?: string;
    submittedAt?: Date;
    sendFailed?: boolean;
  }): Promise<string> {
    const oxid = input.oxid ?? cryptoRandom();
    const submittedAt = input.submittedAt ?? new Date();
    await this.execute(
      `INSERT INTO o3revocation
         (OXID, OXSHOPID, OXLANG, OXSUBMITTED, OXNAME, OXORDERIDENT, OXEMAIL, OXFREETEXT, OXSENDFAILED)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        oxid,
        input.shopId ?? 1,
        input.lang ?? 0,
        submittedAt,
        input.name,
        input.orderIdent,
        input.email,
        input.freeText ?? null,
        input.sendFailed ? 1 : 0,
      ],
    );
    return oxid;
  }

  async findRevocationByOrderIdent(orderIdent: string): Promise<string | null> {
    const rows = await this.query<{ OXID: string }>(
      `SELECT OXID FROM o3revocation WHERE OXORDERIDENT = ? ORDER BY OXSUBMITTED DESC LIMIT 1`,
      [orderIdent],
    );
    return rows[0]?.OXID ?? null;
  }

  async readRevocationFlags(oxid: string): Promise<{ sendFailed: number; submitted: Date } | null> {
    const rows = await this.query<{ OXSENDFAILED: number; OXSUBMITTED: Date }>(
      `SELECT OXSENDFAILED, OXSUBMITTED FROM o3revocation WHERE OXID = ? LIMIT 1`,
      [oxid],
    );
    const row = rows[0];
    if (!row) return null;
    return { sendFailed: row.OXSENDFAILED, submitted: row.OXSUBMITTED };
  }

  async deleteRevocation(oxid: string): Promise<void> {
    await this.execute(`DELETE FROM o3revocation WHERE OXID = ?`, [oxid]);
  }

  async revocationExists(oxid: string): Promise<boolean> {
    const rows = await this.query<{ c: number }>(
      `SELECT COUNT(*) AS c FROM o3revocation WHERE OXID = ?`,
      [oxid],
    );
    return (rows[0]?.c ?? 0) > 0;
  }
}

function cryptoRandom(): string {
  return [...crypto.getRandomValues(new Uint8Array(16))]
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}
