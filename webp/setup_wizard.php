<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Wizard - Toko Baju</title>
    <link rel="stylesheet" href="asset/style.css">
    <style>
        .setup-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .setup-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .setup-header p {
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        .step {
            margin-bottom: 20px;
            padding: 15px;
            border-left: 4px solid #3498db;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .step.success {
            border-left-color: #27ae60;
            background: #eafaf1;
        }
        .step.error {
            border-left-color: #e74c3c;
            background: #fdeaea;
        }
        .step h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .step p {
            margin: 0;
            color: #555;
            font-size: 14px;
        }
        .status-icon {
            float: right;
            font-size: 20px;
        }
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        .action-buttons a,
        .action-buttons button {
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <?php
    include 'db/koneksi.php';
    
    $all_ok = true;
    $results = array();
    
    // 1. Check Database Connection
    if ($mysqli->connect_errno) {
        $results['db_connection'] = array('status' => false, 'message' => 'Gagal terhubung: ' . $mysqli->connect_error);
        $all_ok = false;
    } else {
        $results['db_connection'] = array('status' => true, 'message' => 'Database terhubung dengan baik');
    }
    
    // 2. Check Tables
    if ($mysqli->connect_errno == 0) {
        $required_tables = array('users', 'categories', 'products', 'orders', 'order_items');
        $existing = array();
        
        $db_result = $mysqli->query("SHOW TABLES FROM $DB_NAME");
        while ($row = $db_result->fetch_array()) {
            $existing[] = $row[0];
        }
        
        $missing = array_diff($required_tables, $existing);
        if (empty($missing)) {
            $results['tables'] = array('status' => true, 'message' => 'Semua tabel sudah ada ✓');
        } else {
            $results['tables'] = array('status' => false, 'message' => 'Tabel hilang: ' . implode(', ', $missing));
            $all_ok = false;
        }
    }
    
    // 3. Check Admin User
    if ($mysqli->connect_errno == 0) {
        $admin_result = $mysqli->query("SELECT id FROM users WHERE username = 'admin'");
        if ($admin_result->num_rows > 0) {
            $results['admin'] = array('status' => true, 'message' => 'Admin user sudah ada');
        } else {
            $results['admin'] = array('status' => false, 'message' => 'Admin user belum dibuat');
            $all_ok = false;
        }
    }
    
    // 4. Check Products
    if ($mysqli->connect_errno == 0) {
        $prod_result = $mysqli->query("SELECT COUNT(*) as count FROM products");
        $count = $prod_result->fetch_assoc()['count'];
        $results['products'] = array('status' => true, 'message' => 'Total produk: ' . $count);
    }
    ?>
    
    <div class="setup-container">
        <div class="setup-header">
            <h1>🛍️ Setup Wizard</h1>
            <p>Toko Baju Online</p>
        </div>
        
        <div class="step <?php echo $results['db_connection']['status'] ? 'success' : 'error'; ?>">
            <h3>1. Koneksi Database
                <span class="status-icon"><?php echo $results['db_connection']['status'] ? '✅' : '❌'; ?></span>
            </h3>
            <p><?php echo $results['db_connection']['message']; ?></p>
        </div>
        
        <div class="step <?php echo $results['tables']['status'] ? 'success' : 'error'; ?>">
            <h3>2. Tabel Database
                <span class="status-icon"><?php echo $results['tables']['status'] ? '✅' : '❌'; ?></span>
            </h3>
            <p><?php echo $results['tables']['message']; ?></p>
        </div>
        
        <div class="step <?php echo $results['admin']['status'] ? 'success' : 'error'; ?>">
            <h3>3. Admin User
                <span class="status-icon"><?php echo $results['admin']['status'] ? '✅' : '❌'; ?></span>
            </h3>
            <p><?php echo $results['admin']['message']; ?></p>
        </div>
        
        <div class="step success">
            <h3>4. Produk & Data
                <span class="status-icon">ℹ️</span>
            </h3>
            <p><?php echo $results['products']['message']; ?></p>
        </div>
        
        <div class="action-buttons">
            <?php if ($all_ok): ?>
                <p style="color: #27ae60; font-size: 16px; margin-bottom: 20px;">
                    <strong>✅ Semuanya siap! Silakan login.</strong>
                </p>
                <a href="login.php" class="btn-primary">← Masuk ke Login</a>
            <?php else: ?>
                <p style="color: #e74c3c; font-size: 16px; margin-bottom: 20px;">
                    <strong>⚠️ Ada beberapa hal yang perlu diperbaiki.</strong>
                </p>
                <button onclick="location.reload();" class="btn-primary">🔄 Refresh Check</button>
                <a href="README.md" class="btn-secondary" target="_blank">📖 Baca Dokumentasi</a>
            <?php endif; ?>
        </div>
        
        <hr style="margin-top: 30px; border: none; border-top: 1px solid #ddd;">
        
        <div style="margin-top: 20px; font-size: 12px; color: #7f8c8d;">
            <p><strong>Informasi Sistem:</strong></p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Database: <?php echo htmlspecialchars($DB_NAME); ?></li>
                <li>Host: <?php echo htmlspecialchars($DB_HOST); ?></li>
                <li>PHP Version: <?php echo phpversion(); ?></li>
                <li>Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></li>
            </ul>
        </div>
    </div>
</body>
</html>
