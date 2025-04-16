<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mahiru_shop";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: product-management.php");
    exit();
}

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
if (!$product) {
    echo "<script>alert('Product doesn\'t exist!'); window.location='product-management.php';</script>";
    exit();
}

// Cập nhật sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
    $name = $conn->real_escape_string($_POST["product-name"]);
    $description = $conn->real_escape_string($_POST["product-description"]);
    $price = (float)$_POST["product-price"];
    $category = $conn->real_escape_string($_POST["product-category"]);
    $is_hidden = $_POST["product-hidden"] === "1" ? 1 : 0;

    $image = $product['image'];
    if (!empty($_FILES["product-image"]["name"])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["product-image"]["name"]);
        move_uploaded_file($_FILES["product-image"]["tmp_name"], $target_file);
        $image = $target_file;
    }

    $update_stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, image = ?, is_hidden = ? WHERE id = ?");
    $update_stmt->bind_param("ssdssii", $name, $description, $price, $category, $image, $is_hidden, $id);
    if ($update_stmt->execute()) {
        echo "<script>alert('Product updated successfully!'); window.location='product-management.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
    $update_stmt->close();
}

// Chỉ ẩn sản phẩm khi bấm nút Delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete"])) {
    $hide_stmt = $conn->prepare("UPDATE products SET is_hidden = 1 WHERE id = ?");
    $hide_stmt->bind_param("i", $id);
    if ($hide_stmt->execute()) {
        echo "<script>alert('Product hidden successfully!'); window.location='product-management.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
    $hide_stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/admin-styles.css">
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
                    <div class="user-icon"><i data-lucide="user-circle"></i></div>
                    <span class="admin-name">ADMIN</span>
                    <a href="./loginad.php" class="logout">Log out</a>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
                <div class="admin-content">
                    <section id="edit-product"> 
                        <h2>Edit Product</h2>
                        <form class="product-form" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="product-name">Product Name</label>
                                <input type="text" id="product-name" name="product-name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="product-description">Description</label>
                                <textarea id="product-description" name="product-description"><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="product-price">Price</label>
                                <input type="number" id="product-price" name="product-price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="product-category">Category</label>
                                <select id="product-category" name="product-category">
                                    <?php
                                    $categories = ["Gundam", "Kamen Rider", "Standee", "Keychain", "Plush", "Figure"];
                                    foreach ($categories as $cat) {
                                        $selected = ($product['category'] == $cat) ? 'selected' : '';
                                        echo "<option value=\"$cat\" $selected>$cat</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="product-hidden">Hidden Status</label>
                                <select id="product-hidden" name="product-hidden">
                                    <option value="0" <?php if ($product['is_hidden'] == 0) echo "selected"; ?>>Enabled</option>
                                    <option value="1" <?php if ($product['is_hidden'] == 1) echo "selected"; ?>>Disabled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Current Image</label>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" width="150">
                            </div>
                            <div class="form-group">
                                <label for="product-image">Upload New Image</label>
                                <input type="file" id="product-image" name="product-image" accept="image/*">
                                <p class="help-text">Leave blank to keep the current image</p>
                            </div>
                            <button type="submit" name="update" class="action-btn" style="background-color:green;color: white;">Update</button>
                            <button type="submit" name="delete" class="action-btn" style="background-color:red;color: white;" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                        </form>
                    </section>
                </div>
            </div>
        </main>

        <footer>
            <div class="container">
                <p>© Mahiru Shop. We are pleased to serve you.</p>
            </div>
        </footer>
    </div>
</body>
</html>
