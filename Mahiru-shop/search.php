<?php
session_start();

// Phần kết nối cơ sở dữ liệu
$host = 'localhost';
$dbname = 'mahiru_shop';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Lấy thông tin user từ session
$currentUser = isset($_SESSION['user_name']) ? [
    'username' => $_SESSION['user_name'],
    'role'     => $_SESSION['user_role'] ?? 'user'
] : null;

// Xử lý thêm sản phẩm vào giỏ hàng
if (isset($_GET['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "Please log in to add products to your cart.";
        header("Location: login.php");
        exit;
    }

    $productId = (int)$_GET['add_to_cart'];
    $userId = $_SESSION['user_id'];

    $checkStmt = $conn->prepare("SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id");
    $checkStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        $newQuantity = $existingItem['quantity'] + 1;
        $updateStmt = $conn->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
        $updateStmt->bindValue(':quantity', $newQuantity, PDO::PARAM_INT);
        $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $updateStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $updateStmt->execute();
    } else {
        $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, 1)");
        $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $insertStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $insertStmt->execute();
    }

    $_SESSION['success_message'] = "Product added to cart successfully!";
    $redirectParams = [
        'name' => urlencode($_GET['name'] ?? ''),
        'category' => urlencode($_GET['category'] ?? 'all'),
        'min_price' => $_GET['min_price'] ?? 0,
        'max_price' => $_GET['max_price'] ?? 300,
        'sort' => $_GET['sort'] ?? 'relevance',
        'page' => $_GET['page'] ?? 1
    ];
    header("Location: search.php?" . http_build_query($redirectParams));
    exit;
}

