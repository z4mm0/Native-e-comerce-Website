<?php
include 'db/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    header("Location: order_history.php");
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Get order details - allow admin or owner to view
$order_stmt = $mysqli->prepare("SELECT o.*, u.username, u.email FROM orders o 
                                JOIN users u ON o.user_id = u.id 
                                WHERE o.id = ? AND (o.user_id = ? OR ? = (SELECT id FROM users WHERE id = ? AND role = 'admin'))");
$order_stmt->bind_param("iiii", $order_id, $user_id, $user_id, $user_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: order_history.php");
    exit();
}

// Get order items
$items_stmt = $mysqli->prepare("SELECT oi.*, p.name FROM order_items oi 
                               JOIN products p ON oi.product_id = p.id 
                               WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check whether current user is admin so we can render correct back-link
$role_stmt = $mysqli->prepare("SELECT role FROM users WHERE id = ?");
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_res = $role_stmt->get_result()->fetch_assoc();
$is_admin = ($role_res && isset($role_res['role']) && $role_res['role'] === 'admin');
$role_stmt->close();

// Get payment info
$payment_stmt = $mysqli->prepare("SELECT * FROM payments WHERE order_id = ?");
$payment_stmt->bind_param("i", $order_id);
$payment_stmt->execute();
$payment = $payment_stmt->get_result()->fetch_assoc();

// Function to get payment method text
function getPaymentMethodText($method) {
    $methodMap = [
        'transfer_bank' => 'Cash / Transfer Bank',
        'kartu_kredit' => 'Kartu Kredit',
        'e_wallet' => 'E-Wallet',
        'cicilan' => 'Cicilan 0%'
    ];
    return $methodMap[$method] ?? 'Tidak Diketahui';
}

// Generate reference number
$reference_number = 'INV-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . '-' . date('YmdHi', strtotime($order['created_at']));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #<?php echo $order['id']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }

        .receipt-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
        }

        .store-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #798763;
        }

        .store-info {
            font-size: 12px;
            color: #666;
            line-height: 1.5;
        }

        .receipt-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0 15px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .info-section {
            margin-bottom: 15px;
            font-size: 13px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }

        .info-label {
            font-weight: bold;
            width: 40%;
            word-break: break-word;
        }

        .info-value {
            text-align: right;
            width: 60%;
            word-break: break-all;
        }

        .divider {
            border-top: 1px dashed #999;
            margin: 15px 0;
        }

        .items-table {
            width: 100%;
            margin: 15px 0;
            font-size: 12px;
            border-collapse: collapse;
        }

        .items-table thead {
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
        }

        .items-table th {
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
        }

        .items-table td {
            padding: 8px 5px;
            border-bottom: 1px dashed #ddd;
        }

        .item-name {
            font-weight: 500;
        }

        .item-qty {
            text-align: center;
            width: 50px;
        }

        .item-price {
            text-align: right;
            width: 80px;
        }

        .item-total {
            text-align: right;
            font-weight: bold;
        }

        .total-section {
            margin: 15px 0;
            padding: 10px 0;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 13px;
        }

        .total-label {
            font-weight: normal;
        }

        .total-amount {
            text-align: right;
            font-weight: bold;
        }

        .grand-total {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 18px;
            font-weight: bold;
            padding: 10px 0;
            border-top: 1px solid #333;
        }

        .payment-status {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #d4edda;
            border: 1px solid #28a745;
            border-radius: 4px;
            font-weight: bold;
            color: #155724;
        }

        .footer-message {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
            border-top: 1px dashed #999;
            padding-top: 15px;
            line-height: 1.6;
        }

        .qr-placeholder {
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px dashed #ddd;
            border-radius: 4px;
            font-size: 12px;
            color: #999;
        }

        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            print-color-adjust: none;
            -webkit-print-color-adjust: none;
        }

        .action-buttons a,
        .action-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn-print {
            background: #798763;
            color: white;
        }

        .btn-print:hover {
            background: #6a7555;
        }

        .btn-back {
            background: #999;
            color: white;
        }

        .btn-back:hover {
            background: #777;
        }

        .btn-download {
            background: #2196F3;
            color: white;
        }

        .btn-download:hover {
            background: #1976D2;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt-container {
                max-width: 80mm;
                margin: 0;
                box-shadow: none;
                padding: 10mm;
            }

            .action-buttons {
                display: none;
            }

            a {
                color: black;
                text-decoration: none;
            }
        }

        @media (max-width: 600px) {
            .receipt-container {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons a,
            .action-buttons button {
                width: 100%;
            }
        }

        .receipt-no {
            font-size: 11px;
            text-align: center;
            color: #666;
            margin: 10px 0;
        }

        .customer-info-box {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 12px;
        }

        .customer-info-box p {
            margin: 5px 0;
        }

        .invoice-status {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 15px 0;
            padding: 10px;
            background: #d4edda;
            border-radius: 4px;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="store-name">🛍️ HighmonkBoquet.id</div>
            <div class="store-info">
                Toko Fashion Online<br>
                Email: info@highmonkboquet.id<br>
                Terima Kasih Telah Berbelanja!
            </div>
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">📄 STRUK PEMBAYARAN</div>

        <!-- Receipt Number -->
        <div class="receipt-no">
            No. Struk: <?php echo htmlspecialchars($reference_number); ?><br>
            Order ID: #<?php echo $order['id']; ?>
        </div>

        <!-- Customer Info -->
        <div class="info-section customer-info-box">
            <strong>📦 Data Pembelian</strong>
            <p>Nama: <?php echo htmlspecialchars($order['username']); ?></p>
            <p>Email: <?php echo htmlspecialchars($order['email']); ?></p>
            <p>Tanggal: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
        </div>

        <div class="divider"></div>

        <!-- Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="item-name">Produk</th>
                    <th class="item-qty">Qty</th>
                    <th class="item-price">Harga</th>
                    <th class="item-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="item-name"><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="item-qty"><?php echo $item['quantity']; ?></td>
                    <td class="item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                    <td class="item-total">Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Total Section -->
        <div class="total-section">
            <div class="grand-total">
                <span>TOTAL:</span>
                <span>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></span>
            </div>
        </div>

        <!-- Payment Info -->
        <?php if ($payment): ?>
        <div class="info-section">
            <strong>💳 Informasi Pembayaran</strong>
            <div class="info-row">
                <span class="info-label">Metode:</span>
                <span class="info-value"><?php echo getPaymentMethodText($payment['payment_method']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Jumlah:</span>
                <span class="info-value">Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">ID Transaksi:</span>
                <span class="info-value"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Waktu:</span>
                <span class="info-value"><?php echo date('d/m/Y H:i:s', strtotime($payment['created_at'])); ?></span>
            </div>
        </div>

        <!-- Payment Status -->
        <?php if ($payment['status'] === 'completed'): ?>
        <div class="invoice-status">
            ✓ PEMBAYARAN DITERIMA
        </div>
        <?php else: ?>
        <div class="payment-status" style="background: #fff3cd; border-color: #ffc107; color: #cc6600;">
            ⏳ Status: <?php echo ucfirst(htmlspecialchars($payment['status'])); ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="divider"></div>

        <!-- Footer Message -->
        <div class="footer-message">
            <strong>Terima Kasih!</strong><br>
            Pesanan Anda sedang diproses.<br>
            Kami akan mengirimkan barang secepatnya.<br>
            <br>
            📱 Hubungi kami jika ada pertanyaan<br>
            Email: info@highmonkboquet.id
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn-print" onclick="window.print()">🖨️ Cetak Struk</button>
            <?php if (isset($is_admin) && $is_admin): ?>
                <a href="admin_order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn-back">← Kembali ke Detail Pesanan</a>
            <?php else: ?>
                <a href="order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn-back">← Kembali ke Detail Pesanan</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Optional: Auto-print on load (uncomment if needed)
        // window.addEventListener('load', function() {
        //     window.print();
        // });
    </script>
</body>
</html>
