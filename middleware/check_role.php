<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/datn');
}

$uriClean = strtok($_SERVER['REQUEST_URI'], '?');

$publicPages = [
    BASE_PATH . "/pages/sinhvien/trangchu",
    BASE_PATH . "/pages/sinhvien/chitietthongbao",
    BASE_PATH . "/pages/sinhvien/thongbaomoi",
    BASE_PATH . "/pages/sinhvien/tainguyen",
    BASE_PATH . "/pages/sinhvien/timkiem",
    BASE_PATH . "/pages/sinhvien/xemdanhsachcongty",
    BASE_PATH . "/pages/sinhvien/require_login",
];

if (!isset($_SESSION['user']) && !in_array($uriClean, $publicPages)) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: " . BASE_PATH . "/login");
    exit;
}

// nếu đã đăng nhập thì kiểm tra vai trò
$vaiTro = $_SESSION['user']['VaiTro'] ?? null;

$phanQuyen = [
    'Admin' => '/datn/admin/pages',
    'Cán bộ Khoa/Bộ môn' => '/datn/pages/canbo',
    'Giáo viên' => '/datn/pages/giaovien',
    'Sinh viên' => '/datn/pages/sinhvien'
];

$hopLe = false;
foreach ($phanQuyen as $vai => $duongDan) {
    if ($vai === $vaiTro && strpos($uriClean, $duongDan) === 0) {
        $hopLe = true;
        break;
    }
}

if (!$hopLe && isset($_SESSION['user'])) {
    switch ($vaiTro) {
        case 'Admin':
            header("Location:" . BASE_PATH . "/admin/pages/trangchu"); break;
        case 'Cán bộ Khoa/Bộ môn':
            header("Location: " . BASE_PATH . "/pages/canbo/trangchu"); break;
        case 'Giáo viên':
            header("Location: " . BASE_PATH . "/pages/giaovien/trangchu"); break;
        case 'Sinh viên':
            header("Location: " . BASE_PATH . "/pages/sinhvien/trangchu"); break;
        default:
            header("Location: " . BASE_PATH . "/login"); break;
    }
    exit;
}
