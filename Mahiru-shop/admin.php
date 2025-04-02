<?php
session_start();

// Kết nối cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mahiru_shop";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// 1. Tổng số sản phẩm
$total_products_sql = "SELECT COUNT(*) as total FROM products";
$total_products_result = $conn->query($total_products_sql);
$total_products = $total_products_result->fetch_assoc()['total'];

// 2. Tổng số đơn hàng
$total_orders_sql = "SELECT COUNT(*) as total FROM orders";
$total_orders_result = $conn->query($total_orders_sql);
$total_orders = $total_orders_result->fetch_assoc()['total'];

// 3. Tổng số khách hàng
$total_customers_sql = "SELECT COUNT(*) as total FROM users";
$total_customers_result = $conn->query($total_customers_sql);
$total_customers = $total_customers_result->fetch_assoc()['total'];

// 4. Doanh thu (chỉ tính đơn hàng có status = "completed")
$revenue_sql = "SELECT SUM(total_price) as total FROM orders WHERE status = 'completed'";
$revenue_result = $conn->query($revenue_sql);
if ($revenue_result === false) {
    die("Lỗi truy vấn doanh thu: " . $conn->error);
}
$revenue_row = $revenue_result->fetch_assoc();
$revenue = $revenue_row['total'] ?? 0;

// 5. Lấy danh sách đơn hàng gần đây (giới hạn 3 bản ghi)
$recent_orders_sql = "SELECT o.id, o.name, o.created_at, o.total_price, o.status 
                      FROM orders o 
                      ORDER BY o.created_at DESC 
                      LIMIT 3";
$recent_orders_result = $conn->query($recent_orders_sql);

// 6. Lấy danh sách sản phẩm bán chạy nhất (dựa trên sold_count, giới hạn 3 sản phẩm)
$top_products_sql = "SELECT id, name, price, image, sold_count 
                     FROM products 
                     ORDER BY sold_count DESC 
                     LIMIT 3";
$top_products_result = $conn->query($top_products_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/admin-styles.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="page-container">
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
                <div class="admin-panel">
                    <div class="admin-content">
                        <h1>Dashboard Overview</h1>
                        <div class="dashboard-stats">
                            <div class="stat-card">
                                <h3>Total Products</h3>
                                <p class="stat-number"><?php echo number_format($total_products); ?></p>
                            </div>
                            <div class="stat-card">
                                <h3>Total Orders</h3>
                                <p class="stat-number"><?php echo number_format($total_orders); ?></p>
                            </div>
                            <div class="stat-card">
                                <h3>Total Customers</h3>
                                <p class="stat-number"><?php echo number_format($total_customers); ?></p>
                            </div>
                            <div class="stat-card">
                                <h3>Revenue</h3>
                                <p class="stat-number">$<?php echo number_format($revenue, 2); ?></p>
                                <?php if ($revenue == 0): ?>
                                    <p style="color: red; font-size: 0.9em;">No completed orders yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h2>Recent Orders</h2>
                        <table class="order-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_orders_result->num_rows > 0): ?>
                                    <?php while ($order = $recent_orders_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['name']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                            <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                            <td><?php echo ucfirst($order['status']); ?></td>
                                            <td><a href="order-details.php?order_id=<?php echo $order['id']; ?>" class="action-btn">View</a></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No recent orders found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <h2>Top Selling Products</h2>
                        <div class="product-grid">
                            <?php if ($top_products_result->num_rows > 0): ?>
                                <?php while ($product = $top_products_result->fetch_assoc()): ?>
                                    <div class="product-card">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                                        <p class="sales">Sales: <?php echo $product['sold_count']; ?></p>
                                        <a class="action-btn" href="edit-product.php?id=<?php echo $product['id']; ?>">Edit</a>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No top selling products found.</p>
                            <?php endif; ?>
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
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>

<?php
$conn->close();
?>