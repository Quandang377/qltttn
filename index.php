<?php
session_start();
define('BASE_PATH', '/datn');

$URL = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Loại bỏ tiền tố BASE_PATH
if (strpos($URL, BASE_PATH) === 0) {
    $URL = substr($URL, strlen(BASE_PATH));
}
$URL = trim($URL, '/');
$splitURL = explode('/', $URL);

// ==== Các route đặc biệt ==== //
if ($splitURL[0] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: " . BASE_PATH . "/login");
    exit;
}

if ($splitURL[0] === 'login') {
    require_once 'login.php';
    exit;
}

// ==== Nếu chưa đăng nhập => về login ==== //
if (!isset($_SESSION['user'])) {
    header("Location: " . BASE_PATH . "/login");
    exit;
}

// ==== Trang chủ tương ứng vai trò ==== //
if ($URL === '') {
    switch ($_SESSION['user']['VaiTro']) {
        case 'Admin':
            header("Location: " . BASE_PATH . "/admin/pages/trangchu");
            break;
        case 'Cán bộ Khoa/Bộ môn':
            header("Location: " . BASE_PATH . "/pages/canbo/trangchu");
            break;
        case 'Giáo viên':
            header("Location: " . BASE_PATH . "/pages/giaovien/trangchu");
            break;
        case 'Sinh viên':
            header("Location: " . BASE_PATH . "/pages/sinhvien/trangchu");
            break;
        default:
            require_once '404.php';
    }
    exit;
}

// ==== Phân luồng các khu vực riêng ==== //
$specialAreas = ['admin', 'api'];
if (in_array($splitURL[0], $specialAreas)) {
    $target = $splitURL[0] . '/index.php';
    if (file_exists($target)) {
        require_once $target;
    } else {
        require_once '404.php';
    }
    exit;
}

// ==== Xử lý pages/role/page ==== //
if ($splitURL[0] === 'pages') {
    $role = $splitURL[1] ?? '';
    $page = $splitURL[2] ?? 'trangchu';

    $filePath = "pages/$role/$page";
    if (!str_ends_with($filePath, '.php')) {
        $filePath .= '.php';
    }

    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        require_once '404.php';
    }
    exit;
}

// ==== Nếu không khớp gì cả => 404 ==== //
require_once '404.php';
