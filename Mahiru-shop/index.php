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

    // Insert the product if it doesn't exist (run once or on setup)
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE id = 1");
    $checkStmt->execute();
    if ($checkStmt->fetchColumn() == 0) {
        $insertSql = "INSERT INTO products (id, name, description, price, image, category, sold_count, created_at) VALUES
                      (1, 'Stellaron Hunter SAM', 'Honkai Star Rail', 199.98, 'uploads/SAM.webp', 'Figure', 50, '2025-03-21 09:28:18')";
        $conn->exec($insertSql);
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ========== LẤY THÔNG TIN USER TỪ SESSION ==========
$currentUser = isset($_SESSION['user_name']) ? [
    'username' => $_SESSION['user_name'],
    'role'     => $_SESSION['user_role'] ?? 'user'
] : null;

// ========== LẤY DANH MỤC TỪ BẢNG products ==========
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products WHERE is_hidden = 0");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// ========== XỬ LÝ TÌM KIẾM, LỌC SẢN PHẨM & PHÂN TRANG ==========
$searchName = $_GET['name'] ?? '';
$category   = $_GET['category'] ?? 'all';
$minPrice   = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int)$_GET['min_price'] : 0;
$maxPrice   = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : 300;

$limit = 9;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;

// Validate price range if both values are provided
if ($minPrice > $maxPrice) {
    $temp = $minPrice;
    $minPrice = $maxPrice;
    $maxPrice = $temp;
}

$whereClauses = [];
$params = [];

// Luôn chỉ hiển thị các sản phẩm không bị ẩn
$whereClauses[] = "is_hidden = 0";
if (!($minPrice === 0 && $maxPrice === 300)) {
    $whereClauses[] = "price BETWEEN :min_price AND :max_price";
    $params[':min_price'] = $minPrice;
    $params[':max_price'] = $maxPrice;
}
if (!empty($searchName)) {
    $whereClauses[] = "name LIKE :name";
    $params[':name'] = "%$searchName%";
}
if ($category !== 'all') {
    $whereClauses[] = "category = :category";
    $params[':category'] = $category;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$productQuery = $conn->prepare("SELECT * FROM products $whereSql LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $productQuery->bindValue($key, $value, $type);
}
$productQuery->bindValue(':limit', $limit, PDO::PARAM_INT);
$productQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
$productQuery->execute();
$products = $productQuery->fetchAll(PDO::FETCH_ASSOC);

// Tính tổng số sản phẩm sau khi lọc
$totalQuery = $conn->prepare("SELECT COUNT(*) FROM products $whereSql");
foreach ($params as $key => $value) {
    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $totalQuery->bindValue($key, $value, $type);
}
$totalQuery->execute();
$totalProducts = $totalQuery->fetchColumn();

// Tính tổng số trang
$totalPages = ceil($totalProducts / $limit);

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
                <span><i class="fas fa-map-marker-alt"></i>1104 Wall Street</span>
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
    <div class=" FuelPHP
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
            <div class="user-menu">
            </div>
        </div>
    </div>
    <nav>
        <div class="container">
            <ul>
                <li><a href="index.php">Home</a></li>
                <?php foreach ($categories as $cat): ?>
                    <li><a href="index.php?category=<?php echo htmlspecialchars($cat['category']); ?>">
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $cat['category']))); ?>
                    </a></li>
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
                        <input type="number" name="min_price" min="0" value="<?php echo htmlspecialchars($minPrice); ?>" placeholder="Min">
                        <span>to</span>
                        <input type="number" name="max_price" min="0" value="<?php echo htmlspecialchars($maxPrice); ?>" placeholder="Max">
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
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="product-image">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                    </a>
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                                    <span class="price">$<?php echo htmlspecialchars($product['price']); ?></span>
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn">Add to Cart</a>
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
            <a href="index.php?p=<?php echo $page - 1; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>">« Previous</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="index.php?p=<?php echo $i; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="index.php?p=<?php echo $page + 1; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>">Next »</a>
        <?php endif; ?>
    </div>
</main>

<footer>
    <div class="container">
        <p>© Mahiru Shop. We are pleased to serve you.</p>
    </div>
</footer>

</body>
</html>
