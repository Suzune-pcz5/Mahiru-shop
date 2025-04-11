<?php
session_start();

// ========== KẾT NỐI CSDL ==========
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

// ========== LẤY THÔNG TIN USER TỪ SESSION ==========
$currentUser = isset($_SESSION['user_name']) ? [
    'username' => $_SESSION['user_name'],
    'role'     => $_SESSION['user_role'] ?? 'user'
] : null;

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
    // Tạo lại URL với các tham số hiện tại, trừ 'add_to_cart'
    $redirectParams = $_GET;
    unset($redirectParams['add_to_cart']);
    header("Location: index_account.php?" . http_build_query($redirectParams));
    exit;
}

// ========== LẤY DANH MỤC TỪ BẢNG products ==========
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// ========== XỬ LÝ TÌM KIẾM, LỌC SẢN PHẨM & PHÂN TRANG ==========
$searchName = $_GET['name'] ?? '';
$category   = $_GET['category'] ?? 'all';
$minPrice   = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$maxPrice   = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 300;

$limit = 9;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Xử lý nếu min_price > max_price
if ($minPrice > $maxPrice) {
    $temp = $minPrice;
    $minPrice = $maxPrice;
    $maxPrice = $temp;
}

$whereClauses = [];
$params = [];

if (!empty($searchName)) {
    $whereClauses[] = "name LIKE :name";
    $params[':name'] = "%$searchName%";
}
if ($category !== 'all') {
    $whereClauses[] = "category = :category";
    $params[':category'] = $category;
}
// Lọc theo khoảng giá
$whereClauses[] = "price BETWEEN :min_price AND :max_price";
$params[':min_price'] = $minPrice;
$params[':max_price'] = $maxPrice;

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Lấy tổng số sản phẩm
$countSql = "SELECT COUNT(*) FROM products $whereSql";
$countStmt = $conn->prepare($countSql);
foreach ($params as $key => $value) {
    $type = in_array($key, [':min_price', ':max_price']) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $countStmt->bindValue($key, $value, $type);
}
$countStmt->execute();
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Truy vấn sản phẩm
$sql = "SELECT * FROM products $whereSql LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $type = in_array($key, [':min_price', ':max_price']) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $type);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mahiru Shop</title>
    <link rel="stylesheet" href="./css/styles.css" />
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
                        <input type="hidden" name="min_price" value="<?php echo htmlspecialchars($minPrice); ?>" />
                        <input type="hidden" name="max_price" value="<?php echo htmlspecialchars($maxPrice); ?>" />
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
            <div class="filter-sidebar">
                <form action="index_account.php" method="GET">
                    <h3>Name:</h3>
                    <div class="filter-name">
                        <input type="text" name="name" placeholder="Enter product name" class="filter-input" value="<?php echo htmlspecialchars($searchName); ?>">
                    </div>
                    <h3>Category:</h3>
                    <div class="filter-category">
                        <select name="category" class="filter-select">
                            <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-price">
                        <h3>Price:</h3>
                        <div class="price-range-inputs">
                            <input type="number" name="min_price" min="0" max="300" placeholder="Min" value="<?php echo htmlspecialchars($minPrice); ?>">
                            <span>to</span>
                            <input type="number" name="max_price" min="0" max="300" placeholder="Max" value="<?php echo htmlspecialchars($maxPrice); ?>">
                        </div>
                    </div>
                    <button type="submit" class="filter-button" style="margin-top: 10px">Search</button>
                </form>
            </div>
            <section class="product-grid">
                <div class="product-categories">
                    <?php if (count($products) > 0): ?>
                        <?php foreach (array_chunk($products, 3) as $productRow): ?>
                            <div class="product-row">
                                <?php foreach ($productRow as $product): ?>
                                    <div class="product-card">
                                        <a href="product_details_acc.php?id=<?php echo $product['id']; ?>" class="product-image">
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                        </a>
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p><?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?></p>
                                        <span class="price">$<?php echo htmlspecialchars($product['price']); ?></span>
                                        <a href="index_account.php?add_to_cart=<?php echo $product['id']; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>&page=<?php echo $page; ?>" class="btn">Add to Cart</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No products found. Check your filters or database.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="index_account.php?page=<?php echo $page - 1; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>">« Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="index_account.php?page=<?php echo $i; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="index_account.php?page=<?php echo $page + 1; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>">Next »</a>
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

</body>
</html>
