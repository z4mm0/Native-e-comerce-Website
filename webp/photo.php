<?php
include 'db/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get order_id from query parameter
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;

// Verify order belongs to user if order_id is provided
if ($order_id) {
    $verify_stmt = $mysqli->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        // Order not found or doesn't belong to user
        $order_id = null;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Foto - HighmonkBoquet.id</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .photo-container {
            text-align: center;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 15px 0;
        }
        h1 {
            color: #333;
            margin: 20px 0 10px 0;
        }
        .description {
            color: #666;
            font-size: 14px;
            margin-bottom: 25px;
        }
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 25px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #798763;
            color: white;
        }
        .btn-primary:hover {
            background-color: #6a7555;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background-color: #999;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #777;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .payment-status {
            background-color: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="photo-container">
        <h1>💳 QRIS E-Wallet</h1>
        
        <?php if ($order_id): ?>
            <div class="payment-status">
                ✓ Pesanan #<?php echo $order_id; ?> - Sedang Diproses Pembayaran
            </div>
        <?php endif; ?>
        
        <p class="description">Scan kode QRIS di bawah dengan aplikasi e-wallet Anda untuk melakukan pembayaran</p>
        
        <img src="pict/QRIS.jpeg" alt="QRIS E-Wallet" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22250%22 height=%22220%22><rect fill=%22%23f0f0f0%22 width=%22250%22 height=%22220%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-size=%2214%22>Gambar QRIS tidak tersedia</text></svg>'">
        
        <div class="button-group">
            <?php if ($order_id): ?>
                <a href="struk_pembayaran.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">
                    ✓ Pembayaran Selesai - Lihat Struk
                </a>
            <?php endif; ?>
            
            <a href="order_history.php" class="btn btn-secondary">
                ← Kembali ke Riwayat Pesanan
            </a>
        </div>
    </div>
</body>
</html>
