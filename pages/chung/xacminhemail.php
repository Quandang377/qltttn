<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác Minh Email</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<body>
   
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Xác Minh Email
                    </h1>
                </div>
                <div class="row">
                    <div class="col-lg-4 col-md-offset-4">
                <div class="panel panel-default">
                <div class="panel-body">
                    <div class="form-container">
                <form id="FormDangNhap" method="post">
                    
                        <div class="form-group">
                        <div class="row form-group">
                            <div class="col-lg-8">
                                <label >Email</label>
                            <input class="form-control"id="email"name="email" type="email"  ></div>
                            <div class="col-sm-2 text-center">
                                00:59
                            <button type="button" class="btn btn-primary btn-lg mt-3">Gửi OTP</button>
                        </div>
                        </div>
                        <div class="form-group">
                            <label >Nhập mã OTP</label>
                            <input class="form-control"id="OTP"name="OTP" type="text"  >
                        </div>
                        <div class="row">
                        <div class="col-md-offset text-center">
                            <button onclick="window.location='pages/chung/doimatkhau';" type="button" class="btn btn-primary btn-lg mt-3">Xác nhận</button>
                        </div>
                        </div>
                    </div>
                </form>
            </div>
                </div>
                </div>
                
        
