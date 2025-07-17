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

if (empty($letter_id)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin giấy giới thiệu']);
    exit();
}

try {
    // Lấy thông tin công ty từ giấy giới thiệu
    $stmt = $conn->prepare("
        SELECT g.TenCty, g.DiaChi, g.id_dot, d.TenDot, d.ThoiGianBatDau, d.ThoiGianKetThuc
        FROM giaygioithieu g
        LEFT JOIN dotthuctap d ON g.id_dot = d.ID
        WHERE g.ID = ? AND g.TrangThai IN (2, 4)
    ");
    $stmt->execute([$letter_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy giấy giới thiệu hoặc giấy không ở trạng thái cho phép chọn người nhận']);
        exit();
    }
    
    // Lấy danh sách tất cả sinh viên cùng công ty và cùng đợt (nếu có)
    $stmt = $conn->prepare("
        SELECT DISTINCT s.ID_TaiKhoan, s.Ten, s.MSSV, d.TenDot
        FROM giaygioithieu g
        INNER JOIN sinhvien s ON g.Idsinhvien = s.ID_TaiKhoan
        LEFT JOIN dotthuctap d ON g.id_dot = d.ID
        WHERE g.TenCty = ? 
        AND (g.id_dot = ? OR (g.id_dot IS NULL AND ? IS NULL))
        AND g.TrangThai IN (2, 4)
        ORDER BY s.MSSV
    ");
    $stmt->execute([$company['TenCty'], $company['id_dot'], $company['id_dot']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'company' => $company,
        'students' => $students
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_company_students: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi truy vấn dữ liệu']);
}
?>
