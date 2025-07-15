<?php
// Cấu hình cho hosting
$servername = "localhost"; // Hoặc IP/hostname của hosting
$username = "your_hosting_username"; // Username database từ hosting
$password = "your_hosting_password"; // Password database từ hosting
$dbname = "your_hosting_database"; // Tên database trên hosting

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Log lỗi thay vì hiển thị trực tiếp
    error_log("Database connection failed: " . $e->getMessage());
    die("Hệ thống đang bảo trì. Vui lòng thử lại sau.");
}
?>
