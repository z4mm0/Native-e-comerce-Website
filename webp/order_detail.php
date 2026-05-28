<?php
include 'db/koneksi.php';

// Auto-migrate: Check if confirmed_received_at column exists, if not create it
$check_column = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'confirmed_received_at'");
if ($check_column->num_rows == 0) {
    // Add column if not exists
    $mysqli->query("ALTER TABLE orders ADD COLUMN confirmed_received_at TIMESTAMP NULL DEFAULT NULL AFTER status");
}

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

// Handle confirm delivery
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'confirm_delivery') {
    // Verify order status is 'shipped'
    $verify_stmt = $mysqli->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    if ($verify_stmt) {
        $verify_stmt->bind_param("ii", $order_id, $user_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $verify_order = $verify_result->fetch_assoc();
        
        if ($verify_order && $verify_order['status'] == 'shipped') {
            // Update order status dan confirmed_received_at
            $stmt = $mysqli->prepare("UPDATE orders SET status = 'delivered', confirmed_received_at = NOW() WHERE id = ? AND user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $order_id, $user_id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $_SESSION['success'] = "✓ Terima kasih! Konfirmasi penerimaan paket telah dikirim ke admin.";
                    } else {
                        $_SESSION['error'] = "Tidak ada perubahan yang dilakukan. Silakan refresh halaman.";
                    }
                } else {
                    $_SESSION['error'] = "Gagal mengkonfirmasi pesanan: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = "Database error: " . $mysqli->error;
            }
        } else {
            $_SESSION['error'] = "Pesanan harus dalam status 'Dikirim' untuk dikonfirmasi.";
        }
        $verify_stmt->close();
    } else {
        $_SESSION['error'] = "Database error: " . $mysqli->error;
    }
    header("Location: order_detail.php?order_id=$order_id");
    exit();
}

// Handle cancel order (user hanya bisa batal saat pending)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cancel_order') {
    // Cek status harus pending
    $check_stmt = $mysqli->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("ii", $order_id, $user_id);
        $check_stmt->execute();
        $order_check = $check_stmt->get_result()->fetch_assoc();
        
        if ($order_check && $order_check['status'] == 'pending') {
            $stmt = $mysqli->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $order_id, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "✓ Pesanan telah dibatalkan. Anda dapat membuat pesanan baru kapan saja.";
                    header("Location: order_detail.php?order_id=$order_id");
                    exit();
                }
            }
        }
    }
}

// Get order
$result = $mysqli->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$result->bind_param("ii", $order_id, $user_id);
$result->execute();
$order = $result->get_result()->fetch_assoc();

if (!$order) {
    header("Location: order_history.php");
    exit();
}
$result = $mysqli->prepare("SELECT oi.*, p.name FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE oi.order_id = ?");
$result->bind_param("i", $order_id);
$result->execute();
$items = $result->get_result()->fetch_all(MYSQLI_ASSOC);

