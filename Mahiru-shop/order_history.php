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

// Lấy user_id từ session
$userId = $_SESSION['user_id'];
// ========== LẤY DANH MỤC TỪ BẢNG products ==========
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Xử lý bộ lọc
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '1970-01-01';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Xây dựng câu truy vấn dựa trên bộ lọc
$query = "SELECT * FROM orders WHERE user_id = :user_id";
$params = [':user_id' => $userId];

if ($statusFilter !== 'all') {
    $query .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

$query .= " AND created_at BETWEEN :start_date AND :end_date";
$params[':start_date'] = $startDate . ' 00:00:00';
$params[':end_date'] = $endDate . ' 23:59:59';

$query .= " ORDER BY created_at DESC";

// Thực hiện truy vấn
$orderStmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $orderStmt->bindValue($key, $value);
}
$orderStmt->execute();
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="./css/order_history.css">
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
            <span class="name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <div class="login-dropdown">
                <a href="order_history.php" class="login-option">Order History</a>
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
      <nav>
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
            <div class="order-history-container">
                <h1 class="page-title">Order History</h1>
                
                <div class="order-filters">
                    <form method="GET" action="order_history.php" class="order-filters">
                        <div class="filter-tabs">
                            <button type="submit" name="status" value="all" class="filter-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All Orders</button>
                            <button type="submit" name="status" value="pending" class="filter-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</button>
                            <button type="submit" name="status" value="processing" class="filter-tab <?php echo $statusFilter === 'processing' ? 'active' : ''; ?>">Processing</button>
                            <button type="submit" name="status" value="delivered" class="filter-tab <?php echo $statusFilter === 'delivered' ? 'active' : ''; ?>">Delivered</button>
                            <button type="submit" name="status" value="cancelled" class="filter-tab <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">Cancelled</button>
                        </div>
                        <div class="date-filter">
                            <input type="date" name="start_date" class="date-input" value="<?php echo htmlspecialchars($startDate); ?>">
                            <span>To</span>
                            <input type="date" name="end_date" class="date-input" value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                    </form>
                </div>

                <div class="order-table">
                    <table>
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
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($order['created_at']))); ?></td>
                                        <td>Cash</td> <!-- Payment method không có trong bảng orders, tạm để "Cash" -->
                                        <td><span class="status-badge <?php echo htmlspecialchars(strtolower($order['status'])); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                                        <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                        <td>
                                            <a href="order_id_history.php?order_id=<?php echo htmlspecialchars($order['id']); ?>" class="details-btn">Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer>
      <div class="container">
        <p>&copy; Mahiru Shop. We are pleased to serve you.</p>
      </div>
    </footer>
</body>
</html>