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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit();
}

$letter_id = $_POST['letter_id'] ?? '';
$nguoinhan = $_POST['nguoinhan'] ?? '';
$ghichu = $_POST['ghichu'] ?? '';

// Log để debug
error_log("Debug save_receiver_info: letter_id = $letter_id, nguoinhan = $nguoinhan, ghichu = $ghichu");
error_log("POST data: " . print_r($_POST, true));

if (empty($letter_id) || empty($nguoinhan)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
    exit();
}

try {
    $conn->beginTransaction();
    
    // Kiểm tra xem giấy giới thiệu có tồn tại và ở trạng thái hợp lệ không
    $checkStmt = $conn->prepare("
        SELECT ID, TrangThai 
        FROM giaygioithieu 
        WHERE ID = ?
    ");
    $checkStmt->execute([$letter_id]);
    $letter = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$letter) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy giấy giới thiệu']);
        exit();
    }
    
    // Kiểm tra trạng thái hợp lệ (trạng thái 2: Đã in, 4: Chờ lấy)
    if (!in_array($letter['TrangThai'], [2, 4])) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Giấy giới thiệu phải ở trạng thái "Đã in" hoặc "Chờ lấy" mới có thể chọn người nhận. Trạng thái hiện tại: ' . $letter['TrangThai']]);
        exit();
    }
    
    // Kiểm tra xem người nhận có tồn tại trong bảng sinhvien không
    $checkReceiverStmt = $conn->prepare("
        SELECT ID_TaiKhoan, Ten, MSSV 
        FROM sinhvien 
        WHERE ID_TaiKhoan = ?
    ");
    $checkReceiverStmt->execute([$nguoinhan]);
    $receiver = $checkReceiverStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$receiver) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sinh viên được chọn làm người nhận']);
        exit();
    }
    
    // Cập nhật thông tin người nhận và chuyển sang trạng thái "Đã nhận"
    $stmt = $conn->prepare("
        UPDATE giaygioithieu 
        SET id_nguoinhan = ?, ghi_chu = ?, ngay_nhan = NOW(), TrangThai = 3
        WHERE ID = ?
    ");
    
    $stmt->execute([$nguoinhan, $ghichu, $letter_id]);
    
    if ($stmt->rowCount() > 0) {
        $conn->commit();
        
        $receiverName = $receiver['Ten'] . ' (' . $receiver['MSSV'] . ')';
        
        echo json_encode([
            'success' => true, 
            'message' => "Đã ghi nhận người nhận: {$receiverName}. Giấy giới thiệu chuyển sang trạng thái 'Đã nhận'"
        ]);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật thông tin. Vui lòng kiểm tra lại trạng thái giấy giới thiệu.']);
    }
    
} catch (PDOException $e) {
    $conn->rollback();
    error_log("Database error in save_receiver_info: " . $e->getMessage());
    error_log("SQL Error Code: " . $e->getCode());
    error_log("SQL State: " . $e->errorInfo[0] ?? 'Unknown');
    
    // Trả về lỗi chi tiết hơn cho development
    echo json_encode([
        'success' => false, 
        'message' => 'Có lỗi xảy ra khi lưu thông tin người nhận: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("General error in save_receiver_info: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi hệ thống: ' . $e->getMessage()]);
}
?>
