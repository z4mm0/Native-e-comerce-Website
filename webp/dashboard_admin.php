<?php
include 'db/koneksi.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get all products
$result = $mysqli->query("SELECT p.*, c.name as category_name FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         ORDER BY p.created_at DESC");
$products = $result->fetch_all(MYSQLI_ASSOC);

// Get categories
$cat_result = $mysqli->query("SELECT * FROM categories ORDER BY name");
$categories = $cat_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Toko Baju</title>
    <link rel="stylesheet" href="asset/ds_admin.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🛍️ HighmonkBoquet.id - Admin Panel</h1>
            <div>
                <span>Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Manajemen Produk</h2>
        
        <div style="margin-bottom: 30px;">
           <a href="product_management.php?action=create" class="btn-primary">+ Tambah Produk Baru</a>
           <a href="admin_orders.php" class="btn-secondary">📋 Lihat Pesanan Pelanggan</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Tanpa Kategori'); ?></td>
                    <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                    <td><?php echo $product['stock']; ?></td>
                    <td>
                        <a href="product_management.php?action=edit&id=<?php echo $product['id']; ?>" class="btn-warning">Edit</a>
                        <a href="product_management.php?action=delete&id=<?php echo $product['id']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </t--body>
        </table>

        <h2>Daftar Kategori</h2>
        <div style="margin-bottom: 30px;">
            <a href="category_management.php?action=create" class="btn-primary">+ Tambah Kategori</a>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo $category['id']; ?></td>
                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                    <td><?php echo htmlspecialchars($category['description']); ?></td>
                    <td>
                        <a href="category_management.php?action=edit&id=<?php echo $category['id']; ?>" class="btn-warning">Edit</a>
                        <a href="category_management.php?action=delete&id=<?php echo $category['id']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
