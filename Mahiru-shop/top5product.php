<?php
// Kết nối database
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

// Truy vấn top 5 sản phẩm bán chạy nhất
$sql = "SELECT id, name, sold_count, price, (sold_count * price) AS revenue 
        FROM products 
        ORDER BY sold_count DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute();
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <li><a href="./top5product.php"class="active"> Top 5 Product</a></li>
                </ul>
            </div>
            <section class="admin-content">
                <h3>Top 5 product</h3>
                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>product</th>
                            <th>total</th>
                            <th>Revenue</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php if (!empty($topProducts)): ?>
        <?php foreach ($topProducts as $index => $product): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo $product['sold_count']; ?></td>
                <td>$<?php echo number_format($product['revenue'], 2); ?></td>
                <td>
                    <a href="related-invoice.php?product_id=<?php echo $product['id']; ?>"class="btn">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="5">No products found.</td>
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