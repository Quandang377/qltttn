<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/includes/database.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Không tìm thấy ID để xóa.");
}

$stmt = $pdo->prepare("UPDATE DOTTHUCTAP SET TrangThai = -1 WHERE ID = :id AND TrangThai = 0");
$stmt->execute(['id' => $id]);

header("Location: /datn/pages/canbo/dotthuctap");
exit;
?>