<?php
session_start();

// Phần kết nối cơ sở dữ liệu
$host = 'localhost'; // Tên máy chủ MySQL
$dbname = 'mahiru_shop'; // Tên cơ sở dữ liệu
$username = 'root'; // Tên người dùng MySQL
$password = ''; // Mật khẩu MySQL

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ========== LẤY THÔNG TIN USER TỪ SESSION ==========
$currentUser = null;
if (isset($_SESSION['user_name']) && isset($_SESSION['user_role'])) {
    $currentUser = [
        'username' => $_SESSION['user_name'],
        'role'     => $_SESSION['user_role']
    ];
}

// ========== XỬ LÝ THÊM SẢN PHẨM VÀO GIỎ HÀNG ==========
if (isset($_GET['add_to_cart']) && isset($_SESSION['user_id'])) {
    $productId = (int)$_GET['add_to_cart'];
    $userId = $_SESSION['user_id'];

    // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
    $checkStmt = $conn->prepare("SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id");
    $checkStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        // Nếu sản phẩm đã có, tăng số lượng
        $newQuantity = $existingItem['quantity'] + 1;
        $updateStmt = $conn->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
        $updateStmt->bindValue(':quantity', $newQuantity, PDO::PARAM_INT);
        $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $updateStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $updateStmt->execute();
    } else {
        // Nếu sản phẩm chưa có, thêm mới
        $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, 1)");
        $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $insertStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $insertStmt->execute();
    }

    $_SESSION['success_message'] = "Product added to cart successfully!";
    // Chuyển hướng về search_account.php, giữ các tham số tìm kiếm hiện tại
    $redirectParams = [
        'name' => urlencode($_GET['name'] ?? ''),
        'category' => urlencode($_GET['category'] ?? 'all'),
        'price' => $_GET['price'] ?? 300,
        'sort' => $_GET['sort'] ?? 'relevance',
        'page' => $_GET['page'] ?? 1
    ];
    header("Location: search_account.php?" . http_build_query($redirectParams));
    exit;
}

// ========== LẤY DANH MỤC TỪ BẢNG products ==========
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Xử lý tìm kiếm, lọc và sắp xếp
$searchName = isset($_GET['name']) ? $_GET['name'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$priceRange = isset($_GET['price']) ? $_GET['price'] : 300;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance'; // Mặc định là relevance

// Phân trang
$limit = 9; // Số sản phẩm trên mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Trang hiện tại, mặc định là 1
$offset = ($page - 1) * $limit; // Tính toán offset

// Lấy tổng số sản phẩm theo điều kiện lọc
$countSql = "SELECT COUNT(*) FROM products WHERE price <= :price";
if (!empty($searchName)) {
    $countSql .= " AND name LIKE :name";
}
if ($category != 'all') {
    $countSql .= " AND category = :category";
}
$countStmt = $conn->prepare($countSql);
$countStmt->bindValue(':price', $priceRange, PDO::PARAM_INT);
if (!empty($searchName)) {
    $countStmt->bindValue(':name', "%$searchName%", PDO::PARAM_STR);
}
if ($category != 'all') {
    $countStmt->bindValue(':category', $category, PDO::PARAM_STR);
}
$countStmt->execute();
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit); // Tính tổng số trang

// Xây dựng câu truy vấn SQL với điều kiện lọc
$sql = "SELECT * FROM products WHERE price <= :price";
if (!empty($searchName)) {
    $sql .= " AND name LIKE :name";
}
if ($category != 'all') {
    $sql .= " AND category = :category";
}

// Thêm ORDER BY để sắp xếp trên toàn bộ kết quả
switch ($sort) {
    case 'newest':
        $sql .= " ORDER BY created_at DESC";
        break;
    case 'best_selling':
        $sql .= " ORDER BY sold_count DESC";
        break;
    case 'low_to_high':
        $sql .= " ORDER BY price ASC";
        break;
    case 'high_to_low':
        $sql .= " ORDER BY price DESC";
        break;
    case 'relevance':
    default:
        // Mặc định không sắp xếp hoặc sắp xếp theo tên (có thể tùy chỉnh)
        $sql .= " ORDER BY name ASC";
        break;
}

