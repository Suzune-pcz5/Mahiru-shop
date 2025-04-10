<?php
session_start();

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += 1; // Tăng số lượng nếu sản phẩm đã có
    } else {
        $_SESSION['cart'][$product_id] = 1; // Thêm sản phẩm mới với số lượng 1
    }
    header("Location: cart.php");
    exit();
}
?>