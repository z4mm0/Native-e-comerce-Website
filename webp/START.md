# ⚡ QUICK START - Toko Baju Online

## 🚀 Mulai Dalam 3 Langkah

### 1️⃣ Copy File
Pastikan folder `webp` sudah di: `C:\xampp\htdocs\webp`

### 2️⃣ Setup Database
Buka: http://localhost/phpmyadmin

**Langkah:**
1. Klik "New" untuk database baru
2. Nama: `webp`
3. Klik Create
4. Pilih database `webp`
5. Klik tab SQL
6. Copy-paste isi file `db/schema.sql`
7. Klik Go

### 3️⃣ Akses Website
Buka: http://localhost/webp/

---

## 🔐 Akun Default

**Admin:**
- Username: `admin`
- Password: `password`

**Register user baru:**
- Klik "Daftar di sini" di halaman login

---

## 📋 Menu Utama

### Sebagai ADMIN:
1. **Login** → Dashboard Admin
2. **Tambah Produk** → Form input → Simpan
3. **Edit Produk** → Ubah data → Update
4. **Hapus Produk** → Konfirmasi → Delete
5. **Manajemen Kategori** → Sama seperti produk
6. **Lihat Pesanan** → Update status → Done

### Sebagai USER:
1. **Register** → Input data → Daftar
2. **Login** → Dashboard User
3. **Belanja** → Filter kategori → Pilih produk
4. **Keranjang** → Ubah qty → Hapus item
5. **Checkout** → Buat pesanan → Selesai
6. **Riwayat** → Lihat pesanan → Lihat detail

---

## ✅ Test Cepat

**Admin Test (2 menit):**
```
1. Login admin (admin/password)
2. Klik "Tambah Produk Baru"
3. Isi form:
   - Nama: Kaos Testing
   - Harga: 50000
   - Stok: 10
   - Klik Simpan
4. Lihat di list, klik Edit, ubah stok, klik Update
5. Klik Hapus
6. Done!
```

**User Test (3 menit):**
```
1. Klik "Daftar di sini"
2. Isi username, email, password
3. Klik Register
4. Login dengan akun baru
5. Klik "Tambah ke Keranjang"
6. Klik "🛒 Keranjang"
7. Klik "Checkout"
8. Klik "Pesanan Saya"
9. Lihat order yang baru dibuat
10. Done!
```

**Admin View Order (1 menit):**
```
1. Login sebagai admin
2. Klik "Lihat Pesanan Pelanggan"
3. Lihat order user yang baru dibuat
4. Ubah status dari Pending → Processing → Shipped
5. Done!
```

---

## 🔧 Verifikasi

Buka: http://localhost/webp/test_db.php

**Cek:**
- ✅ Koneksi database OK
- ✅ Semua tabel ada
- ✅ Admin user terdaftar

---

## 📁 File-File Penting

| File | Fungsi |
|------|--------|
| `login.php` | Login |
| `register.php` | Register |
| `dashboard_admin.php` | Admin main |
| `dashboard_user.php` | User shopping |
| `product_management.php` | CRUD produk |
| `category_management.php` | CRUD kategori |
| `cart.php` | Keranjang |
| `order_history.php` | Pesanan user |
| `db/koneksi.php` | Database config |
| `asset/style.css` | Styling |

---

## ⚠️ Jika Error

### Error: "Connect Failed"
✅ **Solusi:**
- Cek MySQL running (hijau di XAMPP Control Panel)
- Pastikan username/password di `db/koneksi.php` benar
- Default: root (tanpa password)

### Error: "Table doesn't exist"
✅ **Solusi:**
- Import `db/schema.sql` ke phpmyadmin
- Pastikan database `webp` sudah dibuat
- Refresh halaman

### Halaman White/Blank
✅ **Solusi:**
- Buka "View Source" atau F12 untuk lihat error
- Cek Apache access logs
- Pastikan file ada di `C:\xampp\htdocs\webp\`

---

## 💡 Tips

1. **Selalu check browser console (F12)** untuk JavaScript error
2. **Lihat Apache error logs** untuk PHP error
3. **Test database connection** di `test_db.php`
4. **Baca file dokumentasi** (`README.md`, `FITUR.md`)

---

## 🎯 Apa Saja yang Sudah Siap?

✅ Database dengan 5 tabel
✅ 13 file PHP (login, register, CRUD, dll)
✅ CSS styling lengkap responsive
✅ Password hashing (bcrypt)
✅ Session management
✅ Role-based access control
✅ Keranjang belanja (session)
✅ Order management
✅ Admin dashboard
✅ User dashboard
✅ Testing tool

**Tinggal buka dan pakai! 🚀**

---

## 📚 Dokumentasi Lengkap

- `README.md` - Info lengkap project
- `SETUP.md` - Panduan setup detail
- `FITUR.md` - Penjelasan fitur & kode
- `test_db.php` - Test koneksi database

---

**Status:** ✅ Ready to Use!

Jika semua langkah sudah selesai, website Anda sudah siap digunakan! 
Silakan login dan test semua fitur.

**Selamat! 🎉**
