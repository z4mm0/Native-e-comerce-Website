<?php
include 'db/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all products
$result = $mysqli->query("SELECT p.*, c.name as category_name FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.stock > 0
                         ORDER BY p.created_at DESC");
$products = $result->fetch_all(MYSQLI_ASSOC);

// Get categories
$cat_result = $mysqli->query("SELECT * FROM categories ORDER BY name");
$categories = $cat_result->fetch_all(MYSQLI_ASSOC);

// Filter by category if selected
$filter_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
if ($filter_category) {
    $stmt = $mysqli->prepare("SELECT p.*, c.name as category_name FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             WHERE p.stock > 0 AND p.category_id = ?
                             ORDER BY p.created_at DESC");
    $stmt->bind_param('i', $filter_category);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
}

// Get cart count
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Baju - Belanja Online</title>
    <link rel="stylesheet" href="asset/ds_user.css?v=<?php echo time(); ?>">
</head>
<body>
   <nav class="navbar">
    <div class="nav-container">
        <div class="brand">
            <h1 class="brand-text">🛍️ HighmonkBoquet.id</h1>
        </div>
       <div class="user-menu">
    <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</span>
    <a href="cart.php" class="btn-navactive">
    <img src="asset/cart4.svg" width="18" height="18" style="filter: invert(1);"></a>
    <a href="order_history.php" class="btn-nav">
        <img src="asset/box-seam-fill.svg" width="18" height="18" style="filter: invert(1);"></a>
    <a href="logout.php" class="btn-logout"><img src="asset/box-arrow-right.svg" width="18" height="18" style="filter: invert(1);"></a>
</div>
    </div>
</nav>

<div class="main-wrapper">
    <header class="page-header">
        <h2>Koleksi Produk Terbaru</h2>
        <p>Temukan gaya terbaikmu hari ini!</p>
    </header>

    <section class="filter-section">
        <p class="filter-title">FILTER KATEGORI:</p>
        <div class="filter-buttons">
            <a href="dashboard_user.php" class="filter-btn <?php echo $filter_category === 0 ? 'active' : ''; ?>">Semua</a>
            <?php foreach ($categories as $cat): ?>
                <a href="dashboard_user.php?category=<?php echo $cat['id']; ?>" 
                   class="filter-btn <?php echo $filter_category == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="products-grid">
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="pict/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22250%22 height=%22220%22><rect fill=%22%23f0f0f0%22 width=%22250%22 height=%22220%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-size=%2214%22>Gambar tidak tersedia</text></svg>'">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p class="description"><?php echo substr(htmlspecialchars($product['description']), 0, 60) . '...'; ?></p>
                        <p class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                        <p class="stock">Stok: <?php echo $product['stock']; ?> pcs</p>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <form method="POST" action="tambah_keranjang.php" style="margin: 0;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn-add-cart"><img src="asset/cart4.svg" width="18" height="28" style="filter: invert(1);"></button>
                            </form>
                            <form method="POST" action="tambah_keranjang.php" style="margin: 0;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect_to_cart" value="1">
                                <button type="submit" class="btn-add-cart">Pesan <br>Sekarang</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-box">
                <p>Tidak ada produk tersedia saat ini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>              