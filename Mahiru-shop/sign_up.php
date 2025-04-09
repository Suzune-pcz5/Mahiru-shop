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
    $role = "User"; // Default role is User
    $created_at = date("Y-m-d H:i:s"); // Current timestamp

    if (!empty($user_name) && !empty($email) && !empty($pass) && !empty($address) && !empty($phone)) {
        // Check if there is any Admin yet; if not, assign the first user as Admin
        $check_admin = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='Admin'");
        $admin_count = $check_admin->fetch_assoc()['total'];

        if ($admin_count == 0) {
            $role = "Admin";
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Email exists -> Log in directly
            $_SESSION['user_id'] = $row['id'];
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
            // Create a new account
            $hashed_password = password_hash($pass, PASSWORD_BCRYPT);

            // Find the smallest available ID (fill gaps)
            $result = $conn->query("SELECT id + 1 AS next_id 
                                    FROM users 
                                    WHERE id + 1 NOT IN (SELECT id FROM users) 
                                    ORDER BY next_id 
                                    LIMIT 1");
            $row = $result->fetch_assoc();
            $next_id = $row['next_id'] ?? 1; // Default to 1 if no rows exist

            // Ensure the ID is not already taken (race condition check)
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $checkStmt->bind_param("i", $next_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            while ($checkResult->fetch_assoc()) {
                $next_id++; // Increment until an available ID is found
                $checkStmt->bind_param("i", $next_id);
                $checkStmt->execute();
                $checkResult = $stmt->get_result();
            }

            // Insert the new record with the assigned ID
            $stmt = $conn->prepare("INSERT INTO users (id, username, email, password, address, phone, role, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $next_id, $user_name, $email, $hashed_password, $address, $phone, $role, $created_at);

            if ($stmt->execute()) {
                // Registration successful -> Log in immediately
                $_SESSION['user_id'] = $next_id; // Thêm dòng này để lưu user_id
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
                <p><?php echo $message; ?></p>
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