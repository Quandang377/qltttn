<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chon']) && is_array($_POST['chon'])) {
    $dsID = $_POST['chon'];

    // Chuẩn bị câu lệnh cập nhật trạng thái
    $sqlTK = "UPDATE taikhoan SET TrangThai = 0 WHERE ID_taikhoan = ?";
    $sqlSV = "UPDATE sinhvien SET TrangThai = 0 WHERE ID_taikhoan = ?";
    $sqlGV = "UPDATE giaovien SET TrangThai = 0 WHERE ID_taikhoan = ?";
    $sqlCB = "UPDATE canbokhoa SET TrangThai = 0 WHERE ID_taikhoan = ?";
    $sqlAD = "UPDATE admin SET TrangThai = 0 WHERE ID_taikhoan = ?";

    $stmtTK = $conn->prepare($sqlTK);
    $stmtSV = $conn->prepare($sqlSV);
    $stmtGV = $conn->prepare($sqlGV);
    $stmtCB = $conn->prepare($sqlCB);
    $stmtAD = $conn->prepare($sqlAD);

    // Giáo viên
    $stmtLayGV = $conn->prepare("SELECT ID_taikhoan, Ten FROM giaovien WHERE ID_taikhoan = ?");
    $stmtCheckSV = $conn->prepare("
        SELECT COUNT(*) 
        FROM sinhvien SV
        JOIN dotthuctap D ON SV.ID_Dot = D.ID
        WHERE SV.ID_GVHD = ? AND D.TrangThai NOT IN (0, -1)
    ");

    // Cán bộ khoa
    $stmtLayCB = $conn->prepare("SELECT ID_taikhoan, Ten FROM canbokhoa WHERE ID_taikhoan = ?");

    // admin
    $stmtLayAD = $conn->prepare("SELECT ID_taikhoan, Ten FROM admin WHERE ID_taikhoan = ?");

    // Kiểm tra đợt thực tập đang quản lý (dùng chung cho CBK & admin)
    $stmtCheckDot = $conn->prepare("
        SELECT COUNT(*) 
        FROM dotthuctap 
        WHERE NguoiQuanLy = ? AND TrangThai NOT IN (0, -1)
    ");

    $gvDangCoSV = [];
    $cbDangQuanLyDot = [];
    $adminDangQuanLyDot = [];

    foreach ($dsID as $idtaikhoan) {
        // 1. Giáo viên
        $stmtLayGV->execute([$idtaikhoan]);
        $rowGV = $stmtLayGV->fetch(PDO::FETCH_ASSOC);

        if ($rowGV) {
            $idGV = $rowGV['ID_taikhoan'];
            $stmtCheckSV->execute([$idGV]);
            $soLuongSV = $stmtCheckSV->fetchColumn();

            if ($soLuongSV > 0) {
                $gvDangCoSV[] = $rowGV['Ten'];
                continue;
            }
            $stmtGV->execute([$idtaikhoan]);
        }

        // 2. Cán bộ khoa
        $stmtLayCB->execute([$idtaikhoan]);
        $rowCB = $stmtLayCB->fetch(PDO::FETCH_ASSOC);

        if ($rowCB) {
            $idCB = $rowCB['ID_taikhoan'];
            $stmtCheckDot->execute([$idCB]);
            $soLuongDot = $stmtCheckDot->fetchColumn();

            if ($soLuongDot > 0) {
                $cbDangQuanLyDot[] = $rowCB['Ten'];
                continue;
            }
            $stmtCB->execute([$idtaikhoan]);
        }

        // 3. admin
        $stmtLayAD->execute([$idtaikhoan]);
        $rowAD = $stmtLayAD->fetch(PDO::FETCH_ASSOC);

        if ($rowAD) {
            $idAD = $rowAD['ID_taikhoan'];
            $stmtCheckDot->execute([$idAD]);
            $soLuongDotAD = $stmtCheckDot->fetchColumn();

            if ($soLuongDotAD > 0) {
                $adminDangQuanLyDot[] = $rowAD['Ten'];
                continue;
            }
            $stmtAD->execute([$idtaikhoan]);
        }

        // Xóa các bảng còn lại
        $stmtTK->execute([$idtaikhoan]);
        $stmtSV->execute([$idtaikhoan]);
    }

    if (!empty($gvDangCoSV) || !empty($cbDangQuanLyDot) || !empty($adminDangQuanLyDot)) {
        $msg = [];
        if (!empty($gvDangCoSV)) {
            $msg[] = "GV: " . implode(', ', $gvDangCoSV);
        }
        if (!empty($cbDangQuanLyDot)) {
            $msg[] = "CB: " . implode(', ', $cbDangQuanLyDot);
        }
        if (!empty($adminDangQuanLyDot)) {
            $msg[] = "admin: " . implode(', ', $adminDangQuanLyDot);
        }

        $chuoiThongBao = implode(' | ', $msg);
        header("Location: quanlythanhvien?msg=blocked&ten=" . urlencode($chuoiThongBao));
    } else {
        header("Location: quanlythanhvien?msg=deleted");
    }

    exit;
} else {
    header("Location: quanlythanhvien?msg=empty");
    exit;
}
