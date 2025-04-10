<?php
session_start();
// Kết nối cơ sở dữ liệu
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "mahiru_shop";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Kết nối CSDL thất bại: " . $e->getMessage();
    exit();
}

// Lấy order_id từ URL
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kiểm tra nếu order_id không hợp lệ
if ($orderId <= 0) {
    header("Location: order-management.php");
    exit();
}

// Truy vấn thông tin hóa đơn và thông tin khách hàng
$orderStmt = $conn->prepare("
    SELECT o.*, u.username, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = :order_id
");
$orderStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
$orderStmt->execute();
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: order-management.php");
    exit();
}

// Truy vấn danh sách sản phẩm trong hóa đơn
$itemsStmt = $conn->prepare("
    SELECT oi.*, p.name, p.image, p.category
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = :order_id
");
$itemsStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
$itemsStmt->execute();
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Tính ngày giao hàng dự kiến (giả sử 5 ngày sau ngày đặt hàng)
$orderDate = new DateTime($order['created_at']);
$estimatedDelivery = (clone $orderDate)->modify('+5 days');

// Tính Subtotal (tổng giá sản phẩm)
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Phí vận chuyển (giả sử cố định là $5.00)
$shipping = 5.00;

// Tổng tiền
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/lucide@latest"></script>
    <title>Order Details</title>
    <link rel="stylesheet" href="./css/order.css">
</head>
<body>
    <header>
        <div class="container">
            <h1 class="logo">MAHIRU<span>.</span>ADMIN</h1>
            <nav>
                <ul>
                    <li><a href="./admin.php">Dashboard</a></li>
                    <li><a href="./user-management.php">User</a></li>
                    <li><a href="./order-management.php">Orders</a></li>
                    <li><a href="./product-management.php">Product</a></li>
                    <li><a href="./business_performance.php">Statistic</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <div class="user-icon">
                    <i data-lucide="user-circle"></i>
                </div>
                <span class="admin-name">ADMIN</span>
                <a href="./loginad.php" class="logout">Log out</a>
            </div>
        </div>
    </header>
    <main>
        <div class="container">
            <div class="order-details">
                <div class="order-header">
                    <h1 class="order-id">INVOICE ID: INV-<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></h1>
                    <div class="order-actions">
                        <!-- Thêm nút Edit Status -->
                        <a href="change_order_status.php?order_id=<?php echo $order['id']; ?>" class="btn">Edit Status</a>
                    </div>
                </div>
                <div class="order-dates">
                    <p>Order date: <?php echo $orderDate->format('F d, Y'); ?></p>
                    <p>Estimated delivery: <?php echo $estimatedDelivery->format('F d, Y'); ?></p>
                </div>

                <div class="order-items">
                    <?php foreach ($items as $item): ?>
                        <div class="product-item">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image2">
                            <div class="product-details">
                                <h3 class="product-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($item['category']); ?></p>
                                <p class="product-price">$<?php echo number_format($item['price'], 2); ?> Qty: <?php echo $item['quantity']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-summary">
                    <h2 class="section-title">Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>$<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-section">
                        <h2 class="section-title">Shipping Information</h2>
                        <div class="info-row">
                            <div class="info-label">Client: <?php echo htmlspecialchars($order['name']); ?></div>
                            <div class="info-label">Address:</div>
                            <div class="info-value">
                                <?php echo nl2br(htmlspecialchars($order['address'])); ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['phone'] ?: 'N/A'); ?></div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h2 class="section-title">Payment Information</h2>
                        <div class="info-row">
                            <div class="info-label">Payment Method:</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>©Mahiru Shop. We are pleased to serve you.</p>
        </div>
    </footer>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>