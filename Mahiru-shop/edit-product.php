<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mahiru_shop";

// Kết nối đến MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy ID sản phẩm từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: product-management.php");
    exit();
}

// Truy vấn sản phẩm từ database
$sql = "SELECT * FROM products WHERE id = $id";
$result = $conn->query($sql);
$product = $result->fetch_assoc();

if (!$product) {
    echo "<script>alert('Sản phẩm không tồn tại!'); window.location='product-management.php';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
    $name = $_POST["product-name"];
    $description = $_POST["product-description"];
    $price = $_POST["product-price"];
    $category = $_POST["product-category"];
    $image = $product['image']; // Giữ nguyên ảnh cũ nếu không cập nhật

    if (!empty($_FILES["product-image"]["name"])) {
        $target_dir = "uploads/"; // Thư mục lưu ảnh
        $target_file = $target_dir . basename($_FILES["product-image"]["name"]);
        move_uploaded_file($_FILES["product-image"]["tmp_name"], $target_file);
        $image = $target_file;
    }

    // Cập nhật dữ liệu vào database
    $update_sql = "UPDATE products SET name='$name', description='$description', price='$price', category='$category', image='$image' WHERE id=$id";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('Cập nhật sản phẩm thành công!'); window.location='product-management.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Xử lý xóa sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete"])) {
    $delete_sql = "DELETE FROM products WHERE id=$id";
    if ($conn->query($delete_sql) === TRUE) {
        echo "<script>alert('Xóa sản phẩm thành công!'); window.location='product-management.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    <div class="user-icon">
                        <i data-lucide="user-circle"></i>
                    </div>
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
                                    <option value="gundam" <?php if($product['category'] == 'gundam') echo 'selected'; ?>>Gundam Models</option>
                                    <option value="kamen rider" <?php if($product['category'] == 'kamen rider') echo 'selected'; ?>>Kamen Rider</option>
                                    <option value="Standee" <?php if($product['category'] == 'Standee') echo 'selected'; ?>>Standee</option>
                                    <option value="Keychain" <?php if($product['category'] == 'Keychain') echo 'selected'; ?>>Keychain</option>
                                    <option value="Plush" <?php if($product['category'] == 'Plush') echo 'selected'; ?>>Plush</option>
                                    <option value="Figure" <?php if($product['category'] == 'Figure') echo 'selected'; ?>>Figure</option>
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
                <p>&copy; Mahiru Shop. We are pleased to serve you.</p>
            </div>
        </footer>
    </div>
</body>
</html>
