<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
session_start();
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$idThongBao = $_POST['idThongBao'] ?? null;

if ($idTaiKhoan && $idThongBao) {
    $stmt = $conn->prepare("INSERT IGNORE INTO ThongBao_Xem (ID_TaiKhoan, ID_ThongBao) VALUES (?, ?)");
    $stmt->execute([$idTaiKhoan, $idThongBao]);
    echo 'OK';
} else {
    http_response_code(400);
    echo 'ERR';
}