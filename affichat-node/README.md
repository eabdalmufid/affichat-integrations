# @affidev/affichat

[![npm version](https://img.shields.io/npm/v/@affidev/affichat.svg?color=00A884)](https://www.npmjs.com/package/@affidev/affichat)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Official TypeScript and JavaScript SDK for the **AffiChat WhatsApp Gateway** REST API.

Zero runtime dependencies. Built on native `fetch` and compatible with Node.js 18+, Bun, Deno, Next.js, and modern browser environments.

---

## Installation

```bash
npm install @affidev/affichat
```

---

## Quick Start

```typescript
import { AffiChat } from "@affidev/affichat";

const client = new AffiChat({
  apiKey: "YOUR_API_KEY",
  baseUrl: "https://chat.affidev.com", // Optional, defaults to production
});

// Check API key and quota
const keyInfo = await client.utilities.checkApiKey();
console.log("Plan:", keyInfo.data.plan, "Remaining:", keyInfo.data.remainingMessages);

// Send text message
await client.messages.sendText({
  sessionId: "default",
  to: "6281234567890",
  text: "Halo, pesanan Anda sedang kami siapkan.",
});

// Send image with caption
await client.messages.sendImage({
  sessionId: "default",
  to: "6281234567890",
  imageUrl: "https://example.com/promo.jpg",
  caption: "Katalog Promo Mingguan",
});

// Dispatch bulk broadcast campaign
const campaign = await client.broadcast.sendBulk({
  sessionId: "default",
  recipients: ["6281234567890", "6289876543210"],
  message: "Pengumuman jadwal operasional toko.",
});
console.log("Campaign ID:", campaign.data?.id);

// List WhatsApp groups
const groups = await client.groups.list("default");
console.log("Joined groups count:", groups.data.length);
```

---

## API Methods (19 Endpoints)

### Messages (`client.messages`)
- `sendText(params)` — `POST /api/send-text`
- `sendImage(params)` — `POST /api/send-image`
- `sendDocument(params)` — `POST /api/send-document`
- `sendVideo(params)` — `POST /api/send-video`
- `sendLocation(params)` — `POST /api/send-location`
- `sendContact(params)` — `POST /api/send-contact`
- `sendSticker(params)` — `POST /api/send-sticker`
- `sendPoll(params)` — `POST /api/send-poll`

### Broadcast (`client.broadcast`)
- `sendBulk(params)` — `POST /api/send-bulk`
- `listCampaigns(params)` — `GET /api/campaigns`
- `getCampaign(id)` — `GET /api/campaigns/:id`
- `pauseCampaign(id)` — `POST /api/campaigns/:id/pause`
- `resumeCampaign(id)` — `POST /api/campaigns/:id/resume`
- `cancelCampaign(id)` — `POST /api/campaigns/:id/cancel`

### Groups (`client.groups`)
- `list(sessionId | params)` — `GET /api/groups`
- `getMetadata(sessionId, groupJid)` — `GET /api/groups/:jid`

### Utilities (`client.utilities`)
- `healthCheck()` — `GET /api/health`
- `checkApiKey()` — `GET /api/key/check`
- `checkProfile(params)` — `GET /api/profile`

---

## Error Handling

The SDK exposes custom error types for distinct API failure modes:

```typescript
import { AffiChat, QuotaExceededError, AuthenticationError } from "@affidev/affichat";

try {
  await client.messages.sendText({
    sessionId: "default",
    to: "6281234567890",
    text: "Pesan penting",
  });
} catch (error) {
  if (error instanceof QuotaExceededError) {
    console.error("Quota limit reached. Please upgrade your active plan.");
  } else if (error instanceof AuthenticationError) {
    console.error("Invalid or inactive API key.");
  }
}
```

---

## Webhook Signature Verification

To process real-time incoming events (`messages.upsert`, `session.status`) securely:

```typescript
import { AffiChatWebhook } from "@affidev/affichat";
import express from "express";

const app = express();

// Raw body is required for HMAC-SHA256 signature verification
app.use(express.raw({ type: "application/json" }));

app.post("/webhook", (req, res) => {
  const signature = req.headers["x-affichat-signature"] as string;
  const secret    = process.env.AFFICHAT_WEBHOOK_SECRET!;

  const isValid = AffiChatWebhook.verifySignature(req.body, signature, secret);
  if (!isValid) {
    return res.status(401).json({ error: "Invalid signature" });
  }

  const event = AffiChatWebhook.parseEvent(req.body.toString("utf8"));

  if (event.event === "messages.upsert") {
    const message = event.data;
    console.log("Incoming message:", message);
  }

  return res.json({ status: "success" });
});
```

---

## License

MIT License.
