<?php
include 'db/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
$total = 0;

// Flash messages
if (isset($_SESSION['success'])) { $success = $_SESSION['success']; unset($_SESSION['success']); }
if (isset($_SESSION['error'])) { $error = $_SESSION['error']; unset($_SESSION['error']); }

// Handle remove from cart
if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $product_id = intval($_GET['product_id']);
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
        return $item['product_id'] != $product_id;
    });
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: cart.php");
    exit();
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_quantity') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // Remove item if quantity <= 0
    if ($quantity <= 0) {
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
            return $item['product_id'] != $product_id;
        });
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $_SESSION['success'] = "Produk dihapus dari keranjang.";
    } else {
        // Check product stock
        $stmt = $mysqli->prepare("SELECT stock FROM products WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                $_SESSION['error'] = "Produk tidak ditemukan.";
            } elseif ($quantity > $row['stock']) {
                $_SESSION['error'] = "Stok tidak cukup. Maks: " . $row['stock'];
            } else {
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['product_id'] == $product_id) {
                        $item['quantity'] = $quantity;
                        break;
                    }
                }
                unset($item);
                $_SESSION['success'] = "Jumlah produk berhasil diperbarui.";
            }
        } else {
            $_SESSION['error'] = "Database error: " . $mysqli->error;
        }
    }

    header("Location: cart.php");
    exit();
}

// Recalculate total
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Handle checkout
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($cart)) {
        $error = "Keranjang kosong!";
    } else {
        // Validate stock for all items before creating order
        foreach ($cart as $item_check) {
            $check_stmt = $mysqli->prepare("SELECT stock FROM products WHERE id = ?");
            if ($check_stmt) {
                $check_stmt->bind_param("i", $item_check['product_id']);
                $check_stmt->execute();
                $row = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if (!$row) {
                    $error = "Produk tidak ditemukan: " . htmlspecialchars($item_check['name']);
                    break;
                }

                if ($item_check['quantity'] > $row['stock']) {
                    $error = "Stok tidak cukup untuk produk " . htmlspecialchars($item_check['name']) . ". Tersedia: " . $row['stock'];
                    break;
                }
            } else {
                $error = "Database error: " . $mysqli->error;
                break;
            }
        }

        if (empty($error)) {
            // Begin transaction to avoid negative stock due to race conditions
            $mysqli->begin_transaction();
            $user_id = $_SESSION['user_id'];
            $insert_order = $mysqli->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')");
            $insert_order->bind_param("id", $user_id, $total);

            $ok = true;
            if ($insert_order->execute()) {
                $order_id = $mysqli->insert_id;

                // Prepare statements
                $insert_item_stmt = $mysqli->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $update_stock_stmt = $mysqli->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

                foreach ($cart as $item) {
                    $product_id = $item['product_id'];
                    $quantity = $item['quantity'];
                    $price = $item['price'];

                    // Insert order item
                    if (!$insert_item_stmt->bind_param("iidi", $order_id, $product_id, $quantity, $price) || !$insert_item_stmt->execute()) {
                        $ok = false;
                        $error = "Gagal menambahkan item ke pesanan: " . $insert_item_stmt->error;
                        break;
                    }

                    // Reduce stock only if sufficient
                    if (!$update_stock_stmt->bind_param("iii", $quantity, $product_id, $quantity) || !$update_stock_stmt->execute()) {
                        $ok = false;
                        $error = "Gagal mengurangi stok: " . $update_stock_stmt->error;
                        break;
                    }

                    if ($update_stock_stmt->affected_rows === 0) {
                        $ok = false;
                        $error = "Stok tidak cukup untuk produk " . htmlspecialchars($item['name']) . ". Silakan periksa kembali jumlah pesanan.";
                        break;
                    }
                }

                if ($ok) {
                    $mysqli->commit();
                    unset($_SESSION['cart']);
                    $_SESSION['order_id'] = $order_id;
                    header("Location: pembayaran.php?order_id=" . $order_id);
                    exit();
                } else {
                    $mysqli->rollback();
                }

            } else {
                $mysqli->rollback();
                $error = "Gagal membuat pesanan: " . $insert_order->error;
            }
        }
    }
}
?>  

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Toko Baju</title>
   <style>
    /* Gunakan font yang sama dengan halaman utama */
