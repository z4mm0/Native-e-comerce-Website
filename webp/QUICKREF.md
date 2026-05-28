# 🎯 QUICK REFERENCE - Toko Baju Online

## 🚀 MULAI DALAM 3 LANGKAH

```
1. Folder webp → C:\xampp\htdocs\
2. Database: Import db/schema.sql ke phpmyadmin
3. Buka: http://localhost/webp/
```

---

## 🔐 LOGIN CREDENTIALS

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | password |
| User | Register baru | Your password |

---

## 🗺️ WEBSITE STRUCTURE

```
┌─────────────────────────────────────────┐
│        LOGIN / REGISTER                 │
│   login.php | register.php              │
└─────────────┬───────────────────────────┘
              │
         [Check Role]
         │         │
         ▼         ▼
    [ADMIN]    [USER]
       │          │
       ▼          ▼
    ADMIN      SHOP
    PANEL      DASHBOARD
```

### ADMIN FLOW
```
dashboard_admin.php
├── Manage Products (product_management.php)
│   ├── CREATE - Tambah produk
│   ├── READ - Lihat list produk
│   ├── UPDATE - Edit produk
│   └── DELETE - Hapus produk
├── Manage Categories (category_management.php)
│   ├── CREATE - Tambah kategori
│   ├── READ - Lihat list kategori
│   ├── UPDATE - Edit kategori
│   └── DELETE - Hapus kategori
└── View Orders (admin_orders.php)
    ├── List all orders
    └── Update order status
```

### USER FLOW
```
dashboard_user.php
├── Browse Products
│   ├── View all products
│   ├── Filter by category
│   └── View product details
├── Shopping Cart (cart.php)
│   ├── Add products
│   ├── Manage quantity
│   ├── Remove items
│   └── Checkout
└── Order Management
    ├── order_history.php - Riwayat pesanan
    └── order_detail.php - Detail 1 pesanan
```

---

## 📱 PAGE DESCRIPTIONS

### Authentication Pages

**login.php** ✅ READY
- Halaman login pengguna
- Validasi username & password
- Auto redirect sesuai role

**register.php** ✅ READY
- Registrasi pengguna baru
- Validasi input (unique username/email)
- Password hashing

**logout.php** ✅ READY
- Hapus session
- Redirect ke login

### Admin Pages

**dashboard_admin.php** ✅ READY
- Menu utama admin
- List produk & kategori
- Link ke management pages

**product_management.php** ✅ READY
- Form tambah produk
- Form edit produk
- Delete produk
- Field: nama, deskripsi, harga, stok, kategori, gambar

**category_management.php** ✅ READY
- Form tambah kategori
- Form edit kategori
- Delete kategori
- Field: nama, deskripsi

**admin_orders.php** ✅ READY
- Lihat semua order customer
- Update status order
- Link detail order

### User Pages

**dashboard_user.php** ✅ READY
- Product listing
- Filter by kategori
- Add to cart button
- Display: nama, harga, stok, image

**cart.php** ✅ READY
- Lihat keranjang (session)
- Edit quantity
- Hapus item
- Checkout button
- Total price

**order_history.php** ✅ READY
- List user's orders
- Order ID, tanggal, total, status
- Link ke detail

**order_detail.php** ✅ READY
- Detail 1 order
- List items dalam order
- Total price & status

**add_to_cart.php** ✅ READY
- Logic tambah ke keranjang (session)
- Validasi stok
- Redirect balik

### Utility Pages

**index.php** ✅ READY
- Home page (redirect)
- Auto check role & redirect

**test_db.php** ✅ READY
- Test database connection
- Check tables
- Show users
- Show stats

**setup_wizard.php** ✅ READY
- Visual setup checker
- Database status
- Table status
- Admin user status

---

## 💾 DATABASE TABLES REFERENCE

### users
```
id | username | password | email | role | created_at
```
**Relationships:** one-to-many dengan orders

### categories
```
id | name | description | created_at
```
**Relationships:** one-to-many dengan products

### products
```
id | name | description | price | stock | category_id | image | created_at
```
**Relationships:** many-to-one dengan categories, one-to-many dengan order_items

### orders
```
id | user_id | total | status | created_at
```
**Status:** pending, processing, shipped, delivered, cancelled
**Relationships:** many-to-one dengan users, one-to-many dengan order_items

### order_items
```
id | order_id | product_id | quantity | price
```
**Relationships:** many-to-one dengan orders, many-to-one dengan products

---

## 🔒 SECURITY FEATURES IMPLEMENTED

✅ **Password Security**
- Using: password_hash() dengan PASSWORD_DEFAULT (bcrypt)
- Verification: password_verify()

✅ **SQL Injection Prevention**
- Using: Prepared Statements
- Example: $stmt->bind_param("sss", $var1, $var2, $var3)

