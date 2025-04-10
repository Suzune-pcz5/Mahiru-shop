<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

// Lấy thông tin user từ session
$currentUser = isset($_SESSION['user_name']) ? [
    'username' => $_SESSION['user_name'],
    'role'     => $_SESSION['user_role'] ?? 'user'
] : null;

// Lấy ID sản phẩm từ URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header("Location: index_account.php");
    exit();
}

// Lấy thông tin sản phẩm từ bảng products
$productStmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$productStmt->bindValue(':id', $productId, PDO::PARAM_INT);
$productStmt->execute();
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index_account.php");
    exit();
}

// Lấy danh mục từ bảng products
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Định nghĩa các biến cho search bar (để giữ giao diện đồng bộ)
$searchName = $_GET['name'] ?? '';
$category   = $_GET['category'] ?? 'all';
$priceRange = $_GET['price'] ?? '99999999';

// Xử lý thêm sản phẩm vào giỏ hàng
if (isset($_GET['add_to_cart']) && isset($_SESSION['user_id'])) {
    $productId = (int)$_GET['add_to_cart'];
    $userId = $_SESSION['user_id'];
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1; // Get the quantity from the form, default to 1 if not set

    // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
    $checkStmt = $conn->prepare("SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id");
    $checkStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        // Nếu sản phẩm đã có, tăng số lượng theo quantity được chọn
        $newQuantity = $existingItem['quantity'] + $quantity;
        $updateStmt = $conn->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
        $updateStmt->bindValue(':quantity', $newQuantity, PDO::PARAM_INT);
        $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $updateStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $updateStmt->execute();
    } else {
        // Nếu sản phẩm chưa có, thêm mới với quantity được chọn
        $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
        $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $insertStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $insertStmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $insertStmt->execute();
    }

    $_SESSION['success_message'] = "Product added to cart successfully!";
    header("Location: index_account.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Product Details - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/product_details.css" />
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
                    <input type="text" id="searchInput" placeholder="Search here" value="<?php echo htmlspecialchars($searchName); ?>" />
                    <button class="search-button" onclick="performSearch()">Search</button>
                </div>
                <script>
                    function performSearch() {
                        let searchValue = document.getElementById("searchInput").value.trim();
                        if (searchValue) {
                            window.location.href = "search_account.php?name=" + encodeURIComponent(searchValue);
                        }
                    }
                </script>
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
                        <!-- Replace the link with a form -->
                        <form action="product_details_acc.php" method="GET" class="add-to-cart-form">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>" />
                            <input type="hidden" name="add_to_cart" value="<?php echo $product['id']; ?>" />
                            <input type="hidden" name="quantity" class="hidden-quantity" value="1" />
                            <button type="submit" class="add-to-cart">Add to Cart</button>
                        </form>
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

    <script>
        // JavaScript để xử lý tăng/giảm số lượng và cập nhật hidden input
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                const hiddenInput = this.parentElement.parentElement.querySelector('.hidden-quantity');
                let value = parseInt(input.value);
                if (this.classList.contains('plus')) {
                    value++;
                } else if (this.classList.contains('minus') && value > 1) {
                    value--;
                }
                input.value = value;
                hiddenInput.value = value; // Update the hidden input with the new quantity
            });
        });
    </script>
</body>
</html>