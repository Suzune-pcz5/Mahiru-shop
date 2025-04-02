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
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Kiểm tra nếu order_id không hợp lệ
if ($orderId <= 0) {
    header("Location: order-management.php");
    exit();
}

// Lấy thông tin hóa đơn
$orderStmt = $conn->prepare("SELECT id, status FROM orders WHERE id = :order_id");
$orderStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
$orderStmt->execute();
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: order-management.php");
    exit();
}

// Xử lý cập nhật trạng thái khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = isset($_POST['new-status']) ? $_POST['new-status'] : '';

    // Kiểm tra trạng thái mới có hợp lệ không
    $validStatuses = ['pending', 'processing', 'confirmed', 'delivered', 'cancelled'];
    if (in_array($newStatus, $validStatuses)) {
        // Cập nhật trạng thái trong cơ sở dữ liệu
        $updateStmt = $conn->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
        $updateStmt->bindValue(':status', $newStatus, PDO::PARAM_STR);
        $updateStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $updateStmt->execute();

        // Chuyển hướng về trang chi tiết hóa đơn với thông báo
        header("Location: detail-order.php?id=$orderId&message=Status updated successfully!");
        exit();
    } else {
        $error = "Invalid status selected.";
    }
}
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
                <span class="admin-name">Admin</span>
                <a href="./loginad.php" class="logout">Log out</a>
            </div>
        </div>
    </header>
    <main>
        <div class="container">
            <div class="admin-content2">
                <h1>Change Order Status</h1>
                <?php if (isset($error)): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <form id="orderStatusForm" method="POST" action="">
                    <div class="form-group">
                        <label for="order-id">Order ID:</label>
                        <input type="text" id="order-id" name="order-id" value="#<?php echo htmlspecialchars($order['id']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="current-status">Current Status:</label>
                        <input type="text" id="current-status" name="current-status" value="<?php echo htmlspecialchars(ucfirst($order['status'])); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="new-status">New Status:</label>
                        <select id="new-status" name="new-status">
                            <option value="">Select new status</option>
                            <option value="pending" <?php if ($order['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="processing" <?php if ($order['status'] === 'processing') echo 'selected'; ?>>Processing</option>
                            <option value="confirmed" <?php if ($order['status'] === 'confirmed') echo 'selected'; ?>>Confirmed</option>
                            <option value="delivered" <?php if ($order['status'] === 'delivered') echo 'selected'; ?>>Delivered</option>
                            <option value="cancelled" <?php if ($order['status'] === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit">Update Status</button>
                </form>
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

        // Hiển thị thông báo nếu có message từ URL
        <?php if (isset($_GET['message'])): ?>
            alert("<?php echo htmlspecialchars($_GET['message']); ?>");
        <?php endif; ?>
    </script>
</body>
</html>