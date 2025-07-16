<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';


$id = $_GET['id_dot'] ?? null;

if (!$id) {
    echo json_encode(['error' => 'Thiếu ID']);
    exit;
}

$stmt = $conn->prepare("SELECT GV.ID_TaiKhoan, GV.Ten 
                        FROM dot_giaovien DG 
                        JOIN giaovien GV ON DG.ID_GVHD = GV.ID_TaiKhoan 
                        WHERE DG.ID_Dot = :id");
$stmt->execute(['id' => $id]);
$gvList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVien WHERE ID_Dot = :id AND (ID_GVHD IS NULL OR ID_GVHD = '')");
$stmt->execute(['id' => $id]);
$svConLai = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'giaovien' => $gvList,
    'sv_con_lai' => $svConLai
]);
