<?php
// Debug download tainguyen
session_start();

// Mô phỏng session sinh viên
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'ID_TaiKhoan' => 3,
        'VaiTro' => 'Sinh viên',
        'TenDangNhap' => 'test_user'
    ];
}

require_once __DIR__ . '/../../template/config.php';

echo "=== DEBUG DOWNLOAD TAINGUYEN ===\n";

// Lấy thông tin file từ GET parameter
$fileId = $_GET['file'] ?? 40; // ID file từ debug trước

echo "File ID: $fileId\n";

// Lấy thông tin user
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'];
$stmt = $conn->prepare("SELECT ID_Dot FROM sinhvien WHERE ID_TaiKhoan = ?");
$stmt->execute([$idTaiKhoan]);
$idDot = $stmt->fetchColumn();

echo "User ID: $idTaiKhoan\n";
echo "Dot ID: $idDot\n";

// Lấy thông tin file
$stmt = $conn->prepare("
    SELECT f.ID, f.TenFile, f.TenHienThi, f.DIR 
    FROM file f
    INNER JOIN tainguyen_dot td ON f.ID = td.id_file
    WHERE f.ID = ? AND f.Loai = 'Tainguyen' AND f.TrangThai = 1 AND td.id_dot = ?
");
$stmt->execute([$fileId, $idDot]);
$fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fileInfo) {
    echo "❌ File không tồn tại hoặc không có quyền truy cập\n";
    exit;
}

echo "✅ File info found:\n";
echo "  TenFile: " . $fileInfo['TenFile'] . "\n";
echo "  TenHienThi: " . $fileInfo['TenHienThi'] . "\n";
echo "  DIR: " . $fileInfo['DIR'] . "\n";

// Xử lý path
$originalPath = $fileInfo['DIR'];
$possiblePaths = [];

// Logic từ tainguyen2_clean.php
if (strpos($originalPath, 'C:\\xampp\\htdocs\\datn\\file\\') !== false) {
    $fileName = basename($originalPath);
    $possiblePaths[] = __DIR__ . '/../../file/' . $fileName;
}
else if (strpos($originalPath, 'file\\') !== false || strpos($originalPath, 'file/') !== false) {
    $fileName = basename($originalPath);
    $possiblePaths[] = __DIR__ . '/../../file/' . $fileName;
}
else if (strpos($originalPath, 'file/') === 0) {
    $possiblePaths[] = __DIR__ . '/../../' . $originalPath;
}
else if (strpos($originalPath, '/') === false && strpos($originalPath, '\\') === false) {
    $possiblePaths[] = __DIR__ . '/../../file/' . $originalPath;
}
else {
    $possiblePaths[] = $originalPath;
}

// Thêm các path khác
$possiblePaths[] = __DIR__ . '/../../file/' . basename($originalPath);
$possiblePaths[] = __DIR__ . '/../../file/' . $fileInfo['TenFile'];

$possiblePaths = array_unique($possiblePaths);

echo "\nPossible paths:\n";
foreach ($possiblePaths as $i => $path) {
    echo "  $i: $path\n";
    if (file_exists($path)) {
        echo "    ✅ EXISTS (size: " . filesize($path) . " bytes)\n";
    } else {
        echo "    ❌ NOT EXISTS\n";
    }
}

// Tìm path tồn tại
$foundPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $foundPath = $path;
        break;
    }
}

if ($foundPath) {
    echo "\n✅ Found working path: $foundPath\n";
    echo "File size: " . filesize($foundPath) . " bytes\n";
    echo "File extension: " . pathinfo($foundPath, PATHINFO_EXTENSION) . "\n";
    
    // Test actual download
    if (isset($_GET['test_download']) && $_GET['test_download'] === '1') {
        echo "\n=== TESTING DOWNLOAD ===\n";
        
        $fileSize = filesize($foundPath);
        $fileName = $fileInfo['TenHienThi'] ?: $fileInfo['TenFile'];
        
        // Set headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . $fileSize);
        header('Content-Description: File Transfer');
        
        // Send file
        readfile($foundPath);
        exit;
    }
    
    echo "\nTo test download, add ?test_download=1 to URL\n";
} else {
    echo "\n❌ No working path found\n";
}

echo "\n=== END DEBUG ===\n";
?>
