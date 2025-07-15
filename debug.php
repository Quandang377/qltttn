<?php
// Debug helper để kiểm tra lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Information</h2>";
echo "<strong>Server Info:</strong><br>";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";

echo "<br><strong>Session Info:</strong><br>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session Data: ";
var_dump($_SESSION);

echo "<br><strong>Database Connection Test:</strong><br>";
try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';
    echo "✓ Database connection successful<br>";
    
    // Test query
    $stmt = $conn->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . count($tables) . "<br>";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<br><strong>File Path Test:</strong><br>";
$testPaths = [
    '/datn/template/config.php',
    '/datn/middleware/check_role.php',
    '/datn/pages/sinhvien/trangchu.php'
];

foreach ($testPaths as $path) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
    if (file_exists($fullPath)) {
        echo "✓ $path exists<br>";
    } else {
        echo "✗ $path NOT FOUND<br>";
    }
}

echo "<br><strong>URL Parsing Test:</strong><br>";
$URL = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "Original URL: " . $URL . "<br>";

define('BASE_PATH', '/datn');
if (strpos($URL, BASE_PATH) === 0) {
    $URL = substr($URL, strlen(BASE_PATH));
}
$URL = trim($URL, '/');
$splitURL = explode('/', $URL);
echo "Processed URL: " . $URL . "<br>";
echo "Split URL: ";
var_dump($splitURL);
?>
