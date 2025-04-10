<?php
session_start();

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

// Xử lý khi form gửi dữ liệu
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];    
    $password = $_POST["password"];
    $email = $_POST["email"];
    $address = $_POST["address"];
    $phone = $_POST["phone"];
    $role = $_POST["role"];

    // Kiểm tra email đã tồn tại chưa
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        echo "<script>alert('Email is already exists.'); window.history.back();</script>";
        exit();
    }
    $check_stmt->close();

    // Thêm người dùng vào database
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, address, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $password, $email, $address, $phone, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Thêm user thành công!'); window.location.href = 'user-management.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi thêm user!');</script>";
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
    <title>Add User - Mahiru Shop Admin</title>
    <link rel="stylesheet" href="./css/user.css">
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
                    <span class="admin-name">ADMIN</span>
                    <a href="./loginad.php" class="logout">Log out</a>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
                <div class="admin-panel">
                    <div class="admin-sidebar">
                        <h2>User Management</h2>
                        <ul>
                            <li><a href="./user-management.php">User List</a></li>
                            <li><a href="./add-user.php">Add New User</a></li>
                        </ul>
                    </div>
                    <div class="admin-content">
                        <form class="user-form" action="add-user.php" method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" required>
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="password-input">
                                    <input type="password" id="password" name="password" required>
                                    <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="text" name="email" required> <!-- Thay type="email" thành type="text" -->
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" name="address"> <!-- Sửa type="address" thành type="text" -->
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" name="phone"> <!-- Sửa type="phone" thành type="text" -->
                            </div>
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select id="role" name="role">
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="action-btn" style="background-color: green; color: white;">Add</button> 
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        <footer>
            <div class="container">
                <p>&copy;Mahiru Shop. We are pleased to serve you.</p>
            </div>
        </footer>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>