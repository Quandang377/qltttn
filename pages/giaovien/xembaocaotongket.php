<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

// Kiểm tra đăng nhập và lấy ID giáo viên
$id_gvhd = $_SESSION['user']['ID_TaiKhoan'] ?? null;
if (!$id_gvhd) die('Bạn chưa đăng nhập!');

$errorMsg = '';

// Xử lý đóng/mở nộp báo cáo tổng kết - AJAX (không reload trang)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['luu_trangthai_tongket'], $_POST['id_dot'])) {
    $id_dot = (int)$_POST['id_dot'];
    $trangthai_tongket = isset($_POST['trangthai_tongket']) && $_POST['trangthai_tongket'] == '1' ? 1 : 0;
    
    try {
        // Kiểm tra xem đã có bản ghi nào chưa
        $stmt = $conn->prepare("SELECT ID FROM Baocaotongket WHERE ID_TaiKhoan = ? AND ID_Dot = ?");
        $stmt->execute([$id_gvhd, $id_dot]);
        $existing_record = $stmt->fetch();
        
        if ($existing_record) {
            // Nếu đã có bản ghi thì chỉ update trường TrangThai
            $stmt = $conn->prepare("UPDATE Baocaotongket SET TrangThai = ? WHERE ID_TaiKhoan = ? AND ID_Dot = ?");
            $result = $stmt->execute([$trangthai_tongket, $id_gvhd, $id_dot]);
        } else {
            // Nếu chưa có bản ghi thì insert mới
            $stmt = $conn->prepare("INSERT INTO Baocaotongket (ID_TaiKhoan, ID_Dot, TrangThai) VALUES (?, ?, ?)");
            $result = $stmt->execute([$id_gvhd, $id_dot, $trangthai_tongket]);
        }
        
        // Trả về JSON cho AJAX (không reload trang)
        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
            echo json_encode([
                'success' => true,
                'message' => $trangthai_tongket ? 'Đã mở nộp báo cáo tổng kết' : 'Đã đóng nộp báo cáo tổng kết',
                'status' => $trangthai_tongket
            ]);
            exit;
        }
    } catch (Exception $e) {
        // Trả về lỗi cho AJAX
        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái: ' . $e->getMessage(),
                'status' => null
            ]);
            exit;
        }
        $errorMsg = 'Có lỗi xảy ra khi cập nhật trạng thái!';
    }
    
    // Nếu không phải AJAX thì reload trang
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Lấy trạng thái cho phép nộp báo cáo tổng kết cho từng đợt
$trangthai_tongket_dot = [];
$stmt = $conn->prepare("SELECT ID_Dot, TrangThai FROM Baocaotongket WHERE ID_TaiKhoan = ?");
$stmt->execute([$id_gvhd]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $trangthai_tongket_dot[$row['ID_Dot']] = $row['TrangThai'];
}

