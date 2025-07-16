<?php
// Bắt đầu session an toàn
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../template/config.php";
require_once __DIR__ . '/../../middleware/check_role.php';

// Lấy thông tin user trước
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$userRole = $_SESSION['user']['VaiTro'] ?? null;

// Kiểm tra đăng nhập và role sinh viên
if (!$idTaiKhoan || $userRole !== 'Sinh viên') {
    http_response_code(403);
    die('Không có quyền truy cập');
}

if (!isset($_GET['file'])) {
    http_response_code(400);
    die('Tham số file không hợp lệ');
}

$fileParam = $_GET['file'];

// Lấy thông tin đợt của sinh viên
$stmt = $conn->prepare("SELECT ID_Dot FROM sinhvien WHERE ID_TaiKhoan = ?");
$stmt->execute([$idTaiKhoan]);
$idDot = $stmt->fetchColumn();

if (!$idDot) {
    http_response_code(403);
    die('Bạn chưa được phân vào đợt thực tập nào');
}

// Kiểm tra file có thuộc đợt thực tập của sinh viên không
$stmt = $conn->prepare("
    SELECT f.ID, f.TenFile, f.TenHienThi, f.DIR 
    FROM file f
    INNER JOIN tainguyen_dot td ON f.ID = td.id_file
    WHERE f.ID = ? AND f.Loai = 'Tainguyen' AND f.TrangThai = 1 AND td.id_dot = ?
");
$stmt->execute([$fileParam, $idDot]);
$fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fileInfo) {
    http_response_code(404);
    die('File không tồn tại hoặc không có quyền truy cập');
}

// Xử lý đường dẫn file để đảm bảo tính chính xác
$originalPath = $fileInfo['DIR'];

// Kiểm tra nhiều khả năng đường dẫn
$possiblePaths = [];

// Path 1: Nếu là đường dẫn Windows localhost
if (strpos($originalPath, 'C:\\xampp\\htdocs\\datn\\file\\') !== false) {
    $fileName = basename($originalPath);
    $possiblePaths[] = __DIR__ . '/../../file/' . $fileName;
}

// Path 2: Nếu đường dẫn chứa "file/" hoặc "file\"
if (strpos($originalPath, 'file/') !== false || strpos($originalPath, 'file\\') !== false) {
    $fileName = basename($originalPath);
    $possiblePaths[] = __DIR__ . '/../../file/' . $fileName;
}

// Path 3: Nếu đường dẫn bắt đầu bằng "file/"
if (strpos($originalPath, 'file/') === 0) {
    $possiblePaths[] = __DIR__ . '/../../' . $originalPath;
}

// Path 4: Nếu chỉ là tên file
if (strpos($originalPath, '/') === false && strpos($originalPath, '\\') === false) {
    $possiblePaths[] = __DIR__ . '/../../file/' . $originalPath;
}

// Path 5: Thêm các path backup
$possiblePaths[] = __DIR__ . '/../../file/' . basename($originalPath);
$possiblePaths[] = __DIR__ . '/../../file/' . $fileInfo['TenFile'];

// Path 6: Nếu originalPath đã là đường dẫn đầy đủ
if (is_file($originalPath)) {
    $possiblePaths[] = $originalPath;
}

// Loại bỏ path trùng lặp
$possiblePaths = array_unique($possiblePaths);

// Tìm đường dẫn file tồn tại
$filePath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $filePath = $path;
        break;
    }
}

// Nếu không tìm thấy file nào, báo lỗi
if (!$filePath) {
    http_response_code(404);
    
    // Debug mode
    if (isset($_GET['debug'])) {
        echo "<h1>Debug: File không tồn tại</h1>";
        echo "<p><strong>File ID:</strong> " . htmlspecialchars($fileParam) . "</p>";
        echo "<p><strong>User ID:</strong> " . htmlspecialchars($idTaiKhoan) . "</p>";
        echo "<p><strong>Dot ID:</strong> " . htmlspecialchars($idDot) . "</p>";
        echo "<p><strong>Đường dẫn gốc:</strong> " . htmlspecialchars($originalPath) . "</p>";
        echo "<p><strong>Đã thử tìm tại:</strong></p><ul>";
        foreach ($possiblePaths as $path) {
            echo "<li>" . htmlspecialchars($path) . 
                 " - " . (file_exists($path) ? "EXISTS" : "NOT EXISTS") . "</li>";
        }
        echo "</ul>";
        
        // Check file directory
        $fileDir = __DIR__ . '/../../file/';
        echo "<p><strong>File directory:</strong> " . htmlspecialchars($fileDir) . "</p>";
        if (is_dir($fileDir)) {
            echo "<p><strong>Directory contents:</strong></p><ul>";
            $files = scandir($fileDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    echo "<li>" . htmlspecialchars($file) . "</li>";
                }
            }
            echo "</ul>";
        }
        
        echo "<p><a href='tainguyen2.php'>Quay lại trang tài nguyên</a></p>";
    } else {
        echo "<!DOCTYPE html><html><head><title>File không tồn tại</title></head><body>";
        echo "<h1>File không tồn tại</h1>";
        echo "<p>Đường dẫn gốc: " . htmlspecialchars($originalPath) . "</p>";
        echo "<p>Đã thử tìm tại:</p><ul>";
        foreach ($possiblePaths as $path) {
            echo "<li>" . htmlspecialchars($path) . "</li>";
        }
        echo "</ul>";
        echo "<p><a href='tainguyen2.php'>Quay lại trang tài nguyên</a></p>";
        echo "<p><a href='download_tainguyen.php?file=" . urlencode($fileParam) . "&debug=1'>Xem debug chi tiết</a></p>";
        echo "</body></html>";
    }
    exit;
}

