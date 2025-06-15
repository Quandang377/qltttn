<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tieude = $_POST['tieude'] ?? '';
  $noidung = $_POST['noidung'] ?? '';

  $stmt = $conn->prepare("INSERT INTO THONGBAO (TIEUDE, NOIDUNG, NGAYDANG, TRANGTHAI) VALUES (:tieude, :noidung, NOW(),1)");
  $stmt->execute([
    'tieude' => $tieude,
    'noidung' => $noidung,
  ]);

  header("Location: quanlythongbao");
  exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Đăng thông báo</title>
  <?php
  require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
  ?>
</head>

<body>
  <div id="wrapper">

    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
    ?>
    <div id="page-wrapper">
      <div class="container-fluid">
        <div class="row mt-5">
          <div class="col-lg-12">
            <h1 class="page-header">Đăng thông báo</h1>
          </div>
          <div class="row">
            <div class="col-md-12 ">
              <div class="form-container" style="margin-top: 20px;">
                <form id="FormThongBao" method="post" enctype="multipart/form-data">

                  <div class="form-group">
                    <label>Tiêu đề</label>
                    <input class="form-control" id="tieude" name="tieude" type="text" placeholder="Nhập tiêu đề"
                      required>
                  </div>
                  <div class="form-group">
                    <label>Nội dung thông báo</label>
                    <textarea class="form-control" id="noidung" name="noidung" required></textarea>
                    <script>
                      CKEDITOR.replace('noidung', {
                      });
                    </script>

                  </div>
                  <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary btn-lg mt-3">Đăng tải</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
