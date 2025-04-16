<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mahiru_shop";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = trim($_POST['password']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $role = "Users"; // Vai trò mặc định là Users
    $created_at = date("Y-m-d H:i:s"); // Thời gian hiện tại

    if (!empty($user_name) && !empty($email) && !empty($pass) && !empty($address) && !empty($phone)) {
        // Kiểm tra xem có Admin nào chưa, nếu chưa thì gán người đầu tiên là Admin
        $check_admin = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='Admin'");
        $admin_count = $check_admin->fetch_assoc()['total'];
        if ($admin_count == 0) {
            $role = "Admin";
        }

        // Kiểm tra username hoặc email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $user_name, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if ($row['status'] == 'Deactive') {
                $message = "The account has been locked";
            } else {
                $message = "Username or Email already exists!";
            }
        } else {
            // Tạo tài khoản mới
            $hashed_password = password_hash($pass, PASSWORD_BCRYPT);

            // Tìm ID nhỏ nhất có sẵn
            $result = $conn->query("SELECT id + 1 AS next_id 
                                    FROM users 
                                    WHERE id + 1 NOT IN (SELECT id FROM users) 
                                    ORDER BY next_id 
                                    LIMIT 1");
            $row = $result->fetch_assoc();
            $next_id = $row['next_id'] ?? 1;

            // Kiểm tra lại để tránh trùng lặp ID
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $checkStmt->bind_param("i", $next_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            while ($checkResult->fetch_assoc()) {
                $next_id++;
                $checkStmt->bind_param("i", $next_id);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
            }

            // Chèn bản ghi mới với ID đã xác định
            $stmt = $conn->prepare("INSERT INTO users (id, username, email, password, address, phone, role, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $next_id, $user_name, $email, $hashed_password, $address, $phone, $role, $created_at);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $next_id;
                $_SESSION['user_name'] = $user_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_address'] = $address;
                $_SESSION['user_phone'] = $phone;
                $_SESSION['user_role'] = $role;

                echo "<script>
                        alert('Account registered successfully! Logging in...');
                        window.location.href = 'index_account.php';
                      </script>";
                exit();
            } else {
                $message = "Registration failed. Please try again! Error: " . $conn->error;
            }
        }
    } else {
        $message = "Please fill in all fields!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - MAHIRU.</title>
    <link rel="stylesheet" href="./css/login.css">
</head>
<body>
    <div class="page-container">
        <header>
            <div class="container">
                <h1>MAHIRU.</h1>
                <nav>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <main>
            <div class="login-container">
                <h2>Sign up</h2>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="username">Full Name</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <input type="text" id="address" name="address" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" required>
                    </div>
                    <button type="submit">Submit</button>
                </form>
                <div class="form-footer">
                    <p>Already have an account? <a href="login.php" class="signup-link">Login</a></p>
                </div>  
                <?php if (!empty($message)): ?>
                    <script>alert('<?php echo addslashes($message); ?>');</script>
                <?php endif; ?>
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