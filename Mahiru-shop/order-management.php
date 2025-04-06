<?php
// Kết nối cơ sở dữ liệu
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mahiru_shop';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1) Xây dựng truy vấn đếm tổng số orders
$sql_count = "SELECT COUNT(*) AS total FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";

// 2) Xây dựng truy vấn lấy dữ liệu
$sql_data = "SELECT o.*, u.id AS user_id, u.username 
             FROM orders o 
             JOIN users u ON o.user_id = u.id 
             WHERE 1=1";

// Mảng lưu điều kiện, kiểu bind_param, và giá trị
$conditions = "";
$bindTypes = "";
$bindValues = [];

// Filter: start-date
if (!empty($_GET['start-date'])) {
    $conditions .= " AND o.created_at >= ?";
    $bindTypes .= "s";
    $bindValues[] = $_GET['start-date'];
}
// Filter: end-date
if (!empty($_GET['end-date'])) {
    $conditions .= " AND o.created_at <= ?";
    $bindTypes .= "s";
    $bindValues[] = $_GET['end-date'] . " 23:59:59";
}
// Filter: order-status
if (!empty($_GET['order-status'])) {
    $conditions .= " AND o.status = ?";
    $bindTypes .= "s";
    $bindValues[] = $_GET['order-status'];
}
// Filter: address (sử dụng LIKE để hỗ trợ tìm chuỗi)
if (!empty($_GET['address'])) {
    $conditions .= " AND o.address LIKE ?";
    $bindTypes .= "s";
    $bindValues[] = "%" . $_GET['address'] . "%";
}

// Gộp điều kiện vào câu lệnh
$sql_count .= $conditions;
$sql_data  .= $conditions;

// 3) Phân trang
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số orders
$stmt_count = $conn->prepare($sql_count);
if (!empty($bindTypes)) {
    $stmt_count->bind_param($bindTypes, ...$bindValues);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total = $row_count ? $row_count['total'] : 0;
$totalPages = ceil($total / $limit);
$stmt_count->close();

// 4) Truy vấn dữ liệu chính thức
$sql_data .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$bindTypesData = $bindTypes . "ii";
$bindValuesData = $bindValues;
$bindValuesData[] = $limit;
$bindValuesData[] = $offset;

$stmt_data = $conn->prepare($sql_data);
$stmt_data->bind_param($bindTypesData, ...$bindValuesData);
$stmt_data->execute();
$result_data = $stmt_data->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard - Mahiru Shop</title>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="./css/order.css" />
</head>
<body>
  <header>
    <div class="container">
      <h1 class="logo">MAHIRU<span>.</span> ADMIN</h1>
      <nav>
        <ul>
          <li><a href="./admin.php">Dashboard</a></li>
          <li><a href="./user-management.php">User</a></li>
          <li><a href="./order-management.php" class="active">Orders</a></li>
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
          <h2>Order Management</h2>
          <ul>
            <li><a href="./order-management.php" class="active">Order List</a></li>
          </ul>
        </div>

        <section class="card" style="margin-bottom: 0px">
          <div class="filters">
            <form method="GET" action="">
              <div class="filters-row">
                <div class="filter-group">
                  <label class="filter-label">Date Range</label>
                  <div class="date-range">
                    <div>
                      <label>From:</label>
                      <input type="date" name="start-date" value="<?php echo isset($_GET['start-date']) ? $_GET['start-date'] : ''; ?>" />
                    </div>
                    <div class="date-to">
                      <label>To:</label>
                      <input type="date" name="end-date" value="<?php echo isset($_GET['end-date']) ? $_GET['end-date'] : ''; ?>" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="filters-row">
                <div class="filter-group">
                  <label class="filter-label">Status</label>
                  <select name="order-status">
                    <option value="">All Statuses</option>
                    <?php
                      $statuses = ['pending', 'processing', 'confirmed', 'delivered', 'canceled'];
                      foreach ($statuses as $status) {
                        $selected = (isset($_GET['order-status']) && $_GET['order-status'] === $status) ? 'selected' : '';
                        echo "<option value=\"$status\" $selected>" . ucfirst($status) . "</option>";
                      }
                    ?>
                  </select>
                </div>

                <div class="filter-group">
                  <label class="filter-label">Address</label>
                  <input type="text" name="address" placeholder="e.g. District 1" value="<?php echo isset($_GET['address']) ? htmlspecialchars($_GET['address']) : ''; ?>" />
                </div>

                <button type="submit" class="btn">Search</button>
              </div>
            </form>
          </div>

          <table>
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Username</th>
                <th>Date</th>
                <th>Address</th>
                <th>Total</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if ($result_data->num_rows > 0) {
                while ($row = $result_data->fetch_assoc()) {
                  $date = date('Y-m-d', strtotime($row['created_at']));
                  $statusText = ucfirst($row['status']);
                  echo "<tr>";
                  echo "<td>#{$row['id']}</td>";
                  echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                  echo "<td>{$date}</td>";
                  echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                  echo "<td>\${$row['total_price']}</td>";
                  echo "<td>{$statusText}</td>";
                  echo "<td><a href='./detail-order.php?id={$row['id']}' class='btn'>View Details</a></td>";
                  echo "</tr>";
                }
              } else {
                echo "<tr><td colspan='8'>No orders found.</td></tr>";
              }
              ?>
            </tbody>
          </table>

          <!-- Phân trang -->
          <div class="pagination" style="margin: 15px;">
            <?php
            if ($totalPages > 1):
              if ($page > 1):
                echo "<a href=\"?" . http_build_query(array_merge($_GET, ['page' => $page - 1])) . "\">«</a>";
              else:
                echo "<span>«</span>";
              endif;

              for ($p = 1; $p <= $totalPages; $p++) {
                $query = http_build_query(array_merge($_GET, ['page' => $p]));
                $class = $p == $page ? 'class="active"' : '';
                echo "<a href=\"?$query\" $class>$p</a>";
              }

              if ($page < $totalPages):
                echo "<a href=\"?" . http_build_query(array_merge($_GET, ['page' => $page + 1])) . "\">»</a>";
              else:
                echo "<span>»</span>";
              endif;
            endif;
            ?>
          </div>
        </section>
      </div>
    </div>
  </main>

  <footer>
    <div class="container">
      <p>©Mahiru Shop. We are pleased to serve you.</p>
    </div>
  </footer>

  <script>
    lucide.createIcons();
  </script>
</body>
</html>
