<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kiểm tra nếu form được gửi
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}

// Kết nối cơ sở dữ liệu
$host = 'localhost';
$dbname = 'mahiru_shop';
$dbUsername = 'root';
$dbPassword = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $dbUsername, $dbPassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Lấy user_id từ session
$userId = $_SESSION['user_id'];

// Lấy dữ liệu từ form
$name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$streetAddress = isset($_POST['street_address']) ? trim($_POST['street_address']) : '';
$city = isset($_POST['city']) ? trim($_POST['city']) : '';
$state = isset($_POST['state']) ? trim($_POST['state']) : '';
$zipCode = isset($_POST['zip_code']) ? trim($_POST['zip_code']) : '';
$country = isset($_POST['country']) ? trim($_POST['country']) : '';
$totalPrice = isset($_POST['total']) ? (float)$_POST['total'] : 0;
$paymentMethod = isset($_POST['payment']) ? trim($_POST['payment']) : ''; // Lấy giá trị payment từ form

// Gộp các trường địa chỉ thành một chuỗi
$addressParts = array_filter([$streetAddress, $city, $state, $zipCode, $country], function($value) {
    return !empty($value);
});
$address = implode(', ', $addressParts);

// Kiểm tra dữ liệu đầu vào
if (empty($name) || empty($address) || $totalPrice <= 0 || empty($paymentMethod)) {
    // Nếu dữ liệu không hợp lệ, chuyển hướng về checkout.php với thông báo lỗi
    $_SESSION['error'] = "Please fill in all required fields correctly, including payment method.";
    header("Location: checkout.php");
    exit();
}

// Lấy dữ liệu giỏ hàng từ cơ sở dữ liệu
$stmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.name, p.price, p.image 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = :user_id
");
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra giỏ hàng
if (empty($cartItems)) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: cart.php");
    exit();
}

// Bắt đầu giao dịch
$conn->beginTransaction();

try {
    // Lưu đơn hàng vào bảng orders
    $orderStmt = $conn->prepare("
        INSERT INTO orders (user_id, name, address, total_price, payment_method, status, created_at)
        VALUES (:user_id, :name, :address, :total_price, :payment_method, 'pending', NOW())
    ");
    $orderStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $orderStmt->bindValue(':name', $name, PDO::PARAM_STR);
    $orderStmt->bindValue(':address', $address, PDO::PARAM_STR);
    $orderStmt->bindValue(':total_price', $totalPrice, PDO::PARAM_STR);
    $orderStmt->bindValue(':payment_method', $paymentMethod, PDO::PARAM_STR); // Lưu payment_method
    $orderStmt->execute();

    // Lấy ID của đơn hàng vừa tạo
    $orderId = $conn->lastInsertId();

    // Lưu chi tiết đơn hàng vào bảng order_items
    $detailStmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (:order_id, :product_id, :quantity, :price)
    ");
    foreach ($cartItems as $item) {
        // Debug: In ra product_id và quantity
        echo "Product ID: " . $item['product_id'] . ", Quantity: " . $item['quantity'] . "<br>";
        
        $detailStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $detailStmt->bindValue(':product_id', $item['product_id'], PDO::PARAM_INT);
        $detailStmt->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
        $detailStmt->bindValue(':price', $item['price'], PDO::PARAM_STR);
        $detailStmt->execute();

        // Tăng sold_count trong bảng products
        $updateSoldStmt = $conn->prepare("
            UPDATE products 
            SET sold_count = sold_count + :quantity 
            WHERE id = :product_id
        ");
        $updateSoldStmt->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
        $updateSoldStmt->bindValue(':product_id', $item['product_id'], PDO::PARAM_INT);
        $updateSoldStmt->execute();

        // Kiểm tra lỗi sau khi cập nhật sold_count
        if ($updateSoldStmt->errorCode() !== '00000') {
            $errorInfo = $updateSoldStmt->errorInfo();
            echo "SQL Error: " . $errorInfo[2];
        }
    }

    // Xóa giỏ hàng của người dùng
    $deleteStmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
    $deleteStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $deleteStmt->execute();

    // Commit giao dịch
    $conn->commit();

    // Lưu thông tin đơn hàng vào session để hiển thị trên trang xác nhận
    $_SESSION['order_id'] = $orderId;
    $_SESSION['order_success'] = "Your order has been placed successfully!";
    $_SESSION['payment_method'] = $paymentMethod; // Lưu payment_method vào session để hiển thị trên trang xác nhận

    // Chuyển hướng đến trang xác nhận đơn hàng
    header("Location: order_confirmation.php");
    exit();

} catch (Exception $e) {
    // Nếu có lỗi, rollback giao dịch
    $conn->rollBack();
    $_SESSION['error'] = "An error occurred while processing your order: " . $e->getMessage();
    header("Location: checkout.php");
    exit();
}
?>