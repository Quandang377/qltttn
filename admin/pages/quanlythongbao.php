<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$order = "DESC";
if (isset($_GET['loc'])) {
  if ($_GET['loc'] === 'old')
    $order = "ASC";
}

$stmt = $conn->prepare("SELECT ID, TIEUDE, NOIDUNG, NGAYDANG FROM THONGBAO WHERE TRANGTHAI=1 ORDER BY NGAYDANG $order");
$stmt->execute();
$thongbaos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php";
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
            <div class="panel panel-default">
              <div class="panel-heading">
                Danh sách thông báo
              </div>
              <div class="panel-body">
                <div class="table-responsive">
                  <table class="table" id="TableDotTT">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Tiêu đề</th>
                        <th>Thời gian đăng</th>
                        <th>Hành động</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $i = 1;
                      foreach ($thongbaos as $thongbao): ?>
                        <?php $link = 'admin/pages/chitietthongbao?id='. urlencode($thongbao['ID']); ?>
                        <tr>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;"><?= $i++ ?></td>
                          <td onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                            <?= htmlspecialchars($thongbao['TIEUDE']) ?>
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
        <div style="position: fixed; bottom: 5%; right: 5%;">
          <a href="admin/pages/taothongbao" class="fixed-button btn btn-primary btn-lg">
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