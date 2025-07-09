<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/datn/uploads/Images/';
$uploadUrl = '/datn/uploads/Images/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Không có file hợp lệ.']);
    exit;
}

$filename = basename($_FILES['file']['name']);
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$allowExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowExt)) {
    echo json_encode(['status' => 'error', 'message' => 'Chỉ cho phép ảnh JPG, PNG, GIF, WEBP.']);
    exit;
}

$newFileName = uniqid('logo_') . '.' . $ext;
$targetPath = $uploadDir . $newFileName;
$targetUrl = $uploadUrl . $newFileName;

if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
    echo json_encode(['status' => 'success', 'path' => $targetUrl]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi khi lưu file.']);
}
