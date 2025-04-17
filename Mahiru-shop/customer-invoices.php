<?php
session_start();

// Kết nối CSDL
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

// Lấy tham số từ URL
$userId    = $_GET['user_id'] ?? null;
$fromDate  = $_GET['start-date'] ?? '';
$toDate    = $_GET['end-date'] ?? '';

// Kiểm tra userId hợp lệ
if (!$userId) {
    echo "Thiếu thông tin khách hàng.";
    exit();
}

// Lấy tên khách hàng
$stmtUser = $conn->prepare("SELECT username FROM users WHERE id = :userId");
$stmtUser->execute([':userId' => $userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Không tìm thấy khách hàng.";
    exit();
}

// Lấy danh sách đơn hàng
$query = "SELECT id, created_at, total_price, status FROM orders WHERE user_id = :userId AND status = 'completed'";
$params = [':userId' => $userId];

if (!empty($fromDate) && !empty($toDate)) {
    $query .= " AND created_at BETWEEN :fromDate AND :toDate";
    $params[':fromDate'] = $fromDate . ' 00:00:00';
    $params[':toDate']   = $toDate . ' 23:59:59';
}

$query .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Customer Invoices</title>
    <link rel="stylesheet" href="./css/order.css">
</head>
<body>

<header>
    <div class="container">
        <h1 class="logo">MAHIRU<span>.</span> ADMIN</h1>
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
    <div class="container admin-panel" style="border-left-width: 0; padding-left: 0; padding-right: 0;">
        <section class="admin-content">
            <h3>Invoices of <?php echo htmlspecialchars($user['username']); ?></h3>

            <?php if (!empty($fromDate) && !empty($toDate)): ?>
                <p>From <strong><?php echo htmlspecialchars($fromDate); ?></strong> to <strong><?php echo htmlspecialchars($toDate); ?></strong></p>
            <?php endif; ?>
<table class="table-wrapper">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $index => $order): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($order['status'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No invoices found in selected range.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <br>
            <a href="./top5customer.php" class="btn">← Back to Top 5</a>
        </section>
    </div>
</main>

<footer>
    <div class="container">
        <p>©Mahiru Shop. We are pleased to serve you.</p>
    </div>
</footer>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>