<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đổi mật khẩu</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>

<body>

    <div class="container-fluid">
        <div class="page-header">
            <h1>
                Đổi Mật Khẩu
            </h1>
        </div>
        <div class="row">
            <div class="col-lg-4 col-md-offset-4">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-container">
                            <form id="FormDangNhap" method="post">

                                <div class="form-group">
                                    <div class="form-group">
                                        <label>Nhập mật khẩu mới</label>
                                        <input class="form-control" id="passsword" name="passsword" type="passsword">
                                    </div>
                                    <div class="form-group">
                                        <label>Xác nhận mật khẩu</label>
                                        <input class="form-control" id="passsword" name="passsword" type="passsword">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-offset text-center">
                                            <button onclick="window.location='pages/chung/dangnhap';" type="button"
                                                class="btn btn-primary btn-lg mt-3">Xác nhận</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>