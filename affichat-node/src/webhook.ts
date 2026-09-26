import crypto from "crypto";
import type { WebhookEvent } from "./types/webhook.js";

export class AffiChatWebhook {
  static verifySignature(rawBody: string | Buffer, signature: string, secret: string): boolean {
    if (!rawBody || !signature || !secret) {
      return false;
    }

    const payload = Buffer.isBuffer(rawBody) ? rawBody.toString("utf8") : String(rawBody);
    const expected = crypto.createHmac("sha256", secret).update(payload).digest("hex");

    const bufSig = Buffer.from(signature.trim());
    const bufExpected = Buffer.from(expected);

    if (bufSig.length !== bufExpected.length) {
      return false;
    }

    return crypto.timingSafeEqual(bufSig, bufExpected);
  }

  static parseEvent<T = any>(body: string | Record<string, any>): WebhookEvent<T> {
    if (typeof body === "string") {
      return JSON.parse(body) as WebhookEvent<T>;
    }
    return body as WebhookEvent<T>;
  }
}
