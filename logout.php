<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

session_start();

$_SESSION = [];
setcookie('remember_token', '', time() - 3600, "/");

if (isset($_SESSION['user'])) {
    $stmt = $conn->prepare("UPDATE taikhoan SET remember_token = NULL WHERE ID_TaiKhoan = ?");
    $stmt->execute([$_SESSION['user']['ID_TaiKhoan']]);
}

session_unset();
session_destroy();
header("Location: /datn");
exit;
