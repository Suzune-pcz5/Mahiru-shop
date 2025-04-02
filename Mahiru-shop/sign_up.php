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
    $role = "User"; // Mặc định là User
    $created_at = date("Y-m-d H:i:s"); // Lấy thời gian hiện tại
    $status = "Active"; // Thêm trạng thái mặc định là Active

    if (!empty($user_name) && !empty($email) && !empty($pass) && !empty($address) && !empty($phone)) {
        // Kiểm tra xem có Admin nào chưa, nếu chưa có thì tạo Admin đầu tiên
        $check_admin = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='Admin'");
        $admin_count = $check_admin->fetch_assoc()['total'];

        if ($admin_count == 0) {
            $role = "Admin";
        }

        // Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Email đã tồn tại -> Đăng nhập luôn
            $_SESSION['user_name'] = $row['username'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_address'] = $row['address'];
            $_SESSION['user_phone'] = $row['phone'];
            $_SESSION['user_role'] = $row['role'];

            echo "<script>
                    alert('Welcome back, " . htmlspecialchars($row['username']) . "! Logging in...');
                    window.location.href = 'index_account.php';
                  </script>";
            exit();
        } else {
            // Tạo tài khoản mới
            $hashed_password = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, address, phone, role, created_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $user_name, $email, $hashed_password, $address, $phone, $role, $created_at, $status);

            if ($stmt->execute()) {
                // Đăng ký thành công -> Đăng nhập ngay
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
                $message = "Registration failed. Please try again!";
            }
        }
    } else {
        $message = "Please fill in all fields!";
    }
}

$conn->close();
?>

<!-- Phần HTML giữ nguyên, không thay đổi -->

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
                <p><?php echo $message; ?></p>
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
