<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Không tìm thấy ID để xóa.");
}

$stmt = $conn->prepare("UPDATE dotthuctap SET TrangThai = -1 WHERE ID = :id");
$stmt->execute(['id' => $id]);

$_SESSION['deleted'] = "Đợt thực tập được xóa thành công!";
header("Location: /datn/admin/pages/modotthuctap");
exit;
?>