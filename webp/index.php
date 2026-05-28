<?php
include 'db/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect berdasarkan role
if ($_SESSION['role'] == 'admin') {
    header("Location: dashboard_admin.php");
} else {
    header("Location: dashboard_user.php");
}
exit();
