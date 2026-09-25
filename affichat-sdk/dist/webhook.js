import crypto from "crypto";
export class AffiChatWebhook {
    static verifySignature(rawBody, signature, secret) {
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
    static parseEvent(body) {
        if (typeof body === "string") {
            return JSON.parse(body);
        }
        return body;
    }
}
//# sourceMappingURL=webhook.js.map