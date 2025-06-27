<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
session_start();
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$idThongBao = $_POST['idThongBao'] ?? null;

if ($idTaiKhoan && $idThongBao) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM ThongBao_Xem WHERE ID_TaiKhoan = ? AND ID_ThongBao = ?");
    $stmt->execute([$idTaiKhoan, $idThongBao]);
    if ($stmt->fetchColumn() == 0) {
        $insert = $conn->prepare("INSERT INTO ThongBao_Xem (ID_TaiKhoan, ID_ThongBao) VALUES (?, ?)");
        $insert->execute([$idTaiKhoan, $idThongBao]);
    }
    echo json_encode(['status' => 'OK']);
} else {
    echo json_encode(['status' => 'ERR']);
}