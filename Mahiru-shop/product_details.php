<?php
session_start();

// Kết nối cơ sở dữ liệu
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

// Lấy ID sản phẩm từ URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin sản phẩm từ bảng products
$productStmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$productStmt->bindValue(':id', $productId, PDO::PARAM_INT);
$productStmt->execute();
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit();
}

// Lấy danh mục từ bảng products
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Định nghĩa các biến cho search bar (để giữ giao diện đồng bộ)
$searchName = $_GET['name'] ?? '';
$category   = $_GET['category'] ?? 'all';
$priceRange = $_GET['price'] ?? '9999999';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Product Details - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/product_details.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
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
                <input type="text" id="searchInput" placeholder="Search here" value="<?php echo htmlspecialchars($searchName); ?>" />
                <button class="search-button" onclick="performSearch()">Search</button>
                </div>
                <script>
                    function performSearch() {
                 let searchValue = document.getElementById("searchInput").value.trim();
                if (searchValue) {
                window.location.href = "search.php?name=" + encodeURIComponent(searchValue);
    }
}

                </script>
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
            <div class="product-content">
                <div class="product-details">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                    </div>
                    <div class="product-info">
                        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                        <p class="category">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                        <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                        <p class="description"><?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?></p>
                        <div class="quantity-selector">
                            <button class="quantity-btn minus">-</button>
                            <input type="number" value="1" min="1" class="quantity-input" readonly />
                            <button class="quantity-btn plus">+</button>
                        </div>
                        <a href="login.php" class="add-to-cart">Add to Cart</a>
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

    <script>
        // JavaScript để xử lý tăng/giảm số lượng
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                let value = parseInt(input.value);
                if (this.classList.contains('plus')) {
                    value++;
                } else if (this.classList.contains('minus') && value > 1) {
                    value--;
                }
                input.value = value;
            });
        });
    </script>
</body>
</html>