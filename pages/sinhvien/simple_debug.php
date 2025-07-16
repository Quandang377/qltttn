<?php
// Simple debug for download issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Download Issue</h1>";

// Check session
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'ID_TaiKhoan' => 3,
        'VaiTro' => 'Sinh viên'
    ];
}

echo "<p>Session User ID: " . $_SESSION['user']['ID_TaiKhoan'] . "</p>";

// Check database
require_once __DIR__ . '/../../template/config.php';

// Get file info
$fileId = 40; // From your screenshot
$stmt = $conn->prepare("
    SELECT f.ID, f.TenFile, f.TenHienThi, f.DIR 
    FROM file f
    INNER JOIN tainguyen_dot td ON f.ID = td.id_file
    WHERE f.ID = ? AND f.Loai = 'Tainguyen' AND f.TrangThai = 1
");
$stmt->execute([$fileId]);
$fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($fileInfo) {
    echo "<h2>File Found:</h2>";
    echo "<p>ID: " . $fileInfo['ID'] . "</p>";
    echo "<p>TenFile: " . $fileInfo['TenFile'] . "</p>";
    echo "<p>TenHienThi: " . $fileInfo['TenHienThi'] . "</p>";
    echo "<p>DIR: " . $fileInfo['DIR'] . "</p>";
    
    // Check paths
    $originalPath = $fileInfo['DIR'];
    $possiblePaths = [
        __DIR__ . '/../../file/' . basename($originalPath),
        __DIR__ . '/../../file/' . $fileInfo['TenFile'],
        __DIR__ . '/../../' . $originalPath
    ];
    
    echo "<h2>Path Check:</h2>";
    foreach ($possiblePaths as $i => $path) {
        echo "<p>Path $i: " . htmlspecialchars($path) . "</p>";
        if (file_exists($path)) {
            echo "<p style='color: green;'>✓ EXISTS - Size: " . filesize($path) . " bytes</p>";
        } else {
            echo "<p style='color: red;'>✗ NOT EXISTS</p>";
        }
    }
    
    // Check file directory
    echo "<h2>File Directory Check:</h2>";
    $fileDir = __DIR__ . '/../../file/';
    echo "<p>File directory: " . $fileDir . "</p>";
    if (is_dir($fileDir)) {
        echo "<p style='color: green;'>✓ Directory exists</p>";
        $files = scandir($fileDir);
        echo "<p>Files in directory:</p><ul>";
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "<li>" . htmlspecialchars($file) . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Directory not exists</p>";
    }
    
} else {
    echo "<h2>File NOT Found</h2>";
}

// Test download link
echo "<h2>Test Links:</h2>";
echo "<p><a href='download_tainguyen.php?file=40' target='_blank'>Test Download File 40</a></p>";
echo "<p><a href='tainguyen2.php?debug=1' target='_blank'>Test Tainguyen2 Debug</a></p>";
?>
