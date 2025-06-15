<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";


$idKhaoSat = $_POST['idKhaoSat'];
$idTaiKhoan = 3;
$traloi = $_POST['traloi'];

$conn->beginTransaction();
try {
    $stmt = $conn->prepare("INSERT INTO PhanHoiKhaoSat (ID_KhaoSat, ID_TaiKhoan, ThoiGianTraLoi, TrangThai) VALUES (?, ?, NOW(), 1)");
    $stmt->execute([$idKhaoSat, $idTaiKhoan]);
    $idPhanHoi = $conn->lastInsertId();

    $stmt = $conn->prepare("INSERT INTO CauTraLoi (ID_PhanHoi, ID_CauHoi, TraLoi, TrangThai) VALUES (?, ?, ?, 1)");
    foreach ($traloi as $idCauHoi => $tl) {
        $stmt->execute([$idPhanHoi, $idCauHoi, trim($tl)]);
    }

    $conn->commit();
    header("Location: /datn/pages/sinhvien/khaosat.php?success=1");
} catch (Exception $e) {
    $conn->rollBack();
    die("Lỗi: " . $e->getMessage());
}
