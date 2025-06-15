<?php
require_once 'datn/template/config.php';

$id_khaosat = $_POST['id_khaosat'];
$id_taikhoan = $_POST['id_taikhoan'];
$cau_hoi_ids = $_POST['id_cauhoi'];
$tra_lois = $_POST['traloi'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO PhanHoiKhaoSat (ID_KhaoSat, ID_TaiKhoan, ThoiGianTraLoi) VALUES (?, ?, NOW())");
    $stmt->execute([$id_khaosat, $id_taikhoan]);
    $id_phanhoi = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO CauTraLoi (ID_PhanHoi, ID_CauHoi, TraLoi, TrangThai) VALUES (?, ?, ?, 1)");
    for ($i = 0; $i < count($cau_hoi_ids); $i++) {
        $stmt->execute([$id_phanhoi, $cau_hoi_ids[$i], $tra_lois[$i]]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
