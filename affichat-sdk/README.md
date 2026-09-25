# @affidev/affichat

Official TypeScript & JavaScript SDK for the **AffiChat WhatsApp Gateway** REST API.

Zero runtime dependencies. Built on native `fetch` (compatible with Node.js 18+, Bun, Deno, Next.js, and modern browsers).

## Installation

```bash
npm install @affidev/affichat
```

## Quick Start

```typescript
import { AffiChat } from "@affidev/affichat";

const affi = new AffiChat({
  apiKey: "YOUR_API_KEY",
  baseUrl: "https://chat.affidev.com", // Optional, defaults to production
});

// 1. Check API Key & Quota Status
const keyInfo = await affi.utilities.checkApiKey();
console.log("Plan:", keyInfo.data.plan, "Remaining:", keyInfo.data.remainingMessages);

// 2. Send Text Message
await affi.messages.sendText({
  sessionId: "session1",
  to: "6281234567890",
  text: "Halo! Pesanan Anda telah kami proses.",
});

// 3. Send Image with Caption
await affi.messages.sendImage({
  sessionId: "session1",
  to: "6281234567890",
  imageUrl: "https://example.com/promo.jpg",
  caption: "Katalog Promo Spesial Minggu Ini",
});

// 4. Send Sticker
await affi.messages.sendSticker({
  sessionId: "session1",
  to: "6281234567890",
  stickerUrl: "https://example.com/sticker.webp",
});

// 5. Create Bulk Broadcast Campaign
const campaign = await affi.broadcast.sendBulk({
  sessionId: "session1",
  recipients: ["6281234567890", "6289876543210"],
  message: "Promo eksklusif pelanggan setia!",
});
console.log("Campaign ID:", campaign.data?.id);

// 6. List WhatsApp Groups
const groups = await affi.groups.list("session1");
console.log("Joined groups:", groups.data.length);
```

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

## Error Handling

```typescript
import { AffiChat, QuotaExceededError, AuthenticationError } from "@affidev/affichat";

try {
  await affi.messages.sendText({ sessionId: "s1", to: "628...", text: "Halo" });
} catch (error) {
  if (error instanceof QuotaExceededError) {
    console.error("Batas kuota pesan habis. Silakan perpanjang paket.");
  } else if (error instanceof AuthenticationError) {
    console.error("API Key tidak valid atau dinonaktifkan.");
  }
}
```

## Handling Webhooks

To receive and verify real-time events (`messages.upsert`, `session.status`):

```typescript
import { AffiChatWebhook } from "@affidev/affichat";
import express from "express";

const app = express();
app.use(express.raw({ type: "application/json" })); // keep raw buffer for signature verification

app.post("/webhook", (req, res) => {
  const signature = req.headers["x-affichat-signature"] as string;
  const secret = process.env.AFFICHAT_WEBHOOK_SECRET!;

  // 1. Verify cryptographic HMAC-SHA256 signature
  const isValid = AffiChatWebhook.verifySignature(req.body, signature, secret);
  if (!isValid) {
    return res.status(401).json({ error: "Invalid signature" });
  }

  // 2. Parse event payload
  const event = AffiChatWebhook.parseEvent(req.body.toString("utf8"));
  console.log(`Received event ${event.event} for session ${event.sessionId}`);

  if (event.event === "messages.upsert") {
    console.log("Message:", event.data);
  }

  return res.json({ status: "success" });
});
```

## License

MIT

