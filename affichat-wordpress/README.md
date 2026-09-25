# AffiChat - WhatsApp Gateway for WordPress & WooCommerce

Plugin integrasi WhatsApp Gateway resmi untuk **WordPress** dan **WooCommerce** menggunakan REST API **AffiChat**.

---

## 🌟 Fitur Utama

1. **Universal & Tanpa Ketergantungan**:
   - Dapat berjalan di WordPress biasa (tanpa WooCommerce) maupun bersama WooCommerce.
   - Menu tersendiri di Sidebar WordPress (**AffiChat WA**).
2. **Kirim Cepat (Quick Send)**:
   - Kirim pesan teks WhatsApp manual langsung dari dashboard WP-Admin tanpa perlu membuka HP.
3. **Notifikasi Otomatis WooCommerce**:
   - Notifikasi Admin saat ada pesanan checkout baru.
   - Notifikasi Pembeli berdasarkan status pesanan (*Pending*, *Processing*, *Completed*, *Cancelled*).
   - Tag dinamis lengkap (`{customer_name}`, `{order_number}`, `{order_total}`, `{items_list}`, `{payment_method}`, dll.).
4. **Formulir & Integrasi Undangan**:
   - Terintegrasi dengan Elementor Pro Forms dan Contact Form 7.
   - Fungsi global PHP `affichat_send_whatsapp($phone, $message)` untuk digunakan pada tema undangan online (misal: RSVP konfirmasi kehadiran di `indovite.com`).
   - Action hook: `do_action('affichat_send_whatsapp', $phone, $message)`.
5. **Diagnostik & Uji Sambungan**:
   - Tombol *Tes Koneksi WhatsApp* instan via AJAX dengan status badge real-time.

---

## 📦 Cara Instalasi

1. Download atau zip folder `affichat-wordpress`.
2. Buka dashboard WordPress Anda: **Plugins &rarr; Add New &rarr; Upload Plugin**.
3. Pilih file zip dan klik **Install Now**, lalu klik **Activate Plugin**.
4. Buka menu baru **AffiChat WA &rarr; Pengaturan** di bilah kiri WordPress:
   - Masukkan **API Key** dari Dashboard AffiChat (`https://chat.affidev.com`).
   - Masukkan **ID Sesi WhatsApp** Anda.
   - Klik **Simpan Pengaturan**, lalu klik **Tes Koneksi WhatsApp**.

---

## 💻 Penggunaan Developer Hook (Contoh RSVP Undangan)

```php
// Kirim pesan WhatsApp otomatis dari fungsi PHP mana pun di WordPress
$nomor_tamu = '081234567890';
$pesan      = "Halo Bpk/Ibu, Terima kasih telah mengonfirmasi kehadiran Anda pada acara pernikahan kami.";

affichat_send_whatsapp($nomor_tamu, $pesan);
```

---

## 📄 Lisensi
GPL-2.0+ &copy; AffiChat
