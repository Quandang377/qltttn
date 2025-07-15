<?php
// Cấu hình tự động phát hiện môi trường
function isLocalhost() {
    return in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || 
           strpos($_SERVER['HTTP_HOST'], '.local') !== false;
}

if (isLocalhost()) {
    // Cấu hình cho localhost
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "thuctapdb";
} else {
    // Cấu hình cho hosting
    $servername = "localhost";
    $username = "tttn_caothang"; // Thay bằng username thực tế
    $password = "your_password"; // Thay bằng password thực tế
    $dbname = "tttn_caothang_db"; // Thay bằng tên database thực tế
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    if (isLocalhost()) {
        die("Kết nối thất bại: " . $e->getMessage());
    } else {
        error_log("Database connection failed: " . $e->getMessage());
        die("Hệ thống đang bảo trì. Vui lòng thử lại sau.");
    }
}
?>
