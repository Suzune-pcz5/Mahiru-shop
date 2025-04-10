<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mahiru_shop";

// Kết nối MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra nếu form đã được gửi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["product-name"];
    $description = $_POST["product-description"];
    $price = $_POST["product-price"];
    $category = $_POST["product-category"];

    // Tạo thư mục uploads nếu chưa tồn tại
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Xử lý upload ảnh
    $target_file = $target_dir . basename($_FILES["product-image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ["jpg", "jpeg", "png", "gif"];

    if (!in_array($imageFileType, $allowed_types)) {
        echo "<script>alert('Chỉ chấp nhận file JPG, JPEG, PNG & GIF.');</script>";
        exit;
    }

    if (move_uploaded_file($_FILES["product-image"]["tmp_name"], $target_file)) {
        $image_path = $target_file;
    } else {
        echo "<script>alert('Lỗi khi upload ảnh. Kiểm tra quyền ghi thư mục uploads/.');</script>";
        exit;
    }

    // Chuẩn bị câu lệnh SQL
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $name, $description, $price, $category, $image_path);
    
    if ($stmt->execute()) {
        echo "<script>alert('Sản phẩm đã được thêm thành công!');</script>";
    } else {
        echo "<script>alert('Lỗi: Không thể thêm sản phẩm!');</script>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Mahiru Shop</title>
    <link rel="stylesheet" href="./css/admin-styles.css">
    <script src="https://unpkg.com/lucide@latest"></script>
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
                    <span class="admin-name">Admin: Hatsu</span>
                    <a href="./loginad.php" class="logout">Log out</a>
                </div>
            </div>
        </header>
        <main>
            <div class="container">
                <div class="admin-panel">
                    <div class="admin-sidebar">
                        <h2>Product Management</h2>
                        <ul>
                            <li><a href="./product-management.php">Product List</a></li>
                            <li><a href="./add-product.php" class="active">Add New Product</a></li>
                        </ul>
                    </div>
                    <div class="admin-content">
                        <section id="add-product">
                            <h2>Add New Product</h2>
                            <form class="product-form" action="add-product.php" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="product-name">Product Name</label>
                                    <input type="text" id="product-name" name="product-name" required>
                                </div>
                                <div class="form-group">
                                    <label for="product-description">Description</label>
                                    <textarea id="product-description" name="product-description" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="product-price">Price</label>
                                    <input type="number" id="product-price" name="product-price" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label for="product-category">Category</label>
                                    <select id="product-category" name="product-category" required>
                                        <option value="">Select a category</option>
                                        <option value="Gundam">Gundam Models</option>
                                        <option value="Kamen Rider">Kamen Rider</option>
                                        <option value="Standee">Standee</option>
                                        <option value="Keychain">Keychain</option>
                                        <option value="Plush">Plush</option>
                                        <option value="Figure">Figure</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="product-image">Product Image</label>
                                    <input type="file" id="product-image" name="product-image" accept="image/*" required>
                                </div>
                                <button type="submit" class="action-btn">Add Product</button>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </main>
        <footer>
            <div class="container">
                <p>&copy; Mahiru Shop. We are pleased to serve you.</p>
            </div>
        </footer>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
