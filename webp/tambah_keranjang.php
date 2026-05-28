<?php
include 'db/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // Validate product exists and has stock
    $result = $mysqli->query("SELECT * FROM products WHERE id = $product_id");
    if ($result->num_rows == 0) {
        $_SESSION['error'] = "Produk tidak ditemukan!";
        header("Location: dashboard_user.php");
        exit();
    }

    $product = $result->fetch_assoc();
    if ($quantity > $product['stock']) {
        $_SESSION['error'] = "Stok tidak cukup!";
        header("Location: dashboard_user.php");
        exit();
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    // Add or update item in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $newQty = $item['quantity'] + $quantity;
            if ($newQty > $product['stock']) {
                $_SESSION['error'] = "Stok tidak cukup! Maksimum: " . $product['stock'];
                header("Location: dashboard_user.php");
                exit();
            }
            $item['quantity'] = $newQty;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = array(
            'product_id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity
        );
    }

    $_SESSION['success'] = "Produk berhasil ditambahkan ke keranjang!";
}

if (isset($_POST['redirect_to_cart']) && $_POST['redirect_to_cart'] == '1') {
    header("Location: cart.php");
} else {
    header("Location: dashboard_user.php");
}
exit();
