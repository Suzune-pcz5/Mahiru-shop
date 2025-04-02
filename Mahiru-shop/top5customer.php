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

// Truy vấn top 5 khách hàng dựa trên tổng doanh thu
$stmt = $conn->prepare("
    SELECT u.id, u.username, SUM(o.total_price) as total_revenue
    FROM users u
    JOIN orders o ON u.id = o.user_id
    GROUP BY u.id, u.username
    ORDER BY total_revenue DESC 
    LIMIT 5
");
$stmt->execute();
$topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <div class="container admin-panel" style="
        border-left-width: 0px;
        padding-left: 0px;
        padding-right: 0px;
    ">
            <div class="admin-sidebar">
                <h2>Business Performance</h2>
                <ul>
                    <li><a href="./business_performance.php">Product Statistics</a></li>
                    <li><a href="./top5customer.php" class="active">Top 5 Customer</a></li>
                </ul>
            </div>
            <section class="admin-content">
                <h3>Top 5 Customers</h3>
                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Revenue</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($topCustomers)): ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($topCustomers as $customer): ?>
                                <tr>
                                    <td><?php echo $rank++; ?></td>
                                    <td><?php echo htmlspecialchars($customer['username']); ?></td>
                                    <td>$<?php echo number_format($customer['total_revenue'], 2); ?></td>
                                    <td>
                                        <a href="./customer-invoices.php?user_id=<?php echo $customer['id']; ?>" class="btn">View Invoices</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No customers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
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