// Get payment info (if any)
$payment_stmt = $mysqli->prepare("SELECT * FROM payments WHERE order_id = ?");
if ($payment_stmt) {
    $payment_stmt->bind_param("i", $order_id);
    $payment_stmt->execute();
    $payment = $payment_stmt->get_result()->fetch_assoc();
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

$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
if ($success) {
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo $order['id']; ?> - Toko Baju</title>
    <link rel="stylesheet" href="asset/order_detail.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🛍️ HighmonkBoquet.id</h1>
            <div>
                <a href="order_history.php" class="btn-secondary">← Kembali</a>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Detail Pesanan #<?php echo $order['id']; ?></h2>

        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="order-info">
            <p><strong>Status:</strong> <span class="status status-<?php echo $order['status']; ?>"><?php echo getIndonesianStatus($order['status']); ?></span></p>
            <p><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
            <p><strong>Total:</strong> Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></p>

            <!-- QRIS Code Display -->
            <div style="margin: 15px 0; text-align: center; padding: 15px; background: #f9f9f9; border-radius: 8px; border: 1px solid #ddd;">
                <h4 style="margin: 0 0 10px 0; color: #333;">📱 Pembayaran E-Wallet</h4>
                <p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Scan kode QR berikut menggunakan aplikasi e-wallet Anda:</p>
                <img src="pict/QRIS.jpeg" alt="QRIS Code" style="max-width: 250px; border: 2px solid #ddd; border-radius: 8px; margin: 10px 0;">
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #888;">GCash, OVO, Dana, LinkAja</p>
            </div>

            <?php if (!empty($payment) && !empty($payment['proof_image'])): ?>
                <?php $proof_path = 'pict/payment_proofs/' . $payment['proof_image']; ?>
                <p><strong>Bukti Pembayaran:</strong> <a href="<?php echo $proof_path; ?>" target="_blank">Lihat Bukti</a></p>
                <?php if (preg_match('/\.(jpg|jpeg|png)$/i', $payment['proof_image'])): ?>
                    <p><img src="<?php echo $proof_path; ?>" alt="Bukti Pembayaran" style="max-width:220px; border:1px solid #ddd; padding:6px; border-radius:6px;"></p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($payment) && $payment['status'] === 'completed'): ?>
                <p><a href="struk_pembayaran.php?order_id=<?php echo $order['id']; ?>" class="btn-primary" style="display: inline-block; margin-top: 10px;">🖨️ Lihat Struk Pembayaran</a></p>
            <?php endif; ?>
            <p><a href="struk_order.php?order_id=<?php echo $order['id']; ?>" class="btn-primary" style="display: inline-block; margin-top: 10px;">📄 Lihat Struk Pesanan</a></p>
            <div class="order-actions">
                <?php if ($order['status'] == 'pending'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="cancel_order">
                        <button type="submit" class="btn-danger" onclick="return confirm('Yakin ingin membatalkan pesanan ini? Tindakan ini tidak dapat dibatalkan.')">✕ Batalkan Pesanan</button>
                    </form>

                    <?php if (empty($payment) || (($payment['payment_method'] === 'transfer_bank' || $payment['payment_method'] === 'e_wallet') && $payment['status'] === 'pending')): ?>
                        <div style="display:inline-block; margin-left:12px; vertical-align:middle;">
                            <form method="POST" action="pembayaran.php?order_id=<?php echo $order['id']; ?>" enctype="multipart/form-data" id="uploadProofFormOD">
                                <input type="hidden" name="action" value="upload_proof">
                                <label for="proof_od" style="font-size:13px; display:block; margin-bottom:6px;">Unggah bukti pembayaran (JPG/PNG, ≤2MB):</label>
                                <input type="file" name="proof" id="proof_od" accept="image/jpeg,image/png" required style="display:inline-block;">
                                <button type="submit" class="btn-primary" style="margin-left:8px;">✓ Unggah</button>
                            </form>
                            <p id="proof_msg_od" style="margin:6px 0 0 0; font-size:13px; display:none;"></p>
                        </div>

                        <script>
                        (function(){
                            const input = document.getElementById('proof_od');
                            const btn = document.querySelector('#uploadProofFormOD button[type="submit"]');
                            const msg = document.getElementById('proof_msg_od');
                            const MAX = 2 * 1024 * 1024;
                            function v(){
                                if(!input) return;
                                const f = input.files && input.files[0];
                                if(!f){ btn.disabled=false; msg.style.display='none'; return; }
                                if(f.size>MAX){ btn.disabled=true; msg.style.display='block'; msg.className='error'; msg.textContent='Ukuran file terlalu besar (maks 2MB).'; return; }
                                if(!['image/jpeg','image/png'].includes(f.type)){ btn.disabled=true; msg.style.display='block'; msg.className='error'; msg.textContent='Format tidak didukung. Gunakan JPG/PNG.'; return; }
                                btn.disabled=false; msg.style.display='none';
                            }
                            input && input.addEventListener('change', v);
                            v();
                        })();
                        </script>
                    <?php endif; ?>
                <?php elseif ($order['status'] == 'processing'): ?>
                    <p class="info-message">📋 Pesanan Anda sedang kami proses. Tunggu kabar selanjutnya.</p>
                <?php elseif ($order['status'] == 'shipped'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="confirm_delivery">
                        <button type="submit" class="btn-success" onclick="return confirm('Konfirmasi pesanan telah diterima?')">✓ Konfirmasi Pesanan Diterima</button>
                    </form>
                <?php elseif ($order['status'] == 'delivered'): ?>
                    <p class="success-message">✓ Pesanan telah diterima dengan baik. Terima kasih telah berbelanja!</p>
                <?php elseif ($order['status'] == 'cancelled'): ?>
                    <p class="cancel-message">✕ Pesanan ini telah dibatalkan. Silakan buat pesanan baru jika ingin melanjutkan berbelanja.</p>
                <?php endif; ?>
            </div>
        </div>
        
</body>         
</html>