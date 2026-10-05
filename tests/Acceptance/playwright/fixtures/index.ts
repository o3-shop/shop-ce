import { test as authTest, AuthFixtures } from './auth';
import { MailpitClient } from './mailpit';
import { DbClient } from './db';

export interface ServiceFixtures {
  /** Mailpit client; inbox is cleared before each test. */
  mailpit: MailpitClient;
  /** Long-lived MySQL pool; closed at worker teardown. */
  db: DbClient;
}

export const test = authTest.extend<ServiceFixtures>({
  mailpit: async ({}, use) => {
    const client = new MailpitClient();
    await client.clearInbox();
    await use(client);
  },

  db: [
    async ({}, use) => {
      const client = new DbClient();
      await use(client);
      await client.close();
    },
    { scope: 'worker' },
  ],
});

export { expect } from '@playwright/test';
export type { AuthFixtures, MailpitClient, DbClient };
