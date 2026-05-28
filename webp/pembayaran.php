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

// Get order
$result = $mysqli->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$result->bind_param("ii", $order_id, $user_id);
$result->execute();
$order = $result->get_result()->fetch_assoc();

if (!$order) {
    header("Location: order_history.php");
    exit();
}

// Check if payment already exists
$existing_payment = null;
$check_payment = $mysqli->prepare("SELECT * FROM payments WHERE order_id = ?");
if ($check_payment) {
    $check_payment->bind_param("i", $order_id);
    $check_payment->execute();
    $existing_payment = $check_payment->get_result()->fetch_assoc();
}

$error = '';
$success = '';
$upload_feedback = '';

// Handle payment submission and file upload actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Upload bukti transfer (separate action)
    if (isset($_POST['action']) && $_POST['action'] === 'upload_proof') {
        // Jika belum ada pembayaran, buat otomatis dengan metode transfer_bank
        if (!$existing_payment) {
            $transaction_id = 'TRX-' . date('YmdHis') . '-' . $order_id;
            $insert = $mysqli->prepare("INSERT INTO payments (order_id, user_id, amount, payment_method, status, transaction_id) VALUES (?, ?, ?, 'transfer_bank', 'pending', ?)");
            if ($insert) {
                $insert->bind_param("iids", $order_id, $user_id, $order['total'], $transaction_id);
                if ($insert->execute()) {
                    // Refresh payment data
                    $check_payment = $mysqli->prepare("SELECT * FROM payments WHERE order_id = ?");
                    if ($check_payment) {
                        $check_payment->bind_param("i", $order_id);
                        $check_payment->execute();
                        $existing_payment = $check_payment->get_result()->fetch_assoc();
                    }
                } else {
                    $error = "Gagal membuat data pembayaran: " . $insert->error;
                }
                $insert->close();
            } else {
                $error = "Database error: " . $mysqli->error;
            }
        }

        if (!$existing_payment) {
            $error = $error ?: "Tidak ada pembayaran ditemukan untuk pesanan ini.";
        } else {
            if (!isset($_FILES['proof'])) {
                $error = "Tidak ada file yang diunggah.";
            } elseif ($_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
                switch ($_FILES['proof']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error = "Ukuran file melebihi batas server. Coba file yang lebih kecil (≤ 2MB).";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $error = "Upload terputus. Silakan coba lagi.";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $error = "Folder sementara tidak ditemukan di server.";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $error = "Server gagal menyimpan file sementara (permission).";
                        break;
                    default:
                        $error = "Terjadi kesalahan saat upload (kode: " . $_FILES['proof']['error'] . ").";
                }
                error_log("Upload error for order $order_id: " . $_FILES['proof']['error']);
            } else {
                // Validate image content and size
                $image_info = @getimagesize($_FILES['proof']['tmp_name']);
                if ($image_info === false) {
                    $error = "File bukan gambar yang valid.";
                } else {
                    $file_type = $image_info['mime'];
                    $file_size = $_FILES['proof']['size'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];

                    if (!in_array($file_type, $allowed_types)) {
                        $error = "Format file tidak didukung. Gunakan JPG atau PNG.";
                    } elseif ($file_size > 2 * 1024 * 1024) {
                        $error = "Ukuran file terlalu besar (maks 2MB).";
                    } else {
                        $upload_dir = __DIR__ . '/pict/payment_proofs';
                        if (!is_dir($upload_dir)) {
                            if (!mkdir($upload_dir, 0755, true)) {
                                $error = "Gagal membuat folder untuk menyimpan bukti. Periksa permission direktori.";
                            }
                        }

                        if (empty($error) && !is_writable($upload_dir)) {
                            $error = "Direktori penyimpanan tidak dapat ditulis (writable). Hubungi admin.";
                        }

                        if (empty($error)) {
                            $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
                            $filename = 'proof_order_' . $order_id . '_' . time() . '.' . $ext;
                            $target_path = $upload_dir . DIRECTORY_SEPARATOR . $filename;

                            if (move_uploaded_file($_FILES['proof']['tmp_name'], $target_path)) {
                                @chmod($target_path, 0644);
                                $stmt = $mysqli->prepare("UPDATE payments SET proof_image = ?, status = 'completed', updated_at = NOW() WHERE order_id = ?");
                                if ($stmt) {
                                    $stmt->bind_param("si", $filename, $order_id);
                                    if ($stmt->execute()) {
                                        $success = "Bukti transaksi berhasil diunggah. Pembayaran telah dikonfirmasi.";

                                        // Update order status to processing for completed payments
                                        $order_stmt = $mysqli->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
                                        if ($order_stmt) {
                                            $order_stmt->bind_param("i", $order_id);
                                            $order_stmt->execute();
                                        }

                                        // Refresh payment data
                                        $check_payment = $mysqli->prepare("SELECT * FROM payments WHERE order_id = ?");
                                        if ($check_payment) {
                                            $check_payment->bind_param("i", $order_id);
                                            $check_payment->execute();
                                            $existing_payment = $check_payment->get_result()->fetch_assoc();
                                        }

                                        // Refresh order data
                                        $result = $mysqli->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
                                        if ($result) {
                                            $result->bind_param("ii", $order_id, $user_id);
                                            $result->execute();
                                            $order = $result->get_result()->fetch_assoc();
                                        }
                                    } else {
                                        $error = "Gagal menyimpan informasi bukti: " . $stmt->error;
                                        error_log("DB error saving proof for order $order_id: " . $stmt->error);
                                    }
                                    $stmt->close();
                                } else {
                                    $error = "Database error: " . $mysqli->error;
                                    error_log("DB prepare error for order $order_id: " . $mysqli->error);
                                }
                            } else {
                                $error = "Gagal memindahkan file ke tujuan. Periksa permission direktori.";
                                error_log("Failed to move uploaded file for order $order_id to $target_path");
                            }
                        }
                    }
                }
            }
        }
        $upload_feedback = $success ?: $error;
    } else {
        // Original payment submission handling
        $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
        $amount = $order['total'];
        
        // Validate payment method
        $valid_methods = ['transfer_bank', 'kartu_kredit', 'e_wallet', 'cicilan'];
        
        if (!in_array($payment_method, $valid_methods)) {
            $error = "Metode pembayaran tidak valid!";
        } else {
            // Create or update payment record
            if ($existing_payment) {
                // Update existing payment
                $stmt = $mysqli->prepare("UPDATE payments SET payment_method = ?, status = 'pending', updated_at = NOW() WHERE order_id = ?");
                if (!$stmt) {
                    $error = "Database error: " . $mysqli->error;
                } else {
                    $stmt->bind_param("si", $payment_method, $order_id);
                }
            } else {
                // Create new payment
                $stmt = $mysqli->prepare("INSERT INTO payments (order_id, user_id, amount, payment_method, status) VALUES (?, ?, ?, ?, 'pending')");
                if (!$stmt) {
                    $error = "Database error: " . $mysqli->error;
                } else {
                    $stmt->bind_param("iids", $order_id, $user_id, $amount, $payment_method);
                }
            }
            
            if (isset($stmt) && $stmt && $stmt->execute()) {
                // Generate transaction ID
                $transaction_id = 'TRX-' . date('YmdHis') . '-' . $order_id;
                
                // Update payment with transaction ID
                $update_stmt = $mysqli->prepare("UPDATE payments SET transaction_id = ? WHERE order_id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("si", $transaction_id, $order_id);
                    $update_stmt->execute();
                }

                // For e-wallet, keep status pending and redirect to order_detail for QRIS and proof upload
                if ($payment_method === 'e_wallet') {
                    $success = "Pembayaran E-Wallet dipilih. Silakan scan QRIS dan unggah bukti pembayaran.";
                    header("refresh:3;url=order_detail.php?order_id=" . $order_id);
                }
                
                // Refresh payment data
                $check_payment = $mysqli->prepare("SELECT * FROM payments WHERE order_id = ?");
                if ($check_payment) {
                    $check_payment->bind_param("i", $order_id);
                    $check_payment->execute();
                    $existing_payment = $check_payment->get_result()->fetch_assoc();
                }
                
                // Refresh order data
                $result = $mysqli->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
                if ($result) {
                    $result->bind_param("ii", $order_id, $user_id);
                    $result->execute();
                    $order = $result->get_result()->fetch_assoc();
                }
            } else {
                if (isset($stmt) && $stmt) {
                    $error = "Gagal memproses pembayaran: " . $stmt->error;
                } else {
                    $error = "Terjadi kesalahan database. Pastikan tabel pembayaran sudah dibuat.";
                }
            }
        }
    }
}

