<?php
session_start(); // Bắt đầu session để lưu thông tin đăng nhập

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

// Kiểm tra nếu form đã submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Chuẩn bị truy vấn để tránh SQL Injection
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Kiểm tra nếu có kết quả
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Kiểm tra mật khẩu (giả sử mật khẩu đã hash trong database)
        if ( $row["password"]) {
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["username"] = $row["username"];
            $_SESSION["role"] = $row["role"];
            
            echo "<script>alert('Đăng nhập thành công!'); window.location.href='admin.php';</script>";
            exit();
        } else {
            echo "<script>alert('Mật khẩu không đúng!');</script>";
        }
    } else {
        echo "<script>alert('Email không tồn tại!');</script>";
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
    <title>Login - MAHIRU. </title>
    <link rel="stylesheet" href="./css/loginad.css">
</head>
<body>
    <div class="page-container">
        <header>
            <div class="container">
                <h1>MAHIRU.ADMIN</h1>
            </div>
        </header>

        <main>
            <div class="login-container">
                <h2>Login</h2>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit">Submit</button>
                </form>
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
