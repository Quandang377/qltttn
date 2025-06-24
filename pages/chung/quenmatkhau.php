<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="page-header text-center">
            <h1 style="text-align:center;">Quên mật khẩu</h1>
        </div>
        <div class="row">
            <div class="col-lg-4 col-md-offset-4" style="float:none; margin:0 auto;">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="taikhoan">Nhập email tài khoản</label>
                                <input class="form-control" id="taikhoan" name="taikhoan" type="text" required>
                            </div>
                            <div class="text-center">
                                <button type="submit" name="submit" class="btn btn-primary">Gửi yêu cầu</button>
                            </div>
                        </form>
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['taikhoan'])) {
                            $taikhoan = trim($_POST['taikhoan']);
                            // Kiểm tra định dạng email
                            if (!filter_var($taikhoan, FILTER_VALIDATE_EMAIL)) {
                                echo '<div class="alert alert-danger" style="margin-top:10px;">Vui lòng nhập đúng định dạng email!</div>';
                            } else {
                                require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
                                $stmt = $conn->prepare("SELECT * FROM taikhoan WHERE TaiKhoan = ?");
                                $stmt->execute([$taikhoan]);
                                if ($stmt->fetch()) {
                                    echo '<div class="alert alert-success" style="margin-top:10px;">Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi đến email của bạn.</div>';
                                    // Ở đây bạn có thể gửi email thực tế hoặc tạo mã xác nhận, v.v.
                                } else {
                                    echo '<div class="alert alert-danger" style="margin-top:10px;">Email không tồn tại trong hệ thống!</div>';
                                }
                            }
                        }
                        ?>
                        <div style="margin-top:15px;">
                            <a href="/datn/pages/chung/dangnhap.php">Quay lại đăng nhập</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>