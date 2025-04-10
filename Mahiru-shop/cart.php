<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
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

// Xử lý xóa sản phẩm
if (isset($_GET['remove'])) {
    $productId = (int)$_GET['remove'];
    $deleteStmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
    $deleteStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $deleteStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $deleteStmt->execute();
    header("Location: cart.php");
    exit;
}

// Xử lý cập nhật số lượng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $id => $qty) {
        $id = (int)$id;
        $qty = (int)$qty;
        if ($qty > 0 && $qty <= 10) {
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
            $updateStmt->bindValue(':quantity', $qty, PDO::PARAM_INT);
            $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $updateStmt->bindValue(':product_id', $id, PDO::PARAM_INT);
            $updateStmt->execute();
        } else {
            $deleteStmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
            $deleteStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $deleteStmt->bindValue(':product_id', $id, PDO::PARAM_INT);
            $deleteStmt->execute();
        }
    }
    header("Location: cart.php");
    exit;
}

// ========== LẤY DANH MỤC TỪ BẢNG products ==========
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Lấy dữ liệu giỏ hàng, bao gồm cột description
$stmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.name, p.price, p.image, p.description 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = :user_id
");
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính tổng tiền
$total = 0;
$shipping = 5.00;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your Cart - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/styles.css" />
    <link rel="stylesheet" href="./css/cart.css?v=1.1" />
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
                    <i class="fas fa-user"></i>
                    <span class="name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
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
        <nav>
            <div class="container">
            <ul>
                <li><a href="index_account.php">Home</a></li>
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
            <div class="cart-container">
                <h1 class="page-title">Your Shopping Cart</h1>
                <form action="cart.php" method="post" class="cart-form">
                    <table class="cart-table" data-shipping="<?php echo $shipping; ?>">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cartItems)): ?>
                                <tr>
                                    <td colspan="5" class="empty-cart">Your cart is empty.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr data-product-id="<?php echo $item['product_id']; ?>">
                                        <td>
                                            <div class="cart-item">
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                                                <div class="product-info">
                                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                                    <p><?php echo htmlspecialchars($item['description'] ?? 'No description available'); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="price" data-price="<?php echo $item['price']; ?>">$<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <div class="quantity-control">
                                                <button type="button" class="decrease-btn">-</button>
                                                <input type="number" name="quantity[<?php echo $item['product_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="10" class="quantity-input" readonly>
                                                <button type="button" class="increase-btn">+</button>
                                            </div>
                                        </td>
                                        <td class="subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        <td>
                                            <a href="cart.php?remove=<?php echo $item['product_id']; ?>" class="remove-btn">Remove</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if (!empty($cartItems)): ?>
                        <div class="cart-actions">
                            <button type="submit" name="update_cart" class="update-cart-btn">Update Cart</button>
                        </div>
                    <?php endif; ?>
                </form>
                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>$<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="total">$<?php echo number_format($total + $shipping, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>© Mahiru Shop. We are pleased to serve you.</p>
        </div>
    </footer>

    <script src="./js/cart.js"></script>
</body>
</html> 