// Thêm LIMIT và OFFSET để phân trang sau khi sắp xếp
$sql .= " LIMIT :limit OFFSET :offset";

// Chuẩn bị và thực thi truy vấn
$stmt = $conn->prepare($sql);
$stmt->bindValue(':price', $priceRange, PDO::PARAM_INT);
if (!empty($searchName)) {
    $stmt->bindValue(':name', "%$searchName%", PDO::PARAM_STR);
}
if ($category != 'all') {
    $stmt->bindValue(':category', $category, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hàm tạo URL giữ các tham số hiện tại
function buildSortUrl($sortOption, $searchName, $category, $priceRange, $page) {
    $params = [
        'sort' => $sortOption,
        'name' => urlencode($searchName),
        'category' => urlencode($category),
        'price' => $priceRange,
        'page' => $page
    ];
    return 'search_account.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mahiru Shop</title>
    <link rel="stylesheet" href="./css/search.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <style>
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
        }
        .success-message.show {
            display: block;
        }
    </style>
</head>
<body>
    <header>
        <div class="top-bar">
            <div class="container">
                <div class="contact-info">
                    <span><i class="fas fa-phone"></i> 012345678</span>
                    <span><i class="fas fa-envelope"></i> mahiru@gmail.com</span>
                    <span><i class="fas fa-map-marker-alt"></i> 1104 Wall Street</span>
                </div>
                <div class="user-actions">
                    <?php if ($currentUser): ?>
                        <i class="fas fa-user"></i>
                        <?php if (strtolower($currentUser['role']) === 'admin'): ?>
                            <span class="name">ADMIN</span>
                        <?php else: ?>
                            <span class="name"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                        <?php endif; ?>
                        <div class="login-dropdown">
                            <?php if (strtolower($currentUser['role']) === 'admin'): ?>
                                <a href="edit.php" class="login-option">Edit</a>
                            <?php else: ?>
                                <a href="order_history.php" class="login-option">Order history</a>
                            <?php endif; ?>
                            <a href="edit_profile.php" class="login-option">Edit Profile</a>
                            <a href="index.php" class="login-option">Log out</a>
                        </div>
                    <?php else: ?>
                        <a class="login-link">
                            <i class="fas fa-user"></i>
                            <span class="name">Login/Sign up</span>
                        </a>
                        <div class="login-dropdown">
                            <a href="login.php" class="login-option">Login</a>
                            <a href="sign_up.php" class="login-option">Sign up</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="main-header">
            <div class="container">
                <div class="logo">
                    <a href="index_account.php" class="logo-link"><h1>MAHIRU<span>.</span></h1></a>
                </div>
                <div class="search-bar">
                    <form action="search_account.php" method="GET">
                        <input type="text" name="name" placeholder="Search here" value="<?php echo htmlspecialchars($searchName); ?>" />
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>" />
                        <input type="hidden" name="price" value="<?php echo htmlspecialchars($priceRange); ?>" />
                        <button type="submit" class="search-button">Search</button>
                    </form>
                </div>
                <div class="user-menu">
                    <a href="cart.php" class="icon"><i class="fas fa-shopping-cart"></i></a>
                </div>
            </div>
        </div>
        <nav class="category-nav">
            <div class="container">
                <ul class="category-list">
                    <li><a href="index_account.php">Home</a></li>
                    <?php foreach ($categories as $cat): ?>
                        <li><a href="index_account.php?category=<?= urlencode($cat['category']) ?>"> <?= htmlspecialchars($cat['category']) ?> </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main>
        <div class="container">
            <!-- Filter Bar -->
            <div class="filter-sidebar">
                <form action="search_account.php" method="GET">
                    <h3>Name:</h3>
                    <div class="filter-name">
                        <input type="text" name="name" placeholder="Enter product name" class="filter-input" value="<?php echo htmlspecialchars($searchName); ?>" />
                    </div>

                    <h3>Category:</h3>
                    <div class="filter-category">
                        <select name="category" class="filter-select">
                            <option value="all" <?php echo ($category == 'all') ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo ($category == $cat['category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $cat['category']))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h3>Price:</h3>
                    <div class="filter-price">
                        <label for="priceRange">Range:</label>
                        <div class="range-container custom-range">
                            <div class="range-label">0</div>
                            <input type="range" id="priceRange" name="price" min="0" max="300" value="<?php echo $priceRange; ?>" class="filter-input" />
                            <div class="range-label">300</div>
                        </div>
                        <!-- Thêm phần hiển thị giá trị hiện tại -->
                        <div class="price-value" style="margin-top: 5px;">
                            Current Price: $<span id="priceOutput"><?php echo htmlspecialchars($priceRange); ?></span>
                        </div>
                    </div>

                    <button type="submit" class="filter-button">Search</button>
                </form>
            </div>

            <!-- Product Grid -->
            <section class="product-grid">
                <div class="filter-box">
                    <span class="sort-label">Sort by:</span>
                    <a class="sort-label" href="<?php echo buildSortUrl('relevance', $searchName, $category, $priceRange, $page); ?>"><button class="filter-btn">Relevance</button></a>
                    <a class="sort-label" href="<?php echo buildSortUrl('newest', $searchName, $category, $priceRange, $page); ?>"><button class="filter-btn">Newest</button></a>
                    <a class="sort-label" href="<?php echo buildSortUrl('best_selling', $searchName, $category, $priceRange, $page); ?>"><button class="filter-btn">Best Selling</button></a>
                    <div class="filter-option">
                        <label class="price-btn" for="price-toggle">Price</label>
                        <input type="checkbox" id="price-toggle" class="price-toggle" />
                        <div class="price-dropdown">
                            <a href="<?php echo buildSortUrl('low_to_high', $searchName, $category, $priceRange, $page); ?>" class="price-option">Low to High</a>
                            <a href="<?php echo buildSortUrl('high_to_low', $searchName, $category, $priceRange, $page); ?>" class="price-option">High to Low</a>
                        </div>
                    </div>
                </div>
                <div class="search-results">
                    <h1>Search Results:</h1>
                </div>
                <?php if (count($products) > 0) : ?>
                    <?php foreach (array_chunk($products, 3) as $productRow) : ?>
                        <div class="product-row">
                            <?php foreach ($productRow as $product) : ?>
                                <div class="product-card">
                                    <a href="product_details_acc.php?id=<?php echo $product['id']; ?>" class="product-image">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                    </a>
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                                    <span class="price">$<?php echo htmlspecialchars($product['price']); ?></span>
                                    <a href="search_account.php?add_to_cart=<?php echo $product['id']; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&price=<?php echo $priceRange; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page; ?>" class="btn">Add to Cart</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No products found.</p>
                <?php endif; ?>
            </section>
        </div>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="search_account.php?page=<?php echo $page - 1; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&price=<?php echo $priceRange; ?>&sort=<?php echo $sort; ?>">« Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="search_account.php?page=<?php echo $i; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&price=<?php echo $priceRange; ?>&sort=<?php echo $sort; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="search_account.php?page=<?php echo $page + 1; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&price=<?php echo $priceRange; ?>&sort=<?php echo $sort; ?>">Next »</a>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>© Mahiru Shop. We are pleased to serve you.</p>
        </div>
    </footer>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success-message" id="successPopup"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var popup = document.getElementById('successPopup');
                popup.classList.add('show');
                setTimeout(function() {
                    popup.classList.remove('show');
                    <?php unset($_SESSION['success_message']); ?>
                }, 3000);
            });
        </script>
    <?php endif; ?>

    <!-- Thêm JavaScript để cập nhật giá trị thanh trượt -->
    <script>
        const priceRange = document.getElementById('priceRange');
        const priceOutput = document.getElementById('priceOutput');
        priceOutput.textContent = priceRange.value;
        priceRange.addEventListener('input', function() {
            priceOutput.textContent = priceRange.value;
        });
    </script>

    <script src="./js/popup.js"></script>
</body>
</html>