<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

// Lấy user_id và username từ session
$userId = $_SESSION['user_id'];
$username = $_SESSION['user_name'] ?? 'User'; // Lấy username từ session, mặc định là 'User' nếu không có
$currentUser = isset($_SESSION['user_name']) ? [
    'username' => $_SESSION['user_name'],
    'role'     => $_SESSION['user_role'] ?? 'user'
] : null;

// Lấy dữ liệu đơn hàng của người dùng hiện tại và gán thứ tự riêng
$stmt = $conn->prepare("
    SELECT 
        (@row_number:=@row_number + 1) AS order_number,
        o.id,
        o.created_at AS date,
        o.payment_method AS payment,  -- Sửa ở đây: Lấy giá trị từ cột payment_method
        o.status,
        o.total_price AS total
    FROM orders o
    CROSS JOIN (SELECT @row_number:=0) AS init
    WHERE o.user_id = :user_id
    ORDER BY o.created_at DESC
");
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - MAHIRU.</title>
    <link rel="stylesheet" href="./css/styles.css"> <!-- Link file styles.css hiện có -->
    <link rel="stylesheet" href="./css/order_history.css"> <!-- File CSS riêng cho order_history -->
    <!-- Thêm Font Awesome nếu cần các icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="page-wrapper"> <!-- Bọc toàn bộ nội dung trong page-wrapper -->
        <header>
            <div class="top-bar">
                <div class="container">
                    <div class="contact-info">
                        <span><i class="fas fa-phone"></i> 012345678</span>
                        <span><i class="fas fa-envelope"></i> mahiru@gmail.com</span>
                        <span><i class="fas fa-map-marker-alt"></i> 1104 Wall Street</span>
                    </div>
                    <div class="user-actions">
                        <?php if ($currentUser): ?>
                            <i class="fas fa-user"></i>
                            <?php if (strtolower($currentUser['role']) === 'admin'): ?>
                                <span class="name">ADMIN</span>
                            <?php else: ?>
                                <span class="name"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                            <?php endif; ?>
                            <div class="login-dropdown">
                                <?php if (strtolower($currentUser['role']) === 'admin'): ?>
                                    <a href="edit.php" class="login-option">Admin Panel</a>
                                    <a href="order_history.php" class="login-option">Order History</a>
                                    <a href="edit_profile.php" class="login-option">Edit Profile</a>
                                <?php else: ?>
                                    <a href="order_history.php" class="login-option">Order History</a>
                                    <a href="edit_profile.php" class="login-option">Edit Profile</a>
                                <?php endif; ?>
                                <a href="logout.php" class="login-option">Log out</a>
                            </div>
                        <?php else: ?>
                            <a class="login-link">
                                <i class="fas fa-user"></i>
                                <span class="name">Login/Sign up</span>
                            </a>
                            <div class="login-dropdown">
                                <a href="login.php" class="login-option">Login</a>
                                <a href="sign_up.php" class="login-option">Sign up</a>
                            </div>
                        <?php endif; ?>
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
                            <input type="text" name="name" placeholder="Search here" value="">
                            <input type="hidden" name="category" value="all">
                            <input type="hidden" name="price" value="99999999">
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
                        <?php
                        $categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
                        $categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($categories as $cat): ?>
                            <li><a href="index_account.php?category=<?= urlencode($cat['category']) ?>"><?= htmlspecialchars($cat['category']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </nav>
        </header>

        <main>
            <div class="container">
                <div class="order-history-container">
                    <h2 class="page-title">Order History</h2>
                    <div class="order-filters">
                        <div class="filter-tabs">
                            <button class="filter-tab active" data-status="all">All Orders</button>
                            <button class="filter-tab" data-status="pending">Pending</button>
                            <button class="filter-tab" data-status="processing">Processing</button>
                            <button class="filter-tab" data-status="delivered">Delivered</button>
                            <button class="filter-tab" data-status="cancelled">Cancelled</button>
                            <button class="filter-tab" data-status="completed">Completed</button>
                        </div>
                        <div class="date-filter">
                            <input type="date" id="from-date" class="date-input" value="1970-01-01">
                            <input type="date" id="to-date" class="date-input" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="order-table-container">
                        <table class="order-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td data-label="Order ID">#<?php echo $order['order_number']; ?></td>
                                        <td data-label="Date"><?php echo date('Y-m-d', strtotime($order['date'])); ?></td>
                                        <td data-label="Payment"><?php echo htmlspecialchars($order['payment']); ?></td>
                                        <td data-label="Status"><span class="status-badge <?php echo strtolower($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                        <td data-label="Total">$<?php echo number_format($order['total'], 2); ?></td>
                                        <td data-label="Action"><a href="order_id_history.php?order_id=<?php echo $order['id']; ?>" class="details-btn">Details</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="6">No orders found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <div class="container">
                <p>© Mahiru Shop. We are pleased to serve you.</p>
            </div>
        </footer>
    </div> <!-- Đóng page-wrapper -->

    <script src="./js/order_history.js"></script>
</body>
</html>
