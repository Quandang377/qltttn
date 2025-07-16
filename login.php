<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

// Định nghĩa BASE_PATH để sử dụng trong form
if (!function_exists('isLocalhost')) {
    function isLocalhost() {
        return in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || 
               strpos($_SERVER['HTTP_HOST'], '.local') !== false;
    }
}

if (isLocalhost()) {
    define('BASE_PATH', '/datn');
} else {
    define('BASE_PATH', '/datn'); // Hoặc '' nếu đặt ở root hosting
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT ID_TaiKhoan,TaiKhoan,MatKhau,VaiTro,TrangThai FROM taikhoan WHERE TaiKhoan = ? AND TrangThai = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && ($password === $user['MatKhau'] || password_verify($password, $user['MatKhau']))) {
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['ID_TaiKhoan'];
            $_SESSION['user_role'] = $user['VaiTro'];
            $_SESSION['user_email'] = $user['TaiKhoan'];
            if (!empty($_POST['remember'])) {
                $token = bin2hex(random_bytes(32)); // tạo chuỗi ngẫu nhiên
                setcookie('remember_token', $token, time() + (86400 * 30), "/"); // 30 ngày

                // lưu vào DB
                $stmt = $conn->prepare("UPDATE taikhoan SET remember_token = ? WHERE ID_TaiKhoan = ?");
                $stmt->execute([$token, $user['ID_TaiKhoan']]);
            }
            
            // Redirect về trang được yêu cầu trước khi đăng nhập hoặc trang chủ tương ứng với vai trò
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $redirect_url);
            } else {
                // Redirect theo vai trò
                switch ($user['VaiTro']) {
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
                        header("Location: " . BASE_PATH . "/");
                }
            }
            exit;
        } else {
            $error = "Tài khoản hoặc mật khẩu không đúng hoặc đã bị khóa.";
        }
    } catch (Exception $e) {
        $error = "Lỗi hệ thống: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng nhập hệ thống</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        body {
            background-color: #f7f7f7;
        }

        .login-container {
            margin-top: 80px;
        }

        .login-box {
            background: #fff;
            border: 1px solid #ddd;
            padding: 30px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .login-box h3 {
            margin-bottom: 25px;
            text-align: center;
        }

        .form-control {
            height: 40px;
        }

        .btn-login {
            width: 100%;
        }

        .footer-text {
            margin-top: 15px;
            font-size: 13px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container login-container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-box">
                    <h3>Đăng nhập hệ thống</h3>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="email">Tài khoản</label>
                            <input type="text" name="email" class="form-control" placeholder="Nhập tài khoản" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Mật khẩu</label>
                            <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu"
                                required>
                        </div>
                        <div>
                            <a href="<?= BASE_PATH ?>/quenmatkhau">Quên mật khẩu?</a>
                        </div>
                        <button type="submit" class="btn btn-primary btn-login">Đăng nhập</button>
                        <input type="checkbox" name="remember" id="remember"> <label for="remember">Ghi nhớ đăng nhập</label>
                    </form>
                    <div class="footer-text">
                        © 2025- Hệ thống quản lý thực tập
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>