✅ **XSS Prevention**
- Using: htmlspecialchars()
- Applied on all user output

✅ **Access Control**
- Session check: if (!isset($_SESSION['user_id']))
- Role check: if ($_SESSION['role'] != 'admin')

✅ **Data Validation**
- Input type checking
- Length validation
- Email validation
- Numeric validation

---

## 🧪 TESTING SCENARIOS

### Scenario 1: Admin Create Product (5 min)
```
1. Login → admin / password
2. Dashboard Admin → Tambah Produk Baru
3. Form:
   - Nama: Test Product
   - Harga: 99999
   - Stok: 5
   - Submit
4. Check di list
✅ Success!
```

### Scenario 2: User Register & Shop (10 min)
```
1. Register → isi form → Submit
2. Login → username / password
3. Dashboard User → Lihat produk
4. Add to Cart → qty 2
5. Keranjang → review
6. Checkout → create order
7. Order History → lihat pesanan
✅ Success!
```

### Scenario 3: Admin Update Order Status (3 min)
```
1. Login → admin / password
2. Dashboard → Lihat Pesanan Pelanggan
3. Select order → ubah status
4. Processing → Shipped
5. Submit
✅ Success!
```

---

## ⚡ KEYBOARD SHORTCUTS

None implemented yet, but here are tips:
- Use Tab untuk navigate form fields
- Press Enter untuk submit form
- Use Ctrl+F untuk cari halaman

---

## 📞 TROUBLESHOOTING QUICK FIX

| Error | Solusi |
|-------|--------|
| "Connect Failed" | Check MySQL running + credentials |
| "Table doesn't exist" | Import db/schema.sql |
| White page / 500 error | Check Apache logs |
| Keranjang kosong setelah logout | Normal! Session di-reset |
| Can't delete product | Ada order_items yang reference |
| Lupa password admin | Reset via phpMyAdmin SQL |

---

## 📊 DATA FLOW EXAMPLES

### Shopping Flow:
```
User Browse → Add to Cart → Cart Page → Checkout
    ↓              ↓              ↓           ↓
Display      Session array   Show list   Create order
products     updated         total       in DB
```

### CRUD Flow:
```
Admin → Click Add → Form → Validate → Insert DB → Redirect to list
                               ↓
                            If error → Show error msg
```

### Login Flow:
```
User input → Hash verification → Session set → Role check → Redirect
    ↓              ↓                  ↓           ↓
username       password_verify   $_SESSION   Admin/User
password                        created     dashboard
```

---

## 🎨 STYLING REFERENCE

Default colors used:
- **Primary:** #3498db (Blue)
- **Secondary:** #95a5a6 (Gray)
- **Success:** #27ae60 (Green)
- **Warning:** #f39c12 (Orange)
- **Danger:** #e74c3c (Red)
- **Dark:** #2c3e50 (Dark Blue)

All in `asset/style.css`

---

## 📚 DOCUMENT QUICK LINKS

| Document | Untuk | Baca Ketika |
|----------|-------|------------|
| START.md | Quick start | Pertama kali setup |
| SETUP.md | Detailed setup | Ada error setup |
| README.md | Complete info | Mau detail lengkap |
| FITUR.md | Feature details | Mau ngerti kode |
| FILEMANIFEST.md | File inventory | Cari file tertentu |
| QUICKREF.md | Ini (quick ref) | Butuh cepat |

---

## ✨ WHAT'S INCLUDED

✅ 14 PHP Files (logic & pages)
✅ 1 CSS File (styling)
✅ 2 DB Files (config & structure)
✅ 6 Documentation Files
✅ 2 Utility Files
✅ **Total: 25 Files Ready to Use!**

---

## 🎯 NEXT STEPS

1. **Immediate:** Setup database & test
2. **Short term:** Test all features
3. **Medium term:** Add more products & test shopping
4. **Long term:** 
   - Add payment gateway
   - Email notifications
   - Image upload
   - Reviews & ratings
   - Wishlist
   - Admin analytics

---

## ✅ CHECKLIST SEBELUM SUBMIT

- [ ] Database sudah di-import
- [ ] Semua file sudah ada
- [ ] Login page bisa diakses
- [ ] Admin login berhasil
- [ ] User bisa register
- [ ] User bisa belanja
- [ ] Checkout berjalan
- [ ] Order history berfungsi
- [ ] Admin bisa lihat orders
- [ ] CSS loading baik

---

## 📞 STILL CONFUSED?

1. Baca **START.md** dulu
2. Jalankan **setup_wizard.php**
3. Check di **test_db.php**
4. Baca **README.md** lebih lanjut
5. Lihat **FITUR.md** untuk detail kode

---

**Status:** ✅ READY TO SUBMIT
**All Systems:** GO!

Selamat mengerjakan projek RPL Anda! 🚀

---
Last updated: January 2026
