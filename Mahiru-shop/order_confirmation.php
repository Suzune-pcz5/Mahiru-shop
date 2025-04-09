<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kiểm tra thông báo thành công
if (!isset($_SESSION['order_success']) || !isset($_SESSION['order_id'])) {
    header("Location: cart.php");
    exit();
}

// Lấy thông tin đơn hàng
$orderId = $_SESSION['order_id'];
$successMessage = $_SESSION['order_success'];

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
// ========== LẤY DANH MỤC TỪ BẢNG products ==========
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin đơn hàng
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id");
$orderStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
$orderStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$orderStmt->execute();
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: cart.php");
    exit();
}

// Lấy chi tiết đơn hàng từ bảng order_items
$detailsStmt = $conn->prepare("
    SELECT oi.product_id, oi.quantity, oi.price, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = :order_id
");
$detailsStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
$detailsStmt->execute();
$orderDetails = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);

// Update the order status to 'completed' to reflect in admin revenue
$updateStatusStmt = $conn->prepare("UPDATE orders SET status = 'pending' WHERE id = :order_id");
$updateStatusStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
$updateStatusStmt->execute();

// Xóa thông báo thành công sau khi hiển thị
unset($_SESSION['order_success']);
unset($_SESSION['order_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="./css/categories.css">
    <link rel="stylesheet" href="./css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* CSS để hiển thị các nút cạnh nhau */
        .checkout-actions {
            display: flex;
            gap: 10px; /* Khoảng cách giữa các nút */
            justify-content: center; /* Căn giữa các nút */
        }
        .checkout-actions a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .place-order-btn {
            background-color: #e74c3c; /* Màu đỏ giống nút hiện tại */
            color: white;
        }
        .view-history-btn {
            background-color: #3498db; /* Màu xanh cho nút View Order History */
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <div class="top-bar">
            <div class="container">
                <div class="contact-info">
                    <span><i class="fas fa-phone"></i> 012345678</span>
                    <span><i class="fas fa-envelope"></i> mahiru@gmail.com</span>
                    <span><i class="fas fa-map-marker-alt"></i> 1104 Wall Street</span>
                </div>
                <div class="user-actions">
                    <i class="fas fa-user"></i>
                    <span class="name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <div class="login-dropdown">
                        <a href="order_history.php" class="login-option">Order History</a>
                        <a href="edit_profile.php" class="login-option">Edit Profile</a>
                        <a href="index.php" class="login-option">Log out</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="main-header">
            <div class="container">
                <div class="logo">
                    <a href="index_account.php" class="logo-link"><h1>MAHIRU<span>.</span></h1></a>
                </div>
                <div class="search-bar">
                    <form action="search_account.php" method="GET">
                        <input type="text" name="name" placeholder="Search here" />
                        <button type="submit" class="search-button">Search</button>
                    </form>
                </div>
                <div class="user-menu">
                    <a href="cart.php" class="icon"><i class="fas fa-shopping-cart"></i></a>
                </div>
            </div>
        </div>
        <nav class="category-nav">
            <div class="container">
            <ul class="category-list">
                    <li><a href="index_account.php">Home</a></li>
                    <?php foreach ($categories as $cat): ?>
                        <li><a href="index_account.php?category=<?= urlencode($cat['category']) ?>"> <?= htmlspecialchars($cat['category']) ?> </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main>
        <div class="container">
            <div class="checkout-form">
                <h1 class="page-title">Order Confirmation</h1>
                <div class="order-confirmation">
                    <p class="success-message"><?php echo htmlspecialchars($successMessage); ?></p>
                    <h2>Order Details</h2>
                    <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['id']); ?></p>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                    <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
                    <h2>Items</h2>
                    <?php if (!empty($orderDetails)): ?>
                        <?php foreach ($orderDetails as $item): ?>
                            <div class="summary-item">
                                <div class="cart-item">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</h3>
                                        <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                </div>
                                <span class="subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No items found in this order.</p>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>$5.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                    <div class="checkout-actions">
                        <a href="index_account.php" class="place-order-btn">Continue Shopping</a>
                        <a href="order_history.php" class="view-history-btn">View Order History</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>© Mahiru Shop. We are pleased to serve you.</p>
        </div>
    </footer>
</body>
</html>