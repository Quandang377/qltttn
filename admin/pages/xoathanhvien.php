<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chon']) && is_array($_POST['chon'])) {
    $dsID = $_POST['chon'];

    // Chuẩn bị câu lệnh cập nhật trạng thái
    $sqlTK = "UPDATE TaiKhoan SET TrangThai = 0 WHERE ID_TaiKhoan = ?";
    $sqlSV = "UPDATE SinhVien SET TrangThai = 0 WHERE ID_TaiKhoan = ?";
    $sqlGV = "UPDATE GiaoVien SET TrangThai = 0 WHERE ID_TaiKhoan = ?";
    $sqlCB = "UPDATE CanBoKhoa SET TrangThai = 0 WHERE ID_TaiKhoan = ?";
    $sqlAD = "UPDATE Admin SET TrangThai = 0 WHERE ID_TaiKhoan = ?";

    $stmtTK = $conn->prepare($sqlTK);
    $stmtSV = $conn->prepare($sqlSV);
    $stmtGV = $conn->prepare($sqlGV);
    $stmtCB = $conn->prepare($sqlCB);
    $stmtAD = $conn->prepare($sqlAD);

    // Giáo viên
    $stmtLayGV = $conn->prepare("SELECT ID_TaiKhoan, Ten FROM GiaoVien WHERE ID_TaiKhoan = ?");
    $stmtCheckSV = $conn->prepare("
        SELECT COUNT(*) 
        FROM SinhVien SV
        JOIN DotThucTap D ON SV.ID_Dot = D.ID
        WHERE SV.ID_GVHD = ? AND D.TrangThai NOT IN (0, -1)
    ");

    // Cán bộ khoa
    $stmtLayCB = $conn->prepare("SELECT ID_TaiKhoan, Ten FROM CanBoKhoa WHERE ID_TaiKhoan = ?");

    // Admin
    $stmtLayAD = $conn->prepare("SELECT ID_TaiKhoan, Ten FROM Admin WHERE ID_TaiKhoan = ?");

    // Kiểm tra đợt thực tập đang quản lý (dùng chung cho CBK & Admin)
    $stmtCheckDot = $conn->prepare("
        SELECT COUNT(*) 
        FROM DotThucTap 
        WHERE NguoiQuanLy = ? AND TrangThai NOT IN (0, -1)
    ");

    $gvDangCoSV = [];
    $cbDangQuanLyDot = [];
    $adminDangQuanLyDot = [];

    foreach ($dsID as $idTaiKhoan) {
        // 1. Giáo viên
        $stmtLayGV->execute([$idTaiKhoan]);
        $rowGV = $stmtLayGV->fetch(PDO::FETCH_ASSOC);

        if ($rowGV) {
            $idGV = $rowGV['ID_TaiKhoan'];
            $stmtCheckSV->execute([$idGV]);
            $soLuongSV = $stmtCheckSV->fetchColumn();

            if ($soLuongSV > 0) {
                $gvDangCoSV[] = $rowGV['Ten'];
                continue;
            }
            $stmtGV->execute([$idTaiKhoan]);
        }

        // 2. Cán bộ khoa
        $stmtLayCB->execute([$idTaiKhoan]);
        $rowCB = $stmtLayCB->fetch(PDO::FETCH_ASSOC);

        if ($rowCB) {
            $idCB = $rowCB['ID_TaiKhoan'];
            $stmtCheckDot->execute([$idCB]);
            $soLuongDot = $stmtCheckDot->fetchColumn();

            if ($soLuongDot > 0) {
                $cbDangQuanLyDot[] = $rowCB['Ten'];
                continue;
            }
            $stmtCB->execute([$idTaiKhoan]);
        }

        // 3. Admin
        $stmtLayAD->execute([$idTaiKhoan]);
        $rowAD = $stmtLayAD->fetch(PDO::FETCH_ASSOC);

        if ($rowAD) {
            $idAD = $rowAD['ID_TaiKhoan'];
            $stmtCheckDot->execute([$idAD]);
            $soLuongDotAD = $stmtCheckDot->fetchColumn();

            if ($soLuongDotAD > 0) {
                $adminDangQuanLyDot[] = $rowAD['Ten'];
                continue;
            }
            $stmtAD->execute([$idTaiKhoan]);
        }

        // Xóa các bảng còn lại
        $stmtTK->execute([$idTaiKhoan]);
        $stmtSV->execute([$idTaiKhoan]);
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
            $msg[] = "Admin: " . implode(', ', $adminDangQuanLyDot);
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
