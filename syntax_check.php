<?php
// Test syntax check cho tainguyen2.php
echo "=== SYNTAX CHECK ===\n";

// Kiểm tra syntax
$file_path = __DIR__ . '/pages/sinhvien/tainguyen2.php';
$output = [];
$return_var = 0;

// Check syntax
exec("php -l \"$file_path\"", $output, $return_var);

echo "File: $file_path\n";
echo "Return code: $return_var\n";
echo "Output:\n";
foreach ($output as $line) {
    echo "  $line\n";
}

if ($return_var === 0) {
    echo "\n✓ SYNTAX OK - Không có lỗi syntax\n";
} else {
    echo "\n✗ SYNTAX ERROR - Có lỗi syntax\n";
}

// Kiểm tra file tồn tại
if (file_exists($file_path)) {
    echo "✓ File exists\n";
    echo "File size: " . filesize($file_path) . " bytes\n";
} else {
    echo "✗ File not found\n";
}

echo "\n=== END CHECK ===\n";
?>
