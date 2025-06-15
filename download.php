<?php
if (!isset($_GET['file'])) {
    die("Thiếu tham số file!");
}
$filename = basename($_GET['file']); // Chỉ lấy tên file, tránh truy cập ngoài thư mục
$filepath = $_SERVER['DOCUMENT_ROOT'] . "/datn/file/" . $filename;

if (!file_exists($filepath)) {
    die("File không tồn tại!");
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;