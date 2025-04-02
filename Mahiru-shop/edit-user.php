<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mahiru_shop";

// Kết nối đến MySQL
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra xem có ID trên URL không
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Truy vấn lấy thông tin user
    $sql = "SELECT * FROM users WHERE id = $user_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "User not found!";
        exit();
    }
} else {
    echo "Invalid request!";
    exit();
}

// Xử lý cập nhật thông tin người dùng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
    $email = $_POST["edit-email"];
    $role = $_POST["edit-role"];
    $status = $_POST["edit-status"];
    $address = $_POST["edit-address"];
    $phone = $_POST["edit-phone"];

    $update_sql = "UPDATE users SET email='$email', role='$role', status='$status', address='$address', phone='$phone' WHERE id=$user_id";

    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('User updated successfully!'); window.location.href='user-management.php';</script>";
    } else {
        echo "Error updating user: " . $conn->error;
    }
}

// Xử lý xóa người dùng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete"])) {
    $delete_sql = "DELETE FROM users WHERE id=$user_id";

    if ($conn->query($delete_sql) === TRUE) {
        echo "<script>alert('User deleted successfully!'); window.location.href='user-management.php';</script>";
    } else {
        echo "Error deleting user: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Mahiru Shop Admin</title>
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
                        <h3>Edit User Information</h3>
                        <form class="user-form" method="POST">
                            <div class="form-group">
                                <label for="edit-username">Username:</label>
                                <input type="text" id="edit-username" name="edit-username" value="<?php echo $user['username']; ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label for="edit-email">Email:</label>
                                <input type="email" id="edit-email" name="edit-email" value="<?php echo $user['email']; ?>">
                            </div>

                            <!-- Thêm khung nhập Address -->
                            <div class="form-group">
                                <label for="edit-address">Address:</label>
                                <input type="text" id="edit-address" name="edit-address" value="<?php echo isset($user['address']) ? $user['address'] : ''; ?>">
                            </div>

                            <!-- Thêm khung nhập Phone -->
                            <div class="form-group">
                                <label for="edit-phone">Phone:</label>
                                <input type="text" id="edit-phone" name="edit-phone" value="<?php echo isset($user['phone']) ? $user['phone'] : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="edit-role">Role:</label>
                                <select id="edit-role" name="edit-role">
                                    <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="manager" <?php echo ($user['role'] == 'manager') ? 'selected' : ''; ?>>Manager</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="edit-status">Status:</label>
                                <select id="edit-status" name="edit-status">
                                    <option value="active" <?php echo ($user['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($user['status'] == 'inactive') ? 'selected' : ''; ?>>Deactive</option>
                                </select>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="update" class="action-btn" style="background-color: green; color: white;">Change</button>
                                <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this user?');" class="action-btn" style="background-color: red; color: white;">Delete</button>
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
