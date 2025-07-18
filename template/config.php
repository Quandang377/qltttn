<?php
// Cấu hình tự động phát hiện môi trường
if (!function_exists('isLocalhost')) {
    function isLocalhost() {
        return in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || 
               strpos($_SERVER['HTTP_HOST'], '.local') !== false;
    }
}

if (isLocalhost()) {
    // Cấu hình cho localhost
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "thuctapdb";
} else {
    // Cấu hình cho hosting - BẠN CẦN CẬP NHẬT THÔNG TIN NÀY
    $servername = "localhost";
    $username = "siektefuhosting_root"; // Thay bằng username database thực tế từ hosting
    $password = "0933519887Lol@"; // Thay bằng password database thực tế từ hosting
    $dbname = "siektefuhosting_thuctapdb"; // Thay bằng tên database thực tế từ hosting
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
