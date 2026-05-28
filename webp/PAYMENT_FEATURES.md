# DOKUMENTASI FITUR PEMBAYARAN & STRUK

## Ringkasan Perubahan
Telah ditambahkan fitur untuk menampilkan metode pembayaran pada histori admin dan membuat struk pembayaran otomatis untuk e-wallet.

---

## 📋 File yang Telah Dimodifikasi

### 1. **admin_orders.php**
**Perubahan:**
- Menambahkan kolom baru di tabel daftar pesanan untuk menampilkan metode pembayaran
- Query diubah untuk JOIN dengan tabel `payments`
- Menambahkan fungsi `getPaymentMethodText()` untuk menampilkan metode pembayaran dengan emoji

**Fitur Baru:**
- Kolom "Metode Pembayaran" menampilkan:
  - 🏦 Cash (Transfer Bank)
  - 💳 Kartu Kredit
  - 📱 E-Wallet
  - 📊 Cicilan 0%

**Lokasi Kolom:**
- Ditambahkan sebagai kolom ke-6 sebelum Status

---

### 2. **admin_order_detail.php**
**Perubahan:**
- Menambahkan pengambilan data pembayaran dari tabel `payments`
- Menambahkan fungsi helper `getPaymentMethodText()` dan `getPaymentStatusText()`
- Menambahkan section informasi pembayaran di halaman detail

**Fitur Baru:**
- Menampilkan informasi pembayaran lengkap:
  - Metode Pembayaran
  - Jumlah Pembayaran
  - Status Pembayaran (Menunggu/Lunas/Gagal/Dibatalkan)
  - ID Transaksi
  - Waktu Pembayaran

**Tombol Struk:**
- Tombol "🖨️ Lihat/Cetak Struk" muncul jika pembayaran telah selesai (status = 'completed')

---

### 3. **pembayaran.php** ✨ [PENTING]
**Perubahan Kritis:**
- Menambahkan logika untuk auto-set status pembayaran menjadi 'completed' untuk e-wallet
- Menambahkan redirect berbeda berdasarkan metode pembayaran:
  - **E-Wallet**: Redirect ke `struk_pembayaran.php`
  - **Metode Lain**: Redirect ke `order_detail.php`

**Alur Pembayaran E-Wallet:**
1. User memilih metode E-Wallet
2. Submit form pembayaran
3. Status pembayaran otomatis diubah menjadi 'completed'
4. Redirect ke halaman struk pembayaran
5. User bisa mencetak struk

**Kode Penting:**
```php
// Update payment status to completed for e-wallet
if ($payment_method === 'e_wallet') {
    $complete_stmt = $mysqli->prepare("UPDATE payments SET status = 'completed', updated_at = NOW() WHERE order_id = ?");
    if ($complete_stmt) {
        $complete_stmt->bind_param("i", $order_id);
        $complete_stmt->execute();
    }
}

// Redirect based on payment method
if ($payment_method === 'e_wallet') {
    $success = "✓ Pembayaran E-Wallet berhasil! ID Transaksi: " . $transaction_id;
    header("refresh:3;url=struk_pembayaran.php?order_id=" . $order_id);
} else {
    $success = "Pembayaran berhasil diproses! ID Transaksi: " . $transaction_id;
    header("refresh:3;url=order_detail.php?order_id=" . $order_id);
}
```

---

### 4. **api_get_orders.php**
**Perubahan:**
- Menambahkan JOIN dengan tabel `payments` di query API
- Menambahkan field `payment_method` ke JSON response
- Menambahkan fungsi `getPaymentMethodText()`

**Fungsi:**
- Mendukung auto-refresh tabel admin orders dengan informasi metode pembayaran

---

## ✨ File Baru Dibuat

### **struk_pembayaran.php** [FILE BARU]
Halaman struk pembayaran dengan fitur:

**Fitur Utama:**
- ✅ Tampilan struk profesional (format receipt/kasir)
- ✅ Layout responsif dan print-friendly
- ✅ Informasi lengkap transaksi
- ✅ QR Code placeholder (siap untuk integrasi QR code)
- ✅ CSS terpisah untuk print vs tampilan layar

**Konten Struk:**
1. **Header Toko**
   - Logo & nama toko (🛍️ HighmonkBoquet.id)
   - Informasi kontak

2. **Data Pembeli**
   - Nama pembeli
   - Email
   - Tanggal pembelian

3. **Daftar Item**
   - Nama produk
   - Jumlah
   - Harga satuan
   - Total per item

4. **Informasi Pembayaran**
   - Metode pembayaran
   - Jumlah pembayaran
   - ID Transaksi
   - Waktu pembayaran
   - Status pembayaran

5. **Pesan Terima Kasih**
   - Ucapan terima kasih
   - Informasi follow-up

**Fitur Teknis:**
```css
@media print {
    /* Styling khusus untuk print */
    /* Ukuran kertas: 80mm (thermal printer) */
    max-width: 80mm;
}
```

**Tombol Aksi:**
- 🖨️ Cetak Struk - Buka dialog print browser
- ← Kembali ke Detail Pesanan - Link kembali ke halaman order detail

---

## 📊 Database Schema (Tidak ada perubahan diperlukan)

Struktur yang sudah ada di `payments` table sudah mendukung fitur baru:

```sql
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,  -- ← Sudah ada
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_payment (order_id)
);
```

---

## 🔄 Alur Kerja Lengkap

