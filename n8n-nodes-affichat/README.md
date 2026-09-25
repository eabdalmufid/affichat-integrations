# @affidev/n8n-nodes-affichat

Official **n8n Community Node** for [AffiChat WhatsApp Gateway](https://chat.affidev.com).

This package enables bidirectional automation with WhatsApp:
- **AffiChat (Action Node)**: Send WhatsApp messages, manage broadcast campaigns, list groups, and validate phone numbers across 19 API operations.
- **AffiChat Trigger (Webhook Listener)**: Trigger n8n workflows in real time upon incoming WhatsApp messages (`messages.upsert`) or device status changes (`session.status`).

## Nodes Included

### 1. AffiChat (Action Node)
- **Message (8 Operations)**:
  - Send Text (Supports Spintax `{Halo|Hai}` & Dynamic Variables)
  - Send Image (URL or Base64 + Caption)
  - Send Document (PDF/DOCX/XLSX + Filename)
  - Send Video (MP4)
  - Send GPS Location (Latitude, Longitude, Name, Address)
  - Send Contact (vCard 3.0)
  - Send Sticker (WebP)
  - Send Interactive Poll (Single or Multi-select)
- **Broadcast (6 Operations)**:
  - Send Bulk (Scheduled broadcast queue with delay & anti-ban protection)
  - List Campaigns
  - Get Campaign Progress & Status
  - Pause Campaign
  - Resume Campaign
  - Cancel Campaign
- **Group (2 Operations)**:
  - List Joined WhatsApp Groups
  - Get Group Metadata & Participants
- **Utility (3 Operations)**:
  - Check API Key & Quota Limits
  - Check WhatsApp Phone Profile (Bio, Profile Picture, Registered status)
  - Gateway Health Check

### 2. AffiChat Trigger (Webhook Node)
- **Events**:
  - `messages.upsert`: Real-time incoming WhatsApp messages with full metadata, sender details, and media links.
  - `session.status`: Connection events (`connected`, `disconnected`, `qr_ready`).
  - `*`: Listen to all events.
- **Security**: Built-in HMAC-SHA256 signature verification matching the `X-AffiChat-Signature` header.
- **Filters**:
  - `Sender Filter`: Limit trigger execution to a specific phone number.
  - `Ignore Self Messages`: Automatically skip outgoing messages sent from your own device.

## Installation

In your n8n instance:
1. Go to **Settings > Community Nodes**.
2. Select **Install a community node**.
3. Enter `@affidev/n8n-nodes-affichat` in the **npm package name** field.
4. Agree to the terms and click **Install**.

## Credentials

1. In n8n, create a new credential: **AffiChat API**.
2. Enter your **API Key** generated from your AffiChat Dashboard (*Dashboard > REST API*).
*(The node connects automatically and securely to `https://chat.affidev.com`)*.

## Configuring AffiChat Trigger in n8n

1. Add the **AffiChat Trigger** node to your workflow canvas.
2. Select the event you want to listen to (e.g. `Incoming Messages (messages.upsert)`).
3. *(Optional)* Enter a **Webhook Secret** for signature verification.
4. Copy the Webhook URL provided by n8n (Test URL or Production URL).
5. Open your AffiChat Dashboard:
   - Navigate to **Webhook** settings for your WhatsApp session.
   - Paste your n8n Webhook URL.
   - If configured, paste the same Webhook Secret.
   - Activate the webhook and save.

## License

MIT
