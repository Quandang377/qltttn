<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<body>
   
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Đăng Nhập
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
                            <label >Email</label>
                            <input class="form-control"id="taikhoan"name="taikhoan" type="text" >
                        </div>
                        <div class="form-group">
                            <label >Mật khẩu</label>
                            <input class="form-control"id="matkhau"name="matkhau" type="passsword">
                            <a href="pages/chung/xacminhemail">Quên mật khẩu?</a>
                        </div>
                        <div class="row">
                        <div class="col-md-offset text-center">
                            <button onclick=""   type="button" class="btn btn-primary btn-lg mt-3">Đăng nhập</button>
                        </div>
                        </div>
                    </div>
                </form>
            </div>
                </div>
                </div>
                
        
