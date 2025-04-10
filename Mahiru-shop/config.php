<?php
$servername = "localhost";
$username = "root"; // Nếu dùng XAMPP, mặc định là 'root'
$password = ""; // Mặc định trống
$dbname = "mahiru_shop";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
