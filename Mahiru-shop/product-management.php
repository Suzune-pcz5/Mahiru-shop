<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mahiru_shop";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// ==== Toggle status nếu có request ====
if (isset($_GET['toggle_id']) && isset($_GET['current'])) {
    $toggle_id = (int)$_GET['toggle_id'];
    $current_status = (int)$_GET['current'];
    $new_status = $current_status ? 0 : 1;

    $stmt = $conn->prepare("UPDATE products SET is_hidden = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $toggle_id);
    $stmt->execute();
    $stmt->close();

    header("Location: product-management.php?" . http_build_query(array_diff_key($_GET, ['toggle_id' => '', 'current' => ''])));
    exit();
}

// ==== Xử lý tìm kiếm và phân trang ====
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng sản phẩm
$count_sql = "SELECT COUNT(*) AS total FROM products WHERE 1";
if (!empty($search)) {
    $count_sql .= " AND (name LIKE '%$search%')";
}
$count_result = $conn->query($count_sql);
$totalProducts = $count_result->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $limit);

// Truy vấn danh sách sản phẩm
$sql = "SELECT * FROM products WHERE 1";
if (!empty($search)) {
    $sql .= " AND (name LIKE '%$search%')";
}
$sql .= " LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management - Mahiru Shop</title>
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
                <div class="user-icon"><i data-lucide="user-circle"></i></div>
                <span class="admin-name">ADMIN</span>
                <a href="./loginad.php" class="logout">Log out</a>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="admin-panel">
                <div class="admin-sidebar">
                    <h2>Product Management</h2>
                    <ul>
                        <li><a href="./product-management.php">Product List</a></li>
                        <li><a href="./add-product.php" class="active">Add New Product</a></li>
                    </ul>
                </div>
                <div class="admin-content">
                    <h2>Product List</h2>
                    <form method="GET" class="product-search">
                        <input type="text" name="search" placeholder="Search for a product..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </form>
                    <table class="product-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Image</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $statusText = $row['is_hidden'] ? 'Disable' : 'Enable';
                                $statusColor = $row['is_hidden'] ? 'red' : 'green';
                                $toggleLink = "product-management.php?" . http_build_query(array_merge($_GET, ['toggle_id' => $row['id'], 'current' => $row['is_hidden']]));

                                echo "<tr>";
                                echo "<td>{$row["id"]}</td>";
                                echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                                echo "<td><img src='" . htmlspecialchars($row["image"]) . "' alt='' class='product-image' width='60'></td>";
                                echo "<td>" . htmlspecialchars($row["category"]) . "</td>";
                                echo "<td>$" . number_format($row["price"], 2) . "</td>";
                                echo "<td><a href='$toggleLink' class='status-link' style='color:$statusColor;'>$statusText</a></td>";
                                echo "<td><a href='edit-product.php?id={$row["id"]}' class='action-btn'>Edit</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No products found</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>

                    <div class="pagination">
                        <?php if ($totalPages > 1): ?>
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo;</a>
                            <?php else: ?><span>&laquo;</span><?php endif; ?>

                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>"
                                   class="<?php echo ($p == $page) ? 'active' : ''; ?>"><?php echo $p; ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">&raquo;</a>
                            <?php else: ?><span>&raquo;</span><?php endif; ?>
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
