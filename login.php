<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT ID_TaiKhoan,TaiKhoan,MatKhau,VaiTro,TrangThai FROM TaiKhoan WHERE TaiKhoan = ? AND TrangThai = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['ID_TaiKhoan'];
        $_SESSION['user_role'] = $user['VaiTro'];
        $_SESSION['user_email'] = $user['TaiKhoan'];    
        header("Location: " . BASE_PATH . "/");
        exit;
    } else {
        $error = "Tài khoản hoặc mật khẩu không đúng hoặc đã bị khóa.";
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