<?php
// File test login và session
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<!DOCTYPE html><html><head><title>Test Login</title></head><body>";
echo "<h1>Test Login và Session</h1>";

// Test tạo session
if (!isset($_SESSION['user'])) {
    echo "<p>Tạo session test...</p>";
    $_SESSION['user'] = [
        'ID_TaiKhoan' => 3,
        'VaiTro' => 'Sinh viên',
        'TaiKhoan' => 'Sv1'
    ];
    echo "<p style='color: green;'>✓ Session created</p>";
} else {
    echo "<p style='color: green;'>✓ Session exists</p>";
}

echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";

// Test database
try {
    require_once __DIR__ . "/template/config.php";
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    // Test tài khoản
    $stmt = $conn->prepare("SELECT * FROM taikhoan WHERE ID_TaiKhoan = ?");
    $stmt->execute([3]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($account) {
        echo "<p style='color: green;'>✓ Account found:</p>";
        echo "<pre>" . print_r($account, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>✗ Account not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Test Links</h2>";
echo "<p><a href='debug_blank_screen.php'>Debug Blank Screen</a></p>";
echo "<p><a href='trangchu_minimal.php'>Trangchu Minimal</a></p>";
echo "<p><a href='test_middleware.php'>Test Middleware</a></p>";
echo "<p><a href='pages/sinhvien/trangchu.php'>Trangchu Full</a></p>";

echo "</body></html>";
?>
