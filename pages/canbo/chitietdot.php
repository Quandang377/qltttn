<?php
$successMessage = "";
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']); 
}
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Không tìm thấy ID đợt thực tập.");
}

$stmt = $conn->prepare("SELECT ID,TenDot,Loai,Nam,TenNguoiMoDot,NguoiQuanLy,ThoiGianKetThuc,TrangThai FROM DOTTHUCTAP WHERE ID = :id");
$stmt->execute(['id' => $id]);
$dot = $stmt->fetch();

if (!$dot) {
    die("Không tìm thấy đợt thực tập.");
}
function getSinhVienTrongDot($pdo, $idDot) {
    $stmt = $pdo->prepare("
        SELECT SV.MSSV,
        SV.Ten as TenSinhVien, 
        SV.Lop, 
        GV.Ten as TenGV
        FROM sinhvien SV
        LEFT JOIN giaovien GV ON SV.ID_GVHD = GV.ID_TaiKhoan 
        WHERE SV.ID_Dot = :idDot
    ");
    $stmt->execute(['idDot' => $idDot]);
    return $stmt->fetchAll();
}
$danhSachSinhVien = getSinhVienTrongDot($conn, $id);
 ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($dot['TenDot']) ?></title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>
<body>
<div id="wrapper">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>
    <div id="page-wrapper">
        <div class="container-fluid">
            <div class="row mt-5">
                <div class="col-lg-12">
                    <h1 class="page-header"><?= htmlspecialchars($dot['TenDot']) ?></h1>
                </div>
            </div>
                <?php if (!empty($successMessage)): ?>
                <div id="successAlert" class="alert alert-success">
                    <?= $successMessage ?>
                </div>
                <?php endif; ?>
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h2>Loại: <?= htmlspecialchars($dot['Loai']) ?></h2>
                            <h2>Thời gian kết thúc: <?= htmlspecialchars($dot['ThoiGianKetThuc']) ?></h2>
                            <h2>Trạng thái: <?= $dot['TrangThai'] == 1 ? 'Đang mở' : 'Đã đóng' ?></h2>
                        </div>
                        <div class="col-md-6">
                            <h2>Năm: <?= htmlspecialchars($dot['Nam']) ?></h2>
                            <h2>Người quản lý: <?= htmlspecialchars($dot['NguoiQuanLy']) ?></h2>
                            <h2>Người mở đợt: <?= htmlspecialchars($dot['TenNguoiMoDot']) ?></h2>
                        </div>
                    </div>
                    <div class="row">
                        <?php if ($dot['TrangThai'] == 1): ?>
                            <button onclick="window.location='pages/canbo/phanconghuongdan?id=<?= $id ?>';" class="btn btn-primary btn-lg">Phân công</button>
                            <?php else: ?>
                                <button class="btn btn-primary btn-lg" disabled>Phân công</button>
                            <?php endif; ?>
                        <?php if ($dot['TrangThai'] == 1): ?>
                            <button onclick="window.location='pages/canbo/importexcel?id=<?= $id ?>';" class="btn btn-primary btn-lg">Import</button>
                            <?php else: ?>
                                <button class="btn btn-primary btn-lg" disabled>Import</button>
                            <?php endif; ?>
                        <?php if ($dot['TrangThai'] == 1): ?>
                            <button onclick="window.location='pages/canbo/chinhsuadot?id=<?= $id ?>';" class="btn btn-warning btn-lg" style="min-width: 120px;">Chỉnh sửa</button>
                            <?php else: ?>
                                <button class="btn btn-warning btn-lg" style="min-width: 120px;" disabled>Chỉnh sửa</button>
                            <?php endif; ?>
                       <?php if ($dot['TrangThai'] == 0): ?>
                            <button onclick="if(confirm('Bạn có chắc muốn xóa đợt này?')) window.location='pages/canbo/xoadot?id=<?= $id ?>';" class="btn btn-danger btn-lg" style="min-width: 120px;">Xóa</button>
                            <?php else: ?>
                                <button class="btn btn-danger btn-lg" style="min-width: 120px;" disabled>Xóa</button>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
            <div id="containerDotThucTap" >
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">Danh sách sinh viên thuộc đợt</div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table id="table-dssv" class="table">
                                        <thead>
                                            <tr>
                                                <th>MSSV</th>
                                                <th>Tên sinh viên</th>
                                                <th>Lớp</th>
                                                <th>Tên GVHD</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($danhSachSinhVien as $sv): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($sv['MSSV']) ?></td>
                                                    <td><?= htmlspecialchars($sv['TenSinhVien']) ?></td>
                                                    <td><?= htmlspecialchars($sv['Lop']) ?></td>
                                                    <td><?= htmlspecialchars($sv['TenGV'] ?? 'Chưa phân công') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

</body>
</html>
<script>
    $(document).ready(function () {
        var table = $('#table-dssv').DataTable({
            responsive: true,
            pageLength: 20,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
            }
        });

    });

    window.addEventListener('DOMContentLoaded', () => {
        const alertBox = document.getElementById('successAlert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }, 2000);
        }
    });
</script>