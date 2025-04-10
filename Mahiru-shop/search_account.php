<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'mahiru_shop';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get current user from session
$currentUser = null;
if (isset($_SESSION['user_name']) && isset($_SESSION['user_role'])) {
    $currentUser = [
        'username' => $_SESSION['user_name'],
        'role'     => $_SESSION['user_role']
    ];
}

// Handle add to cart
if (isset($_GET['add_to_cart']) && isset($_SESSION['user_id'])) {
    $productId = (int)$_GET['add_to_cart'];
    $userId = $_SESSION['user_id'];

    try {
        // Check if product exists in cart
        $checkStmt = $conn->prepare("SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id");
        $checkStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $checkStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $checkStmt->execute();
        $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            // Update quantity if product exists
            $newQuantity = $existingItem['quantity'] + 1;
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
            $updateStmt->bindValue(':quantity', $newQuantity, PDO::PARAM_INT);
            $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $updateStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $updateStmt->execute();
        } else {
            // Insert new product if it doesn't exist
            $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, 1)");
            $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $insertStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $insertStmt->execute();
        }

        $_SESSION['success_message'] = "Product added to cart successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error adding product to cart: " . $e->getMessage();
    }

    // Create a new array of GET parameters, excluding 'add_to_cart'
    $redirectParams = $_GET;
    unset($redirectParams['add_to_cart']); // Remove the 'add_to_cart' parameter

    // Redirect to the same page without the 'add_to_cart' parameter
    header("Location: search_account.php?" . http_build_query($redirectParams));
    exit;
}

// Get categories
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Handle search, filter, and sort
$searchName = isset($_GET['name']) ? trim($_GET['name']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$priceRange = isset($_GET['price']) ? (int)$_GET['price'] : 300;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';

// Pagination
$limit = 9;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Count total products
$countSql = "SELECT COUNT(*) FROM products WHERE price <= :price";
$params = [':price' => $priceRange];
if (!empty($searchName)) {
    $countSql .= " AND name LIKE :name";
    $params[':name'] = "%$searchName%";
}
if ($category !== 'all') {
    $countSql .= " AND category = :category";
    $params[':category'] = $category;
}

$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Build product query
$sql = "SELECT * FROM products WHERE price <= :price";
$params = [':price' => $priceRange];
if (!empty($searchName)) {
    $sql .= " AND name LIKE :name";
    $params[':name'] = "%$searchName%";
}
if ($category !== 'all') {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

// Add sorting
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

// Execute product query
$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $type);
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to build URL with current parameters
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahiru Shop</title>
    <link rel="stylesheet" href="./css/search.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .success-message, .error-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
        }
        .success-message {
            background-color: #4CAF50;
            color: white;
        }
        .error-message {
            background-color: #f44336;
            color: white;
        }
        .show {
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
            <form action="search.php" method="GET">
                <input type="text" name="name" placeholder="Search here" value="<?php echo htmlspecialchars($searchName); ?>" />
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
                <form action="search_account.php" method="GET">
                    <h3>Name:</h3>
                    <div class="filter-name">
                        <input type="text" name="name" placeholder="Enter product name" class="filter-input" value="<?php echo htmlspecialchars($searchName); ?>">
                    </div>
                    <h3>Category:</h3>
                    <div class="filter-category">
                        <select name="category" class="filter-select">
                            <option value="all" <?php echo ($category === 'all') ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo ($category === $cat['category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $cat['category']))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <h3>Price:</h3>
                    <div class="filter-price">
                        <label for="priceRange">Range: <span id="priceOutput"><?php echo $priceRange; ?></span></label>
                        <div class="range-container custom-range">
                            <div class="range-label">0</div>
                            <input type="range" id="priceRange" name="price" min="0" max="300" value="<?php echo $priceRange; ?>" class="filter-input">
                            <div class="range-label">300</div>
                        </div>
                    </div>
                    <button type="submit" class="filter-button">Search</button>
                </form>
            </div>

            <section class="product-grid">
                <div class="filter-box">
                    <span class="sort-label">Sort by:</span>
                    <a class="sort-label" href="<?php echo buildSortUrl('relevance', $searchName, $category, $priceRange, $page); ?>"><button class="filter-btn">Relevance</button></a>
                    <a class="sort-label" href="<?php echo buildSortUrl('newest', $searchName, $category, $priceRange, $page); ?>"><button class="filter-btn">Newest</button></a>
                    <a class="sort-label" href="<?php echo buildSortUrl('best_selling', $searchName, $category, $priceRange, $page); ?>"><button class="filter-btn">Best Selling</button></a>
                    <div class="filter-option">
                        <label class="price-btn" for="price-toggle">Price</label>
                        <input type="checkbox" id="price-toggle" class="price-toggle">
                        <div class="price-dropdown">
                            <a href="<?php echo buildSortUrl('low_to_high', $searchName, $category, $priceRange, $page); ?>" class="price-option">Low to High</a>
                            <a href="<?php echo buildSortUrl('high_to_low', $searchName, $category, $priceRange, $page); ?>" class="price-option">High to Low</a>
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
                                    <a href="product_details_acc.php?id=<?php echo $product['id']; ?>" class="product-image">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </a>
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                                    <span class="price">$<?php echo htmlspecialchars($product['price']); ?></span>
                                    <a href="search_account.php?add_to_cart=<?php echo $product['id']; ?>&name=<?php echo urlencode($searchName); ?>&category=<?php echo urlencode($category); ?>&price=<?php echo $priceRange; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page; ?>" class="btn">Add to Cart</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
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
        <div class="success-message show" id="successPopup"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message show" id="errorPopup"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <script>
        // Popup handling
        document.addEventListener('DOMContentLoaded', function() {
            const popups = document.querySelectorAll('.success-message, .error-message');
            popups.forEach(popup => {
                if (popup.classList.contains('show')) {
                    setTimeout(() => {
                        popup.classList.remove('show');
                    }, 3000);
                }
            });

            // Price range slider
            const priceRange = document.getElementById('priceRange');
            const priceOutput = document.getElementById('priceOutput');
            if (priceRange && priceOutput) {
                priceOutput.textContent = priceRange.value;
                priceRange.addEventListener('input', function() {
                    priceOutput.textContent = this.value;
                });
            }
        });
    </script>
</body>
</html>
