<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/middleware/check_role.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;

// Lấy thông tin sinh viên, đợt, giáo viên hướng dẫn, email giáo viên
$stmt = $conn->prepare("
    SELECT 
        sv.Ten, 
        tksv.TaiKhoan AS Email, 
        sv.Lop, 
        dt.TenDot, dt.TrangThai,
        gv.Ten AS TenGV, 
        tkgv.TaiKhoan AS EmailGV
    FROM SinhVien sv
    LEFT JOIN TaiKhoan tksv ON sv.ID_TaiKhoan = tksv.ID_TaiKhoan
    LEFT JOIN DotThucTap dt ON sv.ID_Dot = dt.ID
    LEFT JOIN GiaoVien gv ON sv.ID_GVHD = gv.ID_TaiKhoan
    LEFT JOIN TaiKhoan tkgv ON gv.ID_TaiKhoan = tkgv.ID_TaiKhoan
    WHERE sv.ID_TaiKhoan = ?
");
$stmt->execute([$idTaiKhoan]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

$HienTrangThaiDot='';
$trangThaiDot = $info['TrangThai'];
if ($trangThaiDot == 1)
  $HienTrangThaiDot = 'Đang chuẩn bị';
elseif ($trangThaiDot == 3)
  $HienTrangThaiDot = 'Hoàn Tất phân công';
elseif ($trangThaiDot == 2)
  $HienTrangThaiDot = 'Đã bắt đầu';
elseif ($trangThaiDot == 0)
  $HienTrangThaiDot = 'Đã kết thúc';

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thông tin cá nhân</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_SinhVien.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row" style="margin-top: 40px;">
                    <div class="col-md-12">
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
                                        <label>Lớp:</label>
                                        <p><?= htmlspecialchars($info['Lop'] ?? '') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label>Đợt thực tập:</label>
                                        <p><?= htmlspecialchars($info['TenDot'] ?? 'Chưa phân công') ?>: <?= $HienTrangThaiDot?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label>Giáo viên hướng dẫn:</label>
                                        <p><?= htmlspecialchars($info['TenGV'] ?? 'Chưa phân công') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label>Email GVHD:</label>
                                        <p><?= htmlspecialchars($info['EmailGV'] ?? '') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-center">
                        <a href="doimatkhau" class="btn btn-primary" style="margin-top: 20px;">
                            <span class="glyphicon glyphicon-lock"></span> Đổi mật khẩu
                        </a>
                    </div>
            </div>
        </div>
    </div>
</body>

</html>