<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang Chủ</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<body>
    <div id="wrapper">
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_GiaoVien.php";
    ?>
<div id="page-wrapper">
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Quy Trình Thực Tập Tốt Nghiệp</h1>
        </div>
    </div>
<div class="row panel-row">
    <div class="col-md-3 panel-container">
        <a href="pages/giaovien/danhsachcongty" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default"style="min-height: 170px;">
          <div class="panel-heading">Tìm công ty thực tập</div>
          <div class="panel-body">
            <p>&bull; Xem danh sách công ty từ các khóa trước</p>
            <p>&bull; Tìm trên các trang web</p>
          </div>
        </div>
      </a>
      </div>
      <div class="col-md-3 panel-container">
        <a href="pages/giaovien/quanlygiaygioithieu" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default"style="min-height: 170px;">
          <div class="panel-heading">Xin giấy giới thiệu thực tập</div>
          <div class="panel-body">
            <p>&bull; Gửi thông tin đăng ký xin giấy giới thiệu thực tập</p>
          </div>
        </div>
      </a>
      </div>
      <div class="col-md-3 panel-container">
        <a href="pages/giaovien/xembaocaosinhvien" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default"style="min-height: 170px;">
          <div class="panel-heading">Thực tập, báo cáo tuần</div>
          <div class="panel-body">
            <p>&bull; Bắt dầu thực tập, gửi báo cáo hằng tuần cho giáo viên hướng dẫn</p>
          </div>
        </div>
      </a>
      </div>
      <!-- Nút -->
       <div class="col-md-3 ">
<a href="" data-toggle="modal" data-target="#detailModal" style="text-decoration: none; color: inherit;">
  <div class="panel panel-default"style="min-height: 170px;">
    <div class="panel-heading">Chấm điểm kết thúc</div>
    <div class="panel-body">
        <p>&bull; Phiếu chấm điểm...</p>
        <p>&bull; Nhận xét thực tập...</p>
        <p>&bull; Quyển báo cáo...</p>
    </div>
  </div>
</a>
</div>
<!-- Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Chấm điểm kết thúc</h4>
      </div>
      <div class="modal-body">
        <ul>
          <li>Phiếu chấm điểm thực tập tốt nghiệp (có điểm và chữ ký của Cán bộ hướng dẫn của công ty, kèm mộc)</li>
          <li>Phiếu khảo sát thực tập</li>
          <li>Nhận xét thực tập (đính kèm trong báo cáo, kèm mộc)</li>
          <li>Quyển báo cáo theo quy định</li>
        </ul>
      </div>
      <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>
</div>
 <div class="row">
            <h1>Thông Báo</h1>
    </div>
<div class="row Notification">
    <div class="col-md-9">
        <a href="pages/giaovien/chitietthongbao" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default">
          <div class="panel-heading">Thông báo số 1</div>
          <div class="panel-body">
            <p>Nội dung thông báo số 1.</p>
          </div>
        </div>
      </a>
      <a href="pages/giaovien/chitietthongbao" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default">
          <div class="panel-heading">Thông báo số 1</div>
          <div class="panel-body">
            <p>Nội dung thông báo số 1.</p>
          </div>
        </div>
      </a>
      <a href="pages/giaovien/chitietthongbao" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default">
          <div class="panel-heading">Thông báo số 1</div>
          <div class="panel-body">
            <p>Nội dung thông báo số 1.</p>
          </div>
        </div>
      </a>
      <a href="pages/giaovien/chitietthongbao" style="text-decoration: none; color: inherit;">
        <div class="panel panel-default">
          <div class="panel-heading">Thông báo số 1</div>
          <div class="panel-body">
            <p>Nội dung thông báo số 1.</p>
          </div>
        </div>
      </a>
      </div>
    </div>

<style>
.panel-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  margin-bottom: 30px;
}

.panel-container {
  position: relative;
}

.panel-container:not(:last-child)::after {
  content: "";
  position: absolute;
  top: 50%;
  right: -15px; /* khoảng cách giữa các panel */
  width: 30px;
  height: 2px;
  background-color:rgb(0, 0, 0); /* màu line */
  z-index: 1;
}
</style>
