<?php
if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}
$vaiTro = $_SESSION['user']['VaiTro'];

$uri = $_SERVER['REQUEST_URI'];
$phanQuyen = [
    'Admin' => '/datn/admin/pages',
    'Cán bộ Khoa/Bộ môn' => '/datn/pages/canbo',
    'Giáo viên' => '/datn/pages/giaovien',
    'Sinh viên' => '/datn/pages/sinhvien'
];
$hopLe = false;
foreach ($phanQuyen as $vai => $duongDan) {
    if ($vai === $vaiTro && strpos($uri, $duongDan) === 0) {
        $hopLe = true;
        break;
    }
}

if (!$hopLe) {
    switch ($vaiTro) {
        case 'Admin':
            header("Location:". BASE_PATH ."/admin/pages/trangchu");
            break;
        case 'Cán bộ Khoa/Bộ môn':
            header("Location: ". BASE_PATH ."/pages/canbo/trangchu");
            break;
        case 'Giáo viên':
            header("Location: " . BASE_PATH ."/pages/giaovien/trangchu");
            break;
        case 'Sinh viên':
            header("Location: " . BASE_PATH . "/pages/sinhvien/trangchu");
            break;
        default:
            header("Location: " . BASE_PATH . "/login");
            break;
    }

}
