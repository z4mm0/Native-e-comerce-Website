<?php
include 'db/koneksi.php';

// Check if column exists
$result = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'confirmed_received_at'");

if ($result->num_rows == 0) {
    // Add column if not exists
    $query = "ALTER TABLE orders ADD COLUMN confirmed_received_at TIMESTAMP NULL DEFAULT NULL AFTER status";
    if ($mysqli->query($query)) {
        echo "✅ SUCCESS! Kolom 'confirmed_received_at' berhasil ditambahkan ke tabel orders!<br>";
        echo "Fitur konfirmasi penerimaan pesanan sekarang sudah aktif.";
    } else {
        echo "❌ ERROR: Gagal menambahkan kolom: " . $mysqli->error;
    }
} else {
    echo "✅ INFO: Kolom 'confirmed_received_at' sudah ada di tabel orders.";
}

$mysqli->close();
?>
