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

    // Xử lý dữ liệu đầu vào - có thể là form data hoặc JSON
    $input = file_get_contents('php://input');
    $jsonData = json_decode($input, true);
    
    if ($jsonData) {
        // Dữ liệu JSON từ print_by_company.php
        $action = $jsonData['action'] ?? '';
        $letter_ids = $jsonData['ids'] ?? [];
        $company = $jsonData['company'] ?? '';
        $tax_code = $jsonData['tax_code'] ?? '';
    } else {
        // Dữ liệu form thông thường
        $action = $_POST['action'] ?? '';
        $letter_ids = $_POST['letter_ids'] ?? [];
        $company = '';
        $tax_code = '';
    }

    if (empty($action)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin hành động']);
        exit();
    }

    try {
        $conn->beginTransaction();
        
        if ($action === 'print_all') {
            // In tất cả giấy đã duyệt - chuyển sang trạng thái "ĐÃ IN"
            $stmt = $conn->prepare("
                UPDATE giaygioithieu 
                SET TrangThai = 2 
                WHERE TrangThai = 1
            ");
            $stmt->execute();
            $affected_rows = $stmt->rowCount();
            
        } elseif ($action === 'print_grouped') {
            // In theo nhóm công ty - chuyển sang trạng thái "ĐÃ IN"
            $stmt = $conn->prepare("
                UPDATE giaygioithieu 
                SET TrangThai = 2 
                WHERE TrangThai = 1
            ");
            $stmt->execute();
            $affected_rows = $stmt->rowCount();
            
        } elseif ($action === 'print_single' && !empty($letter_ids)) {
            // In từng giấy - chuyển sang trạng thái "ĐÃ IN"
            $placeholders = str_repeat('?,', count($letter_ids) - 1) . '?';
            $stmt = $conn->prepare("
                UPDATE giaygioithieu 
                SET TrangThai = 2 
                WHERE ID IN ($placeholders) AND TrangThai = 1
            ");
            $stmt->execute($letter_ids);
            $affected_rows = $stmt->rowCount();
            
        } elseif ($action === 'print_selected' && !empty($letter_ids)) {
            // In giấy đã chọn - chuyển sang trạng thái "ĐÃ IN"
            $placeholders = str_repeat('?,', count($letter_ids) - 1) . '?';
            $stmt = $conn->prepare("
                UPDATE giaygioithieu 
                SET TrangThai = 2 
                WHERE ID IN ($placeholders) AND TrangThai = 1
            ");
            $stmt->execute($letter_ids);
            $affected_rows = $stmt->rowCount();
            
        } elseif ($action === 'print_by_company' && !empty($letter_ids)) {
            // In theo công ty - chuyển sang trạng thái "ĐÃ IN"
            $placeholders = str_repeat('?,', count($letter_ids) - 1) . '?';
            $stmt = $conn->prepare("
                UPDATE giaygioithieu 
                SET TrangThai = 2 
                WHERE ID IN ($placeholders) AND TrangThai IN (1, 2)
            ");
            $stmt->execute($letter_ids);
            $affected_rows = $stmt->rowCount();
            
        } elseif ($action === 'print_all_by_company') {
            // In tất cả theo công ty - chuyển sang trạng thái "ĐÃ IN"
            $stmt = $conn->prepare("
                UPDATE giaygioithieu 
                SET TrangThai = 2 
                WHERE TrangThai = 1
            ");
            $stmt->execute();
            $affected_rows = $stmt->rowCount();
            
        } elseif ($action === 'mark_as_waiting' && !empty($letter_ids)) {
            // Chuyển từ "ĐÃ IN" sang "Chờ lấy"
            $placeholders = str_repeat('?,', count($letter_ids) - 1) . '?';
            $stmt = $conn->prepare("
                UPDATE giaygioithieu 
                SET TrangThai = 4 
                WHERE ID IN ($placeholders) AND TrangThai = 2
            ");
            $stmt->execute($letter_ids);
            $affected_rows = $stmt->rowCount();
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
            exit();
        }
        
        if ($affected_rows > 0) {
            $conn->commit();
            $message = '';
            if ($action === 'mark_as_waiting') {
                $message = "Đã chuyển {$affected_rows} giấy giới thiệu sang trạng thái 'Chờ lấy'";
            } else {
                $message = "Đã chuyển {$affected_rows} giấy giới thiệu sang trạng thái 'ĐÃ IN'";
            }
            echo json_encode([
                'success' => true, 
                'message' => $message
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