### Skenario 1: Pembayaran E-Wallet
```
1. User order produk
2. User masuk ke halaman pembayaran (pembayaran.php)
3. User pilih metode "📱 E-Wallet"
4. Submit form
5. ✅ Sistem buat record payment dengan status 'pending'
6. ✅ Sistem ubah status payment menjadi 'completed' (auto for e-wallet)
7. ✅ Sistem ubah status order menjadi 'processing'
8. ✅ Generate transaction ID: TRX-YYYYMMDDHHmm-orderid
9. ⏱️ Redirect setelah 3 detik ke struk_pembayaran.php
10. 📄 Tampilkan struk pembayaran lengkap
11. 🖨️ User bisa cetak struk
```

### Skenario 2: Pembayaran Cash/Transfer Bank
```
1. User order produk
2. User masuk ke halaman pembayaran (pembayaran.php)
3. User pilih metode "🏦 Cash/Transfer"
4. Submit form
5. ✅ Sistem buat record payment dengan status 'pending'
6. ✅ Sistem ubah status order menjadi 'processing'
7. ⏱️ Redirect setelah 3 detik ke order_detail.php
8. 📋 Tampilkan detail order (bukan struk otomatis)
9. 💬 Admin bisa lihat metode pembayaran di daftar order
```

### Skenario 3: Admin Melihat Riwayat Pembayaran
```
1. Admin masuk admin_orders.php
2. 👀 Lihat kolom "Metode Pembayaran" di setiap order
3. 🔍 Klik "Detail" untuk order tertentu
4. 📊 Lihat informasi pembayaran lengkap di admin_order_detail.php
5. 🖨️ Jika pembayaran selesai, admin bisa lihat/cetak struk
```

---

## 🎨 Tampilan Visual

### Admin Orders List
```
┌─────┬──────────┬────────┬──────────┬────────┬────────────────┬──────────┬──────┐
│ ID  │ Pelanggan│ Email  │ Tanggal  │ Total  │ METODE PEMBAYA │ Status   │ Aksi │
├─────┼──────────┼────────┼──────────┼────────┼────────────────┼──────────┼──────┤
│ 123 │ Budi     │ budi@. │ 01/02... │ Rp ... │ 📱 E-Wallet    │ Diproses │ [>]  │
│ 124 │ Ani      │ ani@.  │ 01/02... │ Rp ... │ 🏦 Cash        │ Diterima │ [>]  │
└─────┴──────────┴────────┴──────────┴────────┴────────────────┴──────────┴──────┘
```

### Struk Pembayaran
```
═══════════════════════════════════════════════
          🛍️ HighmonkBoquet.id
          Toko Fashion Online
═══════════════════════════════════════════════
              STRUK PEMBAYARAN

No. Struk: INV-000123-202601291530
Order ID: #123

📦 DATA PEMBELIAN
Nama: Budi Santoso
Email: budi@email.com
Tanggal: 29/01/2026 15:30

───────────────────────────────────────────────
Produk          Qty  Harga    Total
───────────────────────────────────────────────
Kaos Polos      2    Rp 50K   Rp 100K
Celana Jeans    1    Rp 150K  Rp 150K

═══════════════════════════════════════════════
                    TOTAL: Rp 250.000
═══════════════════════════════════════════════

💳 INFORMASI PEMBAYARAN
Metode:      📱 E-Wallet
Jumlah:      Rp 250.000
ID Transaksi: TRX-20260129150000-123
Waktu:       29/01/2026 15:30:45

        ✓ PEMBAYARAN DITERIMA

───────────────────────────────────────────────
         Terima Kasih Telah Berbelanja!
   Pesanan Anda sedang diproses. Kami akan
     mengirimkan barang secepatnya.

   📱 Hubungi kami jika ada pertanyaan
   Email: info@highmonkboquet.id
═══════════════════════════════════════════════
```

---

## ✅ Checklist Testing

### Admin Panel
- [ ] Kolom metode pembayaran tampil di daftar order
- [ ] Metode pembayaran terupdate saat auto-refresh (5 detik)
- [ ] Klik Detail → Lihat info pembayaran lengkap
- [ ] Tombol "Lihat/Cetak Struk" muncul jika pembayaran 'completed'
- [ ] Klik tombol struk → Halaman struk terbuka

### Halaman Struk
- [ ] Tampilan struk profesional
- [ ] Semua data transaksi lengkap
- [ ] Tombol cetak bekerja
- [ ] Layout sesuai saat dicetak
- [ ] Responsive di mobile

### User Payment Flow
- [ ] Pilih metode E-Wallet
- [ ] Submit pembayaran
- [ ] Status payment ubah menjadi 'completed'
- [ ] Redirect ke struk otomatis setelah 3 detik
- [ ] Bisa cetak struk dari halaman struk

### User Payment Flow (Cash)
- [ ] Pilih metode Cash
- [ ] Submit pembayaran
- [ ] Status payment = 'pending'
- [ ] Redirect ke order_detail.php
- [ ] Tidak ada tombol cetak struk (belum bayar)

---

## 🔧 Maintenance

### Jika perlu menambah metode pembayaran baru:
1. Edit array `$valid_methods` di `pembayaran.php`
2. Edit array `$methodMap` di `getPaymentMethodText()` (admin_orders.php, admin_order_detail.php, api_get_orders.php, struk_pembayaran.php)
3. Update form radio button di `pembayaran.php`

### Jika perlu mengubah kondisi auto-complete:
- Edit kondisi di `pembayaran.php` baris: `if ($payment_method === 'e_wallet')`

### Jika perlu custom styling struk:
- Edit CSS di `struk_pembayaran.php` section `<style>`

---

## 📞 Support

Untuk pertanyaan atau masalah:
1. Periksa error.log
2. Test di browser console untuk JS errors
3. Verifikasi database permissions
4. Pastikan session active untuk admin

---

**Tanggal Update:** 29 Januari 2026
**Status:** ✅ Selesai
