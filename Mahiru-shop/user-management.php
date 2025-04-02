<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mahiru_shop";

// Kết nối đến MySQL với mysqli
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Xử lý AJAX để cập nhật trạng thái user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"], $_POST["status"])) {
    $user_id = intval($_POST["id"]);
    // Giả sử nếu checkbox checked thì status chuyển sang Deactive, ngược lại Active
    $new_status = ($_POST["status"] === "Active") ? "Active" : "Deactive";

    $sql = "UPDATE users SET status = '$new_status' WHERE id = $user_id";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "new_status" => $new_status]);
    } else {
        echo json_encode(["success" => false]);
    }
    exit();
}

// Tìm kiếm và phân trang
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$limit = 5; // Số user trên mỗi trang
$offset = ($page - 1) * $limit;

// Xây dựng câu truy vấn tìm kiếm (theo username)
$whereClause = "WHERE username LIKE '%$search%'";

// Lấy số lượng user
$count_query = "SELECT COUNT(*) AS total FROM users $whereClause";
$count_result = $conn->query($count_query);
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Truy vấn danh sách user có tìm kiếm & phân trang
$sql = "SELECT id, username, email, role, status FROM users 
        $whereClause 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Để giữ lại tham số tìm kiếm trong phân trang, định nghĩa $currentParams:
$currentParams = [];
if (!empty($search)) {
    $currentParams['search'] = $search;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Mahiru Shop Admin</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="./css/user.css">
</head>
<body>
    <div class="page-container">
        <header>
            <div class="container">
                <h1 class="logo">MAHIRU<span>.</span> ADMIN</h1>
                <nav>
                    <ul>
                        <li><a href="./admin.php">Dashboard</a></li>
                        <li><a href="./user-management.php" class="active">User</a></li>
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
                        <div class="action-bar">
                            <form method="GET">
                                <input type="text" name="search" placeholder="Search for user name" value="<?= htmlspecialchars($search) ?>">
                                <button type="submit">Search</button>
                            </form>
                        </div>

                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User Name</th>
                                    <th>Email Address</th>
                                    <th>User Role</th>
                                    <th>Status</th>
                                    <th>Lock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $statusClass = ($row['status'] == 'Active') ? 'status-active' : 'status-inactive';

                                        echo "<tr data-user-id='{$row['id']}'>";
                                        echo "<td>{$row['id']}</td>";
                                        echo "<td>{$row['username']}</td>";
                                        echo "<td>{$row['email']}</td>";
                                        echo "<td>{$row['role']}</td>";
                                        echo "<td><span class='status {$statusClass}'>{$row['status']}</span></td>";

                                        echo "<td>
                                                <label class='switch'>
                                                    <input type='checkbox' class='toggle-status' data-id='{$row['id']}' " . ($row['status'] == 'Deactive' ? 'checked' : '') . ">
                                                    <span class='slider'></span>
                                                </label>
                                              </td>";

                                        echo "<td>
                                                <a href='edit-user.php?id={$row['id']}' class='action-btn' style='background-color: green;border-color: green; color: white'>Edit</a>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7'>No users found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>

                        <!-- Phân trang -->
                        <div class="pagination" style="margin-top: 20px;">
                            <?php if ($total_pages > 1): ?>
                                <?php if ($page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($currentParams, ['page' => $page - 1])); ?>">&laquo;</a>
                                <?php else: ?>
                                    <span>&laquo;</span>
                                <?php endif; ?>

                                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                    <a href="?<?= http_build_query(array_merge($currentParams, ['page' => $p])); ?>" class="<?= ($p == $page) ? 'active' : ''; ?>">
                                        <?= $p; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?= http_build_query(array_merge($currentParams, ['page' => $page + 1])); ?>">&raquo;</a>
                                <?php else: ?>
                                    <span>&raquo;</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <footer>
            <div class="container">
                <p>&copy;  Mahiru Shop. We are pleased to serve you.</p>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".toggle-status").forEach(switchElement => {
                switchElement.addEventListener("change", function () {
                    let userId = this.getAttribute("data-id");
                    // Nếu checkbox checked => hiện trạng thái là Deactive, ngược lại Active
                    let newStatus = this.checked ? "Deactive" : "Active";

                    fetch("user-management.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `id=${userId}&status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let row = document.querySelector(`tr[data-user-id='${userId}']`);
                            let statusCell = row.querySelector(".status");
                            statusCell.textContent = data.new_status;
                            statusCell.classList.toggle("status-active", data.new_status === "Active");
                            statusCell.classList.toggle("status-inactive", data.new_status === "Deactive");
                        } else {
                            alert("Failed to update status!");
                        }
                    })
                    .catch(() => alert("Error connecting to server!"));
                });
            });
        });
        lucide.createIcons();
    </script>
</body>
</html>
<?php $conn->close(); ?>