body {
    font-family: 'Quicksand', sans-serif;
    background-color: #fff5f8;
    color: #444;
}

.container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}

h2 {
    color: #798763;
    font-size: 28px;
    margin-bottom: 30px;
    font-weight: 700;
}

/* --- Tabel Keranjang yang Lebih Bersih --- */
.table-wrapper {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(121, 135, 99, 0.1);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: #f8f9fa;
    color: #798763;
    padding: 20px;
    text-align: left;
    font-size: 14px;
    text-transform: uppercase;
    border-bottom: 2px solid #eee;
}

.table td {
    padding: 20px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.table tr:last-child td {
    border-bottom: none;
}

/* --- Styling Tombol Aksi --- */
.btn-danger {
    color: #d81b60;
    text-decoration: none;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 15px;
    border-radius: 8px;
    border: 1px solid #fce4ec;
    transition: 0.3s;
}

.btn-danger:hover {
    background: #fce4ec;
}
.btn-primary {
    color: #d81b60;
    text-decoration: none;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 15px;
    border-radius: 8px;
    border: 1px solid #fce4ec;
    transition: 0.3s;
}

.btn-primary:hover {
    background: #fce4ec;
}

/* --- Ringkasan Total --- */
.cart-summary {
    margin-top: 30px;
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 4px 15px rgba(121, 135, 99, 0.1);
    text-align: right;
}

.cart-summary h3 {
    color: #444;
    font-size: 22px;
    margin-bottom: 20px;
}

.cart-summary .total-price {
    color: #798763;
    font-weight: 700;
    font-size: 26px;
}

.btn-checkout {
    display: inline-block;
    background: #798763;
    color: white;
    padding: 15px 40px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 700;
    transition: 0.3s;
    border: none;
    cursor: pointer;
}

.btn-checkout:hover {
    background: #5f6d50;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(121, 135, 99, 0.2);
}

/* Tombol Secondary */
.btn-secondary {
    background-color: #f1f3ee;
    color: #798763;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    border: 2px solid #798763;
    transition: 0.3s;
    display: inline-block;
    margin: 0 5px;
}

.btn-secondary:hover {
    background-color: #798763;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(121, 135, 99, 0.2);
}

/* Error Message */
.error {
    background-color: #f8d7da;
    color: #721c24;
    padding: 15px 25px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 5px solid #f5c6cb;
}

/* Success Message */
.success {
    background-color: #d4edda;
    color: #155724;
    padding: 15px 25px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 5px solid #c3e6cb;
} 

/* Area Kosong (Gunakan style empty-box yang sudah ada) */
.empty-cart {
    background: white;
    padding: 60px;
    text-align: center;
    border-radius: 15px;
    border: 2px dashed #ddd;
    color: #888;
    width: 100%;
    box-shadow: 0 4px 15px rgba(121, 135, 99, 0.05);
}

.empty-cart a {
    color: #798763;
    font-weight: 700;
}
    </style>
    </head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🛍️ HighmonkBoquet.id</h1>
            <div>
                <a href="dashboard_user.php" class="btn-secondary">← Lanjut Belanja</a>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>🛒 Keranjang Belanja</h2>

        <?php if (isset($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if (empty($cart)): ?>
            <div class="empty-cart">
                <p>🛍️ Keranjang Anda kosong</p>
                <p><a href="dashboard_user.php">← Lanjut berbelanja</a></p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                        <td>
                            <form method="POST" style="display:flex; gap:8px; align-items:center;">
                                <input type="hidden" name="action" value="update_quantity">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" max="999" required style="width:80px; padding:6px; border-radius:6px; border:1px solid #ddd;">
                                <button type="submit" class="btn-primary">Update</button>
                            </form>
                        </td>
                        <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                        <td>
                            <a href="cart.php?action=remove&product_id=<?php echo $item['product_id']; ?>" class="btn-danger" onclick="return confirm('Hapus dari keranjang?')">🗑️ Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-summary">
                <h3>Total: Rp <?php echo number_format($total, 0, ',', '.'); ?></h3>
                <form method="POST">
                    <button type="submit" class="btn-checkout">✓ Lanjut ke Checkout</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
