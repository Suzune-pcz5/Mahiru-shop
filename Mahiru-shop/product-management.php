<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mahiru_shop";

// Kết nối đến MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

// Tính tổng số sản phẩm với điều kiện tìm kiếm
$count_sql = "SELECT COUNT(*) AS total FROM products";
if (!empty($search)) {
    $count_sql .= " WHERE name LIKE '%$search%' OR category LIKE '%$search%'";
}
$count_result = $conn->query($count_sql);
$totalProducts = 0;
if ($count_result && $count_result->num_rows > 0) {
    $row = $count_result->fetch_assoc();
    $totalProducts = $row['total'];
}
$totalPages = ceil($totalProducts / $limit);

// Truy vấn lấy danh sách sản phẩm
$sql = "SELECT * FROM products";
if (!empty($search)) {
    $sql .= " WHERE name LIKE '%$search%' OR category LIKE '%$search%'";
}
$sql .= " LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/admin-styles.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .product-search {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .product-search input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .product-search button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .product-search button:hover {
            background-color: #45a049;
        }
    </style>
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
                            <input type="text" name="search" id="product-search" 
                                   placeholder="Search for a product..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit">Search</button>
                        </form>
                        <table class="product-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Product image</th>
                                    <th>Category</th>
                                    <th>Price</th>                                       
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row["id"] . "</td>";
                                    echo "<td>" . $row["name"] . "</td>";
                                    echo "<td><img src='" . $row["image"] . "' alt='" . $row["name"] . "' class='product-image'></td>";
                                    echo "<td>" . $row["category"] . "</td>";
                                    echo "<td>$" . number_format($row["price"], 2) . "</td>";
                                    echo "<td><a href='edit-product.php?id=" . $row["id"] . "' class='action-btn'>Edit</a></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No products found</td></tr>";
                            }
                            ?>
                            </tbody>
                        </table>

                        <div class="pagination">
                            <?php if($totalPages > 1): ?>
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

                                <?php if($page < $totalPages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">&raquo;</a>
                                <?php else: ?>
                                    <span>&raquo;</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <div class="container">
                <p>&copy; Mahiru Shop. We are pleased to serve you.</p>
            </div>
        </footer>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>