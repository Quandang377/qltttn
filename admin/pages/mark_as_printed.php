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

$action = $_POST['action'] ?? '';
$letter_ids = $_POST['letter_ids'] ?? [];

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin hành động']);
    exit();
}

try {
    $conn->beginTransaction();
    
    if ($action === 'print_all') {
        // In tất cả giấy đã duyệt
        $stmt = $conn->prepare("
            UPDATE giaygioithieu 
            SET TrangThai = 2 
            WHERE TrangThai = 1
        ");
        $stmt->execute();
        $affected_rows = $stmt->rowCount();
        
    } elseif ($action === 'print_grouped') {
        // In theo nhóm công ty
        $stmt = $conn->prepare("
            UPDATE giaygioithieu 
            SET TrangThai = 2 
            WHERE TrangThai = 1
        ");
        $stmt->execute();
        $affected_rows = $stmt->rowCount();
        
    } elseif ($action === 'print_single' && !empty($letter_ids)) {
        // In từng giấy
        $placeholders = str_repeat('?,', count($letter_ids) - 1) . '?';
        $stmt = $conn->prepare("
            UPDATE giaygioithieu 
            SET TrangThai = 2 
            WHERE ID IN ($placeholders) AND TrangThai = 1
        ");
        $stmt->execute($letter_ids);
        $affected_rows = $stmt->rowCount();
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        exit();
    }
    
    if ($affected_rows > 0) {
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => "Đã đánh dấu {$affected_rows} giấy giới thiệu là đã in"
        ]);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không có giấy nào được cập nhật']);
    }
    
} catch (PDOException $e) {
    $conn->rollback();
    error_log("Database error in mark_as_printed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật trạng thái in']);
}
?>
