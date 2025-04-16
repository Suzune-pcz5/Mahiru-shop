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

// Lấy user_id từ session
$userId = $_SESSION['user_id'];

// Lấy thông tin người dùng từ bảng users
$userStmt = $conn->prepare("SELECT username, email, address, phone FROM users WHERE id = :user_id");
$userStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$userStmt->execute();
$userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

// Gán giá trị mặc định từ thông tin người dùng
$fullName = $userInfo['username'] ?? '';
$email = $userInfo['email'] ?? '';
$streetAddress = $userInfo['address'] ?? '';
$phoneNumber = $userInfo['phone'] ?? '';

// Lấy dữ liệu giỏ hàng từ cơ sở dữ liệu
$stmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.name, p.price, p.image 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = :user_id
");
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========== LẤY DANH MỤC TỪ BẢNG products ==========
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra giỏ hàng
if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

// Tính tổng tiền và phí vận chuyển
$total = 0;
$shipping = 5.00;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="./css/categories.css">
    <link rel="stylesheet" href="./css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
                    <i class="fas fa-user"></i>
                    <span class="name"><?php echo htmlspecialchars($userInfo['username']); ?></span>
                    <div class="login-dropdown">
                        <a href="order_history.php" class="login-option">Order History</a>
                        <a href="edit_profile.php" class="login-option">Edit Profile</a>
                        <a href="index.php" class="login-option">Log out</a>
                    </div>
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
                        <input type="text" name="name" placeholder="Search here" />
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
            <form action="process_checkout.php" method="post" class="checkout-form">
                <h1 class="page-title">Checkout</h1>
                <div class="checkout-container">
                    <div class="shipping-info">
                        <h2>Shipping Information</h2>
                        <input type="text" name="full_name" placeholder="Full Name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                        <input type="text" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>">
                        <input type="text" name="street_address" placeholder="Street Address" value="<?php echo htmlspecialchars($streetAddress); ?>" required>
                        <input type="tel" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($phoneNumber); ?>" required>
                    </div>
                    <div class="payment-info">
                        <h2>Payment Method</h2>
                        <div class="payment-options">
                            <label><input type="radio" name="payment" value="cash" checked> Cash on Delivery</label>
                            <label><input type="radio" name="payment" value="bank_transfer"> Bank Transfer</label>
                            <label><input type="radio" name="payment" value="credit_card"> Credit/Debit Card</label>
                        </div>
                    </div>
                </div>
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <?php if (!empty($cartItems)): ?>
                        <?php foreach ($cartItems as $item): ?>
                            <div class="summary-item">
                                <div class="cart-item">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</h3>
                                        <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                </div>
                                <span class="subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Your cart is empty or contains invalid products.</p>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>$<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total + $shipping, 2); ?></span>
                    </div>
                    <input type="hidden" name="total" value="<?php echo $total + $shipping; ?>">
                    <div class="checkout-actions">
                        <a href="cart.php" class="back-to-cart-btn">Back to Cart</a>
                        <button type="submit" class="place-order-btn">Place Order</button>
                    </div>
                </div>
            </form>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>© Mahiru Shop. We are pleased to serve you.</p>
        </div>
    </footer>
</body>
</html>
