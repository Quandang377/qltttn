<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Không tìm thấy ID để xóa.");
}

$stmt = $conn->prepare("UPDATE DOTTHUCTAP SET TrangThai = -1 WHERE ID = :id");
$stmt->execute(['id' => $id]);

header("Location: /datn/pages/canbo/modotthuctap?msg=deleted");
exit;
?>