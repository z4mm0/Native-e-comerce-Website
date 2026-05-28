<?php
include 'db/koneksi.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];

    if ($action == 'create') {
        $stmt = $mysqli->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            $success = "Kategori berhasil ditambahkan!";
            header("Refresh: 2; url=dashboard_admin.php");
        } else {
            $error = "Gagal menambahkan kategori: " . $stmt->error;
        }
    } elseif ($action == 'edit') {
        $id = $_POST['id'];
        $stmt = $mysqli->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $description, $id);
        
        if ($stmt->execute()) {
            $success = "Kategori berhasil diperbarui!";
            header("Refresh: 2; url=dashboard_admin.php");
        } else {
            $error = "Gagal memperbarui kategori: " . $stmt->error;
        }
    }
} elseif ($action == 'delete') {
    $id = $_GET['id'];
    $stmt = $mysqli->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: dashboard_admin.php");
        exit();
    } else {
        $error = "Gagal menghapus kategori: " . $stmt->error;
    }
}

$category = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $mysqli->prepare("SELECT * FROM categories WHERE id=?");
    $result->bind_param("i", $id);
    $result->execute();
    $category = $result->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($action == 'create' ? 'Tambah' : 'Edit'); ?> Kategori - Toko Baju</title>
    <style>
        /* --- Styling Form Kategori (Tema Floral Bliss: Sage & Pink) --- */

:root {
    --primary-green: #798763;
    --dark-sage: #5f6b4d;
    --soft-pink: #fce4ec;
    --bright-pink: #ff85a2;
    --accent-rose: #d81b60;
    --bg-light: #fafbf9;
}

/* Container Form */
.form {
    background: #ffffff;
    padding: 35px;
    border-radius: 25px; /* Lebih membulat agar estetik */
    box-shadow: 0 15px 35px rgba(121, 135, 99, 0.1);
    max-width: 500px;
    margin: 30px auto;
    border: 2px solid var(--soft-pink); /* Border pink lembut */
    position: relative;
    overflow: hidden;
}

/* Garis aksen di atas form (Gradasi Sage ke Pink) */
.form::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(to right, var(--primary-green), var(--bright-pink));
}

.form-group {
    margin-bottom: 22px;
}

.form-group label {
    display: block;
    font-weight: 700;
    color: var(--dark-sage); 
    margin-bottom: 8px;
    font-size: 14px;
    letter-spacing: 0.5px;
}

/* Input & Textarea */
.form-group input[type="text"],
.form-group textarea {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e2e8da;
    border-radius: 15px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    color: #444;
    background-color: var(--bg-light);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;
}

/* Focus: Berubah ke arah Pink agar senada dengan elemen dashboard lainnya */
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--bright-pink);
    background-color: #ffffff;
    box-shadow: 0 0 12px rgba(255, 133, 162, 0.2);
}

/* Tombol Submit Utama */
.form .btn-primary {
    width: 100%;
    padding: 16px;
    background: linear-gradient(45deg, var(--primary-green), var(--dark-sage));
    border: none;
    border-radius: 15px;
    color: white;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 6px 15px rgba(121, 135, 99, 0.2);
    margin-top: 10px;
}

.form .btn-primary:hover {
    background: linear-gradient(45deg, var(--bright-pink), var(--accent-rose));
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(216, 27, 96, 0.3);
}

/* Notifikasi Sukses & Error (Disesuaikan dengan tema) */
.success {
    background-color: #eef2e6; /* Sage Pucat */
    color: var(--dark-sage);
    padding: 15px;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 25px;
    border: 1px solid var(--primary-green);
    font-weight: 600;
}

.error {
    background-color: var(--soft-pink);
    color: var(--accent-rose);
    padding: 15px;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 25px;
    border: 1px solid var(--bright-pink);
    font-weight: 600;
}

/* Heading H2 */
h2 {
    color: var(--dark-sage);
    text-align: center;
    font-weight: 800;
    font-size: 24px;
    margin-top: 40px;
    margin-bottom: 5px;
}
/* --- Perbaikan Navbar & Navigasi --- */
.navbar {
    background: white;
    padding: 15px 0;
    box-shadow: 0 4px 15px rgba(121, 135, 99, 0.1);
    border-bottom: 2px solid var(--soft-pink);
    margin-bottom: 30px;
}

.nav-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 20px;
    color: var(--dark-sage);
    font-weight: 800;
}

/* --- Styling Tombol Navigasi agar Senada --- */
.nav-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

/* Tombol Kembali (Sage Outline) */
.btn-secondary {
    background-color: transparent;
    color: var(--primary-green);
    padding: 10px 20px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 700;
    font-size: 13px;
    border: 2px solid var(--primary-green);
    transition: 0.3s;
    display: inline-block;
}

.btn-secondary:hover {
    background-color: var(--primary-green);
    color: white;
    transform: translateY(-2px);
}

/* Tombol Logout (Pink Soft) */
.btn-logout {
    background-color: var(--soft-pink);
    color: var(--accent-rose);
    padding: 10px 20px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 700;
    font-size: 13px;
    transition: 0.3s;
    display: inline-block;
}

.btn-logout:hover {
    background-color: var(--bright-pink);
    color: white;
    transform: translateY(-2px);
}

/* Judul Halaman */
.page-title {
    color: var(--dark-sage);
    font-weight: 800;
    margin-bottom: 25px;
    text-align: center;
}
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <h1 class="logo">🛍️ Toko Baju - Admin Panel</h1>
                <div class="nav-actions">
                    <a href="dashboard_admin.php" class="btn-secondary">Kembali</a>
                    <a href="logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <h2 class="page-title">
            <?php echo ($action == 'create' ? '🌸 Tambah Kategori Baru' : '✨ Edit Kategori'); ?>
        </h2>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <form method="POST" class="form">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nama Kategori:</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Deskripsi:</label>
                <textarea name="description" rows="4"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn-primary">
                <?php echo ($action == 'create' ? 'Tambah Kategori' : 'Perbarui Kategori'); ?>
            </button>
        </form>
    </div>
</body>
</html>
