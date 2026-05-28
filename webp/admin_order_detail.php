<?php
include 'db/koneksi.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user is admin
$check_admin = $mysqli->prepare("SELECT role FROM users WHERE id = ?");
$check_admin->bind_param("i", $user_id);
$check_admin->execute();
$admin_result = $check_admin->get_result()->fetch_assoc();

if (!$admin_result || $admin_result['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    header("Location: admin_orders.php");
    exit();
}

$order_id = intval($_GET['order_id']);

// Get order details with user info
$order_stmt = $mysqli->prepare("SELECT o.*, u.username, u.email FROM orders o 
                                JOIN users u ON o.user_id = u.id 
                                WHERE o.id = ?");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: admin_orders.php");
    exit();
}

// Get order items
$items_stmt = $mysqli->prepare("SELECT oi.*, p.name FROM order_items oi 
                               JOIN products p ON oi.product_id = p.id 
                               WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get payment info
$payment_stmt = $mysqli->prepare("SELECT * FROM payments WHERE order_id = ?");
$payment_stmt->bind_param("i", $order_id);
$payment_stmt->execute();
$payment = $payment_stmt->get_result()->fetch_assoc();

// Function to get payment method text
function getPaymentMethodText($method) {
    $methodMap = [
        'transfer_bank' => '🏦 Cash',
        'kartu_kredit' => '💳 Kartu Kredit',
        'e_wallet' => '📱 E-Wallet',
        'cicilan' => '📊 Cicilan 0%'
    ];
    return $methodMap[$method] ?? '-';
}

// Function to get payment status text
function getPaymentStatusText($status) {
    $statusMap = [
        'pending' => 'Menunggu Pembayaran',
        'completed' => 'Lunas',
        'failed' => 'Gagal',
        'cancelled' => 'Dibatalkan'
    ];
    return $statusMap[$status] ?? $status;
}

// Function to get Indonesian status text
function getIndonesianStatus($status) {
    $statusMap = [
        'pending' => 'Menunggu Pembayaran',
        'processing' => 'Diproses',
        'shipped' => 'Dikirim',
        'delivered' => 'Diterima',
        'cancelled' => 'Dibatalkan'
    ];
    return $statusMap[$status] ?? $status;
}

// Handle status change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status'])) {
    $new_status = htmlspecialchars($_POST['status']);
    $allowed_statuses = ['pending', 'processing', 'shipped', 'cancelled'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $update_stmt = $mysqli->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("si", $new_status, $order_id);
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Status pesanan berhasil diubah menjadi " . getIndonesianStatus($new_status);
                // Refresh order data
                header("Location: admin_order_detail.php?order_id=$order_id");
                exit();
            } else {
                $_SESSION['error'] = "Gagal mengubah status pesanan";
            }
            $update_stmt->close();
        }
    }
}

// Handle payment confirmation by admin (confirm uploaded proof)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
    // Ensure payment exists and there's a proof to confirm
    if ($payment && !empty($payment['proof_image']) && $payment['status'] === 'pending') {
        $confirm_stmt = $mysqli->prepare("UPDATE payments SET status = 'completed', updated_at = NOW() WHERE order_id = ?");
        if ($confirm_stmt) {
            $confirm_stmt->bind_param("i", $order_id);
            if ($confirm_stmt->execute()) {
                // Optionally update order status to processing if it's still pending
                $order_update = $mysqli->prepare("UPDATE orders SET status = 'processing' WHERE id = ? AND status = 'pending'");
                if ($order_update) {
                    $order_update->bind_param("i", $order_id);
                    $order_update->execute();
                }

                $_SESSION['success'] = "Pembayaran berhasil dikonfirmasi.";
                header("Location: admin_order_detail.php?order_id=$order_id");
                exit();
            } else {
                $_SESSION['error'] = "Gagal mengkonfirmasi pembayaran: " . $confirm_stmt->error;
            }
            $confirm_stmt->close();
        } else {
            $_SESSION['error'] = "Database error: " . $mysqli->error;
        }
    } else {
        $_SESSION['error'] = "Tidak ada bukti pembayaran yang dapat dikonfirmasi atau pembayaran sudah dikonfirmasi.";
    }
}

