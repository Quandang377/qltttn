<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng thông báo</title>
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
                <h1 class="page-header">Đăng thông báo</h1>
            </div>
            <!-- /.col-lg-12 -->
            <div class="container">
  <div class="row">
    <div class="col-md-12 ">
      <div class="form-container" style="margin-top: 20px;">
        <form id="intershipForm" method="post">
          
          <div class="form-group">
            <label>Tiêu đề</label>
            <input class="form-control" id="tieude" name="tieude" type="text" placeholder="Nhập tiêu đề">
          </div>

          <div class="form-group">
            <label>Tệp đính kèm</label>
            <input class="form-control" id="tep" name="tep" type="file" placeholder="Chọn tệp tải lên">
          </div>

          <div class="form-group">
            <label>Nội dung thông báo</label>
            <textarea class="form-control" id="noidung" name="noidung" type="text" placeholder="Nhập nội dung"></textarea>
          </div>
             <div class="form-group text-center">
          <a href="pages/canbo/quanlythongbao" class="btn btn-primary btn-lg mt-3">Đăng tải</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</div>
</div>