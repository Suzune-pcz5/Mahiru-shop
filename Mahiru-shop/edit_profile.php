<?php
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'mahiru_shop';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ========== LẤY DANH MỤC TỪ BẢNG products ==========
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products WHERE is_hidden = 0");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Get current user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If no username, use part before @ in email
if (!isset($user['username']) && isset($user['email'])) {
    $user['username'] = explode('@', $user['email'])[0];
}

// Handle update form
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    // Validate data
    if (empty($username) || empty($email) || empty($address) || empty($phone)) {
        $error = 'Please fill in all required fields (except password if you don\'t want to change it)';
    } else {
        try {
            // If new password entered
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET 
                    username = :username, 
                    email = :email, 
                    password = :password, 
                    address = :address, 
                    phone = :phone 
                    WHERE id = :id");
                $stmt->bindParam(':password', $hashed_password);
            } else {
                $stmt = $conn->prepare("UPDATE users SET 
                    username = :username, 
                    email = :email, 
                    address = :address, 
                    phone = :phone 
                    WHERE id = :id");
            }

            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':id', $user_id);

            if ($stmt->execute()) {
                $success = 'Profile updated successfully!';
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update session
                $_SESSION['user_name'] = $user['username'];
            } else {
                $error = 'Error updating profile';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Mahiru Shop</title>
    <link rel="stylesheet" href="css/edit_profile.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
        }
        .success-message.show {
            display: block;
        }
    </style>
</head>
<body>
<header>
    <div class="top-bar">
        <div class="container">
            <div class="contact-info">
                <span><i class="fas fa-phone"></i> 012345678</span>
                <span><i class="fas fa-envelope"></i> mahiru@gmail.com</span>
                <span><i class="fas fa-map-marker-alt"></i> 1104 Wall Street</span>
            </div>
            <div class="user-actions">
                <?php if (isset($_SESSION['user_name'])): ?>
                    <i class="fas fa-user"></i>
                    <span class="name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <div class="login-dropdown">
                        <a href="order_history.php" class="login-option">Order history</a>
                        <a href="edit_profile.php" class="login-option">Edit Profile</a>
                        <a href="logout.php" class="login-option">Log out</a>
                    </div>
                <?php else: ?>
                    <a class="login-link">
                        <i class="fas fa-user"></i>
                        <span class="name">Login/Sign up</span>
                    </a>
                    <div class="login-dropdown">
                        <a href="login.php" class="login-option">Login</a>
                        <a href="sign_up.php" class="login-option">Sign up</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="main-header">
        <div class="container">
            <div class="logo">
                <a href="index_account.php" class="logo-link"><h1>MAHIRU<span>.</span></h1></a>
            </div>
            <div class="search-bar">
                <form action="search_account.php" method="GET">
                    <input type="text" name="name" placeholder="Search here" value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>" />
                    <button type="submit" class="search-button">Search</button>
                </form>
            </div>
            <div class="user-menu">
                <a href="cart.php" class="icon"><i class="fas fa-shopping-cart"></i></a>
            </div>
        </div>
    </div>
    <nav class="category-nav">
        <div class="container">
            <ul class="category-list">
                <li><a href="index_account.php">Home</a></li>
                <?php foreach ($categories as $cat): ?>
                    <li><a href="index_account.php?category=<?= urlencode($cat['category']) ?>"> <?= htmlspecialchars($cat['category']) ?> </a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
</header>

<div class="profile-container">
    <div class="profile-header">
        <h1><i class="fas fa-user-edit"></i> Edit Profile</h1>
        <p>Update your account information</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form action="edit_profile.php" method="POST">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" class="form-control" 
                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="text" id="email" name="email" class="form-control" 
                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group password-toggle">
            <label for="password">New Password (leave blank to keep current):</label>
            <input type="password" id="password" name="password" class="form-control">
            <i class="fas fa-eye toggle-icon" onclick="togglePassword()"></i>
        </div>
        
        <div class="form-group">
            <label for="address">Shipping Address:</label>
            <textarea id="address" name="address" class="form-control" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone Number:</label>
            <input type="tel" id="phone" name="phone" class="form-control" 
                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
        </div>
        
        <button type="submit" class="btn btn-block">Update Profile</button>
    </form>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.querySelector('.toggle-icon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
    
    // Show success message for 5 seconds
    <?php if (!empty($success)): ?>
        setTimeout(() => {
            document.querySelector('.alert-success').style.display = 'none';
        }, 5000);
    <?php endif; ?>
</script>
</body>
</html>
