<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập hệ thống</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>    
    <div class="container" style="max-width: 500px; margin-top: 40px;">
    <div class="panel panel-default text-center">
        <div class="panel-body">
            <!-- Avatar -->
            <img src="/datn/access/img/accc.PNG" class="img-circle" alt="Avatar" width="80" height="80">

            <!-- Tên và email -->
            <h4 class="text-bold" style="margin-top: 10px;">Đặng Minh Quân</h4>
            <p class="text-muted">quan@caothang.edu.vn</p>

            <!-- Đợt thực tập -->
            <div class="text-left" style="margin-top: 30px;">
                <strong>Đợt thực tập</strong>
                <p>CDTH21-1</p>

                <strong>Giáo viên hướng dẫn</strong>
                <p>Lữ Cao Tiến<br>lctien@caothang.edu.vn</p>
            </div>

            <!-- Các nút chức năng -->
            <div class="btn-group-vertical" style="width: 100%; margin-top: 30px;">
                <a href="doimatkhau" class="btn btn-default">
                    <span class="glyphicon glyphicon-lock"></span> Đổi mật khẩu
                </a>
                <a href="/datn/logout" class="btn btn-default">
                    <span class="glyphicon glyphicon-log-out"></span> Đăng xuất
                </a>
            </div>
        </div>
    </div>
</div>
</html>