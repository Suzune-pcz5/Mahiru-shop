<?php
session_start();
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]); // Xóa sản phẩm khỏi session
    }
    header('Location: cart.php'); // Chuyển hướng về giỏ hàng
    exit;
}
?>