$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
if ($success) {
    unset($_SESSION['success']);
}
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
if ($error) {
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo $order['id']; ?> - Admin</title>
    <link rel="stylesheet" href="asset/order_detail.css">
    <style>
        .admin-detail {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .admin-detail p {
            margin: 8px 0;
            font-size: 14px;
        }
        .admin-detail strong {
            color: #798763;
            min-width: 150px;
            display: inline-block;
        }
        .status-control {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
        }
        .status-control p {
            margin: 10px 0;
            font-weight: 500;
        }
        .status-control select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin-top: 8px;
        }
        .status-control button {
            margin-top: 10px;
            padding: 10px 20px;
            background: #798763;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .status-control button:hover {
            background: #6a7555;
        }
        .customer-info {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #4caf50;
        }
        .customer-info p {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🛍️ HighmonkBoquet.id - Admin</h1>
            <div>
                <a href="admin_orders.php" class="btn-secondary">← Kembali</a>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Detail Pesanan #<?php echo $order['id']; ?></h2>

        <?php if ($success): ?>
            <p class="success">✓ <?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <p class="error">✕ <?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <!-- Customer Info -->
        <div class="customer-info">
            <h3>👤 Informasi Pelanggan</h3>
            <p><strong>Nama:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
        </div>

        <!-- Order Info -->
        <div class="order-info">
            <h3>📦 Informasi Pesanan</h3>
            <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
            <p><strong>Tanggal Pesanan:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
            <p><strong>Status:</strong> <span class="status status-<?php echo $order['status']; ?>"><?php echo getIndonesianStatus($order['status']); ?></span></p>
            <p><strong>Total:</strong> <span style="color: #d81b60; font-weight: bold; font-size: 18px;">Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></span></p>
            
            <?php if (isset($order['confirmed_received_at']) && $order['confirmed_received_at']): ?>
                <p><strong>Dikonfirmasi Diterima:</strong> <?php echo date('d/m/Y H:i', strtotime($order['confirmed_received_at'])); ?> ✓</p>
            <?php endif; ?>
        </div>

        <!-- Payment Info -->
        <?php if ($payment): ?>
        <div class="payment-info-section" style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #2196F3;">
            <h3>💳 Informasi Pembayaran</h3>
            <p><strong>Metode Pembayaran:</strong> <?php echo getPaymentMethodText($payment['payment_method']); ?></p>
            <p><strong>Jumlah:</strong> Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></p>
            <p><strong>Status Pembayaran:</strong> 
                <span class="payment-status status-<?php echo $payment['status']; ?>" style="display: inline-block; padding: 5px 10px; border-radius: 4px; font-weight: bold; 
                    <?php echo $payment['status'] === 'completed' ? 'background: #4caf50; color: white;' : 'background: #ff9800; color: white;'; ?>">
                    <?php echo getPaymentStatusText($payment['status']); ?>
                </span>
            </p>
            <p><strong>ID Transaksi:</strong> <?php echo htmlspecialchars($payment['transaction_id']); ?></p>
            <p><strong>Waktu Pembayaran:</strong> <?php echo date('d/m/Y H:i:s', strtotime($payment['created_at'])); ?></p>

            <?php if (!empty($payment['proof_image'])): ?>
                <?php $proof_path = 'pict/payment_proofs/' . $payment['proof_image']; ?>
                <p><strong>Bukti Pembayaran:</strong> <a href="<?php echo $proof_path; ?>" target="_blank">Lihat / Unduh</a></p>
                <?php if (preg_match('/\.(jpg|jpeg|png)$/i', $payment['proof_image'])): ?>
                    <p><img src="<?php echo $proof_path; ?>" alt="Bukti Pembayaran" style="max-width:260px; border:1px solid #ddd; padding:6px; border-radius:6px;"></p>
                <?php endif; ?>

                <?php if ($payment['status'] === 'pending'): ?>
                    <form method="POST" style="display:inline; margin-top:8px;">
                        <input type="hidden" name="action" value="confirm_payment">
                        <button type="submit" class="btn-primary" onclick="return confirm('Konfirmasi bukti pembayaran dan tandai sebagai Lunas?')">✓ Konfirmasi Pembayaran</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($payment['status'] === 'completed'): ?>
                <p style="margin-top: 10px;">
                    <a href="struk_pembayaran.php?order_id=<?php echo $order['id']; ?>" class="btn-primary" style="display: inline-block; padding: 8px 16px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px;">
                        🖨️ Lihat/Cetak Struk
                    </a>
                </p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="payment-info-section" style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ff9800;">
            <h3>💳 Informasi Pembayaran</h3>
            <p style="color: #cc6600;">Belum ada data pembayaran untuk pesanan ini.</p>
        </div>
        <?php endif; ?>

        <!-- Status Control -->
        <div class="status-control">
            <p>🔄 Ubah Status Pesanan:</p>
            <form method="POST" style="display: inline;">
                <select name="status" id="status-select">
                    <option value="">-- Pilih Status --</option>
                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Diproses</option>
                    <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                </select>
                <button type="submit" onclick="return confirm('Yakin ingin mengubah status pesanan?')">✓ Simpan Perubahan</button>
            </form>
        </div>

        <!-- Items -->
        <h3>📋 Daftar Item</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background: #f0f0f0; font-weight: bold;">
                    <td colspan="3" style="text-align: right; padding: 15px;">Total:</td>
                    <td style="padding: 15px;">Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
