<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$idKhaoSat = $_GET['id'] ?? null;

if (!$idKhaoSat) {
  die("Không tìm thấy khảo sát.");
}

// Lấy thông tin khảo sát
$stmt = $conn->prepare("SELECT * FROM KhaoSat WHERE ID = ?");
$stmt->execute([$idKhaoSat]);
$khaoSat = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách câu hỏi
$stmt = $conn->prepare("SELECT * FROM CauHoiKhaoSat WHERE ID_KhaoSat = ?");
$stmt->execute([$idKhaoSat]);
$cauHoi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách phản hồi
$stmt = $conn->prepare("
    SELECT phks.ID, phks.ID_TaiKhoan, phks.ThoiGianTraLoi, 
        COALESCE(sv.Ten, gv.Ten, tk.TaiKhoan) AS TenNguoiPhanHoi
    FROM PhanHoiKhaoSat phks
    JOIN TaiKhoan tk ON tk.ID_TaiKhoan = phks.ID_TaiKhoan
    LEFT JOIN SinhVien sv ON sv.ID_TaiKhoan = tk.ID_TaiKhoan
    LEFT JOIN GiaoVien gv ON gv.ID_TaiKhoan = tk.ID_TaiKhoan
    WHERE phks.ID_KhaoSat = ?
");
$stmt->execute([$idKhaoSat]);
$phanHoi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy câu trả lời của từng phản hồi
$cauTraLoiTheoPhanHoi = [];
foreach ($phanHoi as $ph) {
  $stmt = $conn->prepare("
        SELECT ch.NoiDung, ctl.TraLoi
        FROM CauTraLoi ctl
        JOIN CauHoiKhaoSat ch ON ch.ID = ctl.ID_CauHoi
        WHERE ctl.ID_PhanHoi = ?
    ");
  $stmt->execute([$ph['ID']]);
  $cauTraLoiTheoPhanHoi[$ph['ID']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Xem phản hồi</title>
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
        <div class="page-header">
          <h1>
            <?= htmlspecialchars($khaoSat['TieuDe']) ?>
          </h1>
          <h4>
            <?= htmlspecialchars($khaoSat['MoTa']) ?>
          </h4>

        </div>
        <div id="containerKhaoSat" class="mt-3">
          <div id="listKhaoSat" class="row">
          </div>
          <div class="row">
            <div class="col-lg-12">
              <div class="panel panel-default">
                <div class="panel-heading">
                  Danh sách người phản hồi
                </div>
                <div class="panel-body">
                  <div class="table-responsive">
                    <table class="table" id="bangphanhoi">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Người phản hồi</th>
                          <th>Thời gian phản hồi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($phanHoi as $index => $ph): ?>
                          <tr onclick="hienPhanHoi(<?= $ph['ID'] ?>)" style="cursor: pointer;">
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($ph['TenNguoiPhanHoi']) ?></td>
                            <td><?= date("H:i d/m/Y", strtotime($ph['ThoiGianTraLoi'])) ?></td>
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
          <?php foreach ($phanHoi as $ph): ?>
            <div class="modal fade" id="modalPhanHoi<?= $ph['ID'] ?>" tabindex="-1" role="dialog"
              aria-labelledby="modalPhanHoiLabel<?= $ph['ID'] ?>" data-backdrop="static" data-keyboard="false">
              <div class="modal-dialog" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                      aria-label="Đóng"><span>&times;</span></button>
                    <h4 class="modal-title" id="modalPhanHoiLabel<?= $ph['ID'] ?>">
                      <?= htmlspecialchars($ph['TenNguoiPhanHoi']) ?>
                    </h4>
                  </div>
                  <div class="modal-body">
                    <?php foreach ($cauTraLoiTheoPhanHoi[$ph['ID']] as $index => $ctl): ?>
                      <div class="form-group">
                        <label>Câu <?= $index + 1 ?>: <?= htmlspecialchars($ctl['NoiDung']) ?></label>
                        <?php if ($ctl['Loai'] === 'multiple'): ?>
                          <?php foreach (explode(';', $ctl['TraLoi']) as $item): ?>
                            <span class="label label-info"
                              style="margin-right: 6px;"><?= htmlspecialchars(trim($item)) ?></span>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <input type="text" class="form-control" value="<?= htmlspecialchars($ctl['TraLoi']) ?>" readonly>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Đóng</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php
        require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
          ?>
        <script>
          function hienPhanHoi(idPhanHoi) {
            $('#modalPhanHoi' + idPhanHoi).modal('show');
          }
          $(document).ready(function () {
            $('#bangphanhoi').DataTable({
              searching: false, info: false,
              lengthChange: false
            });
          });
        </script>
      </div>
    </div>
  </div>
</body>

</html>