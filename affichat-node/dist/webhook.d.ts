import type { WebhookEvent } from "./types/webhook.js";
export declare class AffiChatWebhook {
    static verifySignature(rawBody: string | Buffer, signature: string, secret: string): boolean;
    static parseEvent<T = any>(body: string | Record<string, any>): WebhookEvent<T>;
}
//# sourceMappingURL=webhook.d.ts.map