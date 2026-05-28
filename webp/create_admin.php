<?php
include 'db/koneksi.php';

// ⚠️ PENTING: Hapus file ini setelah membuat admin!
// File ini hanya untuk setup awal

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $security_key = $_POST['security_key'];
    
    // Validasi security key
    if ($security_key !== 'admin-secret-2024') {
        $error = "Security key salah!";
    } elseif (empty($username) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi!";
    } else {
        // Cek username sudah ada
        $check = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Username sudah ada!";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert sebagai admin
            $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
                $success = "✅ Admin '$username' berhasil dibuat!";
                $success .= "<br>Username: $username";
                $success .= "<br>Email: $email";
                $success .= "<br>Password: " . $_POST['password'];
                $success .= "<br><br>⚠️ Setelah ini, HAPUS FILE create_admin.php dari server!";
            } else {
                $error = "Gagal membuat admin: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin - Toko Baju</title>
    <link rel="stylesheet" href="asset/style.css">
    <style>
        .admin-setup {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success-box {
            background: #eafaf1;
            border-left: 4px solid #27ae60;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="admin-setup">
        <h1>🔑 Create New Admin</h1>
        

        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="success-box"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <div class="form-group">
                <label>Username Admin:</label>
                <input type="text" name="username" placeholder="Contoh: admin2" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" placeholder="admin2@store.com" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" placeholder="Minimal 6 karakter" required>
            </div>

            <div class="form-group">
                <label>Security Key:</label>
                <input type="password" name="security_key" placeholder="Masukkan security key" required>
                <small style="color: #7f8c8d;">Hint: admin-secret-2024</small>
            </div>

            <button type="submit" class="btn-primary">Buat Admin Baru</button>
        </form>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">

        <p style="color: #7f8c8d; font-size: 14px;">
            <strong>Panduan:</strong><br>
            1. Isi form dengan data admin baru<br>
            2. Masukkan security key<br>
            3. Klik "Buat Admin Baru"<br>
            4. Catat username & password<br>
            5. HAPUS file ini (create_admin.php)<br>
            6. Login dengan admin baru
        </p>
    </div>
</body>
</html>
