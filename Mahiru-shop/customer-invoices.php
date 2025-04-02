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

// Lấy user_id từ URL
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Kiểm tra nếu user_id không hợp lệ
if ($userId <= 0) {
    header("Location: top5customer.php");
    exit();
}

// Lấy thông tin khách hàng để hiển thị tên
$userStmt = $conn->prepare("SELECT username FROM users WHERE id = :user_id");
$userStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: top5customer.php");
    exit();
}
$username = $user['username'];

// Phân trang
$itemsPerPage = 5; // Số hóa đơn mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $itemsPerPage;

// Đếm tổng số hóa đơn của khách hàng
$countStmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM orders
    WHERE user_id = :user_id
");
$countStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$countStmt->execute();
$totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tính tổng số trang
$totalPages = ceil($totalItems / $itemsPerPage);

// Lấy danh sách hóa đơn của khách hàng
$stmt = $conn->prepare("
    SELECT id, created_at, total_price, status
    FROM orders
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT :offset, :items_per_page
");
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':items_per_page', $itemsPerPage, PDO::PARAM_INT);
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mahiru Shop</title>
    <script src="https://unpkg.com/lucide@latest"></script>
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
        <div class="container">
            <table class="customer-info">
                <tr>
                    <td>Customer Invoices: <?php echo htmlspecialchars($username); ?></td>
                </tr>
            </table>
            <div class="admin-panel">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Orders</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($invoices)): ?>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td>INV-<?php echo str_pad($invoice['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['created_at']); ?></td>
                                    <td>$<?php echo number_format($invoice['total_price'], 2); ?></td>
                                    <td>
                                        <span class="invoice-status status-<?php echo strtolower($invoice['status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($invoice['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="invoice-actions">
                                            <a href="detail-invoice.php?order_id=<?php echo $invoice['id']; ?>" class="btn">View</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No invoices found for this customer.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Phân trang -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?user_id=<?php echo $userId; ?>&page=<?php echo $page - 1; ?>">«</a>
                <?php else: ?>
                    <a href="#" class="disabled">«</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?user_id=<?php echo $userId; ?>&page=<?php echo $i; ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?user_id=<?php echo $userId; ?>&page=<?php echo $page + 1; ?>">»</a>
                <?php else: ?>
                    <a href="#" class="disabled">»</a>
                <?php endif; ?>
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