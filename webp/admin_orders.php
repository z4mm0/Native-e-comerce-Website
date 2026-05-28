<?php
include 'db/koneksi.php';

// Auto-migrate: Check if confirmed_received_at column exists, if not create it
$check_column = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'confirmed_received_at'");
if ($check_column->num_rows == 0) {
    // Add column if not exists
    $mysqli->query("ALTER TABLE orders ADD COLUMN confirmed_received_at TIMESTAMP NULL DEFAULT NULL AFTER status");
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
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

// Get all orders dengan user info dan payment method
$result = $mysqli->query("SELECT o.*, u.username, u.email, p.payment_method FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         LEFT JOIN payments p ON o.id = p.order_id
                         ORDER BY o.created_at DESC");
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Function to get payment method text
function getPaymentMethodText($method) {
    $methodMap = [
        'transfer_bank' => '🏦 Cash',
        'e_wallet' => '📱 E-Wallet',
       
    ];
    return $methodMap[$method] ?? '-';
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    // Admin bisa update status ke: pending, processing, shipped, dan cancelled
    // Status "delivered" (diterima) hanya bisa diubah oleh user melalui konfirmasi
    $allowed_status = array('pending', 'processing', 'shipped', 'cancelled');
    if (in_array($status, $allowed_status)) {
        $stmt = $mysqli->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        if ($stmt->execute()) {
            header("Refresh: 1; url=admin_orders.php");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - Admin</title>

    <style>
        .auto-refresh-indicator {
            display: inline-block;
            padding: 5px 12px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 20px;
        }
        .auto-refresh-indicator.active::before {
            content: "● ";
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0.3; }
        }
        /* --- Khusus Halaman Admin Orders --- */

:root {
    --primary-green: #798763;
    --soft-pink: #fce4ec;
    --bright-pink: #ff85a2;
    --accent-rose: #d81b60;
    --white: #ffffff;
}

/* Pengaturan Dropdown Status - Lebih Glowy */
.table select,
.status-select {
    padding: 10px 35px 10px 15px;
    border-radius: 12px;
    border: 2px solid var(--soft-pink);
    background-color: var(--white);
    font-family: 'Quicksand', sans-serif;
    font-size: 13px;
    font-weight: 700;
    color: #555;
    cursor: pointer;
    appearance: none;
    /* Custom Arrow Warna Pink */
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ff85a2' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
    box-shadow: 0 4px 6px rgba(255, 133, 162, 0.05);
}

.table select:hover,
.status-select:hover {
    border-color: var(--bright-pink);
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(255, 133, 162, 0.15);
}

/* Warna Status Spesifik */
option[value="pending"] { color: #f39c12; background: #fff; }
option[value="shipped"] { color: var(--primary-green); background: #fff; }
option[value="delivered"] { color: #2ecc71; background: #fff; }
option[value="cancelled"] { color: var(--accent-rose); background: #fff; }

/* Styling Tabel Admin - Mewah */
.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
    margin: 20px 0;
}

.table th {
    background: linear-gradient(135deg, var(--primary-green) 0%, #94a381 100%);
    color: white;
    font-weight: 700;
    padding: 18px 20px;
    text-align: left;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 1px;
    border: none;
}

.table th:first-child { border-radius: 15px 0 0 15px; }
.table th:last-child { border-radius: 0 15px 15px 0; }

.table td {
    padding: 15px 20px;
    background: white;
    color: #555;
    font-size: 14px;
    vertical-align: middle;
    border-top: 1px solid var(--soft-pink);
    border-bottom: 1px solid var(--soft-pink);
    transition: 0.3s;
}

.table tr td:first-child { border-left: 1px solid var(--soft-pink); border-radius: 15px 0 0 15px; }
.table tr td:last-child { border-right: 1px solid var(--soft-pink); border-radius: 0 15px 15px 0; }

.table tr:hover td {
    background-color: #fff9fb; /* Sentuhan pink sangat tipis */
    border-color: var(--bright-pink);
}

/* Tombol Update Status - Gradasi */
.table td .btn-primary {
    background: linear-gradient(45deg, var(--primary-green), #94a381);
    color: white;
    padding: 8px 18px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 10px rgba(121, 135, 99, 0.2);
}

.table td .btn-primary:hover {
    background: linear-gradient(45deg, var(--bright-pink), var(--accent-rose));
    transform: scale(1.05);
    box-shadow: 0 6px 15px rgba(216, 27, 96, 0.2);
}

/* Badge Status Konfirmasi */
.confirmed-badge {
    background-color: var(--soft-pink);
    color: var(--accent-rose);
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 11px;
    border: 1px solid var(--bright-pink);
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.confirmed-badge::before { content: '🌸'; font-size: 10px; }

/* --- Khusus Halaman Detail Pesanan --- */
.order-container {
    max-width: 900px;
    margin: 40px auto;
    background: white;
    padding: 40px;
    border-radius: 30px;
    box-shadow: 0 20px 40px rgba(121, 135, 99, 0.1);
    border: 2px solid var(--soft-pink);
    position: relative;
    overflow: hidden;
}

/* Dekorasi pojok container */
.order-container::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 100px;
    height: 100px;
    background: var(--soft-pink);
    border-radius: 50%;
    opacity: 0.5;
}

.section p strong {
    color: var(--accent-rose);
}

.total-amount {
    background: var(--soft-pink);
    padding: 20px;
    border-radius: 20px;
    font-size: 24px;
    font-weight: 700;
    color: var(--primary-green);
    text-align: right;
    margin-top: 30px;
    border-right: 8px solid var(--bright-pink);
}
/* Menghilangkan underline pada link navbar */
.dashboard-nav a {
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Memperbaiki tampilan Selamat Datang */
.dashboard-nav span {
    margin: 0 15px;
    color: var(--primary-green);
    font-weight: 600;
    font-size: 14px;
}

/* Menyelaraskan Header Dashboard */
.navbar h1 {
    font-size: 22px;
    margin: 0;
    color: var(--primary-green);
}

/* Responsivitas untuk Container */
.container {
    max-width: 1200px; /* Admin perlu layar lebih lebar */
    margin: 0 auto;
    padding: 20px;
}
.total-amount span {
    font-size: 38px;
    font-weight: 800;
    color: var(--accent-rose);
    display: block;
}

.btn-back, .btn-secondary {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 25px;
    background: var(--primary-green);
    color: white;
    text-decoration: none;
    font-weight: 700;
    border-radius: 12px;
    transition: 0.3s;
    border: none;
}

.btn-back:hover, .btn-secondary:hover {
    background: var(--bright-pink);
    transform: translateX(-5px);
    box-shadow: 0 5px 15px rgba(255, 133, 162, 0.3);
}

/* Responsif */
@media (max-width: 992px) {
    .table {
        display: block;
        overflow-x: auto;
    }
}
/* --- Penyesuaian Elemen Dashboard di Gambar --- */

/* Header & Judul */
h1, .admin-panel-header {
    font-family: 'Poppins', sans-serif;
    color: var(--primary-green);
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 25px;
}

/* Navbar Admin (Dashboard & Logout) */
.dashboard-nav {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
    background: white;
    padding: 15px 25px;
    border-radius: 20px;
    box-shadow: 0 10px 20px rgba(121, 135, 99, 0.05);
}

/* Tombol Dashboard */
.btn-dashboard {
    background: var(--primary-green);
    color: white;
    padding: 10px 20px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    transition: 0.3s;
}

.btn-dashboard:hover {
    background: var(--bright-pink);
    transform: translateY(-2px);
}

/* Tombol Logout - Sentuhan Pink */
.btn-logout {
    background: var(--soft-pink);
    color: var(--accent-rose);
    padding: 10px 20px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    border: 1px solid var(--bright-pink);
    transition: 0.3s;
}

.btn-logout:hover {
    background: var(--accent-rose);
    color: white;
    box-shadow: 0 5px 15px rgba(216, 27, 96, 0.2);
}

/* Badge Auto-refresh */
.auto-refresh-badge {
    background: #eef2e6;
    color: var(--primary-green);
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid var(--primary-green);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-left: 10px;
}

.auto-refresh-badge::before {
    content: '';
    width: 6px;
    height: 6px;
    background: #2ecc71;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(0.95); opacity: 0.7; }
    70% { transform: scale(1.5); opacity: 0; }
    100% { transform: scale(0.95); opacity: 0; }
}

/* Teks "Belum ada pesanan" agar lebih cantik */
.empty-table-msg {
    text-align: center;
    padding: 40px;
    color: #999;
    font-style: italic;
    background: white;
    border-radius: 15px;
    margin-top: 10px;
    border: 2px dashed var(--soft-pink);
}
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🛍️ HighmonkBoquet.id - Admin Panel</h1>
            <div>
                <a href="dashboard_admin.php" class="btn-secondary">Dashboard</a>
                <span>Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>📋 Manajemen Pesanan Pelanggan
            <span class="auto-refresh-indicator active">Auto-refresh aktif</span>
        </h2>

        <table class="table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Pelanggan</th>
                    <th>Email</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Metode Pembayaran</th>
                    <th>Status</th>
                    <th>Dikonfirmasi Diterima</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="orders-table-body">
                <?php foreach ($orders as $order): ?>
                <tr data-order-id="<?php echo $order['id']; ?>">
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                    <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                    <td><?php echo getPaymentMethodText($order['payment_method']); ?></td>
                    <td>
                        <?php if ($order['status'] != 'delivered'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" onchange="if(confirm('Ubah status menjadi ' + this.options[this.selectedIndex].text + '?')) { this.form.submit(); } else { this.value = '<?php echo $order['status']; ?>'; }" class="status-select">
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Diproses</option>
                                    <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                                </select>
                            </form>
                        <?php else: ?>
                            <span class="status status-<?php echo $order['status']; ?>">
                                <?php echo getIndonesianStatus($order['status']); ?>
                                <?php if ($order['status'] == 'delivered'): ?>
                                    <small>(Dikonfirmasi user)</small>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($order['confirmed_received_at']) && $order['confirmed_received_at']): ?>
                            <span class="confirmed-badge">✓ <?php echo date('d/m/Y H:i', strtotime($order['confirmed_received_at'])); ?></span>
                        <?php else: ?>
                            <span class="not-confirmed">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="admin_order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn-primary">Detail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($orders)): ?>
            <p>Belum ada pesanan.</p>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh data orders setiap 5 detik
        const REFRESH_INTERVAL = 5000; // 5 detik
        
        function updateOrdersTable() {
            fetch('api_get_orders.php')
                .then(response => response.json())
                .then(orders => {
                    const tbody = document.getElementById('orders-table-body');
                    
                    orders.forEach(order => {
                        const row = tbody.querySelector(`tr[data-order-id="${order.id}"]`);
                        if (row) {
                            // Update status
                            const statusCell = row.querySelectorAll('td')[5];
                            if (order.can_edit_status) {
                                statusCell.innerHTML = `<form method="POST" style="display: inline;">
                                    <input type="hidden" name="order_id" value="${order.id}">
                                    <select name="status" onchange="if(confirm('Ubah status menjadi ' + this.options[this.selectedIndex].text + '?')) { this.form.submit(); } else { this.value = '${order.status}'; }" class="status-select">
                                        <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Menunggu Pembayaran</option>
                                        <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>Diproses</option>
                                        <option value="shipped" ${order.status === 'shipped' ? 'selected' : ''}>Dikirim</option>
                                        <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Dibatalkan</option>
                                    </select>
                                </form>`;
                            } else {
                                statusCell.innerHTML = `<span class="status status-${order.status}">${order.status_indonesia}${order.status === 'delivered' ? '<br><small>(Dikonfirmasi user)</small>' : ''}</span>`;
                            }
                            
                            // Update confirmed received
                            const confirmedCell = row.querySelectorAll('td')[6];
                            if (order.confirmed_received_at) {
                                confirmedCell.innerHTML = `<span class="confirmed-badge">${order.confirmed_text}</span>`;
                            } else {
                                confirmedCell.innerHTML = `<span class="not-confirmed">-</span>`;
                            }
                        }
                    });
                })
                .catch(error => console.error('Error fetching orders:', error));
        }
        
        // Update tabel setiap 5 detik
        setInterval(updateOrdersTable, REFRESH_INTERVAL);
    </script>
</body>
</html>
