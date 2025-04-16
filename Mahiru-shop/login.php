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
    $email = trim($_POST['email']);
    $pass = trim($_POST['password']);

    if (!empty($email) && !empty($pass)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if ($row['status'] == 'Deactive') {
                $message = "The account has been locked";
            } else if (password_verify($pass, $row['password'])) {
                session_unset();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['username'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_address'] = $row['address'];
                $_SESSION['user_phone'] = $row['phone'];
                $_SESSION['user_role'] = $row['role'];

                echo "<script>
                        alert('Login successful!');
                        window.location.href = 'index_account.php';
                      </script>";
                exit();
            } else {
                $message = "Incorrect password!";
            }
        } else {
            $message = "Account does not exist!";
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
    <title>Login - MAHIRU.</title>
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
                    <button type="submit">Login</button>
                </form>
                <div class="form-footer">
                    <p>Don't have an account? <a href="sign_up.php" class="signup-link">Sign up</a></p>
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