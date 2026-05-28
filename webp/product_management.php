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

// Get categories for dropdown
$cat_result = $mysqli->query("SELECT * FROM categories ORDER BY name");
$categories = $cat_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = intval($_POST['stock']);
    $category_id = $_POST['category_id'];
    $image = $_POST['image'] ?? '';

    // Validate stock (should not be negative)
    if ($stock < 0) {
        $error = "Stok tidak boleh negatif.";
    }

    // file upload 
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $upload_dir = 'pict/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = $_FILES['image_file']['name'];
        $file_tmp = $_FILES['image_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
       
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if (!in_array($file_ext, $allowed_ext)) {
            $error = "Format gambar tidak didukung. Gunakan: JPG, PNG, GIF, atau WebP";
        } else {
            // nama file unik
            $new_file_name = 'pict_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $file_path)) {
                $image = $new_file_name;
            } else {
                $error = "Gagal mengupload gambar!";
            }
        }
    } elseif ($action == 'edit' && empty($_FILES['image_file']['name'])) {
        // edit tanpa upload baru
        $image = $_POST['image_existing'] ?? '';
    }

    if ($action == 'create' && !$error) {
        $stmt = $mysqli->prepare("INSERT INTO products (name, description, price, stock, category_id, image) VALUES (?, ?, ?, ?, ?, ?)");
        // name(s), description(s), price(d), stock(i), category_id(i), image(s)
        $stmt->bind_param("ssdiis", $name, $description, $price, $stock, $category_id, $image);
        
        if ($stmt->execute()) {
            $success = "Produk berhasil ditambahkan!";
            header("Refresh: 2; url=dashboard_admin.php");
        } else {
            $error = "Gagal menambahkan produk: " . $stmt->error;
        }
    } elseif ($action == 'edit' && !$error) {
        $id = $_POST['id'];
        
        // hapus jika upload gambar baru
        if (!empty($_FILES['image_file']['name']) && !empty($_POST['image_existing'])) {
            $old_file = 'pict/' . $_POST['image_existing'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        $stmt = $mysqli->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category_id=?, image=? WHERE id=?");
        // name(s), description(s), price(d), stock(i), category_id(i), image(s), id(i)
        $stmt->bind_param("ssdiisi", $name, $description, $price, $stock, $category_id, $image, $id);
        
        if ($stmt->execute()) {
            $success = "Produk berhasil diperbarui!";
            header("Refresh: 2; url=dashboard_admin.php");
        } else {
            $error = "Gagal memperbarui produk: " . $stmt->error;
        }
    }
} elseif ($action == 'delete') {
    $id = $_GET['id'];
    
    // hapus file gambar
    $result = $mysqli->prepare("SELECT image FROM products WHERE id=?");
    $result->bind_param("i", $id);
    $result->execute();
    $product = $result->get_result()->fetch_assoc();
    
    if ($product && !empty($product['image'])) {
        $file_path = 'pict/' . $product['image'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $stmt = $mysqli->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: dashboard_admin.php");
        exit();
    } else {
        $error = "Gagal menghapus produk: " . $stmt->error;
    }
}

$product = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $mysqli->prepare("SELECT * FROM products WHERE id=?");
    $result->bind_param("i", $id);
    $result->execute();
    $product = $result->get_result()->fetch_assoc();
}
?>
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($action == 'create' ? 'Tambah' : 'Edit'); ?> Produk - Toko Baju</title>
    <link rel="stylesheet" href="asset/pm.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🛍️ Toko Baju - Admin Panel</h1>
            <div>
                <a href="dashboard_admin.php" class="btn-secondary">Kembali</a>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2><?php echo ($action == 'create' ? 'Tambah Produk Baru' : 'Edit Produk'); ?></h2>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <form method="POST" class="form" enctype="multipart/form-data">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="image_existing" value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nama Produk:</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Deskripsi:</label>
                <textarea name="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Harga:</label>
                <input type="number" name="price" step="0.01" required value="<?php echo $product['price'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label>Stok:</label>
                <input type="number" name="stock" required min="0" step="1" value="<?php echo htmlspecialchars($product['stock'] ?? '0'); ?>">
            </div>

            <div class="form-group">
                <label>Kategori:</label>
                <select name="category_id">
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (($product['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>📁 Pilih Gambar Produk:</label>
                <input type="file" name="image_file" id="image_file" accept="image/*" onchange="previewImage()" >
                
                <?php if (!empty($product['image'])): ?>
                    <p style="font-size: 12px; color: #798763; margin-top: 10px;">
                        ✓ Gambar saat ini: <strong><?php echo htmlspecialchars($product['image']); ?></strong>
                    </p>
                <?php endif; ?>
                
                <div id="image_preview_container" style="margin-top: 15px; text-align: center; display: none;">
                    <p style="font-size: 12px; color: #798763; margin-bottom: 5px;">Preview Gambar:</p>
                    <img id="image_preview" src="" 
                         style="max-width: 200px; border-radius: 15px; border: 2px solid #e2e8da;">
                </div>
            </div>

            <script>
            function previewImage() {
                const fileInput = document.getElementById('image_file');
                const previewContainer = document.getElementById('image_preview_container');
                const previewImg = document.getElementById('image_preview');

                if (fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewContainer.style.display = "block";
                    };
                    reader.readAsDataURL(fileInput.files[0]);
                } else {
                    previewContainer.style.display = "none";
                }
            }
            </script>

            <button type="submit" class="btn-primary">
                <?php echo ($action == 'create' ? 'Tambah Produk' : 'Perbarui Produk'); ?>
            </button>
        </form>
    </div>
</body>
</html>