// Lấy danh sách sinh viên thuộc giáo viên này và cùng đợt
$stmt = $conn->prepare("
    SELECT sv.ID_TaiKhoan, sv.Ten, sv.MSSV
    FROM SinhVien sv
    WHERE sv.ID_GVHD = ? AND sv.ID_Dot = ?
");
$sinhviens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy trạng thái, đường dẫn báo cáo tổng kết, ngày nộp và số lần nộp/xóa/sửa của sinh viên
$baocao_tongket = [];
foreach ($sinhviens as $sv) {
    // Lấy file báo cáo mới nhất
    $stmt2 = $conn->prepare("SELECT TenFile, Dir, NgayNop FROM file WHERE ID_SV = ? AND Loai = 'Baocao' AND TrangThai = 1 ORDER BY ID DESC LIMIT 1");
    $stmt2->execute([$sv['ID_TaiKhoan']]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    // Đếm tổng số file báo cáo đã nộp (bao gồm cả đã xóa/sửa)
    $stmt3 = $conn->prepare("SELECT COUNT(*) FROM file WHERE ID_SV = ? AND Loai = 'Baocao'");
    $stmt3->execute([$sv['ID_TaiKhoan']]);
    $solan = $stmt3->fetchColumn();

    if ($row) {
        $baocao_tongket[$sv['ID_TaiKhoan']] = [
            'TenFile' => $row['TenFile'],
            'Dir' => $row['Dir'],
            'NgayNop' => $row['NgayNop'],
            'SoLan' => $solan
        ];
    } else {
        $baocao_tongket[$sv['ID_TaiKhoan']] = [
            'TenFile' => null,
            'Dir' => null,
            'NgayNop' => null,
            'SoLan' => $solan
        ];
    }
}

// Lấy tất cả các đợt mà giáo viên này đang hướng dẫn sinh viên, trạng thái >= 3
$stmt = $conn->prepare("
    SELECT dt.ID, dt.TenDot, dt.TrangThai
    FROM DotThucTap dt
    WHERE dt.ID IN (
        SELECT DISTINCT sv.ID_Dot
        FROM SinhVien sv
        WHERE sv.ID_GVHD = ?
    ) AND dt.TrangThai >= 3
    ORDER BY dt.ID DESC
");
$stmt->execute([$id_gvhd]);
$dots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Khởi tạo biến $ds_sinhvien_theo_dot
$ds_sinhvien_theo_dot = [];
foreach ($dots as $dot) {
    $stmt = $conn->prepare("
        SELECT sv.ID_TaiKhoan, sv.Ten, sv.MSSV
        FROM SinhVien sv
        WHERE sv.ID_GVHD = ? AND sv.ID_Dot = ?
    ");
    $stmt->execute([$id_gvhd, $dot['ID']]);
    $ds_sinhvien_theo_dot[$dot['ID']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Xử lý tải xuống tất cả báo cáo thành file zip
if (isset($_GET['download_all']) && $_GET['download_all'] == 1) {
    $zip = new ZipArchive();
    
    // Kiểm tra nếu có dot_id cụ thể
    $specific_dot_id = isset($_GET['dot_id']) ? (int)$_GET['dot_id'] : null;
    $zipName = $specific_dot_id ? 
        'baocao_dot_' . $specific_dot_id . '_' . date('Ymd_His') . '.zip' : 
        'baocao_tongket_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;
    
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $added_files = 0; // Track if any files were added
        $student_count = 0; // Track number of students with complete submissions
        
        // Lấy danh sách sinh viên - tất cả đợt hoặc đợt cụ thể
        $dots_to_process = $specific_dot_id ? 
            [$specific_dot_id => $ds_sinhvien_theo_dot[$specific_dot_id] ?? []] : 
            $ds_sinhvien_theo_dot;
        
        foreach ($dots_to_process as $dot_id => $sinhviens) {
            $dot_info = array_filter($dots, function($d) use ($dot_id) { return $d['ID'] == $dot_id; });
            $dot_info = reset($dot_info);
            $dot_name = $dot_info ? preg_replace('/[^a-zA-Z0-9_-]/', '', $dot_info['TenDot']) : "Dot-$dot_id";
            
            foreach ($sinhviens as $sv) {
                // Kiểm tra xem sinh viên đã nộp đủ 4 loại file chưa
                $loai_files = ['Baocao', 'khoasat', 'phieuthuctap', 'nhanxet'];
                $file_count = 0;
                $file_data = [];
                
                foreach ($loai_files as $loai) {
                    $stmt = $conn->prepare("SELECT TenFile, Dir FROM file WHERE ID_SV = ? AND Loai = ? AND TrangThai = 1 ORDER BY ID DESC LIMIT 1");
                    $stmt->execute([$sv['ID_TaiKhoan'], $loai]);
                    $file = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($file && !empty($file['Dir']) && file_exists($file['Dir'])) {
                        $file_count++;
                        $file_data[$loai] = $file;
                    }
                }
                
                // Chỉ thêm sinh viên có đủ 4 loại file vào ZIP
                if ($file_count === 4) {
                    $student_count++;
                    // Tạo folder cho mỗi sinh viên - chỉ sử dụng MSSV
                    $folder_name = $sv['MSSV'];
                    $folder_path = $dot_name . '/' . $folder_name;
                    
                    // Create empty directory entry in ZIP for this student
                    $zip->addEmptyDir($folder_path);
                    
                    // Thêm 4 loại file vào ZIP
                    foreach ($loai_files as $loai) {
                        $file = $file_data[$loai];
                        $file_name = $loai . '_' . $file['TenFile'];
                        // Read file contents instead of directly adding the file
                        $file_contents = file_get_contents($file['Dir']);
                        if ($file_contents !== false) {
                            $zip->addFromString($folder_path . '/' . $file_name, $file_contents);
                            $added_files++;
                        }
                    }
                }
            }
        }
        
        // Close ZIP before sending to browser
        $zip->close();
        
        if ($student_count > 0 && $added_files > 0 && file_exists($zipPath)) {
            // Set appropriate headers and send the file
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Read file in chunks to handle large files
            $handle = fopen($zipPath, 'rb');
            if ($handle) {
                while (!feof($handle)) {
                    echo fread($handle, 8192);
                    flush();
                }
                fclose($handle);
            }
            
            // Delete temp file
            @unlink($zipPath);
            exit;
        } else {
            $errorMsg = "Không có sinh viên nào đã nộp đủ 4 loại file!";
        }
    } else {
        $errorMsg = "Không thể tạo file zip!";
    }
}

$baocao_tongket = [];
$diemData = [];
foreach ($ds_sinhvien_theo_dot as $dot_id => $sinhviens) {
    foreach ($sinhviens as $sv) {
        // Lấy file báo cáo mới nhất và điểm (join)
        $stmt2 = $conn->prepare("
            SELECT 
                f.TenFile, f.Dir, f.NgayNop,
                d.Diem_BaoCao, d.Diem_ChuyenCan, d.Diem_ChuanNghe, d.Diem_ThucTe, d.GhiChu
            FROM file f
            LEFT JOIN diem_tongket d ON d.ID_SV = f.ID_SV AND d.ID_Dot = ?
            WHERE f.ID_SV = ? AND f.Loai = 'Baocao' AND f.TrangThai = 1
            ORDER BY f.ID DESC
            LIMIT 1
        ");
        $stmt2->execute([$dot_id, $sv['ID_TaiKhoan']]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);

        // Lưu thông tin file báo cáo
        $baocao_tongket[$dot_id][$sv['ID_TaiKhoan']] = [
            'TenFile' => $row['TenFile'] ?? null,
            'Dir' => $row['Dir'] ?? null,
            'NgayNop' => $row['NgayNop'] ?? null
        ];

        // Lưu thông tin điểm (nếu có)
        $diemData[$dot_id][$sv['ID_TaiKhoan']] = [
            'diem_baocao' => $row['Diem_BaoCao'] ?? null,
            'diem_chuyencan' => $row['Diem_ChuyenCan'] ?? null,
            'diem_chuannghe' => $row['Diem_ChuanNghe'] ?? null,
            'diem_thucte' => $row['Diem_ThucTe'] ?? null,
            'ghichu' => $row['GhiChu'] ?? null
        ];
    }
}

// Xử lý lưu điểm từ form nhập điểm - AJAX (không reload trang)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['diem_baocao'], $_POST['diem_chuyencan'], $_POST['diem_chuannghe'], $_POST['diem_thucte'], $_POST['id_sv'])) {
    $id_sv = (int)$_POST['id_sv'];
    $diem_baocao = $_POST['diem_baocao'];
    $diem_chuyencan = $_POST['diem_chuyencan'];
    $diem_chuannghe = $_POST['diem_chuannghe'];
    $diem_thucte = $_POST['diem_thucte'];
    $ghichu = $_POST['ghichu'] ?? null;
    $id_dot = isset($_POST['id_dot']) ? (int)$_POST['id_dot'] : null;

    // Kiểm tra đã có điểm chưa
    $sql_check = "SELECT ID FROM diem_tongket WHERE ID_SV = ?" . ($id_dot ? " AND ID_Dot = ?" : "");
    $params = $id_dot ? [$id_sv, $id_dot] : [$id_sv];
    $stmt = $conn->prepare($sql_check);
    $stmt->execute($params);

    if ($stmt->fetch()) {
        // Update
        $sql_update = "UPDATE diem_tongket SET Diem_BaoCao=?, Diem_ChuyenCan=?, Diem_ChuanNghe=?, Diem_ThucTe=?, GhiChu=? WHERE ID_SV=?"
            . ($id_dot ? " AND ID_Dot=?" : "");
        $params_update = [$diem_baocao, $diem_chuyencan, $diem_chuannghe, $diem_thucte, $ghichu, $id_sv];
        if ($id_dot) $params_update[] = $id_dot;
        $stmt = $conn->prepare($sql_update);
        $stmt->execute($params_update);
    } else {
        // Insert
        $sql_insert = "INSERT INTO diem_tongket (ID_SV, Diem_BaoCao, Diem_ChuyenCan, Diem_ChuanNghe, Diem_ThucTe, GhiChu" . ($id_dot ? ", ID_Dot" : "") . ")
            VALUES (?, ?, ?, ?, ?, ?" . ($id_dot ? ", ?" : "") . ")";
        $params_insert = [$id_sv, $diem_baocao, $diem_chuyencan, $diem_chuannghe, $diem_thucte, $ghichu];
        if ($id_dot) $params_insert[] = $id_dot;
        $stmt = $conn->prepare($sql_insert);
        $stmt->execute($params_insert);
    }

    // Trả về JSON cho AJAX
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        echo json_encode([
            'success' => true,
            'message' => 'Đã lưu điểm thành công!'
        ]);
        exit;
    }

    // Nếu không phải AJAX thì reload trang và hiển thị thông báo
    $_SESSION['success_message'] = 'Đã lưu điểm thành công!';
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo tổng kết sinh viên</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
    /* === GLOBAL STYLES === */
    body {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f0f9ff 100%);
        font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
        line-height: 1.6;
        color: #334155;
        overflow-x: hidden;
    }
    
    #page-wrapper {
        padding: 25px;
        min-height: 100vh;
        background: transparent;
    }
    
    .page-header {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 50%, #6366f1 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-align: center;
        margin-bottom: 40px;
        text-shadow: 0 4px 6px rgba(59, 130, 246, 0.1);
        position: relative;
        animation: fadeInDown 0.8s ease-out;
    }
    
    .page-header::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 4px;
        background: linear-gradient(135deg, #0ea5e9, #3b82f6);
        border-radius: 2px;
        animation: scaleIn 0.6s ease-out 0.3s both;
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes scaleIn {
        from {
            transform: translateX(-50%) scaleX(0);
        }
        to {
            transform: translateX(-50%) scaleX(1);
        }
    }

    /* === PANEL STYLES === */
    .panel {
        border-radius: 20px !important;
        border: none !important;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        animation: fadeInUp 0.6s ease-out;
    }
    
    .panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #0ea5e9, #3b82f6, #6366f1);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .panel:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
    }
    
    .panel:hover::before {
        opacity: 1;
    }
    
    .panel-heading {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 0;
        padding: 25px 30px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        color: #1e293b;
        font-weight: 700;
        font-size: 18px;
        position: relative;
        overflow: hidden;
    }
    
    .panel-heading::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(135deg, #0ea5e9, #3b82f6);
    }
    
    .panel-heading::after {
        content: '';
        position: absolute;
        top: 0;
        right: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(14, 165, 233, 0.05), transparent);
        transition: right 0.6s ease;
    }
    
    .panel:hover .panel-heading::after {
        right: 100%;
    }
    
    .panel-body {
        background: rgba(255, 255, 255, 0.98);
        border-radius: 0;
        padding: 30px;
        position: relative;
    }
    
    .toggle-container {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 2px solid #0ea5e9;
        border-radius: 16px;
        margin-bottom: 25px;
        overflow: hidden;
        position: relative;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(14, 165, 233, 0.1);
    }
    
    .toggle-container:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(14, 165, 233, 0.2);
    }
    
    .toggle-container .panel-heading {
        background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
        color: white;
        text-align: center;
        font-weight: 700;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        overflow: hidden;
    }
    
    .toggle-container .panel-heading::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s ease;
    }
    
    .toggle-container:hover .panel-heading::before {
        left: 100%;
    }
    
    .toggle-container .panel-body {
        padding: 25px;
        text-align: center;
        background: white;
        font-size: 16px;
        font-weight: 600;
        color: #475569;
    }

    /* === BUTTON STYLES === */
    .btn {
        border-radius: 12px !important;
        font-weight: 600;
        padding: 12px 24px;
        border: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 13px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.6s ease;
    }
    
    .btn:hover::before {
        left: 100%;
    }
    
    .btn:active {
        transform: scale(0.95);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.6);
        color: white;
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    
    .btn-success:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(16, 185, 129, 0.6);
        color: white;
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
    }
    
    .btn-warning:hover {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(245, 158, 11, 0.6);
        color: white;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    }
    
    .btn-danger:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(239, 68, 68, 0.6);
        color: white;
    }
    
    .btn-sm {
        padding: 8px 16px;
        font-size: 12px;
    }
    
    .btn-xs {
        padding: 6px 12px;
        font-size: 11px;
    }
    
    .btn-lg {
        padding: 16px 32px;
        font-size: 15px;
    }

    /* === TABLE STYLES === */
    .table {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        position: relative;
        animation: slideInUp 0.8s ease-out;
    }
    
    .table::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #0ea5e9, #3b82f6, #6366f1);
        z-index: 1;
    }
    
    .table thead th {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        color: #1e293b;
        font-weight: 700;
        border: none;
        padding: 20px 16px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        vertical-align: middle;
        border-bottom: 2px solid rgba(14, 165, 233, 0.1);
    }
    
    .table thead th::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, #0ea5e9, transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .table thead th:hover::after {
        opacity: 1;
    }
    
    .table thead th:first-child {
        border-radius: 16px 0 0 0;
    }
    
    .table thead th:last-child {
        border-radius: 0 16px 0 0;
    }
    
    .table tbody tr {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    
    .table tbody tr::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.05), rgba(59, 130, 246, 0.05));
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: -1;
    }
    
    .table tbody tr:hover::before {
        opacity: 1;
    }
    
    .table tbody tr:hover {
        transform: translateX(5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .table td {
        padding: 18px 16px;
        vertical-align: middle;
        border: none;
        border-top: 1px solid rgba(226, 232, 240, 0.4);
        position: relative;
        transition: all 0.3s ease;
    }
    
    .table tbody tr:last-child td:first-child {
        border-radius: 0 0 0 16px;
    }
    
    .table tbody tr:last-child td:last-child {
        border-radius: 0 0 16px 0;
    }
    
    .table-striped tbody tr:nth-of-type(odd) {
        background: rgba(248, 250, 252, 0.6);
    }
    
    .table-responsive {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    }
    
    .table-bordered {
        border: 1px solid rgba(226, 232, 240, 0.3);
    }
    
    .table-hover tbody tr:hover {
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.05), rgba(59, 130, 246, 0.05));
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* === TAB STYLES === */
    .nav-tabs {
        border: none;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 16px 16px 0 0;
        padding: 20px 25px 0;
        margin-bottom: 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        position: relative;
        overflow: hidden;
    }
    
    .nav-tabs::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #0ea5e9, #3b82f6, #6366f1);
    }
    
    .nav-tabs .nav-item {
        margin-bottom: -1px;
        margin-right: 10px;
    }
    
    .nav-tabs .nav-link {
        color: #64748b;
        font-weight: 600;
        border: none;
        border-radius: 12px 12px 0 0;
        padding: 16px 28px;
        background: transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 14px;
    }
    
    .nav-tabs .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #0ea5e9, #3b82f6);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .nav-tabs .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.05), rgba(59, 130, 246, 0.05));
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .nav-tabs .nav-link:hover::after {
        opacity: 1;
    }
    
    .nav-tabs .nav-link.active::before {
        transform: scaleX(1);
    }
    
    .nav-tabs .nav-link.active {
        background: white;
        color: #0ea5e9;
        border: none;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        font-weight: 700;
        transform: translateY(-2px);
    }
    
    .nav-tabs .nav-link:hover:not(.active) {
        background: rgba(255, 255, 255, 0.8);
        color: #0ea5e9;
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .tab-content {
        background: white;
        border-radius: 0 0 16px 16px;
        padding: 35px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        position: relative;
        animation: fadeIn 0.6s ease-out;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .nav-pills {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        padding: 15px;
        border-radius: 16px;
        margin-bottom: 25px;
        box-shadow: 0 4px 20px rgba(14, 165, 233, 0.1);
    }
    
    .nav-pills .nav-link {
        color: #64748b;
        font-weight: 600;
        border-radius: 12px;
        margin-right: 12px;
        margin-bottom: 8px;
        padding: 14px 24px;
        background: white;
        border: 2px solid transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 13px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .nav-pills .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(14, 165, 233, 0.1), transparent);
        transition: left 0.6s ease;
    }
    
    .nav-pills .nav-link:hover::before {
        left: 100%;
    }
    
    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
        color: white;
        border-color: #0ea5e9;
        box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
        transform: translateY(-2px);
    }
    
    .nav-pills .nav-link:hover:not(.active) {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        color: #0ea5e9;
        border-color: #0ea5e9;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(14, 165, 233, 0.2);
    }

    /* === FORM STYLES === */
    .form-control {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        font-size: 14px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgba(255, 255, 255, 0.9);
        color: #334155;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        position: relative;
    }
    
    .form-control:focus {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
        outline: none;
        transform: translateY(-2px);
        background: white;
    }
    
    .form-control:hover {
        border-color: #0ea5e9;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .form-control-sm {
        padding: 10px 14px;
        font-size: 13px;
        border-radius: 10px;
    }
    
    .form-group {
        position: relative;
        margin-bottom: 20px;
    }
    
    .form-group label {
        font-weight: 600;
        color: #475569;
        margin-bottom: 10px;
        display: block;
        font-size: 14px;
        position: relative;
    }
    
    .form-group label::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 30px;
        height: 2px;
        background: linear-gradient(135deg, #0ea5e9, #3b82f6);
        border-radius: 1px;
    }
    
    /* Progress bar styles */
    .progress {
        height: 8px;
        border-radius: 10px;
        background: rgba(226, 232, 240, 0.3);
        overflow: hidden;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .progress-bar {
        border-radius: 10px;
        transition: width 0.6s ease;
        position: relative;
        overflow: hidden;
    }
    
    .progress-bar::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shimmer 2s infinite;
    }
    
    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .bg-success {
        background: linear-gradient(135deg, #10b981, #059669) !important;
    }
    
    .bg-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    }
    
    .bg-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    }
    
    /* === ALERT STYLES === */
    .alert {
        border: none;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 25px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
        animation: slideInRight 0.6s ease-out;
    }
    
    .alert::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
        background: currentColor;
        border-radius: 0 3px 3px 0;
    }
    
    .alert::after {
        content: '';
        position: absolute;
        top: 0;
        right: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: right 0.6s ease;
    }
    
    .alert:hover::after {
        right: 100%;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .alert-warning {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    
    .alert-info {
        background: linear-gradient(135deg, #f0f9ff 0%, #dbeafe 100%);
        color: #0284c7;
        border: 1px solid rgba(14, 165, 233, 0.2);
    }
    
    .alert-dismissible .close {
        position: absolute;
        top: 15px;
        right: 20px;
        background: none;
        border: none;
        font-size: 20px;
        color: currentColor;
        opacity: 0.7;
        transition: all 0.3s ease;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .alert-dismissible .close:hover {
        opacity: 1;
        background: rgba(0, 0, 0, 0.1);
        transform: scale(1.1);
    }
    
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* === STATUS ICONS === */
    .status-icon {
        font-size: 18px;
        width: 24px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        padding: 4px;
    }
    
    .text-success { 
        color: #10b981 !important; 
        background: rgba(16, 185, 129, 0.1);
    }
    .text-danger { 
        color: #ef4444 !important; 
        background: rgba(239, 68, 68, 0.1);
    }
    .text-warning { 
        color: #f59e0b !important; 
        background: rgba(245, 158, 11, 0.1);
    }
    .text-muted { 
        color: #6b7280 !important; 
        background: rgba(107, 114, 128, 0.1);
    }
    
    /* === CARD STYLES === */
    .card {
        border: none;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
    }
    
    .student-detail-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .student-detail-table tr {
        transition: all 0.2s ease;
    }
    
    .student-detail-table tr:hover {
        background: rgba(14, 165, 233, 0.05);
    }
    
    .student-detail-label {
        font-weight: 600;
        color: #475569;
        padding: 12px 16px;
        background: rgba(248, 250, 252, 0.8);
        border-radius: 8px 0 0 8px;
        width: 40%;
        vertical-align: middle;
    }
    
    .student-detail-value {
        padding: 12px 16px;
        color: #1e293b;
        background: white;
        border-radius: 0 8px 8px 0;
        font-weight: 500;
        vertical-align: middle;
    }
    
    .student-detail-icon {
        display: inline-block;
        width: 24px;
        text-align: center;
        color: #0ea5e9;
        margin-right: 8px;
    }
    
    .student-detail-action {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 2px solid rgba(226, 232, 240, 0.5);
    }
    
    /* === MODAL STYLES === */
    .modal-content {
        border: none;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-bottom: 2px solid rgba(14, 165, 233, 0.1);
        padding: 25px 30px;
    }
    
    .modal-body {
        padding: 30px;
        background: linear-gradient(135deg, #fefefe 0%, #f8fafc 100%);
    }
    
    .modal-footer {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-top: 2px solid rgba(14, 165, 233, 0.1);
        padding: 20px 30px;
    }

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
        #page-wrapper { 
            padding: 20px 15px; 
        }
        
        .page-header {
            font-size: 2rem;
            margin-bottom: 30px;
        }
        
        .panel-body { 
            padding: 20px; 
        }
        
        .table { 
            font-size: 13px; 
        }
        
        .table thead th,
        .table td {
            padding: 12px 8px;
        }
        
        .btn { 
            padding: 10px 16px; 
            font-size: 12px; 
        }
        
        .nav-tabs {
            padding: 10px 15px 0;
        }
        
        .nav-tabs .nav-link {
            padding: 12px 16px;
            font-size: 13px;
        }
        
        .nav-pills .nav-link {
            padding: 10px 15px;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        .tab-content {
            padding: 20px;
        }
        
        .student-detail-table {
            font-size: 13px;
        }
        
        .student-detail-label,
        .student-detail-value {
            padding: 10px 12px;
        }
        
        .modal-header,
        .modal-body,
        .modal-footer {
            padding: 20px;
        }
        
        .card {
            margin-bottom: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .page-header {
            font-size: 1.75rem;
        }
        
        .panel-body {
            padding: 15px;
        }
        
        .btn {
            padding: 8px 12px;
            font-size: 11px;
        }
        
        .nav-tabs .nav-link {
            padding: 10px 12px;
            font-size: 12px;
        }
        
        .table thead th,
        .table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .student-detail-table {
            font-size: 12px;
        }
        
        .student-detail-label,
        .student-detail-value {
            padding: 8px 10px;
        }
        
        .modal-header,
        .modal-body,
        .modal-footer {
            padding: 15px;
        }
    }

    /* === UTILITIES === */
    .d-flex { display: flex !important; }
    .align-items-center { align-items: center !important; }
    .justify-content-between { justify-content: space-between !important; }
    .text-center { text-align: center !important; }
    .mb-3 { margin-bottom: 1rem !important; }
    .gap-2 > * + * { margin-left: 8px; }
    
    /* === ANIMATIONS === */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .panel {
        animation: fadeInUp 0.6s ease-out;
    }
    
    .btn:active {
        animation: pulse 0.2s ease-in-out;
    }
    
    /* === GRADIENT BACKGROUNDS === */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
    }
    
    .bg-gradient-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .bg-gradient-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .bg-gradient-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }
    
    /* === SCROLLBAR STYLES === */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: rgba(226, 232, 240, 0.3);
        border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #0ea5e9, #3b82f6);
        border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #0284c7, #2563eb);
    }
    
    /* === LOADING STATES === */
    .btn-loading {
        position: relative;
        pointer-events: none;
    }
    
    .btn-loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 16px;
        height: 16px;
        margin: -8px 0 0 -8px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Giaovien.php"; ?>

        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">
                    <i class="fa fa-chart-line" style="margin-right: 15px;"></i>Báo cáo tổng kết sinh viên
                </h1>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fa fa-exclamation-triangle"></i> <?php echo $errorMsg; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($dots)): ?>
                    <div class="alert alert-warning text-center mb-4" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #f59e0b; border-radius: 12px;">
                        <i class="fa fa-exclamation-triangle" style="margin-right: 10px; color: #d97706;"></i>
                        <strong>Thông báo:</strong> Không có đợt nào bạn đang hướng dẫn sinh viên (hoặc chưa có đợt nào trạng thái >= 3).
                    </div>
                <?php else: ?>
                    <!-- Tabs và nội dung các đợt -->
                    <ul class="nav nav-tabs mb-3" id="dotTab" role="tablist">
                        <?php foreach ($dots as $i => $dot): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $i==0?'active':'' ?>" id="dot-tab-<?= $dot['ID'] ?>" data-toggle="tab" href="#dot-<?= $dot['ID'] ?>" role="tab">
                                    <?= htmlspecialchars($dot['TenDot']) ?> (Trạng thái: <?= $dot['TrangThai'] ?>)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="tab-content" id="dotTabContent">
                        <?php foreach ($dots as $i => $dot): ?>
                            <div class="tab-pane fade <?= $i==0?'show active':'' ?>" id="dot-<?= $dot['ID'] ?>" role="tabpanel">
                                <!-- Sub-tabs cho từng đợt -->
                                <ul class="nav nav-pills mb-3" id="subTab-<?= $dot['ID'] ?>" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link" id="detail-tab-<?= $dot['ID'] ?>" data-toggle="pill" href="#detail-<?= $dot['ID'] ?>" role="tab">
                                            <i class="fa fa-list-alt"></i> Chi tiết sinh viên
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link active" id="grade-tab-<?= $dot['ID'] ?>" data-toggle="pill" href="#grade-<?= $dot['ID'] ?>" role="tab">
                                            <i class="fa fa-star"></i> Bảng điểm
                                        </a>
                                    </li>
                                </ul>
                                
                                <div class="tab-content" id="subTabContent-<?= $dot['ID'] ?>">
                                    <!-- Tab Chi tiết sinh viên -->
                                    <div class="tab-pane fade" id="detail-<?= $dot['ID'] ?>" role="tabpanel">
                                        <div class="panel panel-default" style="max-width:1200px;margin:auto;">
                                            <!-- Toggle đóng/mở báo cáo tổng kết cho từng đợt -->
                                            <form method="post" class="mb-3 d-inline" id="form-trangthai-tongket-<?= $dot['ID'] ?>">
                                                <div class="panel panel-default toggle-container">
                                                    <div class="panel-heading"><strong>Đóng/mở nộp báo cáo tổng kết</strong></div>
                                                    <div class="panel-body">
                                                        <span>Trạng thái:</span>
                                                        <input type="checkbox" name="trangthai_tongket" value="1" id="toggle-trangthai-<?= $dot['ID'] ?>"
                                                            <?php 
                                                            // Kiểm tra trạng thái: 1 = mở (checked), 0 = đóng (unchecked)
                                                            if (isset($trangthai_tongket_dot[$dot['ID']]) && $trangthai_tongket_dot[$dot['ID']] == 1) {
                                                                echo 'checked'; 
                                                            }
                                                            ?>
                                                            data-toggle="toggle" data-on="" data-off=""
                                                            data-onstyle="success" data-offstyle="danger"
                                                            data-size="small" data-width="60" data-height="30"
                                                            class="toggle"
                                                        >
                                                        <input type="hidden" name="luu_trangthai_tongket" value="1">
                                                        <input type="hidden" name="id_dot" value="<?= $dot['ID'] ?>">
                                                    </div>
                                                </div>
                                            </form>
                                            <!-- Tiêu đề và thống kê -->
                                            <div class="panel-heading d-flex align-items-center" style="flex-wrap: wrap; justify-content: space-between;">
                                                <span>
                                                    Danh sách sinh viên thuộc quản lí - <?= htmlspecialchars($dot['TenDot']) ?>
                                                </span>
                                                <?php
                                                    $tong_sv = count($ds_sinhvien_theo_dot[$dot['ID']]);
                                                    $so_nop = 0;
                                                    foreach ($ds_sinhvien_theo_dot[$dot['ID']] as $sv) {
                                                        if (!empty($baocao_tongket[$dot['ID']][$sv['ID_TaiKhoan']]['TenFile'])) $so_nop++;
                                                    }
                                                ?>
                                                <span class="ml-auto" style="font-size:15px; color:#1976d2; min-width:220px; text-align:right;">
                                                    <i class="fa fa-users"></i> Số lượng SV: <b><?= $tong_sv ?></b>
                                                    &nbsp;|&nbsp;
                                                    <i class="fa fa-file-text"></i> Đã nộp: <b><?= $so_nop ?></b>
                                                </span>
                                            </div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    <!-- Bảng danh sách sinh viên -->
                                                    <div class="col-md-6">
                                                        <div class="table-responsive">
                                                            <?php if (empty($ds_sinhvien_theo_dot[$dot['ID']])): ?>
                                                                <div class="alert alert-warning text-center mb-0">
                                                                    Không có sinh viên nào thuộc đợt này do bạn hướng dẫn.
                                                                </div>
                                                            <?php else: ?>
                                                            <table class="table table-striped table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>MSSV</th>
                                                                        <th>Trạng thái báo cáo tổng kết</th>
                                                                        <th>Thao tác</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php $stt = 1; foreach ($ds_sinhvien_theo_dot[$dot['ID']] as $sv): ?>
                                                                        <tr class="row-detail"
                                                                            data-id="<?= $sv['ID_TaiKhoan'] ?>"
                                                                            data-dot="<?= $dot['ID'] ?>"
                                                                            style="cursor: pointer;">
                                                                            <td><?= $stt++ ?></td>
                                                                            <td><?= htmlspecialchars($sv['MSSV']) ?></td>
                                                                            <td>
                                                                                <?php
                                                                                $loai_files = ['Baocao', 'khoasat', 'phieuthuctap', 'nhanxet'];
                                                                                $total_files = 0;
                                                                                foreach ($loai_files as $loai) {
                                                                                    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM file WHERE ID_SV = ? AND Loai = ? AND TrangThai = 1");
                                                                                    $stmt_check->execute([$sv['ID_TaiKhoan'], $loai]);
                                                                                    if ($stmt_check->fetchColumn() > 0) $total_files++;
                                                                                }
                                                                                echo $total_files > 0 
                                                                                    ? "<span class='text-success'>Đã nộp {$total_files}/4</span>" 
                                                                                    : "<span class='text-danger'>Chưa nộp 0/4</span>";
                                                                                ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php if ($total_files === 4): ?>
                                                                                    <a href="/datn/pages/giaovien/download_student.php?id_sv=<?= $sv['ID_TaiKhoan'] ?>&id_dot=<?= $dot['ID'] ?>" 
                                                                                       class="btn btn-success btn-xs" title="Tải xuống báo cáo">
                                                                                        <i class="fa fa-download"></i> Tải xuống
                                                                                    </a>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">-</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <!-- Chi tiết sinh viên -->
                                                    <div class="col-md-6">
                                                        <div id="student-detail-panel-<?= $dot['ID'] ?>" class="card shadow-sm p-4" style="border-radius:18px;background:#fff;min-height:420px;">
                                                            <h4 class="mb-3 text-primary">Chi tiết sinh viên</h4>
                                                            <div id="student-detail-content-<?= $dot['ID'] ?>">
                                                                <div class="text-muted text-center">Chọn sinh viên để xem chi tiết</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Bảng điểm -->
                                    <div class="tab-pane fade show active" id="grade-<?= $dot['ID'] ?>" role="tabpanel">
                                        <div class="panel panel-default">
                                            <div class="panel-heading d-flex align-items-center justify-content-between">
                                                <h4 style="margin: 0;">
                                                    <i class="fa fa-star text-warning"></i> 
                                                    Bảng điểm - <?= htmlspecialchars($dot['TenDot']) ?>
                                                </h4>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-primary btn-sm btn-save-all-grades" data-dot-id="<?= $dot['ID'] ?>">
                                                        <i class="fa fa-save"></i> Lưu tất cả
                                                    </button>
                                                    <form method="get" style="margin: 0;">
                                                        <input type="hidden" name="download_all" value="1">
                                                        <input type="hidden" name="dot_id" value="<?= $dot['ID'] ?>">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="fa fa-download"></i> Tải báo cáo
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="panel-body">
                                                <?php if (empty($ds_sinhvien_theo_dot[$dot['ID']])): ?>
                                                    <div class="alert alert-warning text-center">
                                                        Không có sinh viên nào thuộc đợt này để hiển thị điểm.
                                                    </div>
                                                <?php else: ?>                                                    
                                                    <!-- Form để lưu điểm cho đợt này -->
                                                    <form method="post" id="form-save-grades-<?= $dot['ID'] ?>">
                                                        <div class="table-responsive">
                                                            <table class="table table-striped table-bordered table-grades-dot" id="table-grades-<?= $dot['ID'] ?>">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width: 50px;">#</th>
                                                                        <th style="width: 120px;">MSSV</th>
                                                                        <th style="width: 200px;">Họ và tên</th>
                                                                        <th style="width: 80px;">Báo cáo</th>
                                                                        <th style="width: 80px;">Khảo sát</th>
                                                                        <th style="width: 80px;">Phiếu TT</th>
                                                                        <th style="width: 80px;">Nhận xét</th>
                                                                        <th style="width: 100px;">Báo cáo tuần</th>
                                                                        <th style="width: 100px;">Điểm báo cáo</th>
                                                                        <th style="width: 100px;">Điểm chuyên cần</th>
                                                                        <th style="width: 100px;">Điểm chuẩn nghề</th>
                                                                        <th style="width: 100px;">Điểm thực tế</th>
                                                                        <th style="width: 200px;">Ghi chú</th>
                                                                        <th style="width: 120px;">Thao tác</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php 
                                                                    $stt = 1; 
                                                                    foreach ($ds_sinhvien_theo_dot[$dot['ID']] as $sv): 
                                                                        $diem_info = isset($diemData[$dot['ID']][$sv['ID_TaiKhoan']]) ? $diemData[$dot['ID']][$sv['ID_TaiKhoan']] : [];
                                                                        
                                                                        // Lấy trạng thái 4 loại file
                                                                        $loai_files = ['Baocao', 'khoasat', 'phieuthuctap', 'nhanxet'];
                                                                        $file_status = [];
                                                                        $file_details = [];
                                                                        foreach ($loai_files as $loai) {
                                                                            $stmt_file = $conn->prepare("SELECT TenFile, Dir FROM file WHERE ID_SV = ? AND Loai = ? AND TrangThai = 1 ORDER BY ID DESC LIMIT 1");
                                                                            $stmt_file->execute([$sv['ID_TaiKhoan'], $loai]);
                                                                            $file_info = $stmt_file->fetch(PDO::FETCH_ASSOC);
                                                                            $file_status[$loai] = !empty($file_info);
                                                                            $file_details[$loai] = $file_info;
                                                                        }
                                                                        
                                                                        // Lấy thống kê báo cáo tuần
                                                                        $stmt_baocao_tuan = $conn->prepare("SELECT COUNT(*) as total_tasks, 
                                                                                                           SUM(CASE WHEN TienDo = 100 THEN 1 ELSE 0 END) as completed_tasks,
                                                                                                           AVG(TienDo) as avg_progress
                                                                                                           FROM congviec_baocao 
                                                                                                           WHERE IDSV = ? AND ID_Dot = ?");
                                                                        $stmt_baocao_tuan->execute([$sv['ID_TaiKhoan'], $dot['ID']]);
                                                                        $baocao_tuan = $stmt_baocao_tuan->fetch(PDO::FETCH_ASSOC);
                                                                        $total_tasks = $baocao_tuan['total_tasks'] ?? 0;
                                                                        $completed_tasks = $baocao_tuan['completed_tasks'] ?? 0;
                                                                        $avg_progress = round($baocao_tuan['avg_progress'] ?? 0, 1);
                                                                    ?>
                                                                        <tr data-id-sv="<?= $sv['ID_TaiKhoan'] ?>" data-id-dot="<?= $dot['ID'] ?>">
                                                                            <td><?= $stt++ ?></td>
                                                                            <td><strong><?= htmlspecialchars($sv['MSSV']) ?></strong></td>
                                                                            <td><?= htmlspecialchars($sv['Ten']) ?></td>
                                                                            <!-- 4 cột trạng thái file -->
                                                                            <td class="text-center">
                                                                                <?= $file_status['Baocao'] ? '<i class="fa fa-check text-success" title="Đã nộp"></i>' : '<i class="fa fa-times text-danger" title="Chưa nộp"></i>' ?>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <?= $file_status['khoasat'] ? '<i class="fa fa-check text-success" title="Đã nộp"></i>' : '<i class="fa fa-times text-danger" title="Chưa nộp"></i>' ?>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <?= $file_status['phieuthuctap'] ? '<i class="fa fa-check text-success" title="Đã nộp"></i>' : '<i class="fa fa-times text-danger" title="Chưa nộp"></i>' ?>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <?= $file_status['nhanxet'] ? '<i class="fa fa-check text-success" title="Đã nộp"></i>' : '<i class="fa fa-times text-danger" title="Chưa nộp"></i>' ?>
                                                                            </td>
                                                                            <!-- Cột báo cáo tuần -->
                                                                            <td class="text-center">
                                                                                <small class="d-block">
                                                                                    <strong><?= $completed_tasks ?>/<?= $total_tasks ?></strong> công việc
                                                                                </small>
                                                                                <div class="progress" style="height: 5px; margin-top: 2px;">
                                                                                    <div class="progress-bar <?= $avg_progress >= 80 ? 'bg-success' : ($avg_progress >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                                                                         style="width: <?= $avg_progress ?>%"></div>
                                                                                </div>
                                                                                <small class="text-muted"><?= $avg_progress ?>%</small>
                                                                            </td>
                                                                            <!-- Các cột điểm -->
                                                                            <td>
                                                                                <input type="number" 
                                                                                       class="form-control form-control-sm" 
                                                                                       name="grades[<?= $sv['ID_TaiKhoan'] ?>][<?= $dot['ID'] ?>][diem_baocao]"
                                                                                       value="<?= $diem_info['diem_baocao'] ?? '' ?>"
                                                                                       min="0" max="4" step="0.1"
                                                                                       placeholder="0-4">
                                                                            </td>
                                                                            <td>
                                                                                <input type="number" 
                                                                                       class="form-control form-control-sm" 
                                                                                       name="grades[<?= $sv['ID_TaiKhoan'] ?>][<?= $dot['ID'] ?>][diem_chuyencan]"
                                                                                       value="<?= $diem_info['diem_chuyencan'] ?? '' ?>"
                                                                                       min="0" max="2" step="0.1"
                                                                                       placeholder="0-2">
                                                                            </td>
                                                                            <td>
                                                                                <input type="number" 
                                                                                       class="form-control form-control-sm" 
                                                                                       name="grades[<?= $sv['ID_TaiKhoan'] ?>][<?= $dot['ID'] ?>][diem_chuannghe]"
                                                                                       value="<?= $diem_info['diem_chuannghe'] ?? '' ?>"
                                                                                       min="0" max="2" step="0.1"
                                                                                       placeholder="0-2">
                                                                            </td>
                                                                            <td>
                                                                                <input type="number" 
                                                                                       class="form-control form-control-sm" 
                                                                                       name="grades[<?= $sv['ID_TaiKhoan'] ?>][<?= $dot['ID'] ?>][diem_thucte]"
                                                                                       value="<?= $diem_info['diem_thucte'] ?? '' ?>"
                                                                                       min="0" max="2" step="0.1"
                                                                                       placeholder="0-2">
                                                                            </td>
                                                                            <td>
                                                                                <textarea class="form-control form-control-sm" 
                                                                                          name="grades[<?= $sv['ID_TaiKhoan'] ?>][<?= $dot['ID'] ?>][ghichu]"
                                                                                          rows="2" 
                                                                                          placeholder="Ghi chú..."><?= htmlspecialchars($diem_info['ghichu'] ?? '') ?></textarea>
                                                                            </td>
                                                                            <td>
                                                                                <button type="button" 
                                                                                        class="btn btn-primary btn-sm btn-save-single"
                                                                                        data-id-sv="<?= $sv['ID_TaiKhoan'] ?>"
                                                                                        data-id-dot="<?= $dot['ID'] ?>"
                                                                                        title="Lưu điểm sinh viên này">
                                                                                    <i class="fa fa-save"></i>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        
                                                        <!-- Chỉ cần nút làm mới -->
                                                        <div class="text-center mt-3">
                                                            <button type="button" class="btn btn-warning btn-lg" onclick="location.reload()">
                                                                <i class="fa fa-refresh"></i> Làm mới trang
                                                            </button>
                                                        </div>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>                                               
    <link href="https://cdn.jsdelivr.net/npm/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
    <script>
    $(function() {
        // Khởi tạo bootstrap toggle cho các checkbox
        $('[id^=toggle-trangthai-]').bootstrapToggle();
        
        // Kiểm tra và cập nhật màu sắc toggle theo trạng thái từ CSDL
        $('[id^=toggle-trangthai-]').each(function() {
            var $toggle = $(this);
            var isChecked = $toggle.prop('checked');
            
            // Đảm bảo màu sắc hiển thị đúng:
            // - Checked (1) = Mở = Xanh lá (success)
            // - Unchecked (0) = Đóng = Đỏ (danger)
            if (isChecked) {
                $toggle.bootstrapToggle('on');
            } else {
                $toggle.bootstrapToggle('off');
            }
        });
        
        $('[id^=toggle-trangthai-]').change(function() {
            var dotId = $(this).attr('id').replace('toggle-trangthai-', '');
            var isChecked = $(this).prop('checked');
            var $toggle = $(this);
            
            // Tạm thời disable toggle để tránh click liên tục
            $toggle.bootstrapToggle('disable');
            
            // AJAX submit về chính file này
            $.ajax({
                url: '', // Submit về chính file này
                type: 'POST',
                data: {
                    luu_trangthai_tongket: 1,
                    id_dot: dotId,
                    trangthai_tongket: isChecked ? 1 : '',
                    ajax: 1 // Đánh dấu là AJAX request
                },
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        // Có lỗi, hoàn lại trạng thái toggle
                        $toggle.bootstrapToggle(isChecked ? 'off' : 'on');
                        console.log('Lỗi cập nhật trạng thái:', response.message || 'Có lỗi xảy ra');
                    } else {
                        // Cập nhật thành công, đảm bảo màu sắc hiển thị đúng
                        if (isChecked) {
                            $toggle.bootstrapToggle('on');
                        } else {
                            $toggle.bootstrapToggle('off');
                        }
                    }
                    // Không hiển thị thông báo
                },
                error: function() {
                    // Có lỗi, hoàn lại trạng thái toggle
                    $toggle.bootstrapToggle(isChecked ? 'off' : 'on');
                    console.log('Lỗi kết nối khi cập nhật trạng thái');
                },
                complete: function() {
                    // Re-enable toggle
                    $toggle.bootstrapToggle('enable');
                }
            });
        });
        
        // Khởi tạo DataTable cho bảng điểm
        setTimeout(function() {
            $('[id^="table-grades-"]').each(function() {
                if (!$.fn.dataTable.isDataTable(this)) {
                    $(this).DataTable({
                        responsive: true,
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                        },
                        pageLength: 25,
                        order: [[1, 'asc']], // Sắp xếp theo MSSV
                        columnDefs: [
                            { orderable: false, targets: [3, 4, 5, 6, 7, 13] }, // Không cho sắp xếp cột trạng thái file, báo cáo tuần và thao tác
                            { className: "text-center", targets: [3, 4, 5, 6, 7] } // Căn giữa các cột trạng thái
                        ]
                    });
                }
            });
        }, 500);
        
        // Luôn mở tab "Bảng điểm" đầu tiên
        if (!window.location.hash) {
            $('#dotTab a:first').tab('show');
        }
        
        // Khi chuyển tab đợt, luôn mở tab "Bảng điểm" đầu tiên
        $('#dotTab a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr("href");
            var dotId = target.replace('#dot-', '');
            $('#grade-tab-' + dotId).tab('show');
        });
        
        // Mở tab "Bảng điểm" cho đợt đầu tiên khi load trang
        setTimeout(function() {
            var firstDotTab = $('#dotTab a:first');
            if (firstDotTab.length > 0) {
                var firstDotId = firstDotTab.attr('href').replace('#dot-', '');
                $('#grade-tab-' + firstDotId).tab('show');
            }
        }, 100);
        
        // Xử lý lưu điểm đơn lẻ - AJAX không reload trang
        $(document).on('click', '.btn-save-single', function() {
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var id_sv = $btn.data('id-sv');
            var id_dot = $btn.data('id-dot');
            
            var data = {
                id_sv: id_sv,
                id_dot: id_dot,
                diem_baocao: $row.find('input[name*="[diem_baocao]"]').val(),
                diem_chuyencan: $row.find('input[name*="[diem_chuyencan]"]').val(),
                diem_chuannghe: $row.find('input[name*="[diem_chuannghe]"]').val(),
                diem_thucte: $row.find('input[name*="[diem_thucte]"]').val(),
                ghichu: $row.find('textarea[name*="[ghichu]"]').val(),
                ajax: 1 // Đánh dấu là AJAX request
            };
            
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            
            // AJAX submit về chính file này
            $.post('', data, function(response) {
                if (response.success) {
                    $btn.removeClass('btn-primary').addClass('btn-success').html('<i class="fa fa-check"></i>');
                    setTimeout(function() {
                        $btn.removeClass('btn-success').addClass('btn-primary').html('<i class="fa fa-save"></i>');
                    }, 2000);
                    
                    // Hiển thị thông báo thành công
                    showAlert('success', response.message, id_dot);
                    
                    // Cập nhật dữ liệu JavaScript để chi tiết sinh viên hiển thị đúng
                    if (diemData[id_dot] && diemData[id_dot][id_sv]) {
                        diemData[id_dot][id_sv].diem_baocao = data.diem_baocao;
                        diemData[id_dot][id_sv].diem_chuyencan = data.diem_chuyencan;
                        diemData[id_dot][id_sv].diem_chuannghe = data.diem_chuannghe;
                        diemData[id_dot][id_sv].diem_thucte = data.diem_thucte;
                        diemData[id_dot][id_sv].ghichu = data.ghichu;
                    }
                } else {
                    showAlert('danger', 'Có lỗi xảy ra khi lưu điểm!', id_dot);
                }
            }, 'json').fail(function() {
                showAlert('danger', 'Có lỗi xảy ra khi lưu điểm!', id_dot);
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });
        
        // Xử lý lưu tất cả điểm cho một đợt - AJAX không reload trang
        $(document).on('click', '.btn-save-all-grades', function() {
            var $btn = $(this);
            var dot_id = $btn.data('dot-id');
            var $table = $('#table-grades-' + dot_id);
            var promises = [];
            var totalRows = 0;
            var savedRows = 0;
            
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Đang lưu...');
            
            // Lặp qua tất cả các hàng trong bảng
            $table.find('tbody tr').each(function() {
                var $row = $(this);
                var $saveBtn = $row.find('.btn-save-single');
                
                if ($saveBtn.length > 0) {
                    totalRows++;
                    var id_sv = $saveBtn.data('id-sv');
                    var id_dot_row = $saveBtn.data('id-dot');
                    
                    var data = {
                        id_sv: id_sv,
                        id_dot: id_dot_row,
                        diem_baocao: $row.find('input[name*="[diem_baocao]"]').val(),
                        diem_chuyencan: $row.find('input[name*="[diem_chuyencan]"]').val(),
                        diem_chuannghe: $row.find('input[name*="[diem_chuannghe]"]').val(),
                        diem_thucte: $row.find('input[name*="[diem_thucte]"]').val(),
                        ghichu: $row.find('textarea[name*="[ghichu]"]').val(),
                        ajax: 1
                    };
                    
                    // Tạo promise cho mỗi request
                    var promise = $.post('', data, function(response) {
                        if (response.success) {
                            savedRows++;
                            // Cập nhật dữ liệu JavaScript
                            if (diemData[id_dot_row] && diemData[id_dot_row][id_sv]) {
                                diemData[id_dot_row][id_sv].diem_baocao = data.diem_baocao;
                                diemData[id_dot_row][id_sv].diem_chuyencan = data.diem_chuyencan;
                                diemData[id_dot_row][id_sv].diem_chuannghe = data.diem_chuannghe;
                                diemData[id_dot_row][id_sv].diem_thucte = data.diem_thucte;
                                diemData[id_dot_row][id_sv].ghichu = data.ghichu;
                            }
                        }
                    }, 'json');
                    
                    promises.push(promise);
                }
            });
            
            // Đợi tất cả requests hoàn thành
            $.when.apply($, promises).always(function() {
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Lưu tất cả');
                
                // Thay đổi màu nút tạm thời để báo hiệu đã lưu
                if (savedRows === totalRows && totalRows > 0) {
                    $btn.removeClass('btn-primary').addClass('btn-success').html('<i class="fa fa-check"></i> Đã lưu');
                    setTimeout(function() {
                        $btn.removeClass('btn-success').addClass('btn-primary').html('<i class="fa fa-save"></i> Lưu tất cả');
                    }, 2000);
                }
                // Không hiển thị thông báo
            });
        });
        
        // Hàm hiển thị thông báo (cập nhật để hỗ trợ từng đợt)
        function showAlert(type, message, dot_id) {
            var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show grade-panel-alert" role="alert">' +
                           message +
                           '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                           '<span aria-hidden="true">&times;</span>' +
                           '</button>' +
                           '</div>';
            
            if (dot_id) {
                $('#grade-' + dot_id + ' .panel-body').prepend(alertHtml);
            } else {
                $('.panel-body').first().prepend(alertHtml);
            }
            
            // Tự động ẩn sau 5 giây
            setTimeout(function() {
                $('.grade-panel-alert').alert('close');
            }, 5000);
        }
    });
    </script>

    <!-- Modal nhập điểm -->
    <div class="modal fade" id="modalNhapDiem" tabindex="-1" role="dialog" aria-labelledby="modalNhapDiemLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <form id="form-nhap-diem" method="post">
          <div class="modal-content" style="border-radius:18px; box-shadow:0 4px 24px #007bff33;">
            <div class="modal-header d-flex align-items-center justify-content-between" style="background:linear-gradient(90deg,#e3f0ff 70%,#f8fafc 100%); border-radius:18px 18px 0 0;">
              <h5 class="modal-title text-primary" id="modalNhapDiemLabel" style="font-weight:700;">
                <i class="fa fa-pencil-square-o"></i> Nhập điểm sinh viên
              </h5>
              <button type="button" class="close btn-close-custom" data-dismiss="modal" aria-label="Đóng" style="outline:none;">
                <span aria-hidden="true" style="font-size:2rem; color:#fff; background:#dc3545; border-radius:50%; width:36px; height:36px; display:inline-block; text-align:center; line-height:36px;">&times;</span>
              </button>
            </div>
            <div class="modal-body" style="background:#fafdff;">
              <input type="hidden" name="id_sv" id="modal-id-sv">
              <input type="hidden" name="id_dot" id="modal-id-dot">
              <div class="form-group mb-3">
                <label class="font-weight-bold text-primary">Điểm báo cáo <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="4" class="form-control border-primary" name="diem_baocao" required>
              </div>
              <div class="form-group mb-3">
                <label class="font-weight-bold text-primary">Điểm chuyên cần <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="2" class="form-control border-primary" name="diem_chuyencan" required>
              </div>
              <div class="form-group mb-3">
                <label class="font-weight-bold text-primary">Điểm chuẩn nghề <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="2" class="form-control border-primary" name="diem_chuannghe" required>
              </div>
              <div class="form-group mb-3">
                <label class="font-weight-bold text-primary">Điểm thực tế <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="2" class="form-control border-primary" name="diem_thucte" required>
              </div>
              <div class="form-group mb-2">
                <label class="font-weight-bold text-primary">Ghi chú</label>
                <textarea class="form-control border-primary" name="ghichu" rows="2" style="resize:vertical;"></textarea>
              </div>
            </div>
            <div class="modal-footer" style="background:#e3f0ff; border-radius:0 0 18px 18px;">
              <button type="submit" class="btn btn-primary" style="border-radius:8px; min-width:120px; font-weight:600;">
                <i class="fa fa-save"></i> Lưu lại
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <script>
    const sinhvienData = <?php echo json_encode($ds_sinhvien_theo_dot); ?>;
    const baocaoData = <?php echo json_encode($baocao_tongket); ?>;
    const diemData = <?php echo json_encode($diemData); ?>;

    function renderStudentDetail(id_sv, dot_id) {
        const sv = (sinhvienData[dot_id] || []).find(s => s.ID_TaiKhoan == id_sv);
        const bc = (baocaoData[dot_id] && baocaoData[dot_id][id_sv]) ? baocaoData[dot_id][id_sv] : {};
        const diem = (diemData[dot_id] && diemData[dot_id][id_sv]) ? diemData[dot_id][id_sv] : {};
        let html = '';
        if (!sv) {
            html = '<div class="text-muted text-center">Không tìm thấy sinh viên</div>';
        } else {
            html = `
        <table class="student-detail-table">
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-user"></i></span>Họ và tên:</td>
                <td class="student-detail-value">${sv.Ten || ''}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-id-card"></i></span>MSSV:</td>
                <td class="student-detail-value">${sv.MSSV || ''}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-clock-o"></i></span>Ngày giờ nộp:</td>
                <td class="student-detail-value">${bc.NgayNop || '-'}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-file-text"></i></span>Điểm báo cáo:</td>
                <td class="student-detail-value">${diem.diem_baocao ?? '-'}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-check-square-o"></i></span>Điểm chuyên cần:</td>
                <td class="student-detail-value">${diem.diem_chuyencan ?? '-'}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-graduation-cap"></i></span>Điểm chuẩn nghề:</td>
                <td class="student-detail-value">${diem.diem_chuannghe ?? '-'}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-briefcase"></i></span>Điểm thực tế:</td>
                <td class="student-detail-value">${diem.diem_thucte ?? '-'}</td>
            </tr>
            <tr>
                <td class="student-detail-label"><span class="student-detail-icon"><i class="fa fa-sticky-note"></i></span>Ghi chú:</td>
                <td class="student-detail-value">${diem.ghichu ?? '-'}</td>
            </tr>
        </table>
        <div class="student-detail-action text-center">
            <button type="button" class="btn btn-primary" id="btn-nhap-diem" data-id="${id_sv}" data-dot="${dot_id}">Nhập/Sửa điểm</button>
        </div>
        `;
        }
        document.getElementById('student-detail-content-' + dot_id).innerHTML = html;
    }

    $(document).on('click', '.row-detail', function() {
        const id_sv = $(this).data('id');
        const dot_id = $(this).data('dot');
        renderStudentDetail(id_sv, dot_id);
    });

    // Khi click nút nhập điểm, mở modal
    $(document).on('click', '#btn-nhap-diem', function() {
        const id_sv = $(this).data('id');
        const dot_id = $(this).data('dot');
        $('#modal-id-sv').val(id_sv);
        $('#modal-id-dot').val(dot_id);
        
        // Fill dữ liệu điểm hiện tại vào modal
        const diem = (diemData[dot_id] && diemData[dot_id][id_sv]) ? diemData[dot_id][id_sv] : {};
        $('input[name="diem_baocao"]').val(diem.diem_baocao ?? '');
        $('input[name="diem_chuyencan"]').val(diem.diem_chuyencan ?? '');
        $('input[name="diem_chuannghe"]').val(diem.diem_chuannghe ?? '');
        $('input[name="diem_thucte"]').val(diem.diem_thucte ?? '');
        $('textarea[name="ghichu"]').val(diem.ghichu ?? '');
        $('#modalNhapDiem').modal('show');
    });

    // Submit modal bằng AJAX (không reload trang)
    $('#form-nhap-diem').on('submit', function(e) {
        e.preventDefault(); // Ngăn submit bình thường
        
        const diem_baocao = parseFloat($('input[name="diem_baocao"]').val());
        const diem_chuyencan = parseFloat($('input[name="diem_chuyencan"]').val());
        const diem_chuannghe = parseFloat($('input[name="diem_chuannghe"]').val());
        const diem_thucte = parseFloat($('input[name="diem_thucte"]').val());

        // Validate
        if (diem_baocao < 0 || diem_baocao > 4) {
            alert('Điểm báo cáo phải từ 0 đến 4');
            $('input[name="diem_baocao"]').focus();
            return false;
        }
        if (diem_chuyencan < 0 || diem_chuyencan > 2) {
            alert('Điểm chuyên cần phải từ 0 đến 2');
            $('input[name="diem_chuyencan"]').focus();
            return false;
        }
        if (diem_chuannghe < 0 || diem_chuannghe > 2) {
            alert('Điểm chuẩn nghề phải từ 0 đến 2');
            $('input[name="diem_chuannghe"]').focus();
            return false;
        }
        if (diem_thucte < 0 || diem_thucte > 2) {
            alert('Điểm thực tế phải từ 0 đến 2');
            $('input[name="diem_thucte"]').focus();
            return false;
        }

        var $submitBtn = $('#form-nhap-diem button[type="submit"]');
        $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Đang lưu...');

        // AJAX submit về chính file này
        var formData = $(this).serialize() + '&ajax=1';
        $.post('', formData, function(response) {
            if (response.success) {
                $('#modalNhapDiem').modal('hide');
                
                // Cập nhật dữ liệu JavaScript
                const id_sv = $('#modal-id-sv').val();
                const dot_id = $('#modal-id-dot').val();
                if (diemData[dot_id] && diemData[dot_id][id_sv]) {
                    diemData[dot_id][id_sv].diem_baocao = $('input[name="diem_baocao"]').val();
                    diemData[dot_id][id_sv].diem_chuyencan = $('input[name="diem_chuyencan"]').val();
                    diemData[dot_id][id_sv].diem_chuannghe = $('input[name="diem_chuannghe"]').val();
                    diemData[dot_id][id_sv].diem_thucte = $('input[name="diem_thucte"]').val();
                    diemData[dot_id][id_sv].ghichu = $('textarea[name="ghichu"]').val();
                }
                
                // Cập nhật lại input trong bảng điểm nếu có
                var $tableRow = $('tr[data-id-sv="' + id_sv + '"][data-id-dot="' + dot_id + '"]');
                if ($tableRow.length > 0) {
                    $tableRow.find('input[name*="[diem_baocao]"]').val($('input[name="diem_baocao"]').val());
                    $tableRow.find('input[name*="[diem_chuyencan]"]').val($('input[name="diem_chuyencan"]').val());
                    $tableRow.find('input[name*="[diem_chuannghe]"]').val($('input[name="diem_chuannghe"]').val());
                    $tableRow.find('input[name*="[diem_thucte]"]').val($('input[name="diem_thucte"]').val());
                    $tableRow.find('textarea[name*="[ghichu]"]').val($('textarea[name="ghichu"]').val());
                }
                
                // Cập nhật lại chi tiết sinh viên nếu đang hiển thị
                renderStudentDetail(id_sv, dot_id);
                
                // Hiển thị thông báo thành công (function đã được define ở trên)
                if (typeof showAlert === 'function') {
                    showAlert('success', response.message, dot_id);
                }
            } else {
                alert('Có lỗi xảy ra khi lưu điểm!');
            }
        }, 'json').fail(function() {
            alert('Có lỗi xảy ra khi lưu điểm!');
        }).always(function() {
            $submitBtn.prop('disabled', false).html('<i class="fa fa-save"></i> Lưu lại');
        });
    });
    </script>
</body>
</html>
