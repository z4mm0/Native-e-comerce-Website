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

// Generate reference number
$reference_number = 'ORD-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . '-' . date('YmdHi', strtotime($order['created_at']));

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pesanan #<?php echo $order['id']; ?></title>
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

        .status-section {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #e9ecef;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-weight: bold;
            color: #495057;
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

        .btn-pdf {
            background: #dc3545;
            color: white;
        }

        .btn-pdf:hover {
            background: #c82333;
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
        <div class="receipt-title">📄 STRUK PESANAN</div>

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

        <!-- Order Status -->
        <div class="status-section">
            Status Pesanan: <?php echo getIndonesianStatus($order['status']); ?>
        </div>

        <div class="divider"></div>

        <!-- Footer Message -->
        <div class="footer-message">
            <strong>Terima Kasih!</strong><br>
            Pesanan Anda <?php echo strtolower(getIndonesianStatus($order['status'])); ?>.<br>
            Kami akan mengirimkan barang secepatnya.<br>
            <br>
            📱 Hubungi kami jika ada pertanyaan<br>
            Email: info@highmonkboquet.id
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn-print" onclick="window.print()">🖨️ Cetak Struk</button>
            <button class="btn-pdf" onclick="exportToPDF()">📄 Export PDF</button>
            <?php if (isset($is_admin) && $is_admin): ?>
                <a href="admin_order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn-back">← Kembali ke Detail Pesanan</a>
            <?php else: ?>
                <a href="order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn-back">← Kembali ke Detail Pesanan</a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        async function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const receipt = document.querySelector('.receipt-container');

            try {
                // Create canvas from receipt element
                const canvas = await html2canvas(receipt, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff'
                });

                // Create PDF
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                // Calculate dimensions to fit A4
                const imgWidth = 210; // A4 width in mm
                const pageHeight = 295; // A4 height in mm
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                let heightLeft = imgHeight;

                let position = 0;

                // Add first page
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                // Add additional pages if needed
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                // Save PDF
                pdf.save('struk_pesanan_<?php echo $order['id']; ?>.pdf');

            } catch (error) {
                alert('Error exporting PDF: ' + error.message);
                console.error('PDF export error:', error);
            }
        }

        // Optional: Auto-print on load (uncomment if needed)
        // window.addEventListener('load', function() {
        //     window.print();
        // });
    </script>
</body>
</html>
