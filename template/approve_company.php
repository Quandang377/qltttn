<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

// Lấy dữ liệu từ AJAX
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

// Giả sử ID sinh viên là 3 (bạn có thể lấy từ session)
$idSinhVien = 3;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID công ty không hợp lệ!']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM congty WHERE ID = ? AND TrangThai = 1");
$stmt->execute([$id]);
$cty = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cty) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy công ty!']);
    exit;
}

$stmt = $conn->prepare("SELECT ID FROM giaygioithieu WHERE IdSinhVien = ? AND MaSoThue = ?");
$stmt->execute([$idSinhVien, $cty['MaSoThue']]);
$exist = $stmt->fetchColumn();

try {
    if ($exist) {
        $stmt = $conn->prepare("UPDATE giaygioithieu SET TrangThai = 1 WHERE ID = ?");
        $stmt->execute([$exist]);
    } else {
        $stmt = $conn->prepare("INSERT INTO giaygioithieu 
            (TenCty, MaSoThue, DiaChi, LinhVuc, Sdt, Email, IdSinhVien, TrangThai) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $cty['TenCty'],
            $cty['MaSoThue'],
            $cty['DiaChi'],
            $cty['Linhvuc'],
            $cty['Sdt'],
            $cty['Email'],
            $idSinhVien
        ]);
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}