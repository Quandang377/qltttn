<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']['ID_TaiKhoan'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

// Kiểm tra method và dữ liệu
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['letter_id'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

$letterId = (int)$_POST['letter_id'];

if ($letterId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID giấy giới thiệu không hợp lệ']);
    exit();
}

try {
    // Bắt đầu transaction
    $conn->beginTransaction();
    
    // Lấy thông tin giấy giới thiệu
    $stmt = $conn->prepare("
        SELECT g.*, s.Ten AS TenSinhVien, s.MSSV
        FROM giaygioithieu g
        LEFT JOIN sinhvien s ON g.IdSinhVien = s.ID_TaiKhoan
        WHERE g.ID = ? AND g.TrangThai = 0
    ");
    $stmt->execute([$letterId]);
    $giay = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$giay) {
        throw new Exception('Không tìm thấy giấy giới thiệu hoặc đã được duyệt');
    }
    
    // Kiểm tra và thêm công ty nếu chưa tồn tại
    if (!empty($giay['MaSoThue'])) {
        $stmtCheck = $conn->prepare("SELECT ID FROM congty WHERE MaSoThue = ?");
        $stmtCheck->execute([$giay['MaSoThue']]);
        
        if (!$stmtCheck->fetch()) {
            // Thêm công ty mới
            $stmtInsert = $conn->prepare("
                INSERT INTO congty (MaSoThue, TenCty, LinhVuc, Sdt, Email, DiaChi, TrangThai) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmtInsert->execute([
                $giay['MaSoThue'],
                $giay['TenCty'],
                $giay['LinhVuc'] ?? '',
                $giay['Sdt'] ?? '',
                $giay['Email'] ?? '',
                $giay['DiaChi']
            ]);
        }
    }
    
    // Cập nhật trạng thái giấy giới thiệu thành "đã duyệt"
    $stmtUpdate = $conn->prepare("UPDATE giaygioithieu SET TrangThai = 1 WHERE ID = ?");
    $stmtUpdate->execute([$letterId]);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Đã duyệt giấy giới thiệu thành công!',
        'data' => [
            'id' => $letterId,
            'company_name' => $giay['TenCty'],
            'student_name' => $giay['TenSinhVien'],
            'mssv' => $giay['MSSV']
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    error_log("Lỗi duyệt giấy giới thiệu: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>
