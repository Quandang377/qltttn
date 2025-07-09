<?php 
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chon']) && is_array($_POST['chon'])) {
    $dsID = $_POST['chon'];

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

    foreach ($dsID as $id) {
        $stmtTK->execute([$id]);
        $stmtSV->execute([$id]);
        $stmtGV->execute([$id]);
        $stmtCB->execute([$id]);
        $stmtAD->execute([$id]);
    }

    header("Location: quanlythanhvien?msg=deleted");
    exit;
} else {
    header("Location: quanlythanhvien?msg=empty");
    exit;
}


