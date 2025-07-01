<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_login.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id_taikhoan = $_SESSION['user_id'] ?? null;

$stmt = $conn->prepare("SELECT ID, TenDot FROM DotThucTap WHERE TrangThai >= 0 ORDER BY ID DESC");
$stmt->execute();
$dsDot = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tieude = $_POST['tieude'] ?? '';
  $noidung = $_POST['noidung'] ?? '';
  $id_dot = $_POST['id_dot'] ?? null;

  // Kiểm tra id_dot có hợp lệ không
  $validDot = false;
  foreach ($dsDot as $dot) {
    if ($dot['ID'] == $id_dot) {
      $validDot = true;
      break;
    }
  }
  if (!$validDot) {
    die("Đợt thực tập không hợp lệ!");
  }

  $stmt = $conn->prepare("INSERT INTO THONGBAO (TIEUDE, NOIDUNG, NGAYDANG, TRANGTHAI, ID_Dot, ID_TaiKhoan) VALUES (:tieude, :noidung, NOW(),1, :id_dot, :id_taikhoan)");
  $stmt->execute([
    'tieude' => $tieude,
    'noidung' => $noidung,
    'id_dot' => $id_dot,
    'id_taikhoan' => $id_taikhoan,
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
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php";
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
                    <label>Chọn đợt thực tập</label>
                    <select class="form-control" name="id_dot" required>
                      <option value="">-- Chọn đợt --</option>
                      <?php foreach ($dsDot as $dot): ?>
                        <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
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

      </div>
    </div>
  </div>
  <?php
  require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
    ?>
</body>

</html>