<?php
require_once 'datn/template/config.php';

$id_khaosat = $_GET['id_khaosat'];
$stmt = $pdo->prepare("SELECT ID, NoiDung FROM CauHoiKhaoSat WHERE ID_KhaoSat = ? AND TrangThai = 1");
$stmt->execute([$id_khaosat]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
