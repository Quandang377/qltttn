<?php
session_start();
define('BASE_PATH', '/datn');

$URL = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($URL, BASE_PATH) === 0) {
    $URL = substr($URL, strlen(BASE_PATH));
}
$URL = trim($URL, '/');
$splitURL = explode('/', $URL);
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

if (!isset($_SESSION['user'])) {
    header("Location: " . BASE_PATH . "/login");
    exit;
}

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
            echo "Không xác định được vai trò người dùng.";
    }
    exit;
}

$area = ['admin', 'api'];
if (in_array($splitURL[0], $area)) {
    $target = $splitURL[0] . '/index.php';
    if (file_exists($target)) {
        require_once $target;
    } else {
        require_once '404.php';
    }
    exit;
}

// pages/...
if ($splitURL[0] === 'pages') {
    $rolePath = $splitURL[1] ?? '';
    $page = $splitURL[2] ?? 'trangchu';

    $path = "pages/$rolePath/$page";
    if (!str_ends_with($path, '.php')) {
        $path .= '.php';
    }

    if (file_exists($path)) {
        require_once $path;
    } else {
        require_once '404.php';
    }
    exit;
}

require_once '404.php';
?>
