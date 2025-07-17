<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện thao tác này']);
    exit();
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit();
}

try {
    // Lấy danh sách ID giấy giới thiệu cần cập nhật
    $letterIds = $_POST['letter_ids'] ?? [];
    
    if (empty($letterIds)) {
        echo json_encode(['success' => false, 'message' => 'Không có giấy giới thiệu nào được chọn']);
        exit();
    }
    
    // Bắt đầu transaction
    $conn->beginTransaction();
    
    // Chuẩn bị câu lệnh update
    $placeholders = str_repeat('?,', count($letterIds) - 1) . '?';
    $stmt = $conn->prepare("
        UPDATE giaygioithieu 
        SET TrangThai = 4
        WHERE ID IN ($placeholders) 
        AND TrangThai = 2
    ");
    
    // Thực thi câu lệnh
    $stmt->execute($letterIds);
    $updatedCount = $stmt->rowCount();
    
    if ($updatedCount === 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Không có giấy nào được cập nhật']);
        exit();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log hoạt động
    $userInfo = $_SESSION['TenTaiKhoan'] ?? 'Unknown';
    $letterCount = count($letterIds);
    error_log("User $userInfo marked $letterCount letters as waiting (IDs: " . implode(', ', $letterIds) . ")");
    
    echo json_encode([
        'success' => true, 
        'message' => "Đã chuyển $updatedCount giấy giới thiệu sang trạng thái 'Chờ lấy'"
    ]);
    
} catch (PDOException $e) {
    // Rollback nếu có lỗi
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Database error in mark_as_waiting.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật trạng thái']);
}
?>
