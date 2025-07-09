<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

if (!isset($_GET['file'])) {
    http_response_code(400);
    die('Tham số file không hợp lệ');
}

$file = $_GET['file'];
$file = str_replace('\\', '/', $file);
$file = ltrim($file, '/');

if (strpos($file, 'datn/') === 0) {
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $file;
} else {
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/datn/' . $file;
}

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File không tồn tại: ' . htmlspecialchars($filePath));
}

$allowedDirs = [
    $_SERVER['DOCUMENT_ROOT'] . '/datn/file/',
    $_SERVER['DOCUMENT_ROOT'] . '/datn/uploads/'
];
$realPath = realpath($filePath);
$isAllowed = false;
foreach ($allowedDirs as $dir) {
    if (strpos($realPath, realpath($dir)) === 0) {
        $isAllowed = true;
        break;
    }
}
if (!$isAllowed) {
    http_response_code(403);
    die('Không có quyền truy cập file này');
}

$fileName = basename($filePath);
if (isset($_GET['name']) && !empty($_GET['name'])) {
    $customFileName = $_GET['name'];
    $originalExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $customExtension = pathinfo($customFileName, PATHINFO_EXTENSION);
    if (empty($customExtension) && !empty($originalExtension)) {
        $customFileName .= '.' . $originalExtension;
    }
    $downloadFileName = $customFileName;
} else {
    $downloadFileName = $fileName;
}
$fileSize = filesize($filePath);
$fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

// Kiểm tra file Word (docx) phải là file zip hợp lệ
if (in_array($fileExtension, ['docx', 'xlsx', 'pptx'])) {
    $fh = fopen($filePath, 'rb');
    $header = fread($fh, 4);
    fclose($fh);
    if ($header !== "PK\x03\x04") {
        http_response_code(415);
        die('File Office không hợp lệ (không phải file zip).');
    }
}

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
if (!$mimeType) $mimeType = 'application/octet-stream';

// Debug mode
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo "<h2>Debug thông tin file</h2>";
    echo "<strong>File path:</strong> " . htmlspecialchars($filePath) . "<br>";
    echo "<strong>File exists:</strong> " . (file_exists($filePath) ? 'Yes' : 'No') . "<br>";
    echo "<strong>File size:</strong> " . number_format($fileSize) . " bytes<br>";
    echo "<strong>File extension:</strong> " . htmlspecialchars($fileExtension) . "<br>";
    echo "<strong>Mime type:</strong> " . htmlspecialchars($mimeType) . "<br>";
    echo "<strong>Download filename:</strong> " . htmlspecialchars($downloadFileName) . "<br>";
    echo "<strong>File permissions:</strong> " . substr(sprintf('%o', fileperms($filePath)), -4) . "<br>";
    echo "<strong>File modified:</strong> " . date('Y-m-d H:i:s', filemtime($filePath)) . "<br>";
    
    // Kiểm tra header file
    if (in_array($fileExtension, ['docx', 'xlsx', 'pptx'])) {
        $fh = fopen($filePath, 'rb');
        $header = fread($fh, 10);
        fclose($fh);
        echo "<strong>File header (hex):</strong> " . bin2hex($header) . "<br>";
        echo "<strong>Is valid ZIP:</strong> " . (substr($header, 0, 4) === "PK\x03\x04" ? 'Yes' : 'No') . "<br>";
    }
    exit;
}

// Dọn dẹp tất cả output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Tạo tên file an toàn cho download với UTF-8 encoding
$safeFileName = $downloadFileName;

// Nếu là image, pdf, hoặc video, có thể hiển thị inline
$inlineTypes = ['image/', 'application/pdf', 'video/', 'audio/'];
$isInline = false;
foreach ($inlineTypes as $type) {
    if (strpos($mimeType, $type) === 0) {
        $isInline = true;
        break;
    }
}

// Set headers theo chuẩn RFC 6266 để hỗ trợ tên file Unicode
if (isset($_GET['preview']) && $_GET['preview'] === '1' && $isInline) {
    header('Content-Disposition: inline; filename="' . addslashes($safeFileName) . '"; filename*=UTF-8\'\'' . rawurlencode($safeFileName));
} else {
    header('Content-Disposition: attachment; filename="' . addslashes($safeFileName) . '"; filename*=UTF-8\'\'' . rawurlencode($safeFileName));
}

// Set headers chuẩn cho file download
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Content-Description: File Transfer');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT');
header('Accept-Ranges: bytes');

// Đảm bảo không có output trước khi gửi file
if (headers_sent()) {
    die('Headers đã được gửi. Không thể tải file.');
}

// Đọc và xuất file nhị phân với buffer lớn hơn
$fp = fopen($filePath, 'rb');
if ($fp === false) {
    http_response_code(500);
    die('Không thể mở file.');
}

// Gửi file theo chunk để tránh timeout với file lớn
while (!feof($fp)) {
    $chunk = fread($fp, 65536); // 64KB chunks
    if ($chunk === false) {
        break;
    }
    echo $chunk;
    
    // Flush output để tránh timeout
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}

fclose($fp);
exit;