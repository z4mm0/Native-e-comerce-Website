<?php
include 'db/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$result = $mysqli->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC");
$orders = $result->fetch_all(MYSQLI_ASSOC);

$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
if ($success) {
    unset($_SESSION['success']);
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Toko Baju</title>
    <link rel="stylesheet" href="asset/histori.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🛍️ HighmonkBoquet.id</h1>
            <div>
                <a href="dashboard_user.php" class="btn-secondary">← Belanja Lagi</a>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Riwayat Pesanan</h2>

        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="empty-box">
                <p>Anda belum membuat pesanan. <a href="dashboard_user.php">Mulai belanja sekarang</a></p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="status status-<?php echo $order['status']; ?>">
                                <?php echo getIndonesianStatus($order['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn-primary">Lihat Detail</a>
                        </td>
                    </tr>
                    
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
