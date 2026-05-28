<?php
include 'db/koneksi.php';

// Check if column exists
$result = $mysqli->query("SHOW COLUMNS FROM payments LIKE 'proof_image'");

if ($result->num_rows == 0) {
    // Add column if not exists
    $query = "ALTER TABLE payments ADD COLUMN proof_image VARCHAR(255) NULL AFTER notes";
    if ($mysqli->query($query)) {
        echo "✅ SUCCESS! Kolom 'proof_image' berhasil ditambahkan ke tabel payments!<br>";
        echo "Fitur upload bukti transaksi sekarang aktif.";
    } else {
        echo "❌ ERROR: Gagal menambahkan kolom: " . $mysqli->error;
    }
} else {
    echo "✅ INFO: Kolom 'proof_image' sudah ada di tabel payments.";
}

$mysqli->close();
?>