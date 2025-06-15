<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chon'])) {
    $dsID = $_POST['chon'];

    $sql = "UPDATE TaiKhoan SET TrangThai = 0 WHERE ID_TaiKhoan = ?";
    $stmt = $conn->prepare($sql);

    foreach ($dsID as $id) {
        $stmt->execute([$id]);
    }

    header("Location: quanlythanhvien?msg=deleted");
    exit;
} else {
    header("Location: quanlythanhvien?msg=empty");
    exit;
}
?>
