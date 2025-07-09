<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/middleware/check_role.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$vaiTro = $_SESSION['user']['VaiTro'] ?? '';

// Lấy thông tin người dùng
$stmt = $conn->prepare("
    SELECT 
        COALESCE(gv.Ten) AS Ten,
        tk.TaiKhoan AS Email,
        tk.VaiTro
    FROM TaiKhoan tk
    LEFT JOIN CanBoKhoa gv ON tk.ID_TaiKhoan = gv.ID_TaiKhoan
    WHERE tk.ID_TaiKhoan = ?
");

$stmt->execute([$idTaiKhoan]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
$stmtDot = $conn->prepare("
    SELECT dt.ID, dt.TenDot, dt.Nam, dt.ThoiGianBatDau, dt.ThoiGianKetThuc
    FROM dotthuctap dt
    WHERE dt.NguoiQuanLy = ? AND dt.TrangThai != 0 AND dt.TrangThai != -1
    ORDER BY dt.ThoiGianBatDau DESC
");
$stmtDot->execute([$idTaiKhoan]); // Dùng ID để so sánh đúng
$dsDotDangThamGia = $stmtDot->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thông tin cá nhân</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>

    </style>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>
        <div id="page-wrapper">
            <div id="pages-heading"><H1>Thông tin cá nhân</H1> </div>

            <div class="container-fluid"></div>
            <div class="row" style="margin-top: 40px;">
                <div class="col-md-4 text-center">
                    <img src="/datn/access/img/accc.PNG" class="img-circle"
                        style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #eee;">
                    <h3 style="margin-top: 10px;"><?= htmlspecialchars($info['Ten'] ?? 'Chưa cập nhật') ?></h3>
                    <p class="text-muted"><?= htmlspecialchars($info['Email'] ?? '') ?></p>

                </div>

                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Thông tin cá nhân</strong></div>
                        <div class="panel-body">
                            <div class="row" style="margin-bottom: 15px;">
                                <div class="col-sm-6">
                                    <label>Họ và tên:</label>
                                    <p><?= htmlspecialchars($info['Ten'] ?? '') ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <label>Vai trò:</label>
                                    <p><?= htmlspecialchars($info['VaiTro'] ?? '') ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <label>Email đăng nhập:</label>
                                    <p><?= htmlspecialchars($info['Email'] ?? '') ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <label>Đợt thực tập hiện tại:</label>
                                    <p>
                                        <?php if (!empty($dsDotDangThamGia)): ?>
                                        <ul class="list-unstyled">
                                            <?php foreach ($dsDotDangThamGia as $dot): ?>
                                                <li>
                                                    <strong><?= htmlspecialchars($dot['TenDot']) ?></strong>
                                                    <span
                                                        class="text-muted">(<?= date('d/m/Y', strtotime($dot['ThoiGianBatDau'])) ?>
                                                        - <?= date('d/m/Y', strtotime($dot['ThoiGianKetThuc'])) ?>)</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        Chưa có đợt thực tập
                                    <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-center">
                        <a href="doimatkhau" class="btn btn-primary btn-lg" style="margin-top: 20px;">
                            <span class="glyphicon glyphicon-lock"></span> Đổi mật khẩu
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
</body>

</html>