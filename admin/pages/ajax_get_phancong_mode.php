<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idDot = $_POST['id_dot'] ?? null;

    if (!$idDot) {
        echo json_encode(['success' => false]);
        exit;
    }

    // Tổng số GV trong đợt
    $stmt = $conn->prepare("SELECT COUNT(*) FROM dot_giaovien WHERE ID_Dot = :id");
    $stmt->execute(['id' => $idDot]);
    $tongGVHD = (int)$stmt->fetchColumn();

    // Số GV chưa có SV
    $stmt = $conn->prepare("SELECT COUNT(*) FROM dot_giaovien DG
                            LEFT JOIN sinhvien SV ON DG.ID_GVHD = SV.ID_GVHD AND SV.ID_Dot = DG.ID_Dot
                            WHERE DG.ID_Dot = :id AND SV.ID_TaiKhoan IS NULL");
    $stmt->execute(['id' => $idDot]);
    $soGVChuaCoSV = (int)$stmt->fetchColumn();

    // Số SV đã được phân công GV
    $stmt = $conn->prepare("SELECT COUNT(*) FROM sinhvien WHERE ID_Dot = :id AND ID_GVHD IS NULL OR ID_GVHD=''");
    $stmt->execute(['id' => $idDot]);
    $soSVDaCoGVHD = (int)$stmt->fetchColumn();

    $phanCongMode = ($soGVChuaCoSV < $tongGVHD && $soSVDaCoGVHD == 0) ? 'phancong_lai' : 'phancong_moi';

    echo json_encode([
        'success' => true,
        'mode' => $phanCongMode
    ]);
    exit;
}
