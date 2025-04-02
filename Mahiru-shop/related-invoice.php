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

// Lấy product_id từ URL
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Kiểm tra nếu product_id không hợp lệ
if ($productId <= 0) {
    header("Location: business_performance.php");
    exit();
}

// Lấy thông tin sản phẩm để hiển thị tên
$productStmt = $conn->prepare("SELECT name FROM products WHERE id = :product_id");
$productStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
$productStmt->execute();
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: business_performance.php");
    exit();
}
$productName = $product['name'];

// Phân trang
$itemsPerPage = 5; // Số hóa đơn mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $itemsPerPage;

// Đếm tổng số hóa đơn có chứa sản phẩm
$countStmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.id) as total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.product_id = :product_id
");
$countStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
$countStmt->execute();
$totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tính tổng số trang
$totalPages = ceil($totalItems / $itemsPerPage);

// Lấy danh sách hóa đơn có chứa sản phẩm
$stmt = $conn->prepare("
    SELECT DISTINCT o.id, o.created_at, o.total_price
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.product_id = :product_id
    ORDER BY o.created_at DESC
    LIMIT :offset, :items_per_page
");
$stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
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
                    <td>Invoice related to the product: <?php echo htmlspecialchars($productName); ?></td>
                </tr>
            </table>
            <div class="admin-panel">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>InvoicesID</th>
                            <th>Date</th>
                            <th>Amount</th>
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
                                        <div class="invoice-actions">
                                            <a href="detail-invoice.php?order_id=<?php echo $invoice['id']; ?>" class="btn">View</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No invoices found related to this product.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Phân trang -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?product_id=<?php echo $productId; ?>&page=<?php echo $page - 1; ?>">«</a>
                <?php else: ?>
                    <a href="#" class="disabled">«</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?product_id=<?php echo $productId; ?>&page=<?php echo $i; ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?product_id=<?php echo $productId; ?>&page=<?php echo $page + 1; ?>">»</a>
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