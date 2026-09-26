# AffiChat - WhatsApp Gateway for WordPress & WooCommerce

[![Versi Rilis](https://img.shields.io/badge/version-1.0.11-00A884.svg)](https://github.com/eabdalmufid/affichat-integrations/releases)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-96588a.svg)](https://woocommerce.com)
[![HPOS](https://img.shields.io/badge/HPOS-Compatible-success.svg)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://php.net)
[![Lisensi](https://img.shields.io/badge/License-GPL--2.0%2B-black.svg)](LICENSE)

Plugin integrasi resmi **AffiChat (WA Gateway)** untuk **WordPress** dan **WooCommerce**. Menghubungkan website Anda dengan WhatsApp Gateway modern untuk notifikasi transaksi toko, pesan cepat, auto-reply formulir website, pin lokasi GPS, survei polling kepuasan, sinkronisasi buku kontak CRM, hingga siaran massal langsung dari WP-Admin.

---

## Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Struktur Menu WP-Admin](#struktur-menu-wp-admin)
- [Cara Instalasi](#cara-instalasi)
- [Konfigurasi Awal](#konfigurasi-awal)
- [Integrasi WooCommerce](#integrasi-woocommerce)
- [Integrasi Formulir Website (Form Integrasi)](#integrasi-formulir-website-form-integrasi)
- [Alat, Kontak & Siaran](#alat-kontak--siaran)
- [Panduan Developer (PHP Helpers & Hooks)](#panduan-developer-php-helpers--hooks)
- [Sistem Pembaruan Otomatis](#sistem-pembaruan-otomatis)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Lisensi](#lisensi)

---

## Fitur Utama

### 1. Fleksibilitas Arsitektur
- Dapat berjalan di instalasi WordPress mandiri (tanpa WooCommerce) maupun bersama WooCommerce.
- Tampilan antarmuka profesional bertema modern dengan tab navigasi responsif untuk smartphone dan desktop.

### 2. Notifikasi Transaksi WooCommerce
- **Alert Pengelola Toko**: Notifikasi WhatsApp instan saat pesanan checkout baru dibuat.
- **Notifikasi Pelanggan Multi-Status**: Template pesan terpisah untuk 6 status pesanan:
  - *Pending Payment* (Menunggu Pembayaran)
  - *Processing* (Sedang Diproses)
  - *On Hold* (Ditahan / Verifikasi)
  - *Completed* (Selesai Dikirim)
  - *Cancelled* (Dibatalkan)
  - *Refunded* (Dana Dikembalikan)
- **Pengiriman Gambar Produk**: Pesan status pesanan selesai dapat melampirkan foto produk utama secara otomatis melalui WhatsApp.
- **Tombol WhatsApp pada Detail Pesanan**: Tombol kirim ulang notifikasi langsung dari panel admin WooCommerce.
- **Kompatibilitas Penuh HPOS**: Mendukung fitur WooCommerce High-Performance Order Storage (custom order tables).

### 3. Otomasi Formulir Universal (Form Integrasi)
- Mendukung integrasi tanpa kode untuk berbagai form builder:
  - **Elementor Pro Forms** (Action: *AffiChat WhatsApp*)
  - **JetFormBuilder** (Post-submit: *AffiChat WhatsApp*)
  - **Contact Form 7**
  - **WPForms**
  - **Fluent Forms**
- Auto-reply konfirmasi otomatis ke nomor WhatsApp pengisi form.
- Alert ringkasan data formulir ke nomor WhatsApp admin.
- Notifikasi alert saat ada registrasi pengguna baru di WordPress.

### 4. Alat, Kontak & Siaran Cepat
- **Buku Kontak CRM**: Sinkronisasi nomor WhatsApp pelanggan dari riwayat transaksi WooCommerce ke server gateway secara massal atau otomatis saat checkout.
- **Pin Lokasi Toko GPS**: Kirim koordinat fisik toko atau cabang dalam format pin peta interaktif WhatsApp.
- **Survei & Polling Kepuasan**: Jadwalkan pesan polling interaktif beberapa hari setelah pesanan berstatus selesai (*completed*).
- **Siaran Cepat (Quick Broadcast)**: Kirim pengumuman atau promo massal ke daftar nomor sekaligus lengkap dengan gambar dan caption.

### 5. UI Ergonomis & Kontrol Reset
- **Live Preview WhatsApp Web**: Pratinjau interaktif real-time dengan format teks WhatsApp (*tebal*, _miring_, ~coret~).
- **Sistem Reset Terpadu**: Tombol reset template individual per-field dan tombol reset seksi/form dengan dialog konfirmasi modern.
- **Chip Variabel**: Sisipkan tag dinamis (`{customer_name}`, `{order_number}`, `{store_name}`, dll.) dengan sekali klik.

---

## Struktur Menu WP-Admin

Setelah plugin diaktifkan, menu **AffiChat WA** akan tampil di sidebar admin dengan 5 tab:

| Tab | Sub-Menu | Deskripsi |
| :--- | :--- | :--- |
| **Pengaturan & Sesi** | `affichat-wp` | Pengaturan Base URL, API Key, ID Sesi, uji sambungan koneksi gateway, dan panduan konfigurasi. |
| **Kirim Cepat** | `affichat-wp-quick-send` | Kirim pesan WhatsApp manual instan dengan live preview gelembung chat WhatsApp Web. |
| **WooCommerce** | `affichat-wp-woocommerce` | Template notifikasi admin dan pembeli (6 status pesanan), toggle lampiran foto produk, dan daftar tag dinamis. |
| **Form Integrasi** | `affichat-wp-integrations` | Pengaturan auto-reply pengunjung dan alert admin untuk form builder universal. |
| **Alat & Kontak** | `affichat-wp-tools` | Sinkronisasi buku kontak WooCommerce, pin lokasi GPS, survei polling kepuasan, dan siaran massal. |

---

## Cara Instalasi

### Metode 1: Upload File ZIP (Direkomendasikan)
1. Unduh arsip rilis **`affichat-wordpress.zip`** dari [GitHub Releases](https://github.com/eabdalmufid/affichat-integrations/releases/latest).
   > **Catatan:** Jangan gunakan file "Source code (zip)" karena berisi seluruh monorepo. Pilih file aset bernama **`affichat-wordpress.zip`**.
2. Masuk ke WP-Admin, lalu buka **Plugins > Add New Plugin > Upload Plugin**.
3. Pilih file **`affichat-wordpress.zip`**, klik **Install Now**, kemudian klik **Activate Plugin**.

### Metode 2: Melalui Git
```bash
cd wp-content/plugins
git clone https://github.com/eabdalmufid/affichat-integrations.git temp-affichat
cp -r temp-affichat/affichat-wordpress ./affichat-wordpress
rm -rf temp-affichat
```

---

## Konfigurasi Awal

1. Buka menu **AffiChat WA > Pengaturan & Sesi**.
2. Masukkan parameter koneksi:
   - **Base URL Gateway**: URL server AffiChat Anda (misalnya `https://chat.affidev.com`).
   - **Access / API Key**: API Key dari dashboard AffiChat.
   - **ID Sesi WhatsApp**: Nama sesi WhatsApp yang aktif (default: `default`).
3. Klik **Simpan Pengaturan**.
4. Klik **Tes Koneksi WhatsApp** untuk memverifikasi komunikasi antara server WordPress dan gateway.

---

## Integrasi WooCommerce

Saat WooCommerce aktif, tab **WooCommerce** menyediakan pengaturan template pesan untuk setiap tahapan transaksi:

### Tag Dinamis yang Didukung:
- `{site_name}` : Nama website
- `{store_name}` : Nama toko
- `{customer_name}` : Nama lengkap pemesan
- `{customer_phone}` : Nomor WhatsApp pemesan
- `{customer_email}` : Alamat email pemesan
- `{order_id}` : ID pesanan internal
- `{order_number}` : Nomor pesanan publik (contoh: `#1001`)
- `{order_total}` : Total tagihan dengan format mata uang (contoh: `Rp 250.000`)
- `{items_list}` : Rincian nama produk, variasi, dan kuantitas
- `{payment_method}` : Metode pembayaran yang dipilih
- `{shipping_method}` : Metode pengiriman
- `{billing_address}` : Alamat penagihan
- `{shipping_address}` : Alamat pengiriman
- `{order_notes}` : Catatan pesanan dari pembeli

---

## Integrasi Formulir Website (Form Integrasi)

Tab **Form Integrasi** memproses pengiriman data form website:

1. **Auto-Reply Pengunjung**: Mengirimkan konfirmasi otomatis ke nomor WhatsApp pengirim form.
2. **Alert Admin**: Mengirimkan ringkasan isi pesan formulir ke nomor WhatsApp tim pengelola atau CS.
3. **Shortcode Tombol Chat**:
   ```html
   [affichat_button phone="081234567890" text="Hubungi Kami via WhatsApp" message="Halo, saya ingin bertanya tentang produk ini."]
   ```

---

## Alat, Kontak & Siaran

### 1. Sinkronisasi Kontak Toko
- **Otomatis saat Checkout**: Nomor pembeli langsung disimpan ke buku kontak gateway saat transaksi berlangsung.
- **Sinkronisasi Massal**: Mengekspor riwayat nomor pelanggan WooCommerce yang sudah ada ke gateway.

### 2. Kirim Pin Lokasi Toko (GPS)
Mengirimkan pin peta interaktif WhatsApp langsung ke nomor tujuan yang dapat dibuka menggunakan aplikasi navigasi seperti Google Maps.

### 3. Survei & Polling Kepuasan
Mengirim pesan polling kepuasan berformat interaktif WhatsApp setelah masa penundaan hari yang ditentukan tercapai.

### 4. Siaran Cepat (Quick Broadcast)
Mengirimkan pesan pengumuman atau promosi sekaligus ke banyak nomor WhatsApp yang dipisahkan baris baru, opsional dengan gambar banner.

---

## Panduan Developer (PHP Helpers & Hooks)

### 1. Helper Function Global
```php
$nomor    = '081234567890';
$pesan    = 'Halo, pesanan #1024 sedang disiapkan untuk pengiriman.';
$berhasil = affichat_send_whatsapp($nomor, $pesan);
```

### 2. Action Hook
```php
do_action('affichat_send_whatsapp', '081234567890', 'Pesan via Action Hook');
```

### 3. Pemanggilan Direct API Class
```php
// Mengirim gambar dengan teks caption
AffiChat_WP_API::send_image('081234567890', 'https://domain.com/banner.jpg', 'Katalog promo terbaru.');

// Mengirim dokumen PDF
AffiChat_WP_API::send_document('081234567890', 'https://domain.com/invoice-1024.pdf', 'invoice-1024.pdf');

// Mengirim polling interaktif
AffiChat_WP_API::send_poll('081234567890', 'Bagaimana kepuasan belanja Anda?', [
    'Sangat Puas',
    'Cukup Puas',
    'Kurang Puas'
]);

// Mengirim pin lokasi GPS
AffiChat_WP_API::send_location('081234567890', -6.2088, 106.8456, 'Toko Utama', 'Jl. Sudirman No. 1, Jakarta');
```

---

## Sistem Pembaruan Otomatis

Plugin menyertakan mekanisme auto-updater mandiri yang terhubung dengan GitHub Releases:
- Saat rilis baru dipublikasikan di repositori GitHub, indikator update muncul pada daftar plugin di `wp-admin/plugins.php`.
- Pembaruan dan changelog dapat ditinjau serta diterapkan langsung dari dashboard admin tanpa upload ulang file.

---

## Persyaratan Sistem

- **WordPress**: 5.8 atau lebih baru (Diuji hingga 6.7)
- **PHP**: 7.4, 8.0, 8.1, 8.2, atau 8.3
- **Ekstensi PHP**: `curl`, `json`
- **WooCommerce**: 5.0 atau lebih baru (Opsional)

---

## Lisensi

GPL-2.0+ © AffiChat.
