<?php
// Kiểm tra tên bảng chính xác
require_once __DIR__ . "/../../template/config.php";

echo "<h2>Kiểm tra tên bảng trong database</h2>";

// Liệt kê tất cả bảng
$sql = "SHOW TABLES";
$result = $conn->query($sql);
$tables = $result->fetchAll(PDO::FETCH_COLUMN);

echo "<h3>Danh sách bảng:</h3>";
foreach ($tables as $table) {
    echo "- " . $table . "<br>";
}

// Tìm bảng liên quan đến tài nguyên
echo "<h3>Bảng liên quan đến tài nguyên:</h3>";
foreach ($tables as $table) {
    if (stripos($table, 'tai') !== false || stripos($table, 'nguyen') !== false) {
        echo "- " . $table . "<br>";
        
        // Xem cấu trúc bảng
        $sql = "DESCRIBE " . $table;
        $result = $conn->query($sql);
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "&nbsp;&nbsp;Columns: ";
        foreach ($columns as $col) {
            echo $col['Field'] . " (" . $col['Type'] . "), ";
        }
        echo "<br><br>";
    }
}

$conn = null;
?>
