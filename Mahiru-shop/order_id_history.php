<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kiểm tra order_id
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: order_history.php");
    exit();
}

$orderId = (int)$_GET['order_id'];
$userId = $_SESSION['user_id'];

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

// Lấy thông tin người dùng từ bảng users
$userStmt = $conn->prepare("SELECT username, email, address, phone FROM users WHERE id = :user_id");
$userStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: order_history.php");
    exit();
}

// Lấy thông tin đơn hàng
$orderStmt = $conn->prepare("SELECT id, user_id, payment_method, total_price, created_at FROM orders WHERE id = :order_id AND user_id = :user_id");
$orderStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
$orderStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$orderStmt->execute();
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: order_history.php");
    exit();
}

// ========== LẤY DANH MỤC TỪ BẢNG products ==========
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Lấy chi tiết đơn hàng
$detailsStmt = $conn->prepare("
    SELECT oi.product_id, oi.quantity, oi.price, p.name, p.image, p.description 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = :order_id
");
$detailsStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
$detailsStmt->execute();
$orderDetails = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);

// Tính toán Subtotal
$subtotal = 0;
foreach ($orderDetails as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Tính ngày giao hàng dự kiến (Order Date + 5 ngày)
$orderDate = new DateTime($order['created_at']);
$estimatedDelivery = (clone $orderDate)->modify('+5 days');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="./css/order_id_history.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
            <span class="name"><?php echo htmlspecialchars($user['username']); ?></span>
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
            <a href="index_account.php" class="logo-link"><h1>MAhiru<span>.</span></h1></a>
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
      <nav>
        <div class="container">
        <ul>
                <li><a href="index.php">Home</a></li>
                <?php foreach ($categories as $cat): ?>
                    <li><a href="index.php?category=<?php echo htmlspecialchars($cat['category']); ?>">
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $cat['category']))); ?>
                    </a></li>
                <?php endforeach; ?>
            </ul>
        </div>
      </nav>
    </header>

    <main>
        <div class="container">
            <div class="order-details-container">
                <div class="order-header">
                    <div class="breadcrumb">
                        <a href="index_account.php">Home</a> /
                        <a href="order_history.php">Orders</a> /
                        <span>Order #<?php echo htmlspecialchars($order['id']); ?></span>
                    </div>
                    <div class="order-title">
                        <h1>Order ID: #<?php echo htmlspecialchars($order['id']); ?></h1>
                    </div>
                    <div class="order-info">
                        <p>Order date: <?php echo htmlspecialchars($orderDate->format('F d, Y')); ?></p>
                        <p>Estimated delivery: <?php echo htmlspecialchars($estimatedDelivery->format('F d, Y')); ?></p>
                    </div>
                </div>

                <div class="order-items">
                    <h2>Order Items</h2>
                    <?php if (!empty($orderDetails)): ?>
                        <?php foreach ($orderDetails as $item): ?>
                            <div class="item">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($item['description'] ?? 'No description available'); ?></p>
                                    <div class="item-price">
                                        <span>$<?php echo number_format($item['price'], 2); ?></span>
                                        <span class="quantity">Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No items found in this order.</p>
                    <?php endif; ?>
                </div>

                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>$5.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                </div>

                <div class="shipping-payment-info">
                    <div class="shipping-info">
                        <h2>Shipping Information</h2>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="payment-info">
                        <h2>Payment Information</h2>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></p>
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
