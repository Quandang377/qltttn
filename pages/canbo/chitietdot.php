<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/includes/database.php"; 
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/includes/funtions.php"; 

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Không tìm thấy ID đợt thực tập.");
}

$stmt = $pdo->prepare("SELECT ID,TenDot,Loai,Nganh,Nam,TenNguoiMoDot,NguoiQuanLy,ThoiGianKetThuc,TrangThai FROM DOTTHUCTAP WHERE ID = :id");
$stmt->execute(['id' => $id]);
$dot = $stmt->fetch();

if (!$dot) {
    die("Không tìm thấy đợt thực tập.");
}
$danhSachDotThucTap = getAllInternships($pdo);
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

            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h2>Ngành: <?= htmlspecialchars($dot['Nganh']) ?></h2>
                            <h2>Loại: <?= htmlspecialchars($dot['Loai']) ?></h2>
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
            <h2>Danh sách các đợt thực tập</h2>
            <div id="listDotThucTap" class="row">
        </div>
        <div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                </div>
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
                                            <?php $i = 1; foreach ($danhSachDotThucTap as $dot): ?>
                                                <?php $link = 'pages/canbo/chitietdot?id=' . urlencode($dot['ID']); ?>
                                                <tr onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                                                    <td><?= $i++ ?></td>
                                                    <td><?= htmlspecialchars($dot['TenDot']) ?></td>
                                                    <td><?= htmlspecialchars($dot['Nam']) ?></td>
                                                    <td><?= htmlspecialchars($dot['Nganh']) ?></td>
                                                    <td><?= htmlspecialchars($dot['NguoiQuanLy']) ?></td>
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
        </div>
    </div>
</div>

</body>
</html>