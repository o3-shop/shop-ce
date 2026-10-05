const MAILPIT_URL = process.env.MAILPIT_URL ?? 'http://localhost:8025';

export interface MailpitMessage {
  ID: string;
  From: { Name: string; Address: string };
  To: { Name: string; Address: string }[];
  Subject: string;
  Created: string;
  Snippet: string;
}

export interface MailpitSearchOptions {
  to?: string;
  subject?: string;
  timeoutMs?: number;
  pollIntervalMs?: number;
}

export class MailpitClient {
  constructor(private readonly baseURL: string = MAILPIT_URL) {}

  async clearInbox(): Promise<void> {
    const res = await fetch(`${this.baseURL}/api/v1/messages`, { method: 'DELETE' });
    if (!res.ok) {
      throw new Error(`Mailpit clearInbox failed: ${res.status} ${res.statusText}`);
    }
  }

  async listMessages(): Promise<MailpitMessage[]> {
    const res = await fetch(`${this.baseURL}/api/v1/messages`);
    if (!res.ok) {
      throw new Error(`Mailpit listMessages failed: ${res.status} ${res.statusText}`);
    }
    const body = (await res.json()) as { messages?: MailpitMessage[] };
    return body.messages ?? [];
  }

  async getMessageBody(id: string): Promise<{ html: string; text: string }> {
    const res = await fetch(`${this.baseURL}/api/v1/message/${id}`);
    if (!res.ok) {
      throw new Error(`Mailpit getMessageBody failed: ${res.status} ${res.statusText}`);
    }
    const body = (await res.json()) as { HTML?: string; Text?: string };
    return { html: body.HTML ?? '', text: body.Text ?? '' };
  }

  /**
   * Poll the inbox until a message matching the given filter arrives, or
   * the timeout expires. Throws if no match is found in time.
   */
  async waitForMessage(opts: MailpitSearchOptions = {}): Promise<MailpitMessage> {
    const timeout = opts.timeoutMs ?? 10_000;
    const interval = opts.pollIntervalMs ?? 250;
    const deadline = Date.now() + timeout;

    while (Date.now() < deadline) {
      const messages = await this.listMessages();
      const match = messages.find((m) => {
        if (opts.to && !m.To.some((t) => t.Address.toLowerCase() === opts.to!.toLowerCase())) {
          return false;
        }
        if (opts.subject && !m.Subject.includes(opts.subject)) {
          return false;
        }
        return true;
      });
      if (match) {
        return match;
      }
      await new Promise((r) => setTimeout(r, interval));
    }
    throw new Error(
      `Mailpit waitForMessage timed out after ${timeout}ms. Filter: ${JSON.stringify(opts)}`,
    );
  }
}
