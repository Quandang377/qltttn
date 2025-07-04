<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'Admin') {
    header("Location: /datn/");
    exit;
}

// Lấy đường dẫn và tách phần sau /admin
$splitURL = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$adminIndex = array_search('admin', $splitURL);
$subPath = array_slice($splitURL, $adminIndex + 1); // sau 'admin/'

// Mặc định về trang chủ nếu không có gì
if (empty($subPath) || $subPath[0] !== 'pages') {
    header("Location: /datn/admin/pages/trangchu");
    exit;
}

$page = $subPath[1] ?? 'trangchu';
$path = __DIR__ . '/pages/' . $page . '.php';

if (file_exists($path)) {
    require_once $path;
} else {
    require_once __DIR__ . '/../404.php';
}
?>