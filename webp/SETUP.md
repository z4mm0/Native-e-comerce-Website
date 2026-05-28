# Setup Instructions - Toko Baju Online

## ✅ Langkah-Langkah Setup

### 1. Persiapan XAMPP
```
- Pastikan XAMPP sudah install
- Start Apache & MySQL dari XAMPP Control Panel
- Cek di browser: http://localhost/phpmyadmin
```

### 2. Copy File
```
- Copy folder 'webp' ke: C:\xampp\htdocs\
- Struktur akan jadi: C:\xampp\htdocs\webp\
```

### 3. Setup Database
```
Opsi A - Manual:
1. Buka http://localhost/phpmyadmin
2. Klik "New" / "Buat" untuk database baru
3. Masukkan nama: webp
4. Klik Create
5. Pilih database 'webp'
6. Klik tab "SQL"
7. Copy-paste isi dari db/schema.sql
8. Klik Go / Execute

Opsi B - Otomatis (Recommended):
1. Buka http://localhost/webp/setup.php
2. Script akan otomatis membuat database & tabel
```

### 4. Verifikasi Koneksi
Akses: http://localhost/webp/

**Hasil yang diharapkan:**
- ✅ Halaman login muncul
- ❌ Jika error "Connect Failed" → periksa db/koneksi.php

### 5. Login
```
Username: admin
Password: password
```

## 🔧 Troubleshooting

### Error: "Failed to connect to MySQL"
**Solusi:**
- Pastikan MySQL sudah running (hijau di XAMPP)
- Cek username & password di db/koneksi.php
- Default: root (no password)

### Error: "Table doesn't exist"
**Solusi:**
- Run db/schema.sql di phpmyadmin
- Pastikan database 'webp' sudah dibuat
- Refresh halaman setelah buat tabel

### Password admin tidak bisa login
**Reset:**
```sql
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
-- Password akan reset ke: password
```

### File tidak ditemukan (404)
**Solusi:**
- Pastikan file ada di C:\xampp\htdocs\webp\
- Cek nama file (case-sensitive di Linux)
- Jangan ubah nama file utama

## 📊 Test Data

Setelah login sebagai admin, buat data:

**Kategori:**
- T-Shirts
- Pants
- Shoes
- Accessories

**Produk Sample:**
- Nama: Kaos Polos Putih
- Harga: 50000
- Stok: 10
- Kategori: T-Shirts
- Image URL: https://via.placeholder.com/300x300?text=Kaos+Putih

## 🎯 Workflow Test

### Admin Test:
1. Login dengan admin
2. Klik "Tambah Produk Baru"
3. Isi form dan simpan
4. Edit produk yang baru dibuat
5. Hapus produk (tekan OK pada konfirmasi)

### User Test:
1. Register akun baru
2. Login dengan akun baru
3. Belanja produk
4. Checkout
5. Lihat order history

## ✨ Selesai!

Semuanya sudah siap digunakan. Jika ada pertanyaan, cek README.md untuk info lengkap.

---
**Catatan:** Password hashing sudah diimplementasikan untuk keamanan!
