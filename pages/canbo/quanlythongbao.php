<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$idTaiKhoan = $_SESSION['user_id'] ?? null;

// Lấy danh sách đợt mà cán bộ này quản lý
$stmt = $conn->prepare("SELECT ID, TenDot FROM DotThucTap WHERE NguoiQuanLy = ?");
$stmt->execute([$idTaiKhoan]);
$dsDot = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách ID đợt
$dsDotID = array_column($dsDot, 'ID');

$thongbaos = [];

if (!empty($dsDotID)) {
  // Nếu lọc theo đợt cụ thể
  if (!empty($_GET['dot_filter'])) {
    $placeholders = '?';
    $params = [$_GET['dot_filter']];
  } else {
    // Nếu không, lấy theo tất cả các đợt quản lý
    $placeholders = implode(',', array_fill(0, count($dsDotID), '?'));
    $params = $dsDotID;
  }

  $stmt = $conn->prepare("
        SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.NGAYDANG, tb.ID_Dot,
            COALESCE(ad.Ten, cbk.Ten, tk.TaiKhoan) AS NguoiTao,
            dt.TenDot
        FROM THONGBAO tb
        LEFT JOIN admin ad ON tb.ID_TaiKhoan = ad.ID_TaiKhoan
        LEFT JOIN canbokhoa cbk ON tb.ID_TaiKhoan = cbk.ID_TaiKhoan
        LEFT JOIN TaiKhoan tk ON tb.ID_TaiKhoan = tk.ID_TaiKhoan
        LEFT JOIN DotThucTap dt ON tb.ID_Dot = dt.ID
        WHERE tb.TRANGTHAI = 1 AND tb.ID_Dot IN ($placeholders)
        ORDER BY tb.NGAYDANG DESC
    ");
  $stmt->execute($params);
  $thongbaos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Xử lý xóa thông báo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_thongbao_id'])) {
  $idThongBao = $_POST['xoa_thongbao_id'];
  $stmt = $conn->prepare("UPDATE ThongBao SET TrangThai = 0 WHERE ID = ?");
  $stmt->execute([$idThongBao]);
  $_SESSION['success'] = "Xoá thông báo thành công.";
  header("Location: " . $_SERVER['REQUEST_URI']);
  exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Quản lý thông báo</title>
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
        <div class="row">
          <div class="col-lg-12">
            <h1 class="page-header">Quản Lý Thông Báo</h1>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-12">
            <form method="get" class="form-inline" style="margin-bottom: 15px;">
              <label for="dot_filter">Lọc theo đợt:</label>
              <select name="dot_filter" id="dot_filter" class="form-control" onchange="this.form.submit()">
                <option value="">-- Tất cả --</option>
                <?php foreach ($dsDot as $dot): ?>
                  <option value="<?= $dot['ID'] ?>" <?= ($_GET['dot_filter'] ?? '') == $dot['ID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dot['TenDot']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>

            <div class="panel panel-default">
              <div class="panel-heading">
                Danh sách thông báo đã tạo
              </div>
              <div class="panel-body">
                <div class="table-responsive">
                  <table class="table" id="TableDotTT">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Tiêu đề</th>
                        <th>Đợt</th>
                        <th>Người đăng</th>
                        <th>Thời gian đăng</th>
                        <th>Hành động</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $i = 1;
                      foreach ($thongbaos as $thongbao): ?>
                        <?php $link = 'pages/canbo/chitietthongbao?id=' . urlencode($thongbao['ID']); ?>
                        <tr>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;"><?= $i++ ?></td>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                            <?= htmlspecialchars($thongbao['TIEUDE']) ?>
                          </td>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                            <?= htmlspecialchars($thongbao['TenDot'] ?? '') ?>
                          </td>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                          <?= htmlspecialchars($thongbao['NguoiTao']) ?>

                          </td>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                            <?= htmlspecialchars($thongbao['NGAYDANG']) ?>
                          </td>
                          <td>
                            <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thông báo này?');">
                              <input type="hidden" name="xoa_thongbao_id" value="<?= $thongbao['ID'] ?>">
                              <button type="submit" class="btn btn-danger btn-sm">Xoá</button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
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
        <div style="position: fixed; bottom: 5%; right: 5%;z-index: 999;">
          <a href="pages/canbo/taothongbao" class="fixed-button btn btn-primary btn-lg">
            Tạo thông báo
          </a>
        </div>
      </div>

    </div>
  </div>
  <?php
  require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
    ?>
  <script>
    $(document).ready(function () {
      var table = $('#TableDotTT').DataTable({
        responsive: true,
        pageLength: 20,
        language: {
          url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
        }
      });

    });
  </script>
</body>

</html>
<style>
  .noidung-rutgon {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
  }

  .ls-list {
    align-items: flex-start;
    margin-bottom: 20px;
  }

  .ls-img img {
    border-radius: 4px;
    border: 1px solid #ddd;
  }

  .img-content a:hover {
    color: #007bff;
  }
</style>