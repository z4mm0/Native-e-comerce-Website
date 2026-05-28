# 🛍️ Toko Baju Online - Website E-Commerce

Website toko baju online dengan fitur CRUD (Create, Read, Update, Delete) dan dual role (Admin & User).

## 📋 Struktur Database

Database `webp` memiliki tabel-tabel berikut:

### 1. **users** - Tabel Pengguna
- `id` - ID pengguna (Primary Key)
- `username` - Username unik
- `password` - Password (hashed)
- `email` - Email unik
- `role` - Role pengguna (admin/user)
- `created_at` - Waktu pembuatan akun

### 2. **categories** - Kategori Produk
- `id` - ID kategori
- `name` - Nama kategori
- `description` - Deskripsi kategori
- `created_at` - Waktu pembuatan

### 3. **products** - Produk
- `id` - ID produk
- `name` - Nama produk
- `description` - Deskripsi produk
- `price` - Harga
- `stock` - Stok tersedia
- `category_id` - ID kategori (Foreign Key)
- `image` - URL gambar produk
- `created_at` - Waktu pembuatan

### 4. **orders** - Pesanan
- `id` - ID pesanan
- `user_id` - ID pengguna (Foreign Key)
- `total` - Total harga
- `status` - Status pesanan (pending/processing/shipped/delivered/cancelled)
- `created_at` - Waktu pembuatan

### 5. **order_items** - Item Pesanan
- `id` - ID item
- `order_id` - ID pesanan (Foreign Key)
- `product_id` - ID produk (Foreign Key)
- `quantity` - Jumlah
- `price` - Harga satuan saat pembelian

## 🔐 Akun Default

Admin sudah tersedia dengan data berikut:
- **Username:** admin
- **Password:** password
- **Email:** admin@store.com
- **Role:** admin

## 📁 Struktur File & Fungsi

### Halaman Publik
- **index.php** - Halaman utama (redirect ke dashboard sesuai role)
- **login.php** - Halaman login
- **register.php** - Halaman registrasi pengguna baru

### Admin Dashboard
- **dashboard_admin.php** - Dashboard admin dengan daftar produk dan kategori
- **product_management.php** - CRUD produk (Create, Read, Update, Delete)
- **category_management.php** - CRUD kategori

### User Dashboard
- **dashboard_user.php** - Halaman belanja dengan filter kategori
- **add_to_cart.php** - Tambah produk ke keranjang
- **cart.php** - Keranjang belanja dan checkout
- **order_history.php** - Riwayat pesanan pengguna
- **order_detail.php** - Detail pesanan tertentu

### Support Files
- **logout.php** - Logout dan hapus session
- **db/koneksi.php** - Koneksi database
- **db/schema.sql** - Script pembuatan database
- **asset/style.css** - Styling CSS

## 🚀 Cara Menggunakan

### Instalasi
1. Pastikan XAMPP sudah terinstall dan running
2. Copy folder `webp` ke `C:\xampp\htdocs\`
3. Buka phpMyAdmin dan impor file `db/schema.sql`
4. Database `webp` akan terbuat otomatis dengan semua tabel

### Akses Website
1. Buka browser: `http://localhost/webp/`
2. Otomatis redirect ke login page
3. Login dengan:
   - **Admin:** username: `admin`, password: `password`
   - **User:** daftar akun baru di halaman register

## 👨‍💼 Role & Fitur

### Admin
- ✅ Menambah produk baru (Create)
- ✅ Melihat daftar semua produk (Read)
- ✅ Mengubah data produk (Update)
- ✅ Menghapus produk (Delete)
- ✅ Manajemen kategori (CRUD)
- ✅ Melihat stok produk

### User
- ✅ Melihat daftar produk
- ✅ Filter produk berdasarkan kategori
- ✅ Tambah produk ke keranjang
- ✅ Manage keranjang (ubah jumlah, hapus item)
- ✅ Checkout & buat pesanan
- ✅ Lihat riwayat pesanan
- ✅ Lihat detail pesanan

## 🔒 Keamanan

### Implementasi Keamanan:
- ✅ Password hashing menggunakan `password_hash()` dengan bcrypt
- ✅ Session management untuk autentikasi
- ✅ Role-based access control
- ✅ SQL injection prevention dengan Prepared Statements
- ✅ XSS prevention dengan `htmlspecialchars()`

### Catatan Penting:
⚠️ Untuk production:
- Ubah kredensial database di `db/koneksi.php`
- Gunakan environment variables untuk config sensitif
- Implementasikan HTTPS
- Tambahkan CSRF token pada form
- Update password admin default
- Gunakan database dengan password yang kuat

## 🎨 Design & UI

- Responsive design untuk mobile & desktop
- Modern color scheme (blue, gray, white)
- Hover effects dan transitions smooth
- Grid layout untuk product catalog
- Clear typography dan spacing

## 💡 Tips Pengembangan

### Menambah Fitur:
1. **Validasi input** - Selalu validasi data dari user
2. **Error handling** - Tampilkan pesan error yang jelas
3. **User feedback** - Beri notifikasi ketika aksi berhasil
4. **Database transactions** - Gunakan untuk operasi kompleks

### Untuk Upgrade Lebih Lanjut:
- Tambah payment gateway (Midtrans, PayPal)
- Implementasi review & rating produk
- Email notification untuk pesanan
- Upload gambar produk (bukan hanya URL)
- Search & sort produk
- Wishlist/favorite
- Admin analytics & report
- User profile management

## 📞 Support

Jika ada error atau pertanyaan:
1. Cek console browser (F12)
2. Cek error di Apache logs
3. Verifikasi koneksi database di phpmyadmin
4. Pastikan semua file sudah di copy dengan benar

---

**Status:** ✅ Ready to Use - Semua fitur CRUD sudah berfungsi
**Last Updated:** Januari 2026