// Lấy danh mục từ bảng products
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products WHERE is_hidden = 0");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Xử lý tìm kiếm, lọc và sắp xếp
$searchName = isset($_GET['name']) ? $_GET['name'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : 300;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';

// Validate price range if both values are provided
if ($minPrice > $maxPrice) {
    $temp = $minPrice;
    $minPrice = $maxPrice;
    $maxPrice = $temp;
}

// Phân trang
$limit = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Lấy tổng số sản phẩm
$countSql = "SELECT COUNT(*) FROM products WHERE is_hidden = 0";
$params = [];
if (!($minPrice === 0 && $maxPrice === 300)) {
    $countSql .= " AND price BETWEEN :min_price AND :max_price";
    $params[':min_price'] = $minPrice;
    $params[':max_price'] = $maxPrice;
}
if (!empty($searchName)) {
    $countSql .= " AND name LIKE :name";
    $params[':name'] = "%$searchName%";
}
if ($category != 'all') {
    $validCategories = array_column($categories, 'category');
    if (in_array($category, $validCategories)) {
        $countSql .= " AND category = :category";
        $params[':category'] = $category;
    } else {
        $_SESSION['error_message'] = "Invalid category selected.";
    }
}
$countStmt = $conn->prepare($countSql);
foreach ($params as $key => $value) {
    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $countStmt->bindValue($key, $value, $type);
}
$countStmt->execute();
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Xây dựng câu truy vấn SQL
$sql = "SELECT * FROM products WHERE is_hidden = 0";
$params = [];
if (!($minPrice === 0 && $maxPrice === 300)) {
    $sql .= " AND price BETWEEN :min_price AND :max_price";
    $params[':min_price'] = $minPrice;
    $params[':max_price'] = $maxPrice;
}
if (!empty($searchName)) {
    $sql .= " AND name LIKE :name";
    $params[':name'] = "%$searchName%";
}
if ($category != 'all' && in_array($category, $validCategories)) {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

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
        $sql .= " ORDER BY name ASC";
        break;
}

$sql .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $type);
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hàm tạo URL
function buildSortUrl($sortOption, $searchName, $category, $minPrice, $maxPrice, $page) {
    $params = [
        'sort' => $sortOption,
        'name' => urlencode($searchName),
        'category' => urlencode($category),
        'min_price' => $minPrice,
        'max_price' => $maxPrice,
        'page' => $page
    ];
    return 'search.php?' . http_build_query($params);
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
        .error-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #f44336;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
        }
        .error-message.show {
            display: block;
        }
        .price-range-inputs {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .price-range-inputs input {
            width: 80px;
            padding: 5px;
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
                    <a class="login-link">
                        <i class="fas fa-user"></i>
                        <span class="name">Login/Sign up</span>
                    </a>
                    <div class="login-dropdown">
                        <a href="login.php" class="login-option">Login</a>
                        <a href="sign_up.php" class="login-option">Sign up</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="main-header">
            <div class="container">
                <div class="logo">
                    <a href="index.php" class="logo-link"><h1>MAHIRU<span>.</span></h1></a>
                </div>
                <div class="search-bar">
                    <form action="search.php" method="GET">
                        <input type="text" name="name" placeholder="Search here" value="<?php echo htmlspecialchars($searchName); ?>" />
                        <button type="submit" class="search-button">Search</button>
                    </form>
                </div>
                <div class="user-menu"></div>
            </div>
        </div>
        <nav class="category-nav">
            <div class="container">
                <ul class="category-list">
                    <li><a href="index.php">Home</a></li>
                    <?php foreach ($categories as $cat): ?>
                        <li><a href="index.php?category=<?= urlencode($cat['category']) ?>"> <?= htmlspecialchars($cat['category']) ?> </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main>
        <div class="container">
            <div class="filter-sidebar">
                <form action="search.php" method="GET">
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
                    <div class="filter-price">
                        <h3>Price:</h3>
                        <div class="price-range-inputs">
                            <input type="number" name="min_price" min="0" value="<?php echo htmlspecialchars($minPrice); ?>" placeholder="Min">
                            <span>to</span>
                            <input type="number" name="max_price" min="0" value="<?php echo htmlspecialchars($maxPrice); ?>" placeholder="Max">
                        </div>
                    </div>
                    <button type="submit" class="filter-button">Search</button>
                </form>
            </div>
            <section class="product-grid">
                <div class="filter-box">
                    <span class="sort-label">Sort by:</span>
                    <a class="sort-label" href="<?php echo buildSortUrl('relevance', $searchName, $category, $minPrice, $maxPrice, $page); ?>"><button class="filter-btn">Relevance</button></a>
                    <a class="sort-label" href="<?php echo buildSortUrl('newest', $searchName, $category, $minPrice, $maxPrice, $page); ?>"><button class="filter-btn">Newest</button></a>
                    <a class="sort-label" href="<?php echo buildSortUrl('best_selling', $searchName, $category, $minPrice, $maxPrice, $page); ?>"><button class="filter-btn">Best Selling</button></a>
                    <div class="filter-option">
                        <label class="price-btn" for="price-toggle">Price</label>
                        <div class="price-dropdown">
                            <a href="<?php echo buildSortUrl('low_to_high', $searchName, $category, $minPrice, $maxPrice, $page); ?>" class="price-option">Low to High</a>
                            <a href="<?php echo buildSortUrl('high_to_low', $searchName, $category, $minPrice, $maxPrice, $page); ?>" class="price-option">High to Low</a>
                        </div>
                    </div>
                </div>
                <div class="search-results">
                    <h1>Search Results:</h1>
                </div>
                <?php if (count($products) > 0): ?>
                    <?php foreach (array_chunk($products, 3) as $productRow): ?>
                        <div class="product-row">
                            <?php foreach ($productRow as $product): ?>
                                <div class="product-card">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="product-image">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                    </a>
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                                    <span class="price">$<?php echo htmlspecialchars($product['price']); ?></span>
                                    <a href="search.php?add_to_cart=<?php echo $product['id']; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page; ?>" class="btn">Add to Cart</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No products found. Please try adjusting your search or filters, or check back later.</p>
                <?php endif; ?>
            </section>
        </div>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="search.php?page=<?php echo $page - 1; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>&sort=<?php echo $sort; ?>">« Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="search.php?page=<?php echo $i; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>&sort=<?php echo $sort; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="search.php?page=<?php echo $page + 1; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>&sort=<?php echo $sort; ?>">Next »</a>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>© Mahiru Shop. We are pleased to serve you.</p>
        </div>
    </footer>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message show" id="errorPopup"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php elseif (isset($_SESSION['success_message'])): ?>
        <div class="success-message show" id="successPopup"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <script>
        // Tự động ẩn thông báo sau 3 giây
        document.addEventListener('DOMContentLoaded', function() {
            const popups = document.querySelectorAll('.success-message, .error-message');
            popups.forEach(popup => {
                if (popup.classList.contains('show')) {
                    setTimeout(() => {
                        popup.classList.remove('show');
                    }, 3000);
                }
            });
        });
    </script>
</body>
</html>
