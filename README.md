# AffiChat Integrations

[![GitHub Release](https://img.shields.io/github/v/release/eabdalmufid/affichat-integrations?color=00A884)](https://github.com/eabdalmufid/affichat-integrations/releases)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Koleksi paket SDK dan modul integrasi resmi untuk ekosistem **AffiChat (WhatsApp Gateway)**.

Semua modul dirancang untuk berinteraksi langsung dengan 19 endpoint REST API AffiChat secara aman dan terstruktur, serta memproses event **Webhook Real-Time** (`messages.upsert`, `session.status`) dengan verifikasi kriptografi HMAC-SHA256.

---

## Modul Integrasi Resmi

| Modul | Tipe / Distribusi | Status | Deskripsi |
| :--- | :--- | :--- | :--- |
| **[WordPress & WooCommerce Plugin](./affichat-wordpress)** | WordPress Plugin | `v1.0.11` | Notifikasi transaksi toko, auto-reply formulir universal, pin lokasi GPS, survei polling kepuasan, sinkronisasi buku kontak CRM, dan siaran massal. Kompatibel dengan HPOS dan auto-updater GitHub. |
| **[AffiChat SDK](./affichat-sdk)** | NPM (`@affidev/affichat`) | `v1.0.0` | Klien TypeScript dan JavaScript untuk Node.js, Bun, Deno, dan browser. Berbasis `fetch` native tanpa dependensi runtime eksternal. |
| **[n8n Community Node](./n8n-nodes-affichat)** | NPM (`@affidev/n8n-nodes-affichat`) | `v1.0.0` | Node tindakan (*action*) dan pemicu alur kerja (*trigger*) untuk orkestrasi otomasi WhatsApp di platform n8n. |
| **[Laravel Notification Channel](./affichat-laravel)** | Composer (`affidev/affichat`) | `v1.0.0` | Driver channel notifikasi bawaan Laravel, Facade client mandiri, dan middleware verifikasi webhook HMAC. |

---

## Kemampuan Utama

### 1. Operasi REST API (19 Endpoint)
- **Pengiriman Pesan**:
  - Teks biasa, spintax `{Halo|Hai}`, dan variabel template (`/api/send-text`)
  - Gambar dengan caption (`/api/send-image`)
  - Dokumen dan PDF (`/api/send-document`)
  - Audio dan Voice Note (`/api/send-audio`)
  - Video MP4 (`/api/send-video`)
  - Pin lokasi GPS peta interaktif (`/api/send-location`)
  - Kartu kontak vCard (`/api/send-contact`)
  - Polling interaktif (*single/multi-select*) (`/api/send-poll`)
- **Antrean Siaran Massal (Bulk Broadcast)**:
  - Antrean pengiriman nomor massal dengan jeda pengiriman aman
  - Manajemen kampanye siaran: list, detail, pause, resume, cancel
- **Grup WhatsApp**:
  - Daftar grup aktif dan pengambilan metadata partisipan on-demand
- **Buku Kontak CRM**:
  - Sinkronisasi data nama dan nomor kontak pelanggan ke server gateway

### 2. Pipeline Webhook & Real-Time Event
- **Trigger Pesan Masuk**: Menangkap event `messages.upsert` secara instan saat ada pesan masuk.
- **Trigger Status Sesi**: Memantau status koneksi perangkat WhatsApp (`session.status`).
- **Verifikasi HMAC-SHA256**: Memvalidasi keaslian pengirim menggunakan signature pada header `X-AffiChat-Signature`.
- **Penyaring Otomatis**: Mendukung pengabaian pesan bot sendiri (*ignore self*) dan filter nomor pengirim tertentu.

---

## Panduan Memulai

Pilih modul yang sesuai dengan kebutuhan integrasi Anda:
- [Instalasi Plugin WordPress & WooCommerce](./affichat-wordpress)
- [Instalasi Node.js / TypeScript SDK](./affichat-sdk)
- [Instalasi n8n Community Node](./n8n-nodes-affichat)
- [Instalasi Laravel Package](./affichat-laravel)

---

## Lisensi

Repositori monorepo ini dilisensikan di bawah [Lisensi MIT](LICENSE). Masing-masing modul dapat memiliki ketentuan lisensi tersendiri (misalnya GPL-2.0+ untuk plugin WordPress).
