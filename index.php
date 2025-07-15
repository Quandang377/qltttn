<?php
// Kiểm tra đường dẫn config
$configPath = $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
if (!file_exists($configPath)) {
    die("Config file not found: " . $configPath);
}

require_once $configPath;

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình BASE_PATH tự động
if (!function_exists('isLocalhost')) {
    function isLocalhost() {
        return in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || 
               strpos($_SERVER['HTTP_HOST'], '.local') !== false;
    }
}

if (isLocalhost()) {
    define('BASE_PATH', '/datn');
} else {
    // Trên hosting, có thể là root hoặc subfolder
    define('BASE_PATH', '/datn'); // Hoặc '' nếu đặt ở root
}

$URL = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
session_start();
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("SELECT * FROM taikhoan WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user'] = $user;
    } else {
        // Token sai → xóa cookie
        setcookie('remember_token', '', time() - 3600, "/");
    }
}


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
    header("Location: " . BASE_PATH . "/");
    exit;
}

if ($splitURL[0] === 'login') {
    require_once 'login.php';
    exit;
}

// ==== Cho phép truy cập trang chủ sinh viên nếu chưa đăng nhập ==== //
$publicPages = [
    'pages/sinhvien/trangchu',
    'pages/sinhvien/tainguyen',
    'pages/sinhvien/chitietthongbao',
    "pages/sinhvien/xemdanhsachcongty",
    "pages/sinhvien/timkiem",
];

$allowPublicAccess = in_array($URL, $publicPages);

// ==== Nếu chưa đăng nhập và không phải trang public thì chuyển login ==== //
if (!isset($_SESSION['user']) && $URL !== '' && !in_array($URL, $publicPages)) {
    if ($URL !== '/login') {
        header("Location: " . BASE_PATH . "/");
        exit;
    }
} 

// ==== Nếu truy cập root (/) thì chuyển theo vai trò ==== //
if ($URL === '') {
    if (!isset($_SESSION['user'])) {
        // Nếu chưa login, chuyển tới trang chủ sinh viên
        header("Location: " . BASE_PATH . "/pages/sinhvien/trangchu");
    } else {
        // Đã login thì điều hướng theo vai trò
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
?>