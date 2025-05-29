<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>CĐTH21K1</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/qltttn/template/head.php";
    ?>
</head>
<body>
    <div id="wrapper">
        
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/qltttn/template/slidebar_CanBo.php";
    ?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row mt-5">
            <div class="col-lg-12">
                <h1 class="page-header">CĐTH21K1</h1>
            </div>
            </div>
           <div class="panel panel-default">
            <div class="panel-body">
                <div class="row">
                <div class="col-md-6">
                    <h2>Ngành: Công nghệ thông tin</h2>
                    <h2>Loại: Cao Đẳng</h2>
                    <h2>Trạng thái: Đang mở</h2>
                </div>
                <div class="col-md-6">
                    <h2>Năm: 2025</h2>
                    <h2>Người quản lý: Lữ Cao Tiến</h2>
                    <h2>Người mở đợt: Lữ Cao Tiến</h2>
                </div>
                </div>
         <div class="row">
                <button onclick="window.location='pages/canbo/phanconghuongdan';"type="button" class="btn btn-primary btn-lg ">Phân công hướng dẫn</button>
                <button type="button" class="btn btn-warning btn-lg"style="min-width: 120px;">Chỉnh sửa</button>
                <button type="button" class="btn btn-lg"style="min-width: 120px;">Xóa</button>
         </div>
         </div>
         </div>
        <div id="containerDotThucTap" class="mt-3">
            <h2>Danh sách các đợt thực tập</h2>
            <div id="listDotThucTap" class="row">
        </div>
        <div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                </div>
                                <!-- /.panel-heading -->
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Tên đợt</th>
                                                    <th>Năm</th>
                                                    <th>Ngành</th>
                                                    <th>Người quản lý</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr onclick="window.location='pages/canbo/chitietdot'" style="cursor: pointer;">
                                                    <td>1</td>
                                                    <td>CĐTH21K1</td>
                                                    <td>2021</td>
                                                    <td>Công nghệ thông tin</td>
                                                    <td>Lữ Cao Tiến</td>
                                                    
                                                </tr>
                                                <tr onclick="window.location='pages/canbo/chitietdot'" style="cursor: pointer;">
                                                    <td>2</td>
                                                    <td>CĐTH21K1</td>
                                                    <td>2021</td>
                                                    <td>Công nghệ thông tin</td>
                                                    <td>Lữ Cao Tiến</td>
                                                </tr>
                                                <tr onclick="window.location='pages/canbo/chitietdot'" style="cursor: pointer;">
                                                    <td>3</td>
                                                    <td>CĐTH21K1</td>
                                                    <td>2021</td>
                                                    <td>Công nghệ thông tin</td>
                                                    <td>Lữ Cao Tiến</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- /.table-responsive -->
                                </div>
                                <!-- /.panel-body -->
                            </div>
                            <!-- /.panel -->
                        </div>
                        <!-- /.col-lg-6 -->
                    </div>
    </div>