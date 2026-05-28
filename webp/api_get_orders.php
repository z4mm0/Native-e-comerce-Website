<?php
include 'db/koneksi.php';
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
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
        'kartu_kredit' => '💳 Kartu Kredit',
        'e_wallet' => '📱 E-Wallet',
        'cicilan' => '📊 Cicilan 0%'
    ];
    return $methodMap[$method] ?? '-';
}

// Format data untuk JSON
$formatted_orders = [];
foreach ($orders as $order) {
    // Admin bisa edit status jika bukan "delivered"
    $can_edit_status = ($order['status'] != 'delivered');
    $formatted_orders[] = [
        'id' => $order['id'],
        'username' => $order['username'],
        'email' => $order['email'],
        'tanggal' => date('d/m/Y H:i', strtotime($order['created_at'])),
        'total' => 'Rp ' . number_format($order['total'], 0, ',', '.'),
        'payment_method' => getPaymentMethodText($order['payment_method']),
        'status' => $order['status'],
        'status_indonesia' => getIndonesianStatus($order['status']),
        'can_edit_status' => $can_edit_status,
        'confirmed_received_at' => $order['confirmed_received_at'],
        'confirmed_text' => $order['confirmed_received_at'] ? '✓ ' . date('d/m/Y H:i', strtotime($order['confirmed_received_at'])) : '-'
    ];
}

echo json_encode($formatted_orders);
?>
