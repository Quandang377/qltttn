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

if (!isset($_GET['file']) || !isset($_GET['type'])) {
    http_response_code(400);
    die('Tham số không hợp lệ');
}

$fileName = $_GET['file'];
$fileType = $_GET['type'];

// Kiểm tra loại file hợp lệ
$allowedTypes = ['baocao', 'nhanxet', 'phieuthuctap', 'khoasat'];
if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    die('Loại file không hợp lệ');
}

// Mapping loại file với loại trong database
$typeMapping = [
    'baocao' => 'Baocao',
    'nhanxet' => 'nhanxet',
    'phieuthuctap' => 'phieuthuctap',
    'khoasat' => 'khoasat'
];

$dbType = $typeMapping[$fileType];

// Kiểm tra file có thuộc về sinh viên hiện tại không
$stmt = $conn->prepare("SELECT f.DIR FROM file f WHERE f.TenFile = ? AND f.ID_SV = ? AND f.Loai = ? AND f.TrangThai = 1");
$stmt->execute([$fileName, $idTaiKhoan, $dbType]);
$fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fileInfo) {
    http_response_code(404);
    die('File không tồn tại hoặc không có quyền truy cập');
}

// Xử lý đường dẫn file
$originalPath = $fileInfo['DIR'];

// Chuyển đổi đường dẫn database thành đường dẫn phù hợp với hosting
$correctedPath = null;

// Nếu đường dẫn chứa "C:\xampp\htdocs\datn\file\" (localhost), chuyển thành đường dẫn tương đối
if (strpos($originalPath, 'C:\\xampp\\htdocs\\datn\\file\\') !== false) {
    $fileNameFromPath = basename($originalPath);
    $correctedPath = __DIR__ . '/../../file/' . $fileNameFromPath;
}
// Nếu đường dẫn chứa "file\" hoặc "file/" trong bất kỳ vị trí nào
else if (strpos($originalPath, 'file\\') !== false || strpos($originalPath, 'file/') !== false) {
    $fileNameFromPath = basename($originalPath);
    $correctedPath = __DIR__ . '/../../file/' . $fileNameFromPath;
}
// Nếu đường dẫn chỉ là đường dẫn tương đối
else if (strpos($originalPath, 'file/') === 0) {
    $correctedPath = __DIR__ . '/../../' . $originalPath;
}
// Nếu đường dẫn chỉ là tên file
else if (strpos($originalPath, '/') === false && strpos($originalPath, '\\') === false) {
    $correctedPath = __DIR__ . '/../../file/' . $originalPath;
}
// Nếu đường dẫn đã đúng định dạng tương đối
else {
    $correctedPath = $originalPath;
}

// Kiểm tra nhiều khả năng đường dẫn
$possiblePaths = [
    $correctedPath, // Đường dẫn đã chuyển đổi
    __DIR__ . '/../../file/' . basename($originalPath), // Đường dẫn tương đối với tên file
    __DIR__ . '/../../file/' . $fileName, // Đường dẫn với tên file gốc
    $originalPath, // Đường dẫn gốc (fallback)
];

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
    die('File không tồn tại trên server. Đường dẫn gốc: ' . htmlspecialchars($originalPath));
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
        'cpp' => 'text/x-c++src', 'c' => 'text/x-csrc'
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
    header('Content-Disposition: inline; filename="' . addslashes($fileName) . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
} else {
    header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
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
    die('Headers đã được gửi. Không thể tải file.');
}

// Đọc và xuất file
$fp = fopen($filePath, 'rb');
if ($fp === false) {
    http_response_code(500);
    die('Không thể mở file.');
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