// Xử lý tên file download
$downloadFileName = '';
if (isset($_GET['name']) && !empty($_GET['name'])) {
    $downloadFileName = $_GET['name'];
} elseif (!empty($fileInfo['TenHienThi'])) {
    $downloadFileName = $fileInfo['TenHienThi'];
} else {
    $downloadFileName = $fileInfo['TenFile'];
}

// Đảm bảo extension đúng
$originalExtension = pathinfo($fileInfo['TenFile'], PATHINFO_EXTENSION);
$downloadExtension = pathinfo($downloadFileName, PATHINFO_EXTENSION);
if (empty($downloadExtension) && !empty($originalExtension)) {
    $downloadFileName .= '.' . $originalExtension;
}

$fileSize = filesize($filePath);
$fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

// Hàm get MIME type
function getMimeType($extension) {
    $mimeTypes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
        'bmp' => 'image/bmp', 'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'pdf' => 'application/pdf', 'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain', 'html' => 'text/html', 'css' => 'text/css', 'js' => 'application/javascript',
        'json' => 'application/json', 'xml' => 'application/xml',
        'zip' => 'application/zip', 'rar' => 'application/x-rar-compressed', '7z' => 'application/x-7z-compressed',
        'tar' => 'application/x-tar', 'gz' => 'application/gzip',
        'mp4' => 'video/mp4', 'avi' => 'video/x-msvideo', 'mov' => 'video/quicktime',
        'wmv' => 'video/x-ms-wmv', 'flv' => 'video/x-flv', 'webm' => 'video/webm', 'mkv' => 'video/x-matroska',
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'flac' => 'audio/flac', 'aac' => 'audio/aac',
        'php' => 'text/x-php', 'sql' => 'application/sql', 'py' => 'text/x-python', 'java' => 'text/x-java-source',
        'cpp' => 'text/x-c++src', 'c' => 'text/x-csrc', 'mdj' => 'application/octet-stream'
    ];
    return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
}

$mimeType = getMimeType($fileExtension);

// Dọn dẹp output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Kiểm tra xem có phải preview không
$inlineTypes = ['image/', 'application/pdf', 'video/', 'audio/'];
$isInline = false;
foreach ($inlineTypes as $type) {
    if (strpos($mimeType, $type) === 0) {
        $isInline = true;
        break;
    }
}

// Set headers
if (isset($_GET['preview']) && $_GET['preview'] === '1' && $isInline) {
    header('Content-Disposition: inline; filename="' . addslashes($downloadFileName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadFileName));
} else {
    header('Content-Disposition: attachment; filename="' . addslashes($downloadFileName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadFileName));
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Content-Description: File Transfer');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT');
header('Accept-Ranges: bytes');

// Kiểm tra headers
if (headers_sent()) {
    echo "<!DOCTYPE html><html><head><title>Lỗi tải file</title></head><body>";
    echo "<h1>Lỗi tải file</h1>";
    echo "<p>Headers đã được gửi. Không thể tải file.</p>";
    echo "<p><a href='tainguyen2.php'>Quay lại trang tài nguyên</a></p>";
    echo "</body></html>";
    exit;
}

// Đọc và xuất file
$fp = fopen($filePath, 'rb');
if ($fp === false) {
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>Lỗi mở file</title></head><body>";
    echo "<h1>Lỗi mở file</h1>";
    echo "<p>Không thể mở file: " . htmlspecialchars($filePath) . "</p>";
    echo "<p><a href='tainguyen2.php'>Quay lại trang tài nguyên</a></p>";
    echo "</body></html>";
    exit;
}

// Gửi file theo chunk
while (!feof($fp)) {
    $chunk = fread($fp, 65536); // 64KB chunks
    if ($chunk === false) {
        break;
    }
    echo $chunk;
    
    // Flush output
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}

fclose($fp);
exit;
?>