// Get order items for display
$items_result = $mysqli->query("SELECT oi.*, p.name FROM order_items oi 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE oi.order_id = $order_id");
$items = $items_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Pesanan #<?php echo $order['id']; ?> - Toko Baju</title>
    <link rel="stylesheet" href="asset/style.css">
    <link rel="stylesheet" href="asset/payment.css">
</head>
<body>
    <nav class="navbar">
        <div class="container navbar-content">
            <h1>🛍️ HighmonkBoquet.id</h1>
            <div class="nav-buttons">
                <a href="order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn-secondary">← Kembali</a>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>💳 Pembayaran Pesanan #<?php echo $order['id']; ?></h2>

        <?php if ($error): ?>
            <p class="error">⚠️ <?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success">✓ <?php echo htmlspecialchars($success); ?></p>
            <?php if (isset($existing_payment) && $existing_payment && $existing_payment['payment_method'] === 'transfer_bank'): ?>
                <p><a href="order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn-primary">✓ Pembayaran Selesai — Unggah Bukti</a></p>
            <?php endif; ?>
        <?php endif; ?>

        <div class="payment-container">
          
            <div class="order-summary">
                <h3>📋 Ringkasan Pesanan</h3>
                <div class="order-info">
                    <div class="info-item">
                        <div class="info-icon">🆔</div>
                        <div class="info-content">
                            <div class="info-label">Order ID</div>
                            <div class="info-value">#<?php echo $order['id']; ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">📅</div>
                        <div class="info-content">
                            <div class="info-label">Tanggal Pesanan</div>
                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">📊</div>
                        <div class="info-content">
                            <div class="info-label">Status Pesanan</div>
                            <div class="info-value"><span class="status status-<?php echo $order['status']; ?>"><?php echo getIndonesianStatus($order['status']); ?></span></div>
                        </div>
                    </div>
                </div>

                <h4>📦 Detail Item</h4>
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
                            <td><strong>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="total-section">
                    <h3 class="total-amount">💰 Total: Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></h3>
                </div>
            </div>

            <!-- Formulir Pembayaran -->
            <div class="payment-form">
                <h3>💳 Metode Pembayaran</h3>
                
                <?php if ($existing_payment): ?>
                    <div class="payment-info">
                        <p><strong>Status Pembayaran:</strong> <span class="status status-<?php echo $existing_payment['status']; ?>"><?php echo ucfirst($existing_payment['status']); ?></span></p>
                        <p><strong>Metode:</strong> 
                            <?php 
                                switch ($existing_payment['payment_method']) {
                                    case 'transfer_bank':
                                        echo '🏦 Transfer Bank';
                                        break;
                                    case 'kartu_kredit':
                                        echo '💳 Kartu Kredit';
                                        break;
                                    case 'e_wallet':
                                        echo '📱 E-Wallet';
                                        break;
                                    case 'cicilan':
                                        echo '📊 Cicilan 0%';
                                        break;
                                    default:
                                        echo 'Tidak Diketahui';
                                }
                            ?></p>
                        <p><strong>ID Transaksi:</strong> <?php echo htmlspecialchars($existing_payment['transaction_id']); ?></p>
                        <p><strong>Waktu:</strong> <?php echo date('d/m/Y H:i:s', strtotime($existing_payment['created_at'])); ?></p>

                        <?php if (!empty($existing_payment['proof_image'])): ?>
                            <?php $proof_path = 'pict/payment_proofs/' . $existing_payment['proof_image']; ?>
                            <p><strong>Bukti Pembayaran:</strong> <a href="<?php echo $proof_path; ?>" target="_blank">Lihat Bukti</a></p>
                            <?php if (preg_match('/\.(jpg|jpeg|png)$/i', $existing_payment['proof_image'])): ?>
                                <p><img src="<?php echo $proof_path; ?>" alt="Bukti Pembayaran" style="max-width:220px; border:1px solid #ddd; padding:6px; border-radius:6px;"></p>
                            <?php endif; ?>
                        <?php elseif ($existing_payment['payment_method'] === 'transfer_bank' && $existing_payment['status'] === 'pending'): ?>
                            <div style="margin-top:10px;">
                                <form method="POST" enctype="multipart/form-data" id="uploadProofForm">
                                    <input type="hidden" name="action" value="upload_proof">
                                    <label for="proof">Unggah bukti transfer (JPG/PNG, maks 2MB):</label><br>
                                    <input type="file" name="proof" id="proof" accept="image/jpeg,image/png" required style="margin-top:8px;"><br>
                                    <button type="submit" class="btn-primary" id="uploadProofBtn" style="margin-top:8px;">✓ Unggah Bukti Pembayaran</button>
                                </form>

                                <?php if (!empty($upload_feedback)): ?>
                                    <p id="proof_message" class="<?php echo ($success ? 'success' : 'error'); ?>" style="margin-top:8px;"><?php echo htmlspecialchars($upload_feedback); ?></p>
                                <?php else: ?>
                                    <p id="proof_message" style="display:none; margin-top:8px;"></p>
                                <?php endif; ?>

                                <script>
                                    (function() {
                                        const input = document.getElementById('proof');
                                        const btn = document.getElementById('uploadProofBtn');
                                        const msg = document.getElementById('proof_message');
                                        const MAX_SIZE = 2 * 1024 * 1024; // 2MB
                                        function validate() {
                                            if (!input || !btn || !msg) return;
                                            const files = input.files;
                                            if (!files || files.length === 0) {
                                                btn.disabled = false;
                                                msg.style.display = 'none';
                                                return;
                                            }
                                            const file = files[0];
                                            if (file.size > MAX_SIZE) {
                                                btn.disabled = true;
                                                msg.style.display = 'block';
                                                msg.className = 'error';
                                                msg.textContent = 'Ukuran file terlalu besar (maks 2MB).';
                                                return;
                                            }
                                            const allowed = ['image/jpeg','image/png'];
                                            if (!allowed.includes(file.type)) {
                                                btn.disabled = true;
                                                msg.style.display = 'block';
                                                msg.className = 'error';
                                                msg.textContent = 'Format file tidak didukung. Gunakan JPG atau PNG.';
                                                return;
                                            }
                                            btn.disabled = false;
                                            msg.style.display = 'none';
                                        }
                                        input && input.addEventListener('change', validate);
                                        // initial validate
                                        validate();
                                    })();
                                </script>

                                <?php elseif (!$existing_payment && $order['status'] === 'pending'): ?>
                                    <div style="margin-top:10px; background:#fff7e6; padding:10px; border-radius:6px;">
                                        <p style="margin:0 0 8px 0;">Belum ada data pembayaran untuk pesanan ini. Mengunggah bukti akan otomatis membuat data pembayaran dengan metode <strong>Transfer Bank</strong> dan status <strong>Menunggu Pembayaran</strong>.</p>

                                        <form method="POST" enctype="multipart/form-data" id="uploadProofForm2">
                                            <input type="hidden" name="action" value="upload_proof">
                                            <label for="proof2">Unggah bukti transfer (JPG/PNG, maks 2MB):</label><br>
                                            <input type="file" name="proof" id="proof2" accept="image/jpeg,image/png" required style="margin-top:8px;"><br>
                                            <button type="submit" class="btn-primary" id="uploadProofBtn2" style="margin-top:8px;">✓ Unggah Bukti Pembayaran</button>
                                        </form>

                                        <p id="proof_message2" style="display:none; margin-top:8px;"></p>

                                        <script>
                                            (function() {
                                                const input = document.getElementById('proof2');
                                                const btn = document.getElementById('uploadProofBtn2');
                                                const msg = document.getElementById('proof_message2');
                                                const MAX_SIZE = 2 * 1024 * 1024; // 2MB
                                                function validate() {
                                                    if (!input || !btn || !msg) return;
                                                    const files = input.files;
                                                    if (!files || files.length === 0) {
                                                        btn.disabled = false;
                                                        msg.style.display = 'none';
                                                        return;
                                                    }
                                                    const file = files[0];
                                                    if (file.size > MAX_SIZE) {
                                                        btn.disabled = true;
                                                        msg.style.display = 'block';
                                                        msg.className = 'error';
                                                        msg.textContent = 'Ukuran file terlalu besar (maks 2MB).';
                                                        return;
                                                    }
                                                    const allowed = ['image/jpeg','image/png'];
                                                    if (!allowed.includes(file.type)) {
                                                        btn.disabled = true;
                                                        msg.style.display = 'block';
                                                        msg.className = 'error';
                                                        msg.textContent = 'Format file tidak didukung. Gunakan JPG atau PNG.';
                                                        return;
                                                    }
                                                    btn.disabled = false;
                                                    msg.style.display = 'none';
                                                }
                                                input && input.addEventListener('change', validate);
                                                validate();
                                            })();
                                        </script>
                                    </div>
                                <?php endif; ?>
                <?php endif; ?>

                <?php if (!$existing_payment): ?>
                <form method="POST" class="payment-method-form">
                    <fieldset>
                        <legend>Pilih Metode Pembayaran:</legend>

                        <div class="method-option">
                            <input type="radio" id="transfer_bank" name="payment_method" value="transfer_bank" required>
                        </div>
                        <div class="method-option">
                            <input type="radio" id="e_wallet" name="payment_method" value="e_wallet" required>
                            <label for="e_wallet">
                                <strong>📱 E-Wallet</strong>
                                <span class="method-desc">GCash, OVO, Dana, LinkAja</span>
                                <span class="method-info">Pembayaran instan</span>
                            </label>
                        </div>
                    </fieldset>

                    <button type="submit" class="btn-primary">✓ Lanjut Pembayaran</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
