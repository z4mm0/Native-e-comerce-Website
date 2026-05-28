<?php
session_start(); // Pastikan session dimulai
include 'db/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $mysqli->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] == 'user') {
                header("Location: dashboard_user.php");
            } else {
                header("Location: dashboard_admin.php");
            }
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Baju Ceria</title>
    <link rel="stylesheet" href="asset/login.css">
</head>
<body>
    <div class="bg-decoration">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="main-wrapper">
        <div class="form-wrapper">
            <div class="brand-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 11V7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7V11C5.89543 11 5 11.8954 5 13V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V13C19 11.8954 18.1046 11 17 11ZM9 7C9 5.34315 10.3431 4 12 4C13.6569 4 15 5.34315 15 7V11H9V7ZM13 17.1147V18C13 18.5523 12.5523 19 12 19C11.4477 19 11 18.5523 11 18V17.1147C10.4077 16.8329 10 16.2144 10 15.5C10 14.3954 10.8954 13.5 12 13.5C13.1046 13.5 14 14.3954 14 15.5C14 16.2144 13.5923 16.8329 13 17.1147Z" fill="#FF8787"/>
                </svg>
            </div>
            
            <h1>Toko Baju Kita</h1>
            <p class="subtitle">Selamat Datang Kembali!</p>
            
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Masukkan username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn-primary">Masuk!</button>
            </form>
            
            <p class="form-footer">
                Baru di sini? <a href="register.php">Buat akun yuk!</a>
            </p>
        </div>
    </div>
</body>
</html>