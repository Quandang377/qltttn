<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['user']['ID_TaiKhoan'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện thao tác này']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit();
}

$letter_id = $_POST['letter_id'] ?? '';
$nguoinhan_id = $_POST['nguoinhan'] ?? '';
$ghichu = $_POST['ghichu'] ?? '';

if (empty($letter_id) || empty($nguoinhan_id)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
    exit();
}

try {
    $conn->beginTransaction();
    
    // Kiểm tra giấy giới thiệu tồn tại và đã được in
    $stmt = $conn->prepare("
        SELECT g.ID, g.TenCty, g.id_dot
        FROM giaygioithieu g
        WHERE g.ID = ? AND g.TrangThai = 2
    ");
    $stmt->execute([$letter_id]);
    $letter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$letter) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy giấy giới thiệu hoặc giấy chưa được in']);
        exit();
    }
    
    // Kiểm tra người nhận có phải là sinh viên trong danh sách cùng công ty không
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM giaygioithieu g
        WHERE g.TenCty = ? 
        AND (g.id_dot = ? OR (g.id_dot IS NULL AND ? IS NULL))
        AND g.IdSinhVien = ?
        AND g.TrangThai = 2
    ");
    $stmt->execute([$letter['TenCty'], $letter['id_dot'], $letter['id_dot'], $nguoinhan_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Người nhận không thuộc danh sách sinh viên của công ty này']);
        exit();
    }
    
    // Cập nhật tất cả giấy giới thiệu cùng công ty và cùng đợt
    $stmt = $conn->prepare("
        UPDATE giaygioithieu 
        SET TrangThai = 3, 
            id_nguoinhan = ?, 
            ngay_nhan = NOW(), 
            ghi_chu = ?
        WHERE TenCty = ? 
        AND (id_dot = ? OR (id_dot IS NULL AND ? IS NULL))
        AND TrangThai = 2
    ");
    $stmt->execute([$nguoinhan_id, $ghichu, $letter['TenCty'], $letter['id_dot'], $letter['id_dot']]);
    
    $affected_rows = $stmt->rowCount();
    
    if ($affected_rows > 0) {
        $conn->commit();
        
        // Lấy thông tin người nhận để hiển thị
        $stmt = $conn->prepare("SELECT Ten, MSSV FROM sinhvien WHERE ID_TaiKhoan = ?");
        $stmt->execute([$nguoinhan_id]);
        $nguoinhan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => "Đã ghi nhận thông tin nhận giấy cho {$affected_rows} sinh viên. Người nhận: {$nguoinhan['Ten']} ({$nguoinhan['MSSV']})"
        ]);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không có giấy nào được cập nhật']);
    }
    
} catch (PDOException $e) {
    $conn->rollback();
    error_log("Database error in save_receive_info: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi lưu thông tin: ' . $e->getMessage()]);
}
?>
