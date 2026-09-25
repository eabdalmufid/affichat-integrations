# AffiChat Integrations

Kumpulan modul integrasi resmi untuk platform **AffiChat (WA Gateway)**.

Modul-modul ini dirancang khusus untuk memanggil **19 endpoint publik REST API** AffiChat secara aman, ringan, dan *type-safe*, serta mendengarkan event **Webhook Real-Time** (`messages.upsert`, `session.status`) dengan verifikasi kriptografi HMAC-SHA256.

## Modul Tersedia

| Modul | Paket / Tipe | Deskripsi |
| :--- | :--- | :--- |
| **[AffiChat SDK](./affichat-sdk)** | `@affidev/affichat` (NPM) | TypeScript & JavaScript SDK (Zero runtime dependency, native `fetch`, webhook helpers) |
| **[n8n Community Node](./n8n-nodes-affichat)** | `@affidev/n8n-nodes-affichat` (NPM) | Action node (19 operasi) & Trigger node (Webhook listener pesan masuk & status sesi) |
| **[WordPress & WooCommerce Plugin](./affichat-wordpress)** | `affichat-wordpress` (WP Plugin) | Plugin resmi notifikasi WhatsApp otomatis untuk WordPress, Form, & WooCommerce (HPOS compliant, auto-updater) |
| **[Laravel Notification Channel](./affichat-laravel)** | `affidev/affichat` (Composer) | Paket resmi Laravel Notification Channel, Facade Client, & Webhook helper |

## Kemampuan Utama

### 1. Pengiriman & Operasi (19 Endpoint REST API)
- **Kirim Pesan (8)**: Teks (Spintax & Variables), Gambar, Dokumen, Audio, Video, Lokasi, Kontak, Polling.
- **Antrean Siaran (6)**: Send Bulk, List Kampanye, Detail Kampanye, Pause, Resume, Cancel.
- **Grup WhatsApp (2)**: List Grup, Metadata Grup.
- **Utilitas (3)**: Health Check, Cek API Key (`GET /api/key/check`), Cek Profil Nomor WhatsApp.

### 2. Penerimaan Pesan & Event (Webhook & n8n Trigger)
- **n8n Trigger Node (`AffiChatTrigger`)**: Memulai alur kerja n8n saat pesan WhatsApp masuk (`messages.upsert`) atau status perangkat berubah (`session.status`).
- **Verifikasi Tanda Tangan Kriptografi**: Mendukung HMAC-SHA256 (`X-AffiChat-Signature`) untuk memvalidasi keaslian data.
- **Penyaring Otomatis**: Opsi mengabaikan pesan keluar dari nomor bot (`ignoreSelf`) serta filter nomor pengirim spesifik (`senderFilter`).
- **Helper Terpadu**: Tersedia fungsi verifikasi instan di TypeScript SDK (`AffiChatWebhook.verifySignature`) dan Laravel (`AffiChatWebhook::verifySignature`).

## Lisensi

MIT
