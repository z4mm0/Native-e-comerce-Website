<?php
include 'db/koneksi.php';

// Try to add check constraint to enforce non-negative stock. Some MySQL versions may ignore CHECK constraints.
$query = "ALTER TABLE products ADD CONSTRAINT chk_stock_nonnegative CHECK (stock >= 0)";
if ($mysqli->query($query) === TRUE) {
    echo "✅ SUCCESS! Constraint 'chk_stock_nonnegative' added to products table.\n";
} else {
    echo "⚠️ Could not add CHECK constraint (server may not support it or constraint already exists): " . $mysqli->error . "\n";
}

$mysqli->close();
?>