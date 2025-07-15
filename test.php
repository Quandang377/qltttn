<?php
// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test Basic PHP</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

echo "<h3>Test Database Connection</h3>";
try {
    if (!function_exists('isLocalhost')) {
        function isLocalhost() {
            return in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || 
                   strpos($_SERVER['HTTP_HOST'], '.local') !== false;
        }
    }

    if (isLocalhost()) {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "thuctapdb";
        echo "Using localhost config<br>";
    } else {
        $servername = "localhost";
        $username = "siektefuhosting_root";
        $password = "0933519887Lol@";
        $dbname = "siektefuhosting_thuctapdb";
        echo "Using hosting config<br>";
    }

    echo "Connecting to: host=$servername, user=$username, db=$dbname<br>";
    
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connected successfully<br>";
    
    // Test query
    $stmt = $conn->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . count($tables) . "<br>";
    
} catch(PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<h3>Test File Paths</h3>";
$testFiles = [
    $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php",
    $_SERVER['DOCUMENT_ROOT'] . "/datn/middleware/check_role.php",
    $_SERVER['DOCUMENT_ROOT'] . "/datn/pages/sinhvien/trangchu.php"
];

foreach ($testFiles as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists<br>";
    } else {
        echo "✗ $file NOT FOUND<br>";
    }
}

echo "<h3>Test Session</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session save path: " . session_save_path() . "<br>";

echo "<h3>Test Complete</h3>";
echo "If you see this message, basic PHP is working.<br>";
?>
