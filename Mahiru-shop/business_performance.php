<?php
// ========================
// Kết nối CSDL
// ========================
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "mahiru_shop"; // Tên DB của bạn

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    // Thiết lập chế độ lỗi
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Kết nối CSDL thất bại: " . $e->getMessage();
    exit();
}

// ========================
// Lấy dữ liệu filter ngày
// ========================
$fromDate = isset($_GET['start-date']) ? $_GET['start-date'] : '';
$toDate   = isset($_GET['end-date'])   ? $_GET['end-date']   : '';

// ========================
// Phân trang
// ========================
$limit = 7; // Số sản phẩm mỗi trang
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ========================
// Tạo điều kiện search
// ========================
// Chúng ta sẽ gắn điều kiện vào $whereClause
// để thêm vào câu SQL SELECT * FROM products ... WHERE ...
$params = [];
$whereClause = "";

// Nếu cả fromDate và toDate đều có
if (!empty($fromDate) && !empty($toDate)) {
    $whereClause = " WHERE created_at BETWEEN :fromDate AND :toDate ";
    $params[':fromDate'] = $fromDate; // 'YYYY-MM-DD'
    // Muốn tính hết ngày "toDate", thêm 23:59:59
    $params[':toDate']   = $toDate . " 23:59:59";
} 
// Nếu chỉ có fromDate
elseif (!empty($fromDate)) {
    $whereClause = " WHERE created_at >= :fromDate ";
    $params[':fromDate'] = $fromDate;
} 
// Nếu chỉ có toDate
elseif (!empty($toDate)) {
    $whereClause = " WHERE created_at <= :toDate ";
    $params[':toDate']   = $toDate . " 23:59:59";
}

// ========================
// Đếm tổng số row (phục vụ phân trang)
// ========================
$sqlCount = "SELECT COUNT(*) AS total FROM products $whereClause";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->execute($params);
$totalRow = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

// Tính số trang
$totalPages = ceil($totalRow / $limit);

// ========================
// Lấy danh sách products
// ========================
$sql = "SELECT * FROM products 
        $whereClause
        ORDER BY id ASC
        LIMIT :offset, :limit";
$stmt = $conn->prepare($sql);

// Bind param cho WHERE
foreach($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Bind param cho LIMIT
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->execute();

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========================
// Tính các chỉ số cuối trang
// ========================

// 1) Total Revenue (SUM(price * sold_count))
$sqlRevenue = "SELECT SUM(price * sold_count) AS total_revenue FROM products";
$stmtRevenue = $conn->query($sqlRevenue);
$totalRevenue = $stmtRevenue->fetch(PDO::FETCH_ASSOC)['total_revenue'];
if (!$totalRevenue) $totalRevenue = 0;

// 2) Best-selling Product
$sqlBest = "SELECT name, sold_count FROM products ORDER BY sold_count DESC LIMIT 1";
$stmtBest = $conn->query($sqlBest);
$bestProduct = $stmtBest->fetch(PDO::FETCH_ASSOC);

// 3) Least-selling Product
$sqlLeast = "SELECT name, sold_count FROM products ORDER BY sold_count ASC LIMIT 1";
$stmtLeast = $conn->query($sqlLeast);
$leastProduct = $stmtLeast->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
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
                    <li><a href="./business_performance.php" class="active">Statistic</a></li>
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
            <div class="admin-sidebar">
                <h2>Business Performance</h2>
                <ul>
                    <li><a href="./business_performance.php" class="active">Product Statistics</a></li>
                    <li><a href="./top5customer.php">Top 5 Customer</a></li>
                    <li><a href="./top5product.php"class="active"> Top 5 Product</a></li>
                </ul>
            </div>
            <section class="admin-content">
                <h3>Product Statistics</h3>
                <!-- Form Search -->
                <div class="filters">
                    <form method="GET" action="" class="filters-row">
                        <div class="filter-group">
                            <label class="filter-label"></label>
                            <div class="date-range">
                                <div>
                                    <label>From:</label>
                                    <input 
                                        type="date" 
                                        id="start-date" 
                                        name="start-date"
                                        value="<?php echo htmlspecialchars($fromDate); ?>"
                                    />
                                </div>
                                <div class="date-to">
                                    <label>To:</label>
                                    <input 
                                        type="date" 
                                        id="end-date" 
                                        name="end-date"
                                        value="<?php echo htmlspecialchars($toDate); ?>"
                                    />
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn">Search</button>
                    </form>
                </div>

                <!-- Bảng sản phẩm -->
                                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Image</th>
                            <th>Price</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                            <th>Customer Purchased</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(!empty($products)): ?>
                        <?php 
                        $i = $offset + 1; 
                        foreach($products as $prod): 
                            // Tính doanh thu = price * sold_count
                            $revenue = $prod['price'] * $prod['sold_count'];
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($prod['name']); ?></td>
                            <td>
                                <?php if(!empty($prod['image'])): ?>
                                    <img 
                                        src="<?php echo htmlspecialchars($prod['image']); ?>" 
                                        alt="<?php echo htmlspecialchars($prod['name']); ?>" 
                                        class="product-image" 
                                        style="max-width: 80px;"
                                    >
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($prod['price']); ?>$</td>
                            <td><?php echo htmlspecialchars($prod['sold_count']); ?></td>
                            <td><?php echo number_format($revenue, 2); ?>$</td>
                            <td>
                                <a 
                                     href="./related-invoice.php?product_id=<?php echo $prod['id']; ?>" 
                                     class="btn" 
                                     style="background-color: green; border-color: green; color: white"
                                 >
                                     View
                                 </a>

                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No products found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <!-- Phân trang -->
                <div class="pagination">
                    <?php if($totalPages > 1): ?>
                        <!-- Nút về trang trước -->
                        <?php if($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">&laquo;</a>
                        <?php else: ?>
                            <span>&laquo;</span>
                        <?php endif; ?>

                        <?php for($p=1; $p<=$totalPages; $p++): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>" 
                                class="<?php echo ($p == $page) ? 'active' : ''; ?>"
                            >
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Nút qua trang kế -->
                        <?php if($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">&raquo;</a>
                        <?php else: ?>
                            <span>&raquo;</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Thống kê cuối trang -->
                <div class="stats">
                    <div class="stat-item">
                        <h4>Total Revenue</h4>
                        <p>
                            <?php 
                                // Format cho đẹp
                                echo '$' . number_format($totalRevenue, 2); 
                            ?>
                        </p>
                    </div>
                    <div class="stat-item">
                        <h4>Best-selling Product</h4>
                        <?php if($bestProduct): ?>
                            <p>
                                <?php 
                                    echo htmlspecialchars($bestProduct['name']) 
                                         . " (" . $bestProduct['sold_count'] . " sold)"; 
                                ?>
                            </p>
                        <?php else: ?>
                            <p>No data</p>
                        <?php endif; ?>
                    </div>
                    <div class="stat-item">
                        <h4>Least-selling Product</h4>
                        <?php if($leastProduct): ?>
                            <p>
                                <?php 
                                    echo htmlspecialchars($leastProduct['name']) 
                                         . " (" . $leastProduct['sold_count'] . " sold)"; 
                                ?>
                            </p>
                        <?php else: ?>
                            <p>No data</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <script>
        lucide.createIcons();
    </script>
    <footer>
        <div class="container">
            <p>&copy;Mahiru Shop. We are pleased to serve you.</p>
        </div>
    </footer>
</body>
